<?php

namespace App\Services;

use App\Models\Coupon;
use App\Services\Checkout\SnapshotCompactingHelpers;

class CheckoutSubmissionContextService
{
    use SnapshotCompactingHelpers;

    public function fromRequestArray(array $input): array
    {
        $context = [];

        foreach (['client_submission_id'] as $field) {
            $value = trim((string) ($input[$field] ?? ''));
            if ($value !== '') {
                $context[$field] = $value;
            }
        }

        $discountContext = $this->normalizeDiscountContext($input['discount_context'] ?? null);
        if ($discountContext !== null) {
            $context['discount_context'] = $discountContext;
        }

        return $context;
    }

    public function enrich(array $context, array $snapshot, array $seed = []): array
    {
        $normalizedSnapshot = $this->sortRecursive($snapshot);
        $discountContext = $this->canonicalDiscountContextForSnapshot(
            $this->normalizeDiscountContext($context['discount_context'] ?? null),
            $normalizedSnapshot,
            $seed,
        );
        $storageSnapshot = $this->attachDiscountContextToSnapshot($normalizedSnapshot, $discountContext);
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
        $collection = $packages instanceof \Illuminate\Database\Eloquent\Collection
            ? $packages
            : \Illuminate\Database\Eloquent\Collection::make($packages);
        $firstPackage = $collection->first();
        $service = $firstPackage?->service;
        $serviceData = is_array($service?->service_data) ? $service->service_data : [];
        $deliveryMode = (string) ($serviceData['delivery_mode'] ?? 'home');
        $selectedPudo = $deliveryMode === 'pudo' ? ($serviceData['pudo'] ?? null) : null;
        $groupSnapshots = $this->buildPackageGroupSnapshotsFromModels($collection);

        return $this->baseSnapshot(
            $collection->map(function ($package) {
                return [
                    'package_type' => (string) ($package->package_type ?? ''),
                    'quantity' => (int) ($package->quantity ?? 1),
                    'weight' => (string) ($package->weight ?? ''),
                    'first_size' => (string) ($package->first_size ?? ''),
                    'second_size' => (string) ($package->second_size ?? ''),
                    'third_size' => (string) ($package->third_size ?? ''),
                    'single_price_cents' => (int) ($package->single_price ?? 0),
                ];
            })->all(),
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
            (int) app(CartService::class)->subtotalFromModels($collection)->amount(),
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

        return $this->baseSnapshot(
            collect($data['packages'] ?? [])->map(function (array $package) {
                return [
                    'package_type' => (string) ($package['package_type'] ?? ''),
                    'quantity' => (int) ($package['quantity'] ?? 1),
                    'weight' => (string) ($package['weight'] ?? ''),
                    'first_size' => (string) ($package['first_size'] ?? ''),
                    'second_size' => (string) ($package['second_size'] ?? ''),
                    'third_size' => (string) ($package['third_size'] ?? ''),
                    'single_price_cents' => (int) ($package['single_price_cents'] ?? 0),
                ];
            })->all(),
            [
                'origin' => $this->compactAddress($data['origin_address'] ?? []),
                'destination' => $this->compactAddress(
                    $deliveryMode === 'pudo' && is_array($selectedPudo)
                        ? $selectedPudo
                        : ($data['destination_address'] ?? [])
                ),
            ],
            [
                'service_type' => (string) ($services['service_type'] ?? 'Nessuno'),
                'selected' => $this->normalizeServiceTypeList((string) ($services['service_type'] ?? 'Nessuno')),
                'service_payload' => $this->compactServiceData(is_array($serviceData) ? $serviceData : []),
            ],
            $subtotalCents,
            $data['billing_data'] ?? null,
            [[
                'group_key' => 'direct-order',
                'packages' => collect($data['packages'] ?? [])->map(function (array $package) {
                    return [
                        'package_type' => (string) ($package['package_type'] ?? ''),
                        'quantity' => (int) ($package['quantity'] ?? 1),
                        'weight' => (string) ($package['weight'] ?? ''),
                        'first_size' => (string) ($package['first_size'] ?? ''),
                        'second_size' => (string) ($package['second_size'] ?? ''),
                        'third_size' => (string) ($package['third_size'] ?? ''),
                        'single_price_cents' => (int) ($package['single_price_cents'] ?? 0),
                    ];
                })->all(),
                'route' => [
                    'origin' => $this->compactAddress($data['origin_address'] ?? []),
                    'destination' => $this->compactAddress(
                        $deliveryMode === 'pudo' && is_array($selectedPudo)
                            ? $selectedPudo
                            : ($data['destination_address'] ?? [])
                    ),
                ],
                'services' => [
                    'service_type' => (string) ($services['service_type'] ?? 'Nessuno'),
                    'selected' => $this->normalizeServiceTypeList((string) ($services['service_type'] ?? 'Nessuno')),
                    'service_payload' => $this->compactServiceData(is_array($serviceData) ? $serviceData : []),
                ],
                'subtotal_cents' => $subtotalCents,
            ]],
        );
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

        return $this->sortRecursive([
            'total_cents' => $subtotalCents,
            'package_count' => collect($packages)->sum(fn (array $package) => (int) ($package['quantity'] ?? 1)),
            'packages' => $packages,
            'route' => $route,
            'services' => [
                'service_type' => (string) ($services['service_type'] ?? 'Nessuno'),
                'selected' => $this->normalizeServiceTypeList((string) ($services['service_type'] ?? 'Nessuno')),
                'service_payload' => $this->sortRecursive($servicePayload),
            ],
            'group_count' => max(1, count($groups)),
            'groups' => $groups !== [] ? $groups : [[
                'group_key' => 'single-group',
                'packages' => $packages,
                'route' => $route,
                'services' => [
                    'service_type' => (string) ($services['service_type'] ?? 'Nessuno'),
                    'selected' => $this->normalizeServiceTypeList((string) ($services['service_type'] ?? 'Nessuno')),
                    'service_payload' => $this->sortRecursive($servicePayload),
                ],
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

            $groups[$key]['packages'][] = [
                'package_type' => (string) ($package->package_type ?? ''),
                'quantity' => (int) ($package->quantity ?? 1),
                'weight' => (string) ($package->weight ?? ''),
                'first_size' => (string) ($package->first_size ?? ''),
                'second_size' => (string) ($package->second_size ?? ''),
                'third_size' => (string) ($package->third_size ?? ''),
                'single_price_cents' => (int) ($package->single_price ?? 0),
            ];
            $groups[$key]['subtotal_cents'] += (int) ($package->single_price ?? 0);
        }

        ksort($groups);

        return array_values($groups);
    }

    private function normalizeDiscountContext(mixed $value): ?array
    {
        if (! is_array($value)) {
            return null;
        }

        $type = mb_strtolower(trim((string) ($value['type'] ?? '')), 'UTF-8');
        $code = mb_strtoupper(trim((string) ($value['code'] ?? '')), 'UTF-8');

        if ($type === '' || $code === '') {
            return null;
        }

        $discountContext = [
            'type' => $type,
            'code' => $code,
            'discount_percent' => $this->normalizeDecimalAmount($value['discount_percent'] ?? null),
            'discount_amount' => $this->normalizeDecimalAmount($value['discount_amount'] ?? null),
            'subtotal_raw' => $this->normalizeDecimalAmount($value['subtotal_raw'] ?? null),
            'final_total_raw' => $this->normalizeDecimalAmount($value['final_total_raw'] ?? null),
        ];

        $proName = trim((string) ($value['pro_name'] ?? ''));
        if ($proName !== '') {
            $discountContext['pro_name'] = $proName;
        }

        return $this->sortRecursive($discountContext);
    }

    private function canonicalDiscountContextForSnapshot(?array $discountContext, array $snapshot, array $seed = []): ?array
    {
        if ($discountContext === null) {
            return null;
        }

        $subtotalCents = (int) ($snapshot['total_cents'] ?? 0);
        if ($subtotalCents <= 0) {
            return null;
        }

        $subtotalRaw = round($subtotalCents / 100, 2);
        $type = (string) ($discountContext['type'] ?? '');
        $code = (string) ($discountContext['code'] ?? '');

        if ($type === 'coupon') {
            $coupon = Coupon::query()
                ->usable()
                ->where('code', $code)
                ->first();

            if (! $coupon) {
                return null;
            }

            [$valid] = $coupon->validateForUser(isset($seed['user_id']) ? (int) $seed['user_id'] : null);
            if (! $valid) {
                return null;
            }

            return $this->buildCanonicalDiscountContext(
                type: 'coupon',
                code: $coupon->code,
                percentage: (float) $coupon->percentage,
                subtotalRaw: $subtotalRaw,
            );
        }

        if ($type === 'referral') {
            /** @var ReferralAccountingService $referralAccounting */
            $referralAccounting = app(ReferralAccountingService::class);
            $proUser = $referralAccounting->resolveReferralPartner($code);

            if (! $proUser || (isset($seed['user_id']) && (int) $seed['user_id'] === (int) $proUser->id)) {
                return null;
            }

            $breakdown = $referralAccounting->buildReferralBreakdown($subtotalRaw);

            return $this->sortRecursive(array_merge(
                $this->buildCanonicalDiscountContext(
                    type: 'referral',
                    code: $code,
                    percentage: (float) $breakdown['percentage'],
                    subtotalRaw: $subtotalRaw,
                ),
                [
                    'pro_name' => (string) $proUser->name,
                ],
            ));
        }

        return null;
    }

    private function buildCanonicalDiscountContext(string $type, string $code, float $percentage, float $subtotalRaw): array
    {
        $discountAmount = round($subtotalRaw * ($percentage / 100), 2);
        $finalTotal = max(0, round($subtotalRaw - $discountAmount, 2));

        return $this->sortRecursive([
            'type' => $type,
            'code' => mb_strtoupper(trim($code), 'UTF-8'),
            'discount_percent' => round($percentage, 2),
            'discount_amount' => $discountAmount,
            'subtotal_raw' => $subtotalRaw,
            'final_total_raw' => $finalTotal,
        ]);
    }

    private function attachDiscountContextToSnapshot(array $snapshot, ?array $discountContext): array
    {
        if ($discountContext === null) {
            return $snapshot;
        }

        $snapshot['discount_context'] = $discountContext;

        return $this->sortRecursive($snapshot);
    }

    private function fingerprint(array $payload): string
    {
        return hash(
            'sha256',
            json_encode($this->sortRecursive($payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    // compactX, normalizeX, hasMeaningful, sortRecursive, normalizeDecimalAmount
    // sono in trait App\Services\Checkout\SnapshotCompactingHelpers (use sopra).
}
