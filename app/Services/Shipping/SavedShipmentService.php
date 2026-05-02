<?php

namespace App\Services\Shipping;

use App\Models\Package;
use App\Models\PackageAddress;
use App\Models\Service;
use App\Services\CartService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SavedShipmentService
{
    public function __construct(private readonly CartService $cartService) {}

    public function isSaved(int $userId, int|string $packageId): bool
    {
        return DB::table('saved_shipments')->where('user_id', $userId)->where('package_id', $packageId)->exists();
    }

    public function isInOrder(int|string $packageId): bool
    {
        return DB::table('package_order')->where('package_id', $packageId)->exists();
    }

    public function listForUser(int $userId): Collection
    {
        $this->cleanupOrphaned($userId);

        $packages = $this->loadByUser($userId);
        $invalid = $packages->filter(fn ($pkg) => empty($pkg->package_type) || (empty($pkg->weight) && empty($pkg->first_size)));

        if ($invalid->isNotEmpty()) {
            foreach ($invalid as $pkg) {
                DB::table('saved_shipments')->where('user_id', $userId)->where('package_id', $pkg->id)->delete();
                $pkg->delete();
            }
            $packages = $this->loadByUser($userId);
        }

        return $packages;
    }

    public function findDuplicate(int $userId, array $data): bool
    {
        $existing = $this->loadByUser($userId);
        foreach ($data['packages'] as $packageData) {
            if ($this->isDuplicate($existing, $packageData, $data)) {
                return true;
            }
        }

        return false;
    }

    public function create(int $userId, array $data): array
    {
        $packages = DB::transaction(function () use ($data, $userId) {
            $origin = PackageAddress::create($data['origin_address']);
            $destination = PackageAddress::create($data['destination_address']);
            $services = Service::create($this->normalizeServiceData($data['services']));

            $created = [];
            foreach ($data['packages'] as $packageData) {
                $priced = $this->cartService->pricePackageData($packageData, $data['origin_address'] ?? [], $data['destination_address'] ?? []);
                $created[] = Package::create($this->buildPackagePayload($priced, $userId, $origin->id, $destination->id, $services->id));
            }

            return $created;
        });

        foreach ($packages as $package) {
            DB::table('saved_shipments')->insert(['user_id' => $userId, 'package_id' => $package->id, 'created_at' => now(), 'updated_at' => now()]);
        }

        return $packages;
    }

    public function updatePackage(Package $package, array $data): Package
    {
        DB::transaction(function () use ($package, $data) {
            if (isset($data['origin_address']) && $package->originAddress) {
                $package->originAddress->update($data['origin_address']);
            }
            if (isset($data['destination_address']) && $package->destinationAddress) {
                $package->destinationAddress->update($data['destination_address']);
            }
            if (isset($data['services']) && $package->service) {
                $package->service->update($this->normalizeServiceData($data['services']));
            }

            $fields = array_intersect_key($data, array_flip(['package_type', 'quantity', 'weight', 'first_size', 'second_size', 'third_size']));
            $shouldReprice = ! empty($fields) || isset($data['origin_address']) || isset($data['destination_address']);

            if ($shouldReprice) {
                $priced = $this->cartService->pricePackageData(
                    $fields,
                    $package->originAddress?->toArray() ?? [],
                    $package->destinationAddress?->toArray() ?? [],
                    $package->only(['package_type', 'quantity', 'weight', 'first_size', 'second_size', 'third_size']),
                );
                $fields = array_merge($fields, [
                    'quantity' => $priced['quantity'],
                    'weight_price' => $priced['weight_price'] ?? $package->weight_price,
                    'volume_price' => $priced['volume_price'] ?? $package->volume_price,
                    'single_price' => $priced['single_price'] ?? $package->single_price,
                ]);
            }

            if (! empty($fields)) {
                $package->update($fields);
            }
        });

        return $package->load(['originAddress', 'destinationAddress', 'service']);
    }

    public function delete(int $userId, int|string $packageId): void
    {
        DB::table('saved_shipments')->where('user_id', $userId)->where('package_id', $packageId)->delete();
        DB::table('cart_user')->where('user_id', $userId)->where('package_id', $packageId)->delete();
        Package::where('id', $packageId)->where('user_id', $userId)->delete();
    }

    public function copyToCart(int $userId, array $packageIds): int
    {
        $validIds = DB::table('saved_shipments')->where('user_id', $userId)->whereIn('package_id', $packageIds)->pluck('package_id')->toArray();
        $copied = 0;

        foreach ($validIds as $packageId) {
            $original = Package::with(['originAddress', 'destinationAddress', 'service'])->find($packageId);
            if (! $original) {
                continue;
            }

            $newOrigin = $original->originAddress ? PackageAddress::create($original->originAddress->replicate()->toArray()) : null;
            $newDest = $original->destinationAddress ? PackageAddress::create($original->destinationAddress->replicate()->toArray()) : null;
            $newService = $original->service ? Service::create($original->service->replicate()->toArray()) : null;

            $priced = $this->cartService->pricePackageData(
                $original->only(['package_type', 'quantity', 'weight', 'first_size', 'second_size', 'third_size']),
                $newOrigin?->toArray() ?? [],
                $newDest?->toArray() ?? [],
            );

            $newPackage = Package::create($this->buildPackagePayload($priced, $userId, $newOrigin?->id, $newDest?->id, $newService?->id));
            DB::table('cart_user')->insert(['user_id' => $userId, 'package_id' => $newPackage->id]);
            $copied++;
        }

        return $copied;
    }

    private function loadByUser(int $userId): Collection
    {
        $savedIds = DB::table('saved_shipments')->where('user_id', $userId)->pluck('package_id');

        return Package::with(['originAddress', 'destinationAddress', 'service'])->whereIn('id', $savedIds)->get();
    }

    private function cleanupOrphaned(int $userId): void
    {
        $savedIds = DB::table('saved_shipments')->where('user_id', $userId)->pluck('package_id');
        if ($savedIds->isEmpty()) {
            return;
        }

        $orphanedIds = $savedIds->diff(Package::whereIn('id', $savedIds)->pluck('id'));
        if ($orphanedIds->isNotEmpty()) {
            DB::table('saved_shipments')->where('user_id', $userId)->whereIn('package_id', $orphanedIds)->delete();
        }
    }

    private function normalizeServiceData(array $servicesData): array
    {
        $servicesData['service_type'] = ! empty($servicesData['service_type']) ? $servicesData['service_type'] : 'Nessuno';
        $servicesData['date'] = $servicesData['date'] ?? '';
        $servicesData['time'] = $servicesData['time'] ?? '';

        return $servicesData;
    }

    private function buildPackagePayload(array $priced, int $userId, ?int $originId, ?int $destinationId, ?int $serviceId): array
    {
        return [
            'package_type' => $priced['package_type'], 'quantity' => $priced['quantity'],
            'weight' => $priced['weight'], 'first_size' => $priced['first_size'],
            'second_size' => $priced['second_size'], 'third_size' => $priced['third_size'],
            'weight_price' => $priced['weight_price'] ?? null, 'volume_price' => $priced['volume_price'] ?? null,
            'single_price' => $priced['single_price'],
            'origin_address_id' => $originId, 'destination_address_id' => $destinationId,
            'service_id' => $serviceId, 'user_id' => $userId,
        ];
    }

    private function isDuplicate(Collection $existingSaved, array $packageData, array $data): bool
    {
        return $existingSaved->contains(function ($existing) use ($packageData, $data) {
            return $existing->package_type === $packageData['package_type']
                && (string) $existing->weight === (string) $packageData['weight']
                && (string) $existing->first_size === (string) $packageData['first_size']
                && (string) $existing->second_size === (string) $packageData['second_size']
                && (string) $existing->third_size === (string) $packageData['third_size']
                && (int) $existing->quantity === (int) $packageData['quantity']
                && $existing->originAddress
                && $existing->originAddress->city === ($data['origin_address']['city'] ?? '')
                && $existing->originAddress->postal_code === ($data['origin_address']['postal_code'] ?? '')
                && $existing->originAddress->name === ($data['origin_address']['name'] ?? '')
                && $existing->originAddress->address === ($data['origin_address']['address'] ?? '')
                && $existing->destinationAddress
                && $existing->destinationAddress->city === ($data['destination_address']['city'] ?? '')
                && $existing->destinationAddress->postal_code === ($data['destination_address']['postal_code'] ?? '')
                && $existing->destinationAddress->name === ($data['destination_address']['name'] ?? '')
                && $existing->destinationAddress->address === ($data['destination_address']['address'] ?? '');
        });
    }
}
