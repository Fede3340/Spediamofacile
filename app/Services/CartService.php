<?php

namespace App\Services;

use App\Cart\MyMoney;
use App\Models\Service;
use App\Models\Package;
use App\Services\PriceEngineService;

class CartService
{
    // --- Price helpers ---

    public function euroToCents(float|int|null $euro): int
    {
        return (int) round(($euro ?? 0) * 100);
    }

    public function unitPrice(int $totalPriceCents, int $quantity): int
    {
        return $quantity > 0 ? (int) round($totalPriceCents / $quantity) : $totalPriceCents;
    }

    public function mergeQuantity(int $existingPriceCents, int $existingQty, int $addedQty, ?int $newUnitPriceCents = null): array
    {
        $unitPrice = $newUnitPriceCents ?? $this->unitPrice($existingPriceCents, $existingQty);
        $totalQty = $existingQty + $addedQty;
        return ['quantity' => $totalQty, 'single_price' => $unitPrice * $totalQty];
    }

    public function pricePackageData(
        array $packageData,
        array $originAddress = [],
        array $destinationAddress = [],
        array $fallback = [],
    ): array {
        $payload = $fallback;
        foreach ($packageData as $key => $value) {
            if ($value !== null) {
                $payload[$key] = $value;
            }
        }
        $quantity = min(999, max(1, (int) ($payload['quantity'] ?? 1)));

        $weight = max(0, (float) preg_replace('/[^0-9.]/', '', (string) ($payload['weight'] ?? '0')));
        $firstSize = max(0, (float) preg_replace('/[^0-9.]/', '', (string) ($payload['first_size'] ?? '0')));
        $secondSize = max(0, (float) preg_replace('/[^0-9.]/', '', (string) ($payload['second_size'] ?? '0')));
        $thirdSize = max(0, (float) preg_replace('/[^0-9.]/', '', (string) ($payload['third_size'] ?? '0')));

        $priceEngine = app(PriceEngineService::class);
        $volumeM3 = ($firstSize / 100) * ($secondSize / 100) * ($thirdSize / 100);

        // Calcola direttamente in centesimi per evitare errori di arrotondamento float.
        // Solo alla fine convertiamo in euro per i campi di visualizzazione.
        // Se peso o volume sono zero (dati incompleti) il prezzo e' 0 — il frontend
        // impedisce di aggiungere al carrello pacchi con dimensioni mancanti.
        $weightPriceCents = $weight > 0 ? $priceEngine->calculateBandPriceCents('weight', $weight) : 0;
        $volumePriceCents = $volumeM3 > 0 ? $priceEngine->calculateBandPriceCents('volume', $volumeM3) : 0;

        $capSupplementCents = $priceEngine->calculateCapSupplementCents(
            $originAddress['postal_code'] ?? null,
            $destinationAddress['postal_code'] ?? null,
        );

        $unitPriceCents = max($weightPriceCents, $volumePriceCents) + $capSupplementCents;

        return array_merge($payload, [
            'quantity' => $quantity,
            'weight_price' => round($weightPriceCents / 100, 2),
            'volume_price' => round($volumePriceCents / 100, 2),
            'single_price' => $unitPriceCents * $quantity,
            'unit_price_cents' => $unitPriceCents,
        ]);
    }

    public function pricePackageModel(Package $package, ?int $quantity = null): array
    {
        $origin = $package->originAddress?->toArray() ?? [];
        $destination = $package->destinationAddress?->toArray() ?? [];

        return $this->pricePackageData([
            'package_type' => $package->package_type,
            'quantity' => $quantity ?? (int) $package->quantity,
            'weight' => $package->weight,
            'first_size' => $package->first_size,
            'second_size' => $package->second_size,
            'third_size' => $package->third_size,
        ], $origin, $destination);
    }

