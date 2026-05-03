<?php

namespace App\Services\Cart;

use App\Models\Service;
use App\Services\ShipmentServicePricingService;

/**
 * Costruzione signature dei servizi (per dedup carrello + idempotency).
 * Wrapper sui builder di ShipmentServicePricingService::buildSelectionSignature.
 */
class CartSignatureService
{
    public function __construct(private readonly ShipmentServicePricingService $servicePricing) {}

    public function buildFromService(Service $service): string
    {
        return $this->servicePricing->buildSelectionSignature(
            $service->service_type ?? 'Nessuno',
            $service->service_data ?? [],
            (bool) (($service->service_data ?? [])['sms_email_notification'] ?? false),
        );
    }

    public function buildFromArray(string $serviceType, array $serviceData = []): string
    {
        return $this->servicePricing->buildSelectionSignature(
            $serviceType, $serviceData, (bool) ($serviceData['sms_email_notification'] ?? false),
        );
    }

    public function buildFromGuest(array $services = []): string
    {
        $serviceData = $services['serviceData'] ?? $services['service_data'] ?? [];
        $serviceData = is_array($serviceData) ? $serviceData : [];

        return $this->servicePricing->buildSelectionSignature(
            $services['service_type'] ?? 'Nessuno',
            $serviceData,
            (bool) ($services['sms_email_notification'] ?? ($serviceData['sms_email_notification'] ?? false)),
        );
    }
}
