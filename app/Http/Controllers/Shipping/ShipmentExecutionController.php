<?php

namespace App\Http\Controllers\Shipping;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReschedulePickupRequest;
use App\Models\Order;
use App\Services\ShipmentExecutionService;
use App\Services\Shipping\ShipmentExecutionService as ExecutionHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class ShipmentExecutionController extends Controller
{
    public function __construct(
        private readonly ShipmentExecutionService $execution,
        private readonly ExecutionHelper $helper,
    ) {}

    public function show(Order $order): JsonResponse
    {
        Gate::authorize('manageShipment', $order);

        return response()->json(['data' => $this->execution->getExecutionPayload($order)]);
    }

    public function requestPickup(Request $request, Order $order): JsonResponse
    {
        Gate::authorize('manageShipment', $order);

        $payload = $request->validate([
            'pickup_request' => 'nullable|array',
            'pickup_request.enabled' => 'nullable|boolean',
            'pickup_request.date' => ['nullable', 'string', 'max:20', $this->helper->pickupDateValidator($request)],
            'pickup_request.time_slot' => ['nullable', 'string', 'max:50', $this->helper->pickupTimeSlotValidator($request)],
            'pickup_request.notes' => 'nullable|string|max:255',
        ]);

        $result = $this->execution->requestPickup($order, $payload['pickup_request'] ?? null);
        $message = $result['error']
            ?? (($result['status'] ?? null) === 'not_requested' ? 'Ritiro segnato come non richiesto.' : 'Richiesta ritiro elaborata.');

        return $this->payloadResponse($order, $result, ['message' => $message]);
    }

    /**
     * Aggiorna la data di ritiro programmata (F04 — audit BRT 2026-04-18).
     * Range: +1..+10 giorni lavorativi. BRT non ha endpoint reschedule:
     * ri-apriamo la richiesta con nuova data; se BRT fallisce salviamo
     * comunque e logghiamo per follow-up admin.
     */
    public function reschedulePickup(ReschedulePickupRequest $request, Order $order): JsonResponse
    {
        Gate::authorize('manageShipment', $order);

        if ($error = $this->helper->guardRescheduleStatus($order)) {
            return $this->fail($error);
        }

        $validated = $request->validated();
        $newDate = $this->helper->normalizePickupDateInput($validated['pickup_date'] ?? null);
        if (! $newDate) {
            return $this->fail('La data ritiro non è valida.');
        }

        [$minDate, $maxDate] = $this->helper->allowedReschedulingRange();
        if ($newDate->lt($minDate) || $newDate->gt($maxDate)) {
            return $this->fail("La data deve essere compresa tra {$minDate->format('d/m/Y')} e {$maxDate->format('d/m/Y')} (giorni lavorativi).");
        }

        $timeSlot = $validated['pickup_time_slot'] ?? null;
        if ($timeSlot !== null && $timeSlot !== '' && ! in_array($timeSlot, ExecutionHelper::PICKUP_TIME_SLOTS, true)) {
            return $this->fail('Fascia oraria non valida.');
        }

        $result = $this->execution->reschedulePickup($order, [
            'pickup_date' => $newDate->toDateString(),
            'pickup_time_slot' => $timeSlot,
            'pickup_notes' => $validated['pickup_notes'] ?? null,
        ]);
        $brtResult = is_array($result['brt_result'] ?? null) ? $result['brt_result'] : null;

        $this->helper->notifyPickupRescheduled($order, $newDate, $timeSlot);
        Log::info('Pickup rescheduled', [
            'order_id' => $order->id,
            'old_date' => $result['old_date'] ?? null,
            'new_date' => $newDate->format('Y-m-d'),
            'time_slot' => $timeSlot,
            'brt_success' => $brtResult['success'] ?? null,
        ]);

        $brtPending = $brtResult && ! ($brtResult['success'] ?? false);

        return response()->json([
            'success' => true,
            'data' => $this->execution->getExecutionPayload($order->fresh()),
            'message' => 'Data di ritiro aggiornata.'.($brtPending ? ' Sincronizzazione con il corriere in corso, un operatore prenderà in carico la richiesta.' : ''),
        ]);
    }

    public function createBordero(Order $order): JsonResponse
    {
        Gate::authorize('manageShipment', $order);

        $result = $this->execution->createBordero($order);

        return $this->payloadResponse($order, $result, [
            'bordero_reference' => $result['bordero_reference'] ?? null,
            'message' => $result['error'] ?? 'Bordero generato.',
        ]);
    }

    public function downloadBordero(Request $request, Order $order)
    {
        Gate::authorize('manageShipment', $order);

        if (empty($order->bordero_document_base64)) {
            return response()->json(['message' => 'Bordero non disponibile.'], 404);
        }
        $contents = base64_decode((string) $order->bordero_document_base64, true);
        if ($contents === false || $contents === '') {
            return response()->json(['message' => 'Bordero non disponibile.'], 404);
        }

        $mime = $order->bordero_document_mime ?: 'application/pdf';
        $filename = $order->bordero_document_filename ?: sprintf('bordero-%s.%s', $order->id, $mime === 'application/pdf' ? 'pdf' : 'txt');
        $disposition = $request->boolean('inline') ? 'inline' : 'attachment';

        return response($contents, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
            'Content-Length' => (string) strlen($contents),
        ]);
    }

    public function sendDocuments(Order $order): JsonResponse
    {
        Gate::authorize('manageShipment', $order);

        $result = $this->execution->sendDocuments($order);

        return $this->payloadResponse($order, $result, ['dispatch' => $result]);
    }

    private function fail(string $message): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], 422);
    }

    private function payloadResponse(Order $order, array $result, array $extra = []): JsonResponse
    {
        $success = (bool) ($result['success'] ?? false);

        return response()->json(array_merge([
            'success' => $success,
            'data' => $this->execution->getExecutionPayload($order->fresh()),
        ], $extra), $success ? 200 : 422);
    }
}