    public function normalizePackagePricing($packages): int
    {
        $updated = 0;

        foreach ($packages as $package) {
            $priced = $this->pricePackageModel($package);

            if (
                (int) $package->quantity !== (int) $priced['quantity']
                || (float) $package->weight_price !== (float) $priced['weight_price']
                || (float) $package->volume_price !== (float) $priced['volume_price']
                || (int) $package->single_price !== (int) $priced['single_price']
            ) {
                $package->update([
                    'quantity' => $priced['quantity'],
                    'weight_price' => $priced['weight_price'],
                    'volume_price' => $priced['volume_price'],
                    'single_price' => $priced['single_price'],
                ]);
                $updated++;
            }
        }

        return $updated;
    }

    // --- Duplicate detection ---

    public function normalize(?string $value): string
    {
        return mb_strtolower(trim($value ?? ''), 'UTF-8');
    }

    public function samePackageDimensions(array $a, array $b): bool
    {
        return ($a['package_type'] ?? '') === ($b['package_type'] ?? '')
            && (string) ($a['weight'] ?? '') === (string) ($b['weight'] ?? '')
            && (string) ($a['first_size'] ?? '') === (string) ($b['first_size'] ?? '')
            && (string) ($a['second_size'] ?? '') === (string) ($b['second_size'] ?? '')
            && (string) ($a['third_size'] ?? '') === (string) ($b['third_size'] ?? '');
    }

    public function sameAddress(array $a, array $b): bool
    {
        return ($a['city'] ?? '') === ($b['city'] ?? '')
            && ($a['postal_code'] ?? '') === ($b['postal_code'] ?? '')
            && ($a['name'] ?? '') === ($b['name'] ?? '')
            && ($a['address'] ?? '') === ($b['address'] ?? '');
    }

    public function isDuplicate(
        array $packageData, array $originAddress, array $destAddress, string $serviceSignature,
        array $existingPkg, array $existingOrigin, array $existingDest, string $existingServiceSig
    ): bool {
        return $this->samePackageDimensions($packageData, $existingPkg)
            && $this->sameAddress($originAddress, $existingOrigin)
            && $this->sameAddress($destAddress, $existingDest)
            && $serviceSignature === $existingServiceSig;
    }

    // --- Merge key ---

    public function buildMergeKey(Package $pkg): string
    {
        $o = $pkg->originAddress;
        $d = $pkg->destinationAddress;
        $s = $pkg->service;

        return implode('|', [
            $this->normalize($pkg->package_type),
            (string) $pkg->weight, (string) $pkg->first_size, (string) $pkg->second_size, (string) $pkg->third_size,
            $o ? $this->normalize($o->name) . '|' . $this->normalize($o->address) . '|' . $this->normalize($o->city) . '|' . $this->normalize($o->postal_code) : 'no-origin',
            $d ? $this->normalize($d->name) . '|' . $this->normalize($d->address) . '|' . $this->normalize($d->city) . '|' . $this->normalize($d->postal_code) : 'no-dest',
            $s ? $this->normalize($s->service_type) : 'nessuno',
            $s ? $this->buildServiceSignatureFromService($s) : 'no-service-data',
        ]);
    }

    // --- Address grouping ---

