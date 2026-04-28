<?php

namespace App\Services;

use App\Models\Order;

class ShipmentExecutionService
{
    public function __construct(
        private readonly BrtService $brt,
        private readonly ShipmentDocumentDispatcher $documents,
    ) {}

    public function getExecutionPayload(Order $order): array
    {
        $pickupRequest = $this->resolvePickupRequestFromOrder($order);
        $pickupTimeSlot = trim((string) ($order->pickup_time_slot ?: ($pickupRequest['time_slot'] ?? '')));
        $pickupNotes = trim((string) ($order->pickup_notes ?: ($pickupRequest['notes'] ?? '')));

        return [
            'shipment_status' => ! empty($order->brt_parcel_id) ? 'completed' : 'pending',
            'pickup_status' => $order->pickup_status,
            'pickup_enabled' => (bool) ($pickupRequest['enabled'] ?? false),
            'pickup_date' => $pickupRequest['date'] ?? null,
            'pickup_requested_at' => $order->pickup_requested_at?->toIso8601String(),
            'carrier_pickup_ref' => $order->pickup_reference,
            'pickup_time_slot' => $pickupTimeSlot !== '' ? $pickupTimeSlot : null,
            'pickup_notes' => $pickupNotes !== '' ? $pickupNotes : null,
            'bordero_status' => $order->bordero_status,
            'carrier_bordero_ref' => $order->bordero_reference,
            'bordero_download_available' => ! empty($order->bordero_document_base64),
            'documents_status' => $order->documents_status,
            'documents_sent_customer_at' => $order->documents_sent_customer_at?->toIso8601String(),
            'documents_sent_admin_at' => $order->documents_sent_admin_at?->toIso8601String(),
            'last_error' => $order->execution_error,
        ];
    }

    public function requestPickup(Order $order, ?array $override = null): array
    {
        $pickupRequest = $this->normalizePickupRequest($override ?? $this->resolvePickupRequestFromOrder($order));
        $this->persistPickupRequestOnServices($order, $pickupRequest);

        $enabled = (bool) ($pickupRequest['enabled'] ?? false);
        if (! $enabled) {
            $order->pickup_status = 'not_requested';
            $order->pickup_reference = null;
            $order->pickup_requested_at = null;
            $order->pickup_time_slot = null;
            $order->pickup_notes = null;
            $order->save();

            return ['success' => true, 'status' => 'not_requested'];
        }

        $result = $this->brt->requestHomePickup($order, $pickupRequest);

        if (! ($result['success'] ?? false)) {
            $order->pickup_status = $result['status'] ?? 'failed';
            $order->execution_error = $this->appendExecutionError($order, $result['error'] ?? 'Errore richiesta ritiro');
            $order->save();

            return $result;
        }

        $order->pickup_status = $result['status'] ?? 'requested';
        $order->pickup_reference = $result['pickup_reference'] ?? null;
        $order->pickup_requested_at = now();
        $order->pickup_time_slot = $pickupRequest['time_slot'] ?? null;
        $order->pickup_notes = $pickupRequest['notes'] ?? null;
        $order->save();

        return $result;
    }

    public function reschedulePickup(Order $order, array $payload): array
    {
        $pickupDate = trim((string) ($payload['pickup_date'] ?? ''));
        $pickupTimeSlot = trim((string) ($payload['pickup_time_slot'] ?? ''));
        $pickupNotes = array_key_exists('pickup_notes', $payload)
            ? trim((string) ($payload['pickup_notes'] ?? ''))
            : null;

        $pickupRequest = [
            'enabled' => true,
            'date' => $pickupDate,
            'time_slot' => $pickupTimeSlot !== '' ? $pickupTimeSlot : ($order->pickup_time_slot ?: '09:00-18:00'),
            'notes' => $pickupNotes ?? ($order->pickup_notes ?? ''),
        ];

        $this->persistPickupRequestOnServices($order, $this->normalizePickupRequest($pickupRequest));

        $oldDate = $order->pickup_date;
        $order->pickup_date = $pickupDate;
        if ($pickupTimeSlot !== '') {
            $order->pickup_time_slot = $pickupTimeSlot;
        }
        if ($pickupNotes !== null) {
            $order->pickup_notes = $pickupNotes;
        }
        $order->save();

        $brtResult = null;
        if (filled($order->brt_parcel_id)) {
            $brtResult = $this->brt->requestHomePickup($order, $pickupRequest);

            if (($brtResult['success'] ?? false) === true) {
                $order->pickup_status = $brtResult['status'] ?? 'requested';
                $order->pickup_reference = $brtResult['pickup_reference'] ?? $order->pickup_reference;
                $order->pickup_requested_at = now();
                $order->save();
            }
        }

        return [
            'success' => true,
            'old_date' => $oldDate?->format('Y-m-d'),
            'pickup_date' => $pickupDate,
            'pickup_time_slot' => $pickupTimeSlot !== '' ? $pickupTimeSlot : ($order->pickup_time_slot ?: null),
            'pickup_notes' => $pickupNotes ?? $order->pickup_notes,
            'brt_result' => $brtResult,
            'brt_synced' => (bool) ($brtResult['success'] ?? false),
        ];
    }

