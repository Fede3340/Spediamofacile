<?php

namespace App\Http\Controllers\Traits;

use App\Services\CheckoutSubmissionContextService;
use App\Services\PriceEngineService;
use App\Services\ShipmentServicePricingService;

trait BuildsSessionPayload
{
    private function buildSessionPayload(): array
    {
        $submissionContext = $this->buildSubmissionContextPayload();

        return [
            'shipment_details' => session()->get('shipment_details', []),
            'packages' => session()->get('packages', []),
            'services' => session()->get('services', null),
            'total_price' => session()->get('total_price', 0),
            'step' => session()->get('step', 1),
            'content_description' => session()->get('content_description', ''),
            'pickup_date' => session()->get('pickup_date', ''),
            'sms_email_notification' => session()->get('sms_email_notification', false),
            'service_data' => session()->get('service_data', []),
            'origin_address' => session()->get('origin_address'),
            'destination_address' => session()->get('destination_address'),
            'delivery_mode' => session()->get('delivery_mode', 'home'),
            'selected_pudo' => session()->get('selected_pudo'),
            'client_submission_id' => $submissionContext['client_submission_id'] ?? null,
            'pricing_signature' => $submissionContext['pricing_signature'] ?? null,
            'pricing_snapshot_version' => $submissionContext['pricing_snapshot_version'] ?? null,
            'pricing_snapshot' => $submissionContext['pricing_snapshot'] ?? null,
            'flow_state' => $this->buildFlowState(),
        ];
    }

    private function buildSubmissionContextPayload(): array
    {
        $packages = session()->get('packages', []);
        if (! is_array($packages) || empty($packages)) {
            return [];
        }

        [$packages, $subtotalCents] = $this->normalizeSessionPackagesForSubmissionContext($packages);
        $services = $this->normalizeSessionServicesPayload(session()->get('services'));
        $subtotalCents += $this->sessionServiceSurchargeCents($services, $packages);

        $contextSeed = [];
        foreach (['client_submission_id', 'pricing_signature', 'pricing_snapshot_version', 'pricing_snapshot'] as $field) {
            $value = session()->get($field);
            if ($value !== null && $value !== '') {
                $contextSeed[$field] = $value;
            }
        }

        $context = app(CheckoutSubmissionContextService::class)->enrich(
            $contextSeed,
            app(CheckoutSubmissionContextService::class)->snapshotFromDirectOrderPayload([
                'packages' => $packages,
                'origin_address' => session()->get('origin_address'),
                'destination_address' => session()->get('destination_address'),
                'delivery_mode' => session()->get('delivery_mode', 'home'),
                'selected_pudo' => session()->get('selected_pudo'),
                'services' => $services,
            ], $subtotalCents),
            [
                'flow' => 'session-restore',
                'step' => (int) session()->get('step', 1),
            ],
        );

        foreach (['client_submission_id', 'pricing_signature', 'pricing_snapshot_version', 'pricing_snapshot'] as $field) {
            session()->put($field, $context[$field] ?? null);
        }

        return $context;
    }

    private function normalizeSessionPackagesForSubmissionContext(array $packages): array
    {
        $originPostalCode = trim((string) (
            data_get(session()->get('origin_address'), 'postal_code')
            ?? data_get(session()->get('shipment_details', []), 'origin_postal_code')
            ?? ''
        ));
        $destinationPostalCode = trim((string) (
            $this->sessionDestinationPostalCode()
            ?? data_get(session()->get('shipment_details', []), 'destination_postal_code')
            ?? ''
        ));
        $capSupplementCents = app(PriceEngineService::class)->calculateCapSupplementCents(
            $originPostalCode !== '' ? $originPostalCode : null,
            $destinationPostalCode !== '' ? $destinationPostalCode : null,
        );

        $normalizedPackages = [];
        $subtotalCents = 0;

        foreach ($packages as $package) {
            if (! is_array($package)) {
                continue;
            }

            $normalizedPackage = $package;
            $normalizedPackage['quantity'] = max(1, (int) ($package['quantity'] ?? 1));

            $singlePriceCents = (int) ($package['single_price_cents'] ?? 0);
            if ($singlePriceCents <= 0) {
                $singlePriceCents = $this->deriveSessionPackagePriceCents($package, $capSupplementCents);
            }

            $normalizedPackage['single_price_cents'] = $singlePriceCents;
            $normalizedPackages[] = $normalizedPackage;
            $subtotalCents += $singlePriceCents;
        }

        if ($subtotalCents <= 0) {
            $subtotalCents = $this->sessionTotalPriceCents();
        }

        return [$normalizedPackages, $subtotalCents];
    }

