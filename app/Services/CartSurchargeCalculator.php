<?php

namespace App\Services;

use App\Models\Package;
use App\Models\Service;

class CartSurchargeCalculator
{
    /**
     * Calcola i sovrapprezzi dei servizi raggruppati per indirizzo (pacchi DB / models).
     */
    public static function fromModels($packages): int
    {
        if ($packages->isEmpty()) return 0;

        // PERF-03: garantisce eager loading anche se il chiamante non lo ha fatto,
        // evitando query N+1 quando la collezione arriva senza relazioni caricate.
        // loadMissing esiste solo su Eloquent\Collection, non su Support\Collection.
        if (method_exists($packages, 'loadMissing')) {
            $packages->loadMissing(['originAddress', 'destinationAddress', 'service']);
        }

        $pricing = app(ShipmentServicePricingService::class);
        $groups = [];

        foreach ($packages as $package) {
            $service = $package->service;
            if (! $service) continue;

            $serviceSignature = app(CartService::class)->buildServiceSignatureFromService($service);
            $groupKey = self::buildAddressKeyForServices($package, $serviceSignature);

            if (! isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'service' => $service,
                    'packages' => [],
                    'origin_address' => $package->originAddress?->toArray() ?? [],
                    'destination_address' => $package->destinationAddress?->toArray() ?? [],
                ];
            }
            $groups[$groupKey]['packages'][] = $package;
        }

        return array_sum(array_map(function (array $group) use ($pricing) {
            $service = $group['service'];
            $serviceData = $service->service_data ?? [];
            $deliveryMode = $serviceData['delivery_mode'] ?? 'home';

            return $pricing->calculateSurchargeCents(
                $service->service_type ?? 'Nessuno',
                $serviceData,
                (bool) ($serviceData['sms_email_notification'] ?? false),
                [
                    'packages' => $group['packages'] ?? [],
                    'origin_address' => $group['origin_address'] ?? [],
                    'destination_address' => ($deliveryMode === 'pudo' && ! empty($serviceData['pudo']))
                        ? $serviceData['pudo']
                        : ($group['destination_address'] ?? []),
                    'delivery_mode' => $deliveryMode,
                    'selected_pudo' => $serviceData['pudo'] ?? null,
                    'requires_manual_quote' => (bool) ($serviceData['requires_manual_quote'] ?? false),
                ],
            );
        }, $groups));
    }

    /**
     * Calcola i sovrapprezzi dei servizi raggruppati per indirizzo (pacchi sessione / array).
     */
    public static function fromArray(array $packages): int
    {
        if (empty($packages)) return 0;

        $pricing = app(ShipmentServicePricingService::class);
        $groups = [];

        foreach ($packages as $package) {
            $services = is_array($package['services'] ?? null) ? $package['services'] : [];
            $serviceType = $services['service_type'] ?? 'Nessuno';
            $serviceData = $services['serviceData'] ?? $services['service_data'] ?? [];
            $smsEmailNotification = (bool) ($services['sms_email_notification'] ?? $serviceData['sms_email_notification'] ?? false);

            $groupKey = md5(
                ($package['origin_address']['city'] ?? '') . '|'
                . ($package['origin_address']['postal_code'] ?? '') . '|'
                . ($package['destination_address']['city'] ?? '') . '|'
                . ($package['destination_address']['postal_code'] ?? '') . '|'
                . app(CartService::class)->buildServiceSignatureFromGuest($services)
            );

            if (! isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'service_type' => $serviceType,
                    'service_data' => $serviceData,
                    'sms_email_notification' => $smsEmailNotification,
                    'packages' => [],
                    'origin_address' => $package['origin_address'] ?? [],
                    'destination_address' => $package['destination_address'] ?? [],
                ];
            }
            $groups[$groupKey]['packages'][] = $package;
        }

        return array_sum(array_map(function (array $group) use ($pricing) {
            $serviceData = is_array($group['service_data'] ?? null) ? $group['service_data'] : [];
            $deliveryMode = $serviceData['delivery_mode'] ?? 'home';

            return $pricing->calculateSurchargeCents(
                $group['service_type'] ?? 'Nessuno',
                $serviceData,
                (bool) ($group['sms_email_notification'] ?? false),
                [
                    'packages' => $group['packages'] ?? [],
                    'origin_address' => $group['origin_address'] ?? [],
                    'destination_address' => ($deliveryMode === 'pudo' && ! empty($serviceData['pudo']))
                        ? $serviceData['pudo']
                        : ($group['destination_address'] ?? []),
                    'delivery_mode' => $deliveryMode,
                    'selected_pudo' => $serviceData['pudo'] ?? null,
                    'requires_manual_quote' => (bool) ($serviceData['requires_manual_quote'] ?? false),
                ],
            );
        }, $groups));
    }

    private static function buildAddressKeyForServices(Package $package, string $serviceSignature): string
    {
        $origin = $package->originAddress;
        $destination = $package->destinationAddress;
        $n = fn (?string $v) => mb_strtolower(trim($v ?? ''), 'UTF-8');

        $originParts = $origin ? implode('|', [$n($origin->name), $n($origin->address), $n($origin->address_number), $n($origin->city), $n($origin->postal_code), $n($origin->province)]) : 'no-origin';
        $destParts = $destination ? implode('|', [$n($destination->name), $n($destination->address), $n($destination->address_number), $n($destination->city), $n($destination->postal_code), $n($destination->province)]) : 'no-dest';

        return md5($originParts . '::' . $destParts . '::' . $serviceSignature);
    }
}
