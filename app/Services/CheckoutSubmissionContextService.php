<?php

namespace App\Services;

use App\Services\Cart\CartTotalsService;
use App\Services\Checkout\CheckoutDiscountContextResolver;
use App\Services\Checkout\SnapshotCompactingHelpers;
use Illuminate\Database\Eloquent\Collection;

/**
 * Costruisce il submission context idempotente del checkout:
 * - client_submission_id (idempotency key)
 * - pricing_signature (fingerprint sha256 dello snapshot)
 * - pricing_snapshot (struttura dati canonical: pacchi, route, servizi, sconto)
 *
 * La logica di discount (coupon/referral) e' delegata a CheckoutDiscountContextResolver.
 */
class CheckoutSubmissionContextService
{
    use SnapshotCompactingHelpers;

    public function __construct(
        private readonly CheckoutDiscountContextResolver $discountResolver,
        private readonly CartTotalsService $cartTotals,
    ) {}

    public function fromRequestArray(array $input): array
    {
        $context = [];

        foreach (['client_submission_id'] as $field) {
            $value = trim((string) ($input[$field] ?? ''));
            if ($value !== '') {
                $context[$field] = $value;
            }
        }

        $discountContext = $this->discountResolver->normalize($input['discount_context'] ?? null);
        if ($discountContext !== null) {
            $context['discount_context'] = $discountContext;
        }

        return $context;
    }

    public function enrich(array $context, array $snapshot, array $seed = []): array
    {
        $normalizedSnapshot = $this->sortRecursive($snapshot);
        $discountContext = $this->discountResolver->canonicalForSnapshot(
            $this->discountResolver->normalize($context['discount_context'] ?? null),
            $normalizedSnapshot,
            $seed,
        );
        $storageSnapshot = $this->discountResolver->attachToSnapshot($normalizedSnapshot, $discountContext);
        $version = 1;

        $signature = $this->fingerprint([
            'snapshot' => $normalizedSnapshot,
            'version' => $version,
        ]);

        $submissionId = trim((string) ($context['client_submission_id'] ?? ''));
        if ($submissionId === '') {
            $submissionId = 'submission-'.$this->fingerprint([
                'snapshot' => $normalizedSnapshot,
                'version' => $version,
                'seed' => $this->sortRecursive($seed),
            ]);
        }

        return [
            'client_submission_id' => $submissionId,
            'pricing_signature' => $signature,
            'pricing_snapshot_version' => $version,
            'pricing_snapshot' => $storageSnapshot,
            'discount_context' => $discountContext,
        ];
    }

    public function snapshotFromPackages($packages, ?array $billingData = null): array
    {
        // Preserva Eloquent\Collection per non perdere loadMissing() usato nel calcolo subtotal.
        $collection = $packages instanceof Collection
            ? $packages
            : Collection::make($packages);
        $firstPackage = $collection->first();
        $service = $firstPackage?->service;
        $serviceData = is_array($service?->service_data) ? $service->service_data : [];
        $deliveryMode = (string) ($serviceData['delivery_mode'] ?? 'home');
        $selectedPudo = $deliveryMode === 'pudo' ? ($serviceData['pudo'] ?? null) : null;
        $groupSnapshots = $this->buildPackageGroupSnapshotsFromModels($collection);

        return $this->baseSnapshot(
            $collection->map(fn ($package) => $this->packageRow($package))->all(),
            [
                'origin' => $this->compactAddress($firstPackage?->originAddress?->toArray() ?? []),
                'destination' => $this->compactAddress(
                    $deliveryMode === 'pudo' && is_array($selectedPudo)
                        ? $selectedPudo
                        : ($firstPackage?->destinationAddress?->toArray() ?? [])
                ),
            ],
            [
                'service_type' => (string) ($service?->service_type ?? 'Nessuno'),
                'selected' => $this->normalizeServiceTypeList((string) ($service?->service_type ?? 'Nessuno')),
                'service_payload' => $this->compactServiceData($serviceData),
            ],
            (int) $this->cartTotals->subtotalFromModels($collection)->amount(),
            $billingData,
            $groupSnapshots,
        );
    }

    public function snapshotFromDirectOrderPayload(array $data, int $subtotalCents): array
    {
        $services = is_array($data['services'] ?? null) ? $data['services'] : [];
        $serviceData = $services['service_data'] ?? $services['serviceData'] ?? [];
        $deliveryMode = (string) ($data['delivery_mode'] ?? ($serviceData['delivery_mode'] ?? 'home'));
        $selectedPudo = $data['selected_pudo'] ?? $data['pudo'] ?? ($serviceData['pudo'] ?? null);

        $packages = collect($data['packages'] ?? [])->map(fn (array $package) => $this->packageRowFromArray($package))->all();
        $route = [
            'origin' => $this->compactAddress($data['origin_address'] ?? []),
            'destination' => $this->compactAddress(
                $deliveryMode === 'pudo' && is_array($selectedPudo)
                    ? $selectedPudo
                    : ($data['destination_address'] ?? [])
            ),
        ];
        $servicesPayload = [
            'service_type' => (string) ($services['service_type'] ?? 'Nessuno'),
            'selected' => $this->normalizeServiceTypeList((string) ($services['service_type'] ?? 'Nessuno')),
            'service_payload' => $this->compactServiceData(is_array($serviceData) ? $serviceData : []),
        ];

        return $this->baseSnapshot(
            $packages,
            $route,
            $servicesPayload,
            $subtotalCents,
            $data['billing_data'] ?? null,
            [[
                'group_key' => 'direct-order',
                'packages' => $packages,
                'route' => $route,
                'services' => $servicesPayload,
                'subtotal_cents' => $subtotalCents,
            ]],
        );
    }