    private function deriveSessionPackagePriceCents(array $package, int $capSupplementCents): int
    {
        $quantity = max(1, (int) ($package['quantity'] ?? 1));
        $weight = $this->toPositiveFloat($package['weight'] ?? null);
        $firstSize = $this->toPositiveFloat($package['first_size'] ?? null);
        $secondSize = $this->toPositiveFloat($package['second_size'] ?? null);
        $thirdSize = $this->toPositiveFloat($package['third_size'] ?? null);

        $weightPriceCents = $weight > 0
            ? app(PriceEngineService::class)->calculateBandPriceCents('weight', $weight)
            : 0;
        $volume = ($firstSize > 0 && $secondSize > 0 && $thirdSize > 0)
            ? ($firstSize / 100) * ($secondSize / 100) * ($thirdSize / 100)
            : 0.0;
        $volumePriceCents = $volume > 0
            ? app(PriceEngineService::class)->calculateBandPriceCents('volume', $volume)
            : 0;

        $basePriceCents = max($weightPriceCents, $volumePriceCents);
        if ($basePriceCents > 0) {
            return ($basePriceCents + $capSupplementCents) * $quantity;
        }

        $singlePrice = $package['single_price'] ?? null;
        if (is_numeric($singlePrice)) {
            return (int) round(((float) $singlePrice) * 100);
        }

        return 0;
    }

    private function sessionServiceSurchargeCents(array $services, array $packages): int
    {
        $serviceData = $services['service_data'] ?? [];
        if (! is_array($serviceData)) {
            $serviceData = [];
        }

        $deliveryMode = (string) session()->get('delivery_mode', 'home');
        $selectedPudo = session()->get('selected_pudo');
        $destinationAddress = $deliveryMode === 'pudo' && is_array($selectedPudo)
            ? $selectedPudo
            : session()->get('destination_address', []);

        return app(ShipmentServicePricingService::class)->calculateSurchargeCents(
            $services['service_type'] ?? '',
            $serviceData,
            (bool) ($services['sms_email_notification'] ?? $serviceData['sms_email_notification'] ?? false),
            [
                'packages' => $packages,
                'origin_address' => session()->get('origin_address', []),
                'destination_address' => is_array($destinationAddress) ? $destinationAddress : [],
                'delivery_mode' => $deliveryMode,
                'selected_pudo' => is_array($selectedPudo) ? $selectedPudo : null,
                'requires_manual_quote' => (bool) ($serviceData['requires_manual_quote'] ?? false),
            ],
        );
    }

    private function sessionDestinationPostalCode(): ?string
    {
        $deliveryMode = (string) session()->get('delivery_mode', 'home');
        if ($deliveryMode === 'pudo') {
            $selectedPudo = session()->get('selected_pudo');
            $postalCode = trim((string) (
                data_get($selectedPudo, 'postal_code')
                ?? data_get($selectedPudo, 'zip_code')
                ?? ''
            ));

            return $postalCode !== '' ? $postalCode : null;
        }

        $postalCode = trim((string) (data_get(session()->get('destination_address'), 'postal_code') ?? ''));

        return $postalCode !== '' ? $postalCode : null;
    }

