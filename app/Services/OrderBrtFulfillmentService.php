<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

class OrderBrtFulfillmentService
{
    /**
     * Build the carrier options that are derived from the persisted order.
     * This is the canonical place for automatic/manual regeneration flows.
     */
    public function buildAutomaticShipmentOptions(Order $order): array
    {
        $order->loadMissing(['packages.service']);

        $options = [];

        if ($order->is_cod && $order->cod_amount) {
            $options['is_cod'] = true;
            $options['cod_amount'] = $order->cod_amount;
            $options['cod_payment_type'] = $order->cod_payment_type ?? 'BM';
        }

        if ($order->brt_pudo_id) {
            $options['pudo_id'] = $order->brt_pudo_id;
        }

        if ($order->insurance_amount_cents && (int) $order->insurance_amount_cents > 0) {
            $options['insurance_amount'] = (int) $order->insurance_amount_cents;
        }

        $firstPackageServiceData = optional(optional($order->packages->first())->service)->service_data ?? [];
        if (
            is_array($firstPackageServiceData)
            && ! empty($firstPackageServiceData['flags'])
            && is_array($firstPackageServiceData['flags'])
            && in_array('consegna_appuntamento', $firstPackageServiceData['flags'], true)
        ) {
            $options['delivery_appointment'] = true;
        }

        return $options;
    }

    /**
     * Persist the BRT shipment result on the canonical order model.
     *
     * @param  array<string, mixed>  $result
     * @param  array<string, mixed>  $overrides
     */
    public function applyShipmentResult(Order $order, array $result, array $overrides = []): Order
    {
        $order->refresh();

        $allLabels = $result['all_labels'] ?? [];

        $attributes = [
            'brt_parcel_id' => $result['parcel_id'] ?? null,
            'brt_numeric_sender_reference' => $result['numeric_sender_reference'] ?? null,
            'brt_tracking_url' => $result['tracking_url'] ?? null,
            'brt_all_labels' => ! empty($allLabels) ? $allLabels : null,
            'brt_tracking_number' => $result['tracking_number'] ?? null,
            'brt_parcel_number_to' => $result['parcel_number_to'] ?? null,
            'brt_departure_depot' => $result['departure_depot'] ?? null,
            'brt_arrival_terminal' => $result['arrival_terminal'] ?? null,
            'brt_arrival_depot' => $result['arrival_depot'] ?? null,
            'brt_delivery_zone' => $result['delivery_zone'] ?? null,
            'brt_series_number' => $result['series_number'] ?? null,
            'brt_service_type' => $result['service_type'] ?? null,
            'brt_raw_response' => $result['raw_response'] ?? null,
            'status' => Order::LABEL_GENERATED,
            'brt_error' => null,
        ];

        foreach (['brt_pudo_id', 'is_cod', 'cod_amount', 'cod_payment_type'] as $field) {
            if (array_key_exists($field, $overrides)) {
                $attributes[$field] = $overrides[$field];
            }
        }

        $order->fill($attributes);
        $order->brt_label_base64 = $result['label_base64'] ?? null;
        $order->save();

        return $order->fresh();
    }

    /**
     * Canonical success path after a BRT label has been generated.
     *
     * Applies the carrier result to the order and then runs the post-label
     * flow (pickup, bordero, document dispatch) with the caller-specific
     * failure messages.
     *
     * @param  array<string, mixed>  $result
     * @param  array<string, mixed>  $overrides
     */
    public function finalizeSuccessfulShipment(
        Order $order,
        array $result,
        array $overrides = [],
        string $failurePrefix = 'Post-elaborazione documenti fallita',
        string $logMessage = 'Failed to complete shipment documents flow after label generation',
    ): Order {
        $order = $this->applyShipmentResult($order, $result, $overrides);

        return $this->runPostLabelFlow($order, $failurePrefix, $logMessage);
    }

    public function runPostLabelFlow(
        Order $order,
        string $failurePrefix = 'Post-elaborazione documenti fallita',
        string $logMessage = 'Failed to complete shipment documents flow after label generation',
    ): Order {
        try {
            app(ShipmentExecutionService::class)->runAutomaticPostLabelFlow($order->fresh());
        } catch (\Throwable $e) {
            $order->refresh();
            $order->documents_status = 'failed';
            $order->execution_error = $this->appendExecutionError($order, $failurePrefix.': '.$e->getMessage());
            $order->save();

            Log::error($logMessage, [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $order->fresh();
    }

    public function markSkippedBecauseBrtNotConfigured(Order $order): void
    {
        $order->bordero_status = 'skipped';
        $order->documents_status = 'skipped';
        $order->execution_error = $this->appendExecutionError($order, 'BRT non configurato: etichetta e documenti non generati.');
        $order->save();
    }

    public function markGenerationFailed(Order $order, string $message): void
    {
        $order->refresh();
        $order->brt_error = $message;
        $order->bordero_status = $order->bordero_status ?: 'skipped';
        $order->documents_status = $order->documents_status ?: 'skipped';
        $order->execution_error = $this->appendExecutionError($order, 'Etichetta BRT non generata: '.$message);
        $order->save();
    }

    private function appendExecutionError(Order $order, string $message): string
    {
        return trim(($order->execution_error ? $order->execution_error.' | ' : '').$message);
    }
}
