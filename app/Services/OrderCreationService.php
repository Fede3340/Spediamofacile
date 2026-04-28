<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Package;
use Illuminate\Support\Facades\DB;

class OrderCreationService
{
    public function countPackageGroups($packages): int
    {
        return count($this->groupPackagesByAddress($packages));
    }

    public function createOrdersFromPackages($packages, int $userId, ?array $billingData = null, array $submissionContext = []): array
    {
        $groups = $this->groupPackagesByAddress($packages);
        $baseSubmissionId = trim((string) ($submissionContext['client_submission_id'] ?? ''));

        return DB::transaction(function () use ($groups, $userId, $billingData, $submissionContext, $baseSubmissionId) {
            $servicePricing = app(ShipmentServicePricingService::class);
            $orders = [];

            foreach ($groups as $index => $group) {
                $groupPackages = $group['packages'];
                $groupService = $groupPackages->first()?->service;
                $serviceType = $groupService->service_type ?? '';
                $serviceData = $groupService->service_data ?? [];
                $smsEmailNotification = (bool) ($serviceData['sms_email_notification'] ?? false);

                $subtotal = $groupPackages->sum(fn ($pkg) => (int) $pkg->single_price);
                $subtotal += $servicePricing->calculateSurchargeCents($serviceType, $serviceData, $smsEmailNotification, [
                    'packages' => $groupPackages->all(),
                    'origin_address' => $groupPackages->first()?->originAddress?->toArray() ?? [],
                    'destination_address' => (($serviceData['delivery_mode'] ?? 'home') === 'pudo' && !empty($serviceData['pudo']))
                        ? $serviceData['pudo']
                        : ($groupPackages->first()?->destinationAddress?->toArray() ?? []),
                    'delivery_mode' => $serviceData['delivery_mode'] ?? 'home',
                    'selected_pudo' => $serviceData['pudo'] ?? null,
                    'requires_manual_quote' => (bool) ($serviceData['requires_manual_quote'] ?? false),
                ]);

                $selectedServices = $servicePricing->normalizeSelectedServices($serviceType);
                $isCod = in_array('contrassegno', $selectedServices, true);
                $codAmount = $isCod ? $servicePricing->extractContrassegnoAmount($serviceData) : null;
                // Audit F01: persiste modalita' rimborso BRT (BM|CC|AS) e modalita' incasso (contanti|assegno)
                $codPaymentType = $isCod ? $servicePricing->extractCodPaymentType($serviceData) : null;
                $codIncassoType = $isCod ? $servicePricing->extractCodIncassoType($serviceData) : null;
                // Audit F02: persiste valore dichiarato assicurazione in centesimi
                $hasInsurance = in_array('assicurazione', $selectedServices, true);
                $insuranceAmountCents = $hasInsurance
                    ? (int) round($servicePricing->extractAssicurazioneAmount($serviceData) * 100)
                    : null;

                $pudoId = null;
                foreach ($groupPackages as $pkg) {
                    $sd = $pkg->service->service_data ?? [];
                    if (!empty($sd['pudo']['pudo_id']) && ($sd['delivery_mode'] ?? '') === 'pudo') {
                        $pudoId = $sd['pudo']['pudo_id'];
                        break;
                    }
                }

                $order = Order::create([
                    'user_id' => $userId,
                    'subtotal' => $subtotal,
                    'status' => Order::PENDING,
                    'is_cod' => $isCod,
                    'cod_amount' => $codAmount > 0 ? (int) round($codAmount * 100) : null,
                    'cod_payment_type' => $codPaymentType,
                    'cod_incasso_type' => $codIncassoType,
                    'insurance_amount_cents' => ($insuranceAmountCents && $insuranceAmountCents > 0) ? $insuranceAmountCents : null,
                    'brt_pudo_id' => $pudoId,
                    'billing_data' => $billingData,
                    'client_submission_id' => $this->resolveGroupSubmissionId($baseSubmissionId, $group['key'], $index),
                    'pricing_signature' => $submissionContext['pricing_signature'] ?? null,
                    'pricing_snapshot_version' => $submissionContext['pricing_snapshot_version'] ?? null,
                    'pricing_snapshot' => $submissionContext['pricing_snapshot'] ?? null,
                ]);

                foreach ($groupPackages as $package) {
                    Order::attachPackage($order->id, $package->id, $package->quantity ?? 1);
                }

                $orders[] = $order;
            }

            return $orders;
        });
    }

    private function resolveGroupSubmissionId(string $baseSubmissionId, string $groupKey, int $index): ?string
    {
        if ($baseSubmissionId === '') {
            return null;
        }

        if ($index === 0) {
            return $baseSubmissionId;
        }

        return $baseSubmissionId.'|'.$groupKey;
    }

    private function groupPackagesByAddress($packages): array
    {
        $servicePricing = app(ShipmentServicePricingService::class);
        $groups = [];
        $normalize = fn ($v) => mb_strtolower(trim($v ?? ''), 'UTF-8');

        foreach ($packages as $package) {
            $serviceType = $package->service->service_type ?? 'Nessuno';
            $serviceData = $package->service->service_data ?? [];
            $serviceSignature = $servicePricing->buildSelectionSignature($serviceType, $serviceData, (bool) ($serviceData['sms_email_notification'] ?? false));

            $origin = $package->originAddress;
            $destination = $package->destinationAddress;

            $originParts = $origin ? implode('|', [$normalize($origin->name), $normalize($origin->address), $normalize($origin->address_number), $normalize($origin->city), $normalize($origin->postal_code), $normalize($origin->province)]) : 'no-origin';
            $destParts = $destination ? implode('|', [$normalize($destination->name), $normalize($destination->address), $normalize($destination->address_number), $normalize($destination->city), $normalize($destination->postal_code), $normalize($destination->province)]) : 'no-dest';

            $key = md5($originParts . '::' . $destParts . '::' . $normalize($serviceType) . '::' . $serviceSignature);

            if (!isset($groups[$key])) {
                $groups[$key] = ['key' => $key, 'packages' => collect()];
            }
            $groups[$key]['packages']->push($package);
        }

        ksort($groups);

        return array_values($groups);
    }
}
