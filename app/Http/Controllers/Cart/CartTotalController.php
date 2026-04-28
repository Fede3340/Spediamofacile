<?php
namespace App\Http\Controllers\Cart;

use App\Http\Controllers\Controller;

use App\Models\Package;
use App\Services\CartService;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\PackageResource;
use Illuminate\Http\Request;

class CartTotalController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
    ) {}

    // ── Helpers ──────────────────────────────────────────────────

    /**
     * Carica tutti i pacchi presenti nel carrello di un utente.
     *
     * @param  int  $userId
     * @return \Illuminate\Database\Eloquent\Collection<int, Package>
     */
    private function loadCartPackages(int $userId)
    {
        $packageIds = DB::table('cart_user')
            ->where('user_id', $userId)
            ->pluck('package_id');

        // PERF-05: select() limita le colonne caricate; le relazioni hanno i propri select.
        return Package::with(['originAddress', 'destinationAddress', 'service'])
            ->whereIn('id', $packageIds)
            ->select([
                'id', 'package_type', 'quantity', 'weight',
                'first_size', 'second_size', 'third_size',
                'weight_price', 'volume_price', 'single_price',
                'origin_address_id', 'destination_address_id', 'service_id',
                'user_id', 'content_description',
                'created_at', 'updated_at',
            ])
            ->get();
    }

    /**
     * Calcola i metadati del carrello (subtotale, totale, raggruppamento indirizzi).
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, Package>  $packages
     * @return array{empty: bool, subtotal: string, total: string, address_groups: array}
     */
    protected function meta($packages): array
    {
        $subtotal = $this->cartService->subtotalFromModels($packages);
        return [
            'empty' => $packages->isEmpty(),
            'subtotal' => $subtotal->formatted(),
            'total' => $subtotal->formatted(),
            'address_groups' => $this->cartService->buildAddressGroups($packages),
        ];
    }

    // ── Index ────────────────────────────────────────────────────

    /**
     * Mostra il contenuto del carrello con auto-merge e pulizia pacchi invalidi.
     *
     * 1) Carica pacchi  2) Auto-merge identici  3) Normalizza prezzi legacy
     * 4) Rimuove pacchi invalidi  5) Restituisce collection + meta
     */
    public function index(Request $request)
    {
        $userId = auth()->id();

        // PERF-01: primo caricamento.
        $packages = $this->loadCartPackages($userId);

        // Auto-merge pacchi identici: può cancellare record nel DB,
        // quindi ricarichiamo per avere solo i pacchi ancora esistenti.
        $merged = $this->cartService->mergeIdenticalPackages($packages, $userId);
        if ($merged > 0) {
            $packages = $this->loadCartPackages($userId);
        }

        // Normalizza eventuali prezzi legacy in memoria (nessun reload necessario:
        // normalizePackagePricing aggiorna i modelli già caricati via ->save()).
        $this->cartService->normalizePackagePricing($packages);

        // Pulizia pacchi non validi: filtra dalla collezione già in memoria.
        $invalidPackages = $packages->filter(fn ($pkg) =>
            empty($pkg->package_type) || (empty($pkg->weight) && empty($pkg->first_size))
        );

        if ($invalidPackages->isNotEmpty()) {
            foreach ($invalidPackages as $pkg) {
                DB::table('cart_user')
                    ->where('user_id', $userId)
                    ->where('package_id', $pkg->id)
                    ->delete();
                $pkg->delete();
            }
            // Rimuoviamo i pacchi invalidi dalla collezione già caricata
            // invece di fare un altro round-trip al DB.
            $packages = $packages->diff($invalidPackages)->values();
        }

        return PackageResource::collection($packages)
            ->additional(['meta' => $this->meta($packages)]);
    }

    // ── Merge identical (POST /api/cart/merge) ───────────────────

    /**
     * Unisce manualmente i pacchi identici nel carrello.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function mergeIdentical()
    {
        $userId = auth()->id();
        $packages = $this->loadCartPackages($userId);

        if ($packages->count() < 2) {
            return response()->json(['message' => 'Nulla da unire.', 'merged' => 0]);
        }

        $merged = DB::transaction(fn () => $this->cartService->mergeIdenticalPackages($packages, $userId));

        return response()->json([
            'message' => $merged > 0 ? "$merged pacchi identici uniti." : 'Nessun pacco da unire.',
            'merged' => $merged,
        ]);
    }

    // ── Empty cart ───────────────────────────────────────────────

    /**
     * Svuota completamente il carrello dell'utente autenticato.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function emptyCart()
    {
        $userId = auth()->id();

        $cartPackageIds = DB::table('cart_user')
            ->where('user_id', $userId)
            ->pluck('package_id');

        if ($cartPackageIds->isNotEmpty()) {
            Package::whereIn('id', $cartPackageIds)
                ->where('user_id', $userId)
                ->delete();
        }

        DB::table('cart_user')
            ->where('user_id', $userId)
            ->delete();

        return response()->json(['message' => 'Carrello svuotato']);
    }
}
