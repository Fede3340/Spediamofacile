<?php
/**
 * FILE: GuestCartController.php
 * SCOPO: Gestisce il carrello per utenti non registrati (ospiti), salvato in sessione.
 *
 * COSA ENTRA:
 *   - Nessun parametro per index (legge dalla sessione)
 *   - Request con packages[] (tipo, dimensioni, peso), origin_address, destination_address per store
 *
 * COSA ESCE:
 *   - JSON con data (array pacchi), meta (empty, subtotal, total formattati) per index/store
 *   - JSON con message per emptyCart
 *
 * CHIAMATO DA:
 *   - routes/api.php — GET /api/guest-cart, POST /api/guest-cart, DELETE /api/guest-cart
 *   - nuxt: composables/useCart.js (quando utente non autenticato)
 *   - nuxt: pages/carrello.vue, pages/checkout.vue
 *
 * EFFETTI COLLATERALI:
 *   - Sessione: salva/aggiorna/svuota chiave 'cart' con array di pacchi
 *   - I prezzi sono in centesimi (single_price = euro * 100) per evitare errori di arrotondamento
 *   - Duplicati: se un pacco identico (stesse dimensioni, stesso percorso) esiste gia',
 *     aumenta la quantita' invece di creare un nuovo elemento
 *
 * VINCOLI:
 *   - I prezzi nella sessione sono in CENTESIMI (single_price = euro * 100)
 *   - La conversione euro->centesimi avviene in store(): round(euro * 100)
 *   - La sessione si svuota quando il browser viene chiuso (o dopo il timeout)
 *   - I dati del carrello ospite vengono trasferiti nel DB al momento del login
 *   - Duplicati: stesse dimensioni + stessi indirizzi = aumento quantita' (non nuovo elemento)
 *
 * ERRORI TIPICI:
 *   - 422: dati di validazione mancanti (almeno un pacco con dimensioni e indirizzi)
 *
 * PUNTI DI MODIFICA SICURI:
 *   - Per aggiungere campi al pacco ospite: aggiungerli in store() nel blocco $cart[]
 *   - Per cambiare i criteri di deduplicazione: modificare il confronto nel blocco $duplicateIndex
 *
 * COLLEGAMENTI:
 *   - CartController.php — carrello per utenti autenticati (salva in database)
 *   - CustomLoginController.php — trasferisce il carrello sessione nel database al login
 *   - app/Cart/MyMoney.php — formattazione prezzi in centesimi (es. 900 -> "9,00 EUR")
 *   - composables/useCart.js — composable Nuxt che sceglie guest-cart o cart in base all'auth
 */

namespace App\Http\Controllers;

use App\Cart\MyMoney;
use App\Cart\GuestCart;
use App\Models\Package;
use App\Services\CartService;
use App\Services\ShipmentServicePricingService;
use Illuminate\Http\Request;
use App\Http\Resources\PackageResource;
use App\Http\Requests\CartCreateRequest;
use App\Http\Requests\GuestCartCreateRequest;
class GuestCartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
    ) {}

    // Mostra il contenuto del carrello dell'ospite
    // I pacchi sono salvati nella sessione con la chiave 'cart'
    public function index() {
        $packages = session()->get('cart', []);

        return response()->json([
            'data' => $packages,
            'meta' => $this->meta($packages), // Informazioni aggiuntive (totale, se e' vuoto, ecc.)
        ]);
    }

    // Calcola il subtotale del carrello sommando i prezzi di tutti i pacchi
    // Il prezzo di ogni pacco (single_price) e' gia' in centesimi e include la quantita'
    public function subtotal($packages) {
        return $this->cartService->subtotalFromArray($packages);
    }

    // Prepara le informazioni aggiuntive (meta) per la risposta
    // Include: se il carrello e' vuoto, il subtotale e il totale formattati (es. "9,00 EUR")
    protected function meta($packages) {
        return [
            'empty' => count($packages) === 0,
            'subtotal' => $this->subtotal($packages)->formatted(),
            'total' => $this->subtotal($packages)->formatted()
        ];
    }

    // Aggiunge uno o piu' pacchi al carrello dell'ospite
    // Se un pacco identico e' gia' nel carrello (stesse dimensioni, stesso percorso),
    // invece di crearne uno nuovo aumenta la quantita' di quello esistente
    public function store(\App\Http\Requests\StoreGuestCartRequest $request) {
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
    public function emptyCart() {

        session()->put('cart', []);

        return response()->json(['message' => 'Carrello svuotato']);
    }

    // Delegato a CartService — mantenuti come wrapper per retrocompatibilita'
    private function calculateGroupedServiceSurchargeCents(array $packages): int
    {
        return $this->cartService->calculateGroupedSurchargeFromArray($packages);
    }

    private function buildServiceSignatureFromGuest(array $services = []): string
    {
        return $this->cartService->buildServiceSignatureFromGuest($services);
    }

}
