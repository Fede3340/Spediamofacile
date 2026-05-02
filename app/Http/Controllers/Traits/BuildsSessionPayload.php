<?php

namespace App\Http\Controllers\Traits;

use App\Services\CheckoutSubmissionContextService;
use App\Services\PriceEngineService;
use App\Services\ShipmentServicePricingService;

trait BuildsSessionPayload
{
    private const SUBMISSION_FIELDS = ['client_submission_id', 'pricing_signature', 'pricing_snapshot_version', 'pricing_snapshot'];

    private function buildSessionPayload(): array
    {
        $ctx = $this->buildSubmissionContextPayload();

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
            'client_submission_id' => $ctx['client_submission_id'] ?? null,
            'pricing_signature' => $ctx['pricing_signature'] ?? null,
            'pricing_snapshot_version' => $ctx['pricing_snapshot_version'] ?? null,
            'pricing_snapshot' => $ctx['pricing_snapshot'] ?? null,
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

        $seed = [];
        foreach (self::SUBMISSION_FIELDS as $field) {
            if (($v = session()->get($field)) !== null && $v !== '') {
                $seed[$field] = $v;
            }
        }
        $svc = app(CheckoutSubmissionContextService::class);
        $context = $svc->enrich($seed, $svc->snapshotFromDirectOrderPayload([
            'packages' => $packages,
            'origin_address' => session()->get('origin_address'),
            'destination_address' => session()->get('destination_address'),
            'delivery_mode' => session()->get('delivery_mode', 'home'),
            'selected_pudo' => session()->get('selected_pudo'),
            'services' => $services,
        ], $subtotalCents), ['flow' => 'session-restore', 'step' => (int) session()->get('step', 1)]);

        foreach (self::SUBMISSION_FIELDS as $field) {
            session()->put($field, $context[$field] ?? null);
        }

        return $context;
    }

    private function normalizeSessionPackagesForSubmissionContext(array $packages): array
    {
        $shipDet = session()->get('shipment_details', []);
        $origin = $this->trimString(data_get(session()->get('origin_address'), 'postal_code') ?? data_get($shipDet, 'origin_postal_code'));
        $dest = $this->trimString($this->sessionDestinationPostalCode() ?? data_get($shipDet, 'destination_postal_code'));
        $capCents = app(PriceEngineService::class)->calculateCapSupplementCents($origin ?: null, $dest ?: null);

        $normalized = [];
        $subtotal = 0;
        foreach ($packages as $package) {
            if (! is_array($package)) {
                continue;
            }
            $package['quantity'] = max(1, (int) ($package['quantity'] ?? 1));
            $cents = (int) ($package['single_price_cents'] ?? 0);
            $package['single_price_cents'] = $cents > 0 ? $cents : $this->deriveSessionPackagePriceCents($package, $capCents);
            $normalized[] = $package;
            $subtotal += $package['single_price_cents'];
        }

        return [$normalized, $subtotal > 0 ? $subtotal : $this->sessionTotalPriceCents()];
    }

    private function deriveSessionPackagePriceCents(array $package, int $capSupplementCents): int
    {
        $weight = $this->toPositiveFloat($package['weight'] ?? null);
        $w = $this->toPositiveFloat($package['first_size'] ?? null);
        $h = $this->toPositiveFloat($package['second_size'] ?? null);
        $d = $this->toPositiveFloat($package['third_size'] ?? null);
        $engine = app(PriceEngineService::class);
        $weightCents = $weight > 0 ? $engine->calculateBandPriceCents('weight', $weight) : 0;
        $volume = ($w > 0 && $h > 0 && $d > 0) ? ($w / 100) * ($h / 100) * ($d / 100) : 0.0;
        $baseCents = max($weightCents, $volume > 0 ? $engine->calculateBandPriceCents('volume', $volume) : 0);

        if ($baseCents > 0) {
            return ($baseCents + $capSupplementCents) * max(1, (int) ($package['quantity'] ?? 1));
        }
        $single = $package['single_price'] ?? null;

        return is_numeric($single) ? (int) round(((float) $single) * 100) : 0;
    }

