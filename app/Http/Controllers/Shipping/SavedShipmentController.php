<?php

namespace App\Http\Controllers\Shipping;

use App\Http\Controllers\Controller;

use App\Models\PackageAddress;
use App\Models\Package;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\PackageResource;
use App\Http\Requests\PackageStoreRequest;
use App\Services\CartService;
use Illuminate\Http\Request;

class SavedShipmentController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
    ) {}

    private function packageIsSaved(int $userId, int|string $packageId): bool
    {
        return DB::table('saved_shipments')
            ->where('user_id', $userId)
            ->where('package_id', $packageId)
            ->exists();
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $this->cleanupOrphanedShipments($user->id);

        $savedIds = DB::table('saved_shipments')->where('user_id', $user->id)->pluck('package_id');
        $packages = Package::with(['originAddress', 'destinationAddress', 'service'])->whereIn('id', $savedIds)->get();

        $invalidPackages = $packages->filter(fn ($pkg) => empty($pkg->package_type) || (empty($pkg->weight) && empty($pkg->first_size)));
        if ($invalidPackages->isNotEmpty()) {
            foreach ($invalidPackages as $pkg) {
                DB::table('saved_shipments')->where('user_id', $user->id)->where('package_id', $pkg->id)->delete();
                $pkg->delete();
            }
            $savedIds = DB::table('saved_shipments')->where('user_id', $user->id)->pluck('package_id');
            $packages = Package::with(['originAddress', 'destinationAddress', 'service'])->whereIn('id', $savedIds)->get();
        }

        return PackageResource::collection($packages)->additional(['meta' => ['empty' => $packages->isEmpty(), 'count' => $packages->count()]]);
    }

    public function store(PackageStoreRequest $request)
    {
        $data = $request->validated();
        $userId = auth()->id();

        $savedIds = DB::table('saved_shipments')->where('user_id', $userId)->pluck('package_id');
        $existingSaved = Package::with(['originAddress', 'destinationAddress', 'service'])->whereIn('id', $savedIds)->get();

        foreach ($data['packages'] as $packageData) {
            if ($this->isDuplicateSaved($existingSaved, $packageData, $data)) {
                return response()->json(['message' => 'Spedizione già configurata. Modifica almeno un dato per salvarla come nuova configurazione.'], 422);
            }
        }

        $outPackages = DB::transaction(function () use ($data, $userId) {
            $origin = PackageAddress::create($data['origin_address']);
            $destination = PackageAddress::create($data['destination_address']);

            $servicesData = $data['services'];
            $servicesData['service_type'] = !empty($servicesData['service_type']) ? $servicesData['service_type'] : 'Nessuno';
            $servicesData['date'] = $servicesData['date'] ?? '';
            $servicesData['time'] = $servicesData['time'] ?? '';
            $services = Service::create($servicesData);

            $packages = [];
            foreach ($data['packages'] as $packageData) {
                $pricedPackage = $this->cartService->pricePackageData(
                    $packageData,
                    $data['origin_address'] ?? [],
                    $data['destination_address'] ?? [],
                );

                $packages[] = Package::create([
                    'package_type' => $pricedPackage['package_type'], 'quantity' => $pricedPackage['quantity'],
                    'weight' => $pricedPackage['weight'], 'first_size' => $pricedPackage['first_size'],
                    'second_size' => $pricedPackage['second_size'], 'third_size' => $pricedPackage['third_size'],
                    'weight_price' => $pricedPackage['weight_price'] ?? null, 'volume_price' => $pricedPackage['volume_price'] ?? null,
                    'single_price' => $pricedPackage['single_price'],
                    'origin_address_id' => $origin->id, 'destination_address_id' => $destination->id,
                    'service_id' => $services->id, 'user_id' => $userId,
                ]);
            }
            return $packages;
        });

        foreach ($outPackages as $package) {
            DB::table('saved_shipments')->insert(['user_id' => $userId, 'package_id' => $package->id, 'created_at' => now(), 'updated_at' => now()]);
        }

        return PackageResource::collection($outPackages);
    }

    public function update(\App\Http\Requests\UpdateSavedShipmentRequest $request, $id)
    {
        $userId = auth()->id();

        if (! $this->packageIsSaved($userId, $id)) {
            return response()->json(['message' => 'Pacco non trovato nelle spedizioni salvate'], 404);
        }

        $inOrder = DB::table('package_order')
            ->where('package_id', $id)
            ->exists();

        if ($inOrder) {
            return response()->json(['message' => 'Spedizione già associata a un ordine'], 409);
        }

        $package = Package::where('id', $id)->where('user_id', $userId)->firstOrFail();

        $data = $request->validated();

        DB::transaction(function () use ($package, $data) {
            if (isset($data['origin_address']) && $package->originAddress) $package->originAddress->update($data['origin_address']);
            if (isset($data['destination_address']) && $package->destinationAddress) $package->destinationAddress->update($data['destination_address']);

            if (isset($data['services']) && $package->service) {
                $serviceData = $data['services'];
                $serviceData['service_type'] = !empty($serviceData['service_type']) ? $serviceData['service_type'] : 'Nessuno';
                $package->service->update($serviceData);
            }

            $packageFields = array_intersect_key($data, array_flip(['package_type', 'quantity', 'weight', 'first_size', 'second_size', 'third_size']));
            $shouldReprice = !empty($packageFields) || isset($data['origin_address']) || isset($data['destination_address']);

            if ($shouldReprice) {
                $pricedPackage = $this->cartService->pricePackageData(
                    $packageFields,
                    $package->originAddress?->toArray() ?? [],
                    $package->destinationAddress?->toArray() ?? [],
                    $package->only(['package_type', 'quantity', 'weight', 'first_size', 'second_size', 'third_size']),
                );

                $packageFields = array_merge($packageFields, [
                    'quantity' => $pricedPackage['quantity'],
                    'weight_price' => $pricedPackage['weight_price'] ?? $package->weight_price,
                    'volume_price' => $pricedPackage['volume_price'] ?? $package->volume_price,
                    'single_price' => $pricedPackage['single_price'] ?? $package->single_price,
                ]);
            }

            if (!empty($packageFields)) $package->update($packageFields);
        });

        $package->load(['originAddress', 'destinationAddress', 'service']);
        return new PackageResource($package);
    }

    public function destroy($id)
    {
        $userId = auth()->id();

        if (! $this->packageIsSaved($userId, $id)) {
            return response()->json(['message' => 'Pacco non trovato nelle spedizioni salvate'], 404);
        }

        $inOrder = DB::table('package_order')
            ->where('package_id', $id)
            ->exists();

        if ($inOrder) {
            return response()->json(['message' => 'Spedizione già associata a un ordine'], 409);
        }

        DB::table('saved_shipments')->where('user_id', $userId)->where('package_id', $id)->delete();
        DB::table('cart_user')->where('user_id', $userId)->where('package_id', $id)->delete();
        Package::where('id', $id)->where('user_id', $userId)->delete();
        return response()->json(['message' => 'Spedizione rimossa']);
    }

    public function addToCart(\App\Http\Requests\AddSavedShipmentToCartRequest $request)
    {
        $userId = auth()->id();

        $validIds = DB::table('saved_shipments')->where('user_id', $userId)
            ->whereIn('package_id', $request->package_ids)->pluck('package_id')->toArray();

        $copiedCount = 0;
        foreach ($validIds as $packageId) {
            $original = Package::with(['originAddress', 'destinationAddress', 'service'])->find($packageId);
            if (!$original) continue;

            $newOrigin = $original->originAddress ? PackageAddress::create($original->originAddress->replicate()->toArray()) : null;
            $newDest = $original->destinationAddress ? PackageAddress::create($original->destinationAddress->replicate()->toArray()) : null;
            $newService = $original->service ? Service::create($original->service->replicate()->toArray()) : null;

            $pricedPackage = $this->cartService->pricePackageData([
                'package_type' => $original->package_type,
                'quantity' => $original->quantity,
                'weight' => $original->weight,
                'first_size' => $original->first_size,
                'second_size' => $original->second_size,
                'third_size' => $original->third_size,
            ], $newOrigin?->toArray() ?? [], $newDest?->toArray() ?? []);

            $newPackage = Package::create([
                'package_type' => $pricedPackage['package_type'], 'quantity' => $pricedPackage['quantity'],
                'weight' => $pricedPackage['weight'], 'first_size' => $pricedPackage['first_size'],
                'second_size' => $pricedPackage['second_size'], 'third_size' => $pricedPackage['third_size'],
                'weight_price' => $pricedPackage['weight_price'] ?? null, 'volume_price' => $pricedPackage['volume_price'] ?? null,
                'single_price' => $pricedPackage['single_price'],
                'origin_address_id' => $newOrigin?->id, 'destination_address_id' => $newDest?->id,
                'service_id' => $newService?->id, 'user_id' => $userId,
            ]);

            DB::table('cart_user')->insert(['user_id' => $userId, 'package_id' => $newPackage->id]);
            $copiedCount++;
        }

        return response()->json(['message' => 'Spedizioni aggiunte al carrello', 'moved' => $copiedCount]);
    }

    private function cleanupOrphanedShipments(int $userId): void
    {
        $savedIds = DB::table('saved_shipments')->where('user_id', $userId)->pluck('package_id');
        if ($savedIds->isEmpty()) return;

        $orphanedIds = $savedIds->diff(Package::whereIn('id', $savedIds)->pluck('id'));
        if ($orphanedIds->isNotEmpty()) {
            DB::table('saved_shipments')->where('user_id', $userId)->whereIn('package_id', $orphanedIds)->delete();
        }
    }

    private function isDuplicateSaved($existingSaved, array $packageData, array $data): bool
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
