<?php

namespace App\Services\Cart;

use App\Models\Package;
use App\Models\PackageAddress;
use App\Models\Service;
use App\Services\CartService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Logica business CRUD pacchi nel carrello utente autenticato.
 * Il controller gestisce solo HTTP; qui vivono transazioni e merge.
 */
class CartItemService
{
    public function __construct(
        private readonly CartService $cart,
    ) {}

    public function isInCart(int $userId, int|string $packageId): bool
    {
        return DB::table('cart_user')
            ->where('user_id', $userId)
            ->where('package_id', $packageId)
            ->exists();
    }

    public function isInOrder(int|string $packageId): bool
    {
        return DB::table('package_order')->where('package_id', $packageId)->exists();
    }

    public function storePackages(array $data, int $userId): Collection
    {
        return DB::transaction(function () use ($data, $userId) {
            $existingIds = DB::table('cart_user')->where('user_id', $userId)->lockForUpdate()->pluck('package_id');
            $existing = Package::with(['originAddress', 'destinationAddress', 'service'])->whereIn('id', $existingIds)->get();

            $servicesData = $this->cart->applyPudoData($this->cart->normalizeServiceData($data['services']), $data);
            $packages = collect();
            $origin = $destination = $services = null;

            foreach ($data['packages'] as $packageData) {
                $priced = $this->cart->pricePackageData($packageData, $data['origin_address'] ?? [], $data['destination_address'] ?? []);
                $signature = $this->cart->buildServiceSignatureFromArray($servicesData['service_type'] ?? 'Nessuno', $servicesData['service_data'] ?? []);
                $duplicate = $this->findDuplicate($existing, $priced, $data, $signature);

                if ($duplicate) {
                    $this->mergeIntoDuplicate($duplicate, $priced);
                    $packages->push($duplicate);

                    continue;
                }

                if (! $origin) {
                    $origin = PackageAddress::create($data['origin_address']);
                    $destination = PackageAddress::create($data['destination_address']);
                    $services = Service::create($servicesData);
                }

                $created = $this->createPackage($priced, $origin->id, $destination->id, $services->id, $userId);
                $packages->push($created);
                $existing->push($created);
            }

            $this->attachToCart($packages, $userId);

            return $packages;
        });
    }

    public function updatePackage(Package $package, array $data): Package
    {
        return DB::transaction(function () use ($package, $data) {
            if (isset($data['origin_address']) && $package->originAddress) {
                $package->originAddress->update($data['origin_address']);
            }
            if (isset($data['destination_address']) && $package->destinationAddress) {
                $package->destinationAddress->update($data['destination_address']);
            }
            $package->load(['originAddress', 'destinationAddress']);

            if (isset($data['services']) && $package->service) {
                $servicesData = $this->cart->applyPudoData($this->cart->normalizeServiceData($data['services']), $data);
                $package->service->update($servicesData);
            }

            if (! empty($data['packages'])) {
                $this->repriceAndUpdate($package, $data['packages'][0], $data['content_description'] ?? null);
            }

            return $package->load(['originAddress', 'destinationAddress', 'service']);
        });
    }

    public function updateQuantity(Package $package, int $newQty): array
    {
        $priced = $this->cart->pricePackageModel($package, $newQty);
        $package->update([
            'quantity' => $newQty,
            'weight_price' => $priced['weight_price'] ?? $package->weight_price,
            'volume_price' => $priced['volume_price'] ?? $package->volume_price,
            'single_price' => $priced['single_price'] ?? $package->single_price,
        ]);

        return ['quantity' => $newQty, 'single_price' => $priced['single_price'] ?? $package->single_price];
    }

    public function destroy(int $userId, int|string $packageId): void
    {
        DB::table('cart_user')->where('user_id', $userId)->where('package_id', $packageId)->delete();
        Package::where('id', $packageId)->where('user_id', $userId)->delete();
    }

    private function findDuplicate(Collection $existing, array $priced, array $data, string $signature): ?Package
    {
        return $existing->first(function (Package $existingPkg) use ($priced, $data, $signature) {
            if (! $existingPkg->originAddress || ! $existingPkg->destinationAddress || ! $existingPkg->service) {
                return false;
            }

            return $this->cart->isDuplicate(
                $priced, $data['origin_address'] ?? [], $data['destination_address'] ?? [], $signature,
                $existingPkg->toArray(), $existingPkg->originAddress->toArray(), $existingPkg->destinationAddress->toArray(),
                $this->cart->buildServiceSignatureFromService($existingPkg->service),
            );
        });
    }

    private function mergeIntoDuplicate(Package $duplicate, array $priced): void
    {
        $merged = $this->cart->mergeQuantity(
            (int) $duplicate->single_price, (int) $duplicate->quantity,
            (int) ($priced['quantity'] ?? 1), (int) ($priced['unit_price_cents'] ?? 0),
        );
        $duplicate->update([
            'quantity' => $merged['quantity'],
            'weight_price' => $priced['weight_price'] ?? $duplicate->weight_price,
            'volume_price' => $priced['volume_price'] ?? $duplicate->volume_price,
            'single_price' => $merged['single_price'],
        ]);
    }

    private function createPackage(array $priced, int $originId, int $destId, int $serviceId, int $userId): Package
    {
        return Package::create([
            'package_type' => $priced['package_type'],
            'quantity' => (int) ($priced['quantity'] ?? 1),
            'weight' => $priced['weight'],
            'first_size' => $priced['first_size'],
            'second_size' => $priced['second_size'],
            'third_size' => $priced['third_size'],
            'weight_price' => $priced['weight_price'] ?? null,
            'volume_price' => $priced['volume_price'] ?? null,
            'single_price' => $priced['single_price'],
            'origin_address_id' => $originId,
            'destination_address_id' => $destId,
            'service_id' => $serviceId,
            'user_id' => $userId,
        ]);
    }

    private function attachToCart(Collection $packages, int $userId): void
    {
        foreach ($packages as $package) {
            $exists = DB::table('cart_user')->where('user_id', $userId)->where('package_id', $package->id)->exists();
            if (! $exists) {
                DB::table('cart_user')->insert(['user_id' => $userId, 'package_id' => $package->id]);
            }
        }
    }

    private function repriceAndUpdate(Package $package, array $packageData, ?string $contentDescription): void
    {
        $priced = $this->cart->pricePackageData(
            $packageData,
            $package->originAddress?->toArray() ?? [],
            $package->destinationAddress?->toArray() ?? [],
            $package->only(['package_type', 'quantity', 'weight', 'first_size', 'second_size', 'third_size']),
        );
        $package->update([
            'package_type' => $priced['package_type'] ?? $package->package_type,
            'quantity' => (int) ($priced['quantity'] ?? 1),
            'weight' => $priced['weight'] ?? $package->weight,
            'first_size' => $priced['first_size'] ?? $package->first_size,
            'second_size' => $priced['second_size'] ?? $package->second_size,
            'third_size' => $priced['third_size'] ?? $package->third_size,
            'weight_price' => $priced['weight_price'] ?? $package->weight_price,
            'volume_price' => $priced['volume_price'] ?? $package->volume_price,
            'single_price' => $priced['single_price'] ?? $package->single_price,
            'content_description' => $contentDescription ?? $package->content_description,
        ]);
    }
}
