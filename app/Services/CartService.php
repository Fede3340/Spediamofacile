<?php

namespace App\Services;

use App\Cart\MyMoney;
use App\Models\Package;
use App\Models\Service;
use App\Services\Cart\CartItemPricingService;
use App\Services\Cart\CartMergeService;
use App\Services\Cart\CartSignatureService;
use App\Services\Cart\CartTotalsService;

/**
 * Orchestrator del carrello: facade su sub-service specializzati.
 * - duplicate detection / address grouping / service normalization (qui)
 * - pricing item: CartItemPricingService
 * - totali + supplementi: CartTotalsService
 * - merge identici: CartMergeService
 * - signature servizi: CartSignatureService
 */
class CartService
{
    public function __construct(
        private readonly CartItemPricingService $pricing,
        private readonly CartTotalsService $totals,
        private readonly CartMergeService $merge,
        private readonly CartSignatureService $signature,
    ) {}

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

    // --- Service signatures (delega a CartSignatureService) ---

    public function buildServiceSignatureFromService(Service $service): string
    {
        return $this->signature->buildFromService($service);
    }

    public function buildServiceSignatureFromArray(string $serviceType, array $serviceData = []): string
    {
        return $this->signature->buildFromArray($serviceType, $serviceData);
    }

    public function buildServiceSignatureFromGuest(array $services = []): string
    {
        return $this->signature->buildFromGuest($services);
    }

    public function calculateGroupedSurchargeFromModels($packages): int
    {
        return $this->totals->calculateGroupedSurchargeFromModels($packages);
    }

    public function calculateGroupedSurchargeFromArray(array $packages): int
    {
        return $this->totals->calculateGroupedSurchargeFromArray($packages);
    }

    // --- Price helpers (delega a CartItemPricingService) ---

    public function euroToCents(float|int|null $euro): int
    {
        return $this->pricing->euroToCents($euro);
    }

    public function unitPrice(int $totalPriceCents, int $quantity): int
    {
        return $this->pricing->unitPrice($totalPriceCents, $quantity);
    }

    public function mergeQuantity(int $existingPriceCents, int $existingQty, int $addedQty, ?int $newUnitPriceCents = null): array
    {
        return $this->pricing->mergeQuantity($existingPriceCents, $existingQty, $addedQty, $newUnitPriceCents);
    }

    public function pricePackageData(
        array $packageData,
        array $originAddress = [],
        array $destinationAddress = [],
        array $fallback = [],
    ): array {
        return $this->pricing->pricePackageData($packageData, $originAddress, $destinationAddress, $fallback);
    }

    public function pricePackageModel(Package $package, ?int $quantity = null): array
    {
        return $this->pricing->pricePackageModel($package, $quantity);
    }

    public function normalizePackagePricing($packages): int
    {
        return $this->pricing->normalizePackagePricing($packages);
    }

    // --- Merge key + identical packages (delega a CartMergeService) ---

    public function buildMergeKey(Package $pkg): string
    {
        return $this->merge->buildMergeKey($pkg);
    }

    public function mergeIdenticalPackages($packages, int $userId): int
    {
        return $this->merge->mergeIdenticalPackages($packages, $userId);
    }

    // --- Address grouping ---

    public function buildAddressGroups($packages): array
    {
        if ($packages->isEmpty()) {
            return [];
        }
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

            $key = md5($originParts.'::'.$destParts.'::'.$this->normalize($serviceType));

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'package_ids' => [], 'count' => 0,
                    'origin_summary' => $origin ? trim(($origin->name ?? '').' - '.($origin->city ?? '')) : '',
                    'destination_summary' => $destination ? trim(($destination->name ?? '').' - '.($destination->city ?? '')) : '',
                    'service_type' => $serviceType,
                ];
            }
            $groups[$key]['package_ids'][] = $package->id;
            $groups[$key]['count']++;
        }

        return array_values($groups);
    }

    // --- Subtotali (delega a CartTotalsService) ---

    public function subtotalFromModels($packages): MyMoney
    {
        return $this->totals->subtotalFromModels($packages);
    }

    public function subtotalFromArray(array $packages): MyMoney
    {
        return $this->totals->subtotalFromArray($packages);
    }

    // --- Service normalization ---

    public function normalizeServiceData(array $servicesData): array
    {
        $servicesData['service_type'] = ! empty($servicesData['service_type']) ? $servicesData['service_type'] : 'Nessuno';
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

    public function applyPudoData(array $servicesData, array $requestData): array
    {
        if (! empty($requestData['pudo']) && ($requestData['delivery_mode'] ?? 'home') === 'pudo') {
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
}