    private function sessionTotalPriceCents(): int
    {
        $rawTotal = session()->get('total_price', 0);
        if (is_numeric($rawTotal)) {
            return (int) round(((float) $rawTotal) * 100);
        }

        $normalized = str_replace(',', '.', (string) $rawTotal);
        $normalized = preg_replace('/[^0-9.]/', '', $normalized) ?? '0';

        return (int) round(((float) $normalized) * 100);
    }

    private function normalizeSessionServicesPayload(mixed $services): array
    {
        if (! is_array($services)) {
            return [];
        }

        if (! isset($services['service_data']) && isset($services['serviceData']) && is_array($services['serviceData'])) {
            $services['service_data'] = $services['serviceData'];
        }

        unset($services['serviceData']);

        $serviceType = trim((string) ($services['service_type'] ?? ''));
        if ($serviceType === '') {
            unset($services['service_type']);
        } else {
            $services['service_type'] = $serviceType;
        }

        return $services;
    }

    private function buildFlowState(): array
    {
        $shipmentDetails = session()->get('shipment_details', []);
        $packages = session()->get('packages', []);
        $contentDescription = trim((string) session()->get('content_description', ''));
        $pickupDate = trim((string) session()->get('pickup_date', ''));
        $originAddress = session()->get('origin_address');
        $destinationAddress = session()->get('destination_address');
        $deliveryMode = (string) session()->get('delivery_mode', 'home');
        $selectedPudo = session()->get('selected_pudo');

        $quoteReady = $this->hasQuoteState($shipmentDetails, $packages);
        $hasDestinationState = $deliveryMode === 'pudo'
            ? (! empty($selectedPudo) || $this->hasAddressState($destinationAddress))
            : $this->hasAddressState($destinationAddress);
        $servicesReady = $quoteReady && $contentDescription !== '' && $pickupDate !== '';
        $addressesReady = $servicesReady
            && $this->hasAddressState($originAddress)
            && $hasDestinationState;
        $summaryReady = $addressesReady;

        $lastValidRoute = '/la-tua-spedizione/2?step=colli';
        if ($summaryReady) {
            $lastValidRoute = '/la-tua-spedizione/2?step=pagamento';
        } elseif ($servicesReady) {
            $lastValidRoute = '/la-tua-spedizione/2?step=indirizzi';
        } elseif ($quoteReady) {
            $lastValidRoute = '/la-tua-spedizione/2?step=servizi';
        }

        return [
            'quote_ready' => $quoteReady,
            'services_ready' => $servicesReady,
            'addresses_ready' => $addressesReady,
            'summary_ready' => $summaryReady,
            'last_valid_route' => $lastValidRoute,
        ];
    }

    private function hasQuoteState(array $shipmentDetails, array $packages): bool
    {
        return $this->hasPackagesState($packages);
    }

    private function hasPackagesState(array $packages): bool
    {
        if (empty($packages)) {
            return false;
        }

        foreach ($packages as $package) {
            if (! is_array($package) || ! $this->hasCompletePackageState($package)) {
                return false;
            }
        }

        return true;
    }

    private function hasCompletePackageState(array $package): bool
    {
        return filled($package['package_type'] ?? null)
            && (int) ($package['quantity'] ?? 0) >= 1
            && $this->toPositiveFloat($package['weight'] ?? null) > 0
            && $this->toPositiveFloat($package['first_size'] ?? null) > 0
            && $this->toPositiveFloat($package['second_size'] ?? null) > 0
            && $this->toPositiveFloat($package['third_size'] ?? null) > 0;
    }

    private function toPositiveFloat(mixed $value): float
    {
        $normalized = str_replace(',', '.', (string) ($value ?? ''));
        $normalized = preg_replace('/[^0-9.]/', '', $normalized) ?? '0';
        $parsed = (float) $normalized;

        return $parsed > 0 ? $parsed : 0.0;
    }

    private function hasAddressState(mixed $address): bool
    {
        if (! is_array($address)) {
            return false;
        }

        return filled($address['name'] ?? null)
            && filled($address['address'] ?? null)
            && filled($address['city'] ?? null)
            && filled($address['postal_code'] ?? null);
    }
}
