<?php

namespace App\Http\Controllers\Cart;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGuestCartRequest;
use App\Services\CartService;

class GuestCartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
    ) {}

    // Mostra il contenuto del carrello dell'ospite
    // I pacchi sono salvati nella sessione con la chiave 'cart'
    public function index()
    {
        $packages = session()->get('cart', []);

        return response()->json([
            'data' => $packages,
            'meta' => $this->meta($packages), // Informazioni aggiuntive (totale, se e' vuoto, ecc.)
        ]);
    }

    // Calcola il subtotale del carrello sommando i prezzi di tutti i pacchi
    // Il prezzo di ogni pacco (single_price) e' gia' in centesimi e include la quantita'
    public function subtotal($packages)
    {
        return $this->cartService->subtotalFromArray($packages);
    }

    // Prepara le informazioni aggiuntive (meta) per la risposta
    // Include: se il carrello e' vuoto, il subtotale e il totale formattati (es. "9,00 EUR")
    protected function meta($packages)
    {
        return [
            'empty' => count($packages) === 0,
            'subtotal' => $this->subtotal($packages)->formatted(),
            'total' => $this->subtotal($packages)->formatted(),
        ];
    }

    // Aggiunge uno o piu' pacchi al carrello dell'ospite
    // Se un pacco identico e' gia' nel carrello (stesse dimensioni, stesso percorso),
    // invece di crearne uno nuovo aumenta la quantita' di quello esistente
    public function store(StoreGuestCartRequest $request)
    {
        // Recuperiamo il carrello attuale dalla sessione (o un array vuoto se non esiste)
        $cart = session()->get('cart', []);

        // Per ogni pacco inviato dal frontend
        foreach ($request->packages as $pack) {
            $pricedPack = $this->cartService->pricePackageData(
                $pack,
                $request->origin_address ?? [],
                $request->destination_address ?? [],
            );
            $newQty = (int) ($pricedPack['quantity'] ?? 1);
            $newUnitPriceCents = (int) ($pricedPack['unit_price_cents'] ?? 0);

            // Controlliamo se un pacco identico esiste gia' nel carrello
            // (stesse dimensioni, stesso peso, stessi indirizzi di partenza e destinazione)
            $duplicateIndex = null;
            $newServiceSig = $this->cartService->buildServiceSignatureFromGuest($request->services ?? []);
            foreach ($cart as $idx => $existing) {
                $existsAsDuplicate = $this->cartService->isDuplicate(
                    $pricedPack,
                    $request->origin_address ?? [],
                    $request->destination_address ?? [],
                    $newServiceSig,
                    $existing,
                    $existing['origin_address'] ?? [],
                    $existing['destination_address'] ?? [],
                    $this->cartService->buildServiceSignatureFromGuest($existing['services'] ?? []),
                );
                if ($existsAsDuplicate) {
                    $duplicateIndex = $idx;
                    break;
                }
            }

            if ($duplicateIndex !== null) {
                // Se il pacco esiste gia', aumentiamo la quantita' e ricalcoliamo il prezzo
                $merged = $this->cartService->mergeQuantity(
                    (int) ($cart[$duplicateIndex]['single_price'] ?? 0),
                    (int) ($cart[$duplicateIndex]['quantity'] ?? 1),
                    $newQty,
                    $newUnitPriceCents,
                );
                $cart[$duplicateIndex]['quantity'] = $merged['quantity'];
                $cart[$duplicateIndex]['single_price'] = $merged['single_price'];
                $cart[$duplicateIndex]['weight_price'] = $pricedPack['weight_price'];
                $cart[$duplicateIndex]['volume_price'] = $pricedPack['volume_price'];
            } else {
                // Se il pacco e' nuovo, lo aggiungiamo al carrello
                $cart[] = [
                    'package_type' => $pricedPack['package_type'],
                    'quantity' => $newQty,
                    'weight' => $pricedPack['weight'],
                    'first_size' => $pricedPack['first_size'],
                    'second_size' => $pricedPack['second_size'],
                    'third_size' => $pricedPack['third_size'],
                    'weight_price' => $pricedPack['weight_price'],
                    'volume_price' => $pricedPack['volume_price'],
                    'single_price' => $pricedPack['single_price'],
                    'origin_address' => $request->origin_address,
                    'destination_address' => $request->destination_address,
                    'services' => $request->services,
                ];
            }
        }

        // Salviamo il carrello aggiornato nella sessione
        session()->put('cart', $cart);

        return response()->json([
            'data' => $cart,
            'meta' => $this->meta($cart),
            'message' => 'Carrello aggiornato',
        ]);
    }

    // Svuota completamente il carrello dell'ospite
    public function emptyCart()
    {

        session()->put('cart', []);

        return response()->json(['message' => 'Carrello svuotato']);
    }
}