    private function packageRow($package): array
    {
        return [
            'package_type' => (string) ($package->package_type ?? ''),
            'quantity' => (int) ($package->quantity ?? 1),
            'weight' => (string) ($package->weight ?? ''),
            'first_size' => (string) ($package->first_size ?? ''),
            'second_size' => (string) ($package->second_size ?? ''),
            'third_size' => (string) ($package->third_size ?? ''),
            'single_price_cents' => (int) ($package->single_price ?? 0),
        ];
    }

    private function packageRowFromArray(array $package): array
    {
        return [
            'package_type' => (string) ($package['package_type'] ?? ''),
            'quantity' => (int) ($package['quantity'] ?? 1),
            'weight' => (string) ($package['weight'] ?? ''),
            'first_size' => (string) ($package['first_size'] ?? ''),
            'second_size' => (string) ($package['second_size'] ?? ''),
            'third_size' => (string) ($package['third_size'] ?? ''),
            'single_price_cents' => (int) ($package['single_price_cents'] ?? 0),
        ];
    }

    private function baseSnapshot(array $packages, array $route, array $services, int $subtotalCents, ?array $billingData, array $groups = []): array
    {
        $servicePayload = $services['service_payload'] ?? $services['service_data'] ?? [];
        if (! is_array($servicePayload)) {
            $servicePayload = [];
        }

        if (array_key_exists('service_data', $services) && ! array_key_exists('service_payload', $services)) {
            $servicePayload = $this->compactServiceData($servicePayload);
        }

        $servicesNormalized = [
            'service_type' => (string) ($services['service_type'] ?? 'Nessuno'),
            'selected' => $this->normalizeServiceTypeList((string) ($services['service_type'] ?? 'Nessuno')),
            'service_payload' => $this->sortRecursive($servicePayload),
        ];

        return $this->sortRecursive([
            'total_cents' => $subtotalCents,
            'package_count' => collect($packages)->sum(fn (array $package) => (int) ($package['quantity'] ?? 1)),
            'packages' => $packages,
            'route' => $route,
            'services' => $servicesNormalized,
            'group_count' => max(1, count($groups)),
            'groups' => $groups !== [] ? $groups : [[
                'group_key' => 'single-group',
                'packages' => $packages,
                'route' => $route,
                'services' => $servicesNormalized,
                'subtotal_cents' => $subtotalCents,
            ]],
            'billing_type' => is_array($billingData) ? ($billingData['type'] ?? null) : null,
        ]);
    }

    private function buildPackageGroupSnapshotsFromModels($packages): array
    {
        $servicePricing = app(ShipmentServicePricingService::class);
        $normalize = fn ($value) => mb_strtolower(trim((string) $value), 'UTF-8');
        $groups = [];

        foreach ($packages as $package) {
            $origin = $package->originAddress?->toArray() ?? [];
            $destination = $package->destinationAddress?->toArray() ?? [];
            $service = $package->service;
            $serviceData = is_array($service?->service_data) ? $service->service_data : [];
            $serviceType = (string) ($service?->service_type ?? 'Nessuno');
            $serviceSignature = $servicePricing->buildSelectionSignature(
                $serviceType,
                $serviceData,
                (bool) ($serviceData['sms_email_notification'] ?? false),
            );

            $key = md5(implode('::', [
                $this->compactAddressSignature($origin, $normalize),
                $this->compactAddressSignature($destination, $normalize),
                $normalize($serviceType),
                $serviceSignature,
            ]));

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'group_key' => $key,
                    'packages' => [],
                    'route' => [
                        'origin' => $this->compactAddress($origin),
                        'destination' => $this->compactAddress($destination),
                    ],
                    'services' => [
                        'service_type' => $serviceType,
                        'selected' => $this->normalizeServiceTypeList($serviceType),
                        'service_payload' => $this->compactServiceData($serviceData),
                    ],
                    'subtotal_cents' => 0,
                ];
            }

            $groups[$key]['packages'][] = $this->packageRow($package);
            $groups[$key]['subtotal_cents'] += (int) ($package->single_price ?? 0);
        }

        ksort($groups);

        return array_values($groups);
    }

    private function fingerprint(array $payload): string
    {
        return hash(
            'sha256',
            json_encode($this->sortRecursive($payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }
}
