<?php
namespace App\Http\Controllers\Cart;

use App\Http\Controllers\Controller;

use App\Models\PackageAddress;
use App\Models\Package;
use App\Models\Service;
use App\Services\CartService;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\PackageResource;
use App\Http\Requests\PackageStoreRequest;
use Illuminate\Http\Request;

class CartItemController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
    ) {}

    // ── Helpers ──────────────────────────────────────────────────

    private function packageIsInCart(int $userId, int|string $packageId): bool
    {
        return DB::table('cart_user')
            ->where('user_id', $userId)
            ->where('package_id', $packageId)
            ->exists();
    }

    // ── Show ─────────────────────────────────────────────────────

    public function show($id)
    {
        $userId = auth()->id();

        if (! $this->packageIsInCart($userId, $id)) {
            return response()->json(['message' => 'Pacco non trovato nel carrello'], 404);
        }

        $package = Package::with(['originAddress', 'destinationAddress', 'service'])
            ->where('id', $id)
            ->firstOrFail();

        return new PackageResource($package);
    }

    // ── Store ────────────────────────────────────────────────────

    public function store(PackageStoreRequest $request)
    {
        $data = $request->validated();
        $authId = auth()->id();

        $outPackages = DB::transaction(function () use ($data, $authId) {
            $existingPackageIds = DB::table('cart_user')
                ->where('user_id', $authId)
                ->lockForUpdate()
                ->pluck('package_id');

            $existingPackages = Package::with(['originAddress', 'destinationAddress', 'service'])
                ->whereIn('id', $existingPackageIds)
                ->get();

            $servicesData = $this->cartService->normalizeServiceData($data['services']);
            $servicesData = $this->cartService->applyPudoData($servicesData, $data);

            $packages = [];
            $origin = null;
            $destination = null;
            $services = null;

            foreach ($data['packages'] as $packageData) {
                $pricedPackage = $this->cartService->pricePackageData(
                    $packageData,
                    $data['origin_address'] ?? [],
                    $data['destination_address'] ?? [],
                );
                $newQty = (int) ($pricedPackage['quantity'] ?? 1);
                $newUnitPriceCents = (int) ($pricedPackage['unit_price_cents'] ?? 0);

                $newServiceSig = $this->cartService->buildServiceSignatureFromArray(
                    $servicesData['service_type'] ?? 'Nessuno',
                    $servicesData['service_data'] ?? [],
                );

                $duplicate = $existingPackages->first(function ($existing) use ($pricedPackage, $data, $newServiceSig) {
                    if (! $existing->originAddress || ! $existing->destinationAddress || ! $existing->service) {
                        return false;
                    }
                    return $this->cartService->isDuplicate(
                        $pricedPackage,
                        $data['origin_address'] ?? [],
                        $data['destination_address'] ?? [],
                        $newServiceSig,
                        $existing->toArray(),
                        $existing->originAddress->toArray(),
                        $existing->destinationAddress->toArray(),
                        $this->cartService->buildServiceSignatureFromService($existing->service),
                    );
                });

                if ($duplicate) {
                    $merged = $this->cartService->mergeQuantity(
                        (int) $duplicate->single_price,
                        (int) $duplicate->quantity,
                        $newQty,
                        $newUnitPriceCents,
                    );

                    $duplicate->update([
                        'quantity' => $merged['quantity'],
                        'weight_price' => $pricedPackage['weight_price'] ?? $duplicate->weight_price,
                        'volume_price' => $pricedPackage['volume_price'] ?? $duplicate->volume_price,
                        'single_price' => $merged['single_price'],
                    ]);
                    $packages[] = $duplicate;
                } else {
                    if (! $origin) {
                        $origin = PackageAddress::create($data['origin_address']);
                        $destination = PackageAddress::create($data['destination_address']);
                        $services = Service::create($servicesData);
                    }

                    $createdPackage = Package::create([
                        'package_type' => $pricedPackage['package_type'],
                        'quantity' => $newQty,
                        'weight' => $pricedPackage['weight'],
                        'first_size' => $pricedPackage['first_size'],
                        'second_size' => $pricedPackage['second_size'],
                        'third_size' => $pricedPackage['third_size'],
                        'weight_price' => $pricedPackage['weight_price'] ?? null,
                        'volume_price' => $pricedPackage['volume_price'] ?? null,
                        'single_price' => $pricedPackage['single_price'],
                        'origin_address_id' => $origin->id,
                        'destination_address_id' => $destination->id,
                        'service_id' => $services->id,
                        'user_id' => $authId,
                    ]);
                    $packages[] = $createdPackage;
                    $existingPackages->push($createdPackage);
                }
            }

            foreach ($packages as $package) {
                $exists = DB::table('cart_user')
                    ->where('user_id', $authId)
                    ->where('package_id', $package->id)
                    ->exists();

                if (! $exists) {
                    DB::table('cart_user')->insert([
                        'user_id' => $authId,
                        'package_id' => $package->id,
                    ]);
                }
            }

            return $packages;
        });

        return PackageResource::collection($outPackages);
    }

    // ── Update ───────────────────────────────────────────────────

    public function update(\App\Http\Requests\UpdateCartItemRequest $request, $id)
    {
        $userId = auth()->id();

        if (! $request->filled('pudo.pudo_id') && is_array($request->input('selected_pudo'))) {
            $request->merge([
                'pudo' => $request->input('selected_pudo'),
            ]);
        }

        if (! $this->packageIsInCart($userId, $id)) {
            return response()->json(['message' => 'Pacco non trovato nel carrello'], 404);
        }

        $package = Package::where('id', $id)->where('user_id', $userId)->firstOrFail();
        $data = $request->validated();

        return DB::transaction(function () use ($package, $data) {
            if (isset($data['origin_address']) && $package->originAddress) {
                $package->originAddress->update($data['origin_address']);
            }

            if (isset($data['destination_address']) && $package->destinationAddress) {
                $package->destinationAddress->update($data['destination_address']);
            }

            $package->load(['originAddress', 'destinationAddress']);

            if (isset($data['services']) && $package->service) {
                $servicesData = $this->cartService->normalizeServiceData($data['services']);
                $servicesData = $this->cartService->applyPudoData($servicesData, $data);
                $package->service->update($servicesData);
            }

            if (isset($data['packages']) && count($data['packages']) > 0) {
                $packageData = $data['packages'][0];
                $pricedPackage = $this->cartService->pricePackageData(
                    $packageData,
                    $package->originAddress?->toArray() ?? [],
                    $package->destinationAddress?->toArray() ?? [],
                    $package->only([
                        'package_type',
                        'quantity',
                        'weight',
                        'first_size',
                        'second_size',
                        'third_size',
                    ]),
                );

                $package->update([
                    'package_type' => $pricedPackage['package_type'] ?? $package->package_type,
                    'quantity' => (int) ($pricedPackage['quantity'] ?? 1),
                    'weight' => $pricedPackage['weight'] ?? $package->weight,
                    'first_size' => $pricedPackage['first_size'] ?? $package->first_size,
                    'second_size' => $pricedPackage['second_size'] ?? $package->second_size,
                    'third_size' => $pricedPackage['third_size'] ?? $package->third_size,
                    'weight_price' => $pricedPackage['weight_price'] ?? $package->weight_price,
                    'volume_price' => $pricedPackage['volume_price'] ?? $package->volume_price,
                    'single_price' => $pricedPackage['single_price'] ?? $package->single_price,
                    'content_description' => $data['content_description'] ?? $package->content_description,
                ]);
            }

            $package->load(['originAddress', 'destinationAddress', 'service']);
            return new PackageResource($package);
        });
    }

    // ── Update quantity ──────────────────────────────────────────

    public function updateQuantity(\App\Http\Requests\UpdateCartItemQuantityRequest $request, $id)
    {
        $userId = auth()->id();

        if (! $this->packageIsInCart($userId, $id)) {
            return response()->json(['message' => 'Pacco non trovato nel carrello'], 404);
        }

        $inOrder = DB::table('package_order')
            ->where('package_id', $id)
            ->exists();

        if ($inOrder) {
            return response()->json(['message' => 'Pacco già associato a un ordine'], 409);
        }

        $package = Package::with(['originAddress', 'destinationAddress'])
            ->where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $newQty = (int) $request->quantity;
        $pricedPackage = $this->cartService->pricePackageModel($package, $newQty);

        $package->update([
            'quantity' => $newQty,
            'weight_price' => $pricedPackage['weight_price'] ?? $package->weight_price,
            'volume_price' => $pricedPackage['volume_price'] ?? $package->volume_price,
            'single_price' => $pricedPackage['single_price'] ?? $package->single_price,
        ]);

        return response()->json([
            'message' => 'Quantita aggiornata',
            'quantity' => $newQty,
            'single_price' => $pricedPackage['single_price'] ?? $package->single_price,
        ]);
    }

    // ── Destroy ──────────────────────────────────────────────────

    public function destroy($id)
    {
        $userId = auth()->id();

        if (! $this->packageIsInCart($userId, $id)) {
            return response()->json(['message' => 'Pacco non trovato nel carrello'], 404);
        }

        $inOrder = DB::table('package_order')
            ->where('package_id', $id)
            ->exists();

        if ($inOrder) {
            return response()->json(['message' => 'Pacco già associato a un ordine'], 409);
        }

        DB::table('cart_user')
            ->where('user_id', $userId)
            ->where('package_id', $id)
            ->delete();

        Package::where('id', $id)->where('user_id', $userId)->delete();

        return response()->json(['message' => 'Spedizione rimossa dal carrello']);
    }
}