    public function buildAddressGroups($packages): array
    {
        if ($packages->isEmpty()) return [];
        $groups = [];

        foreach ($packages as $package) {
            $origin = $package->originAddress;
            $destination = $package->destinationAddress;
            $serviceType = $package->service->service_type ?? 'Nessuno';

            $originParts = $origin ? implode('|', [
                $this->normalize($origin->name), $this->normalize($origin->address), $this->normalize($origin->address_number),
                $this->normalize($origin->city), $this->normalize($origin->postal_code), $this->normalize($origin->province),
            ]) : 'no-origin';

            $destParts = $destination ? implode('|', [
                $this->normalize($destination->name), $this->normalize($destination->address), $this->normalize($destination->address_number),
                $this->normalize($destination->city), $this->normalize($destination->postal_code), $this->normalize($destination->province),
            ]) : 'no-dest';

            $key = md5($originParts . '::' . $destParts . '::' . $this->normalize($serviceType));

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'package_ids' => [], 'count' => 0,
                    'origin_summary' => $origin ? trim(($origin->name ?? '') . ' - ' . ($origin->city ?? '')) : '',
                    'destination_summary' => $destination ? trim(($destination->name ?? '') . ' - ' . ($destination->city ?? '')) : '',
                    'service_type' => $serviceType,
                ];
            }
            $groups[$key]['package_ids'][] = $package->id;
            $groups[$key]['count']++;
        }

        return array_values($groups);
    }

    // --- Subtotal calculation (delegates surcharge to CartSurchargeCalculator) ---

    public function subtotalFromModels($packages): MyMoney
    {
        $subtotal = $packages->sum(fn ($p) => (int) $p->single_price);
        $subtotal += CartSurchargeCalculator::fromModels($packages);
        return new MyMoney($subtotal);
    }

    public function subtotalFromArray(array $packages): MyMoney
    {
        $subtotal = 0;
        foreach ($packages as $package) {
            $subtotal += (int) ($package['single_price'] ?? 0);
        }
        $subtotal += CartSurchargeCalculator::fromArray($packages);
        return new MyMoney($subtotal);
    }

    // --- Service signatures ---

    public function buildServiceSignatureFromService(Service $service): string
    {
        return app(ShipmentServicePricingService::class)->buildSelectionSignature(
            $service->service_type ?? 'Nessuno', $service->service_data ?? [],
            (bool) (($service->service_data ?? [])['sms_email_notification'] ?? false),
        );
    }

    public function buildServiceSignatureFromArray(string $serviceType, array $serviceData = []): string
    {
        return app(ShipmentServicePricingService::class)->buildSelectionSignature(
            $serviceType, $serviceData, (bool) ($serviceData['sms_email_notification'] ?? false),
        );
    }

    public function buildServiceSignatureFromGuest(array $services = []): string
    {
        $serviceData = $services['serviceData'] ?? $services['service_data'] ?? [];
        return app(ShipmentServicePricingService::class)->buildSelectionSignature(
            $services['service_type'] ?? 'Nessuno',
            is_array($serviceData) ? $serviceData : [],
            (bool) ($services['sms_email_notification'] ?? (is_array($serviceData) ? ($serviceData['sms_email_notification'] ?? false) : false)),
        );
    }

    // --- Backward compat: surcharge calculation delegates ---

    public function calculateGroupedSurchargeFromModels($packages): int
    {
        return CartSurchargeCalculator::fromModels($packages);
    }

    public function calculateGroupedSurchargeFromArray(array $packages): int
    {
        return CartSurchargeCalculator::fromArray($packages);
    }

    // --- Service normalization ---

    public function normalizeServiceData(array $servicesData): array
    {
        $servicesData['service_type'] = !empty($servicesData['service_type']) ? $servicesData['service_type'] : 'Nessuno';
        $servicesData['date'] = $servicesData['date'] ?? '';
        $servicesData['time'] = $servicesData['time'] ?? '';

        if (isset($servicesData['serviceData']) && is_array($servicesData['serviceData'])) {
            $servicesData['service_data'] = $servicesData['serviceData'];
            unset($servicesData['serviceData']);
        }
        if (! isset($servicesData['service_data']) || ! is_array($servicesData['service_data'])) {
            $servicesData['service_data'] = [];
        }
        if (array_key_exists('sms_email_notification', $servicesData)) {
            $servicesData['service_data']['sms_email_notification'] = (bool) $servicesData['sms_email_notification'];
        }

        $pickupRequest = $servicesData['service_data']['pickup_request'] ?? [];
        if (! is_array($pickupRequest)) {
            $pickupRequest = [];
        }

        $pickupDate = trim((string) ($servicesData['date'] ?: ($pickupRequest['date'] ?? '')));
        $pickupTime = trim((string) ($servicesData['time'] ?: ($pickupRequest['time_slot'] ?? '09:00-18:00')));

        if ($pickupDate !== '' || ! empty($pickupRequest)) {
            $servicesData['service_data']['pickup_request'] = [
                'enabled' => (bool) ($pickupRequest['enabled'] ?? ($pickupDate !== '')),
                'date' => $this->normalizePickupRequestDate($pickupDate),
                'time_slot' => $pickupTime !== '' ? $pickupTime : '09:00-18:00',
                'notes' => trim((string) ($pickupRequest['notes'] ?? '')),
            ];
            $servicesData['date'] = $pickupDate;
            $servicesData['time'] = $servicesData['service_data']['pickup_request']['time_slot'];
        }

        return $servicesData;
    }

    private function normalizePickupRequestDate(string $pickupDate): string
    {
        $pickupDate = trim($pickupDate);
        if ($pickupDate === '') {
            return '';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $pickupDate)) {
            return $pickupDate;
        }

        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $pickupDate, $matches)) {
            return sprintf('%s-%02d-%02d', $matches[3], (int) $matches[2], (int) $matches[1]);
        }

        return $pickupDate;
    }

    // --- PUDO helpers ---

    public function applyPudoData(array $servicesData, array $requestData): array
    {
        if (!empty($requestData['pudo']) && ($requestData['delivery_mode'] ?? 'home') === 'pudo') {
            $serviceData = $servicesData['service_data'] ?? [];
            $serviceData['pudo'] = $requestData['pudo'];
            $serviceData['delivery_mode'] = 'pudo';
            $servicesData['service_data'] = $serviceData;
        } elseif (($requestData['delivery_mode'] ?? null) === 'home') {
            $serviceData = $servicesData['service_data'] ?? [];
            unset($serviceData['pudo'], $serviceData['delivery_mode']);
            $servicesData['service_data'] = $serviceData;
        }
        return $servicesData;
    }

    // --- Merge operations ---

    public function mergeIdenticalPackages($packages, int $userId): int
    {
        $cartPackageIds = \Illuminate\Support\Facades\DB::table('cart_user')
            ->where('user_id', $userId)
            ->whereIn('package_id', collect($packages)->pluck('id')->all())
            ->pluck('package_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $packages = collect($packages)
            ->filter(fn ($pkg) => $cartPackageIds->contains((int) $pkg->id))
            ->values();

        if ($packages->count() < 2) return 0;

        $protectedPackageIds = \Illuminate\Support\Facades\DB::table('saved_shipments')
            ->whereIn('package_id', $cartPackageIds->all())
            ->pluck('package_id')
            ->merge(
                \Illuminate\Support\Facades\DB::table('package_order')
                    ->whereIn('package_id', $cartPackageIds->all())
                    ->pluck('package_id')
            )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();
        $protectedLookup = array_fill_keys($protectedPackageIds, true);

        $groups = [];
        foreach ($packages as $pkg) {
            $groups[$this->buildMergeKey($pkg)][] = $pkg;
        }

        $merged = 0;
        foreach ($groups as $groupPackages) {
            if (count($groupPackages) < 2) continue;

            usort($groupPackages, function (Package $left, Package $right) use ($protectedLookup) {
                $leftProtected = isset($protectedLookup[(int) $left->id]) ? 1 : 0;
                $rightProtected = isset($protectedLookup[(int) $right->id]) ? 1 : 0;

                if ($leftProtected !== $rightProtected) {
                    return $rightProtected <=> $leftProtected;
                }

                return $left->id <=> $right->id;
            });

            $master = $groupPackages[0];
            $masterQty = (int) $master->quantity;
            $groupMerged = 0;

            for ($i = 1; $i < count($groupPackages); $i++) {
                $dup = $groupPackages[$i];

                if (isset($protectedLookup[(int) $dup->id])) {
                    continue;
                }

                $masterQty += (int) $dup->quantity;
                \Illuminate\Support\Facades\DB::table('cart_user')->where('user_id', $userId)->where('package_id', $dup->id)->delete();
                $dup->delete();
                $merged++;
                $groupMerged++;
            }

            if ($groupMerged === 0) {
                continue;
            }

            $pricedMaster = $this->pricePackageModel($master, $masterQty);

            $master->update([
                'quantity' => $pricedMaster['quantity'],
                'weight_price' => $pricedMaster['weight_price'],
                'volume_price' => $pricedMaster['volume_price'],
                'single_price' => $pricedMaster['single_price'],
            ]);
        }

        return $merged;
    }
}