    public function createBordero(Order $order): array
    {
        $result = $this->brt->createBordero($order);

        if (! ($result['success'] ?? false)) {
            $order->bordero_status = 'failed';
            $order->execution_error = $result['error'] ?? 'Errore creazione bordero';
            $order->save();

            return $result;
        }

        $order->bordero_status = 'completed';
        $order->bordero_reference = $result['bordero_reference'] ?? null;
        $order->bordero_document_base64 = $result['document_base64'] ?? null;
        $order->bordero_document_mime = $result['document_mime'] ?? 'text/plain';
        $order->bordero_document_filename = $result['document_filename'] ?? null;
        $order->save();

        return $result;
    }

    public function sendDocuments(Order $order): array
    {
        return $this->documents->dispatchForOrder($order);
    }

    public function runAutomaticPostLabelFlow(Order $order): void
    {
        $this->requestPickup($order);
        $bordero = $this->createBordero($order);
        if (! ($bordero['success'] ?? false)) {
            $order->documents_status = 'skipped';
            $order->execution_error = trim(($order->execution_error ? $order->execution_error.' | ' : '').'Documenti non inviati: borderò non disponibile.');
            $order->save();

            return;
        }

        $this->sendDocuments($order);
    }

    private function resolvePickupRequestFromOrder(Order $order): array
    {
        $order->loadMissing(['packages.service']);
        $service = $order->packages->first()?->service;
        $serviceData = $service?->service_data ?? [];

        $pickup = is_array($serviceData['pickup_request'] ?? null)
            ? $serviceData['pickup_request']
            : [];

        if (! isset($pickup['date']) && filled($service?->date)) {
            $pickup['date'] = $this->normalizePickupRequestDate($service->date);
        }

        if (! isset($pickup['time_slot']) && filled($service?->time)) {
            $pickup['time_slot'] = trim((string) $service->time);
        }

        // Se il pickup non ha 'enabled' esplicito, abilitalo se l'ordine ha il servizio "ritiro a domicilio"
        if (! isset($pickup['enabled'])) {
            if (! empty($pickup['date'])) {
                $pickup['enabled'] = true;
            }

            $hasPickupService = $order->packages->contains(fn ($pkg) =>
                $pkg->service && $this->isPickupServiceToken($pkg->service->service_type ?? '')
            );
            if ($hasPickupService) {
                $pickup['enabled'] = true;
            }
        }

        return $pickup;
    }

    private function isPickupServiceToken(string $serviceType): bool
    {
        $tokens = collect(preg_split('/[,;|]+/', mb_strtolower($serviceType, 'UTF-8')) ?: [])
            ->map(fn ($token) => preg_replace('/\s+/', ' ', trim($token)))
            ->filter();

        return $tokens->contains(fn ($token) => in_array($token, [
            'ritiro',
            'ritiro a domicilio',
            'ritiro_a_domicilio',
            'pickup',
            'pickup a domicilio',
            'pickup_a_domicilio',
            'home pickup',
            'home_pickup',
        ], true));
    }

    private function persistPickupRequestOnServices(Order $order, array $pickupRequest): void
    {
        $order->loadMissing(['packages.service']);
        $shouldClearPickup = ! ((bool) ($pickupRequest['enabled'] ?? false));

        $services = $order->packages
            ->pluck('service')
            ->filter()
            ->unique('id');

        foreach ($services as $service) {
            $serviceData = is_array($service->service_data) ? $service->service_data : [];
            $serviceData['pickup_request'] = $pickupRequest;

            $service->update([
                'date' => $shouldClearPickup ? '' : ($pickupRequest['date'] ?: ($service->date ?? '')),
                'time' => $shouldClearPickup ? '' : ($pickupRequest['time_slot'] ?: ($service->time ?? '09:00-18:00')),
                'service_data' => $serviceData,
            ]);
        }
    }

    private function normalizePickupRequest(array $pickupRequest): array
    {
        $resolvedDate = $this->normalizePickupRequestDate((string) ($pickupRequest['date'] ?? ''));
        $resolvedTimeSlot = trim((string) ($pickupRequest['time_slot'] ?? ''));
        $resolvedNotes = trim((string) ($pickupRequest['notes'] ?? ''));
        $enabled = (bool) ($pickupRequest['enabled'] ?? ($resolvedDate !== ''));

        return [
            'enabled' => $enabled,
            'date' => $enabled ? $resolvedDate : '',
            'time_slot' => $enabled ? ($resolvedTimeSlot !== '' ? $resolvedTimeSlot : '09:00-18:00') : '',
            'notes' => $enabled ? $resolvedNotes : '',
        ];
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

    private function appendExecutionError(Order $order, string $message): string
    {
        $current = trim((string) $order->execution_error);
        if ($current === '') {
            return $message;
        }

        if (str_contains($current, $message)) {
            return $current;
        }

        return $current.' | '.$message;
    }
}