    private function sessionServiceSurchargeCents(array $services, array $packages): int
    {
        $serviceData = is_array($services['service_data'] ?? null) ? $services['service_data'] : [];
        $deliveryMode = (string) session()->get('delivery_mode', 'home');
        $pudo = session()->get('selected_pudo');
        $dest = $deliveryMode === 'pudo' && is_array($pudo) ? $pudo : session()->get('destination_address', []);

        return app(ShipmentServicePricingService::class)->calculateSurchargeCents(
            $services['service_type'] ?? '',
            $serviceData,
            (bool) ($services['sms_email_notification'] ?? $serviceData['sms_email_notification'] ?? false),
            [
                'packages' => $packages,
                'origin_address' => session()->get('origin_address', []),
                'destination_address' => is_array($dest) ? $dest : [],
                'delivery_mode' => $deliveryMode,
                'selected_pudo' => is_array($pudo) ? $pudo : null,
                'requires_manual_quote' => (bool) ($serviceData['requires_manual_quote'] ?? false),
            ],
        );
    }

    private function sessionDestinationPostalCode(): ?string
    {
        if ((string) session()->get('delivery_mode', 'home') === 'pudo') {
            $pudo = session()->get('selected_pudo');
            $code = $this->trimString(data_get($pudo, 'postal_code') ?? data_get($pudo, 'zip_code'));
        } else {
            $code = $this->trimString(data_get(session()->get('destination_address'), 'postal_code'));
        }

        return $code !== '' ? $code : null;
    }

    private function sessionTotalPriceCents(): int
    {
        $raw = session()->get('total_price', 0);
        $normalized = is_numeric($raw)
            ? (string) $raw
            : (preg_replace('/[^0-9.]/', '', str_replace(',', '.', (string) $raw)) ?? '0');

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
        $type = trim((string) ($services['service_type'] ?? ''));
        if ($type === '') {
            unset($services['service_type']);
        } else {
            $services['service_type'] = $type;
        }

        return $services;
    }

    private function buildFlowState(): array
    {
        $contentDesc = trim((string) session()->get('content_description', ''));
        $pickupDate = trim((string) session()->get('pickup_date', ''));
        $deliveryMode = (string) session()->get('delivery_mode', 'home');
        $pudo = session()->get('selected_pudo');
        $dest = session()->get('destination_address');

        $quoteReady = $this->hasPackagesState(session()->get('packages', []));
        $hasDest = $deliveryMode === 'pudo' ? (! empty($pudo) || $this->hasAddressState($dest)) : $this->hasAddressState($dest);
        $servicesReady = $quoteReady && $contentDesc !== '' && $pickupDate !== '';
        $addressesReady = $servicesReady && $this->hasAddressState(session()->get('origin_address')) && $hasDest;

        return [
            'quote_ready' => $quoteReady,
            'services_ready' => $servicesReady,
            'addresses_ready' => $addressesReady,
            'summary_ready' => $addressesReady,
            'last_valid_route' => match (true) {
                $addressesReady => '/la-tua-spedizione/2?step=pagamento',
                $servicesReady => '/la-tua-spedizione/2?step=indirizzi',
                $quoteReady => '/la-tua-spedizione/2?step=servizi',
                default => '/la-tua-spedizione/2?step=colli',
            },
        ];
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
        $normalized = preg_replace('/[^0-9.]/', '', str_replace(',', '.', (string) ($value ?? ''))) ?? '0';

        return ($f = (float) $normalized) > 0 ? $f : 0.0;
    }

    private function hasAddressState(mixed $address): bool
    {
        return is_array($address)
            && filled($address['name'] ?? null)
            && filled($address['address'] ?? null)
            && filled($address['city'] ?? null)
            && filled($address['postal_code'] ?? null);
    }

    private function trimString(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }
}
