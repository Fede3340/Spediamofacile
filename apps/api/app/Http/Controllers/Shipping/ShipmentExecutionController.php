<?php

namespace App\Http\Controllers\Shipping;

use App\Http\Controllers\Controller;

use App\Models\Order;
use App\Services\ShipmentExecutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ShipmentExecutionController extends Controller
{
    public function __construct(
        private readonly ShipmentExecutionService $execution,
    ) {}

    public function show(Order $order): JsonResponse
    {
        Gate::authorize('manageShipment', $order);

        return response()->json([
            'data' => $this->execution->getExecutionPayload($order),
        ]);
    }

    public function requestPickup(Request $request, Order $order): JsonResponse
    {
        Gate::authorize('manageShipment', $order);

        $pickupTimeSlots = ['09:00-12:00', '09:00-18:00', '14:00-18:00'];
        $payload = $request->validate([
            'pickup_request' => 'nullable|array',
            'pickup_request.enabled' => 'nullable|boolean',
            'pickup_request.date' => [
                'nullable',
                'string',
                'max:20',
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    $enabled = (bool) data_get($request->input('pickup_request', []), 'enabled', false);
                    $normalizedDate = $this->normalizePickupDateInput($value);

                    if ($normalizedDate === null) {
                        if ($enabled) {
                            $fail('La data ritiro non è valida.');
                        }

                        return;
                    }

                    if ($normalizedDate->isBefore(now()->startOfDay())) {
                        $fail('La data ritiro deve essere oggi o futura.');
                    }
                },
            ],
            'pickup_request.time_slot' => [
                'nullable',
                'string',
                'max:50',
                function (string $attribute, mixed $value, \Closure $fail) use ($request, $pickupTimeSlots): void {
                    $enabled = (bool) data_get($request->input('pickup_request', []), 'enabled', false);
                    $timeSlot = trim((string) ($value ?? ''));

                    if ($timeSlot === '') {
                        if ($enabled) {
                            $fail('Seleziona una fascia oraria valida.');
                        }

                        return;
                    }

                    if (! in_array($timeSlot, $pickupTimeSlots, true)) {
                        $fail('La fascia oraria selezionata non è valida.');
                    }
                },
            ],
            'pickup_request.notes' => 'nullable|string|max:255',
        ]);

        $result = $this->execution->requestPickup($order, $payload['pickup_request'] ?? null);
        $message = $result['error']
            ?? (($result['status'] ?? null) === 'not_requested'
                ? 'Ritiro segnato come non richiesto.'
                : 'Richiesta ritiro elaborata.');

        return response()->json([
            'success' => (bool) ($result['success'] ?? false),
            'data' => $this->execution->getExecutionPayload($order->fresh()),
            'message' => $message,
        ], ($result['success'] ?? false) ? 200 : 422);
    }

    /**
     * Aggiorna la data di ritiro programmata (F04 — audit BRT 2026-04-18).
     *
     * Consentito solo se l'ordine NON è ancora stato ritirato/in transito/consegnato.
     * Range date: da +1 giorno lavorativo a +10 giorni lavorativi dalla data odierna.
     * Chiama BRT PickupService::requestPickup per ripianificare (BRT non ha endpoint
     * "reschedule" dedicato: ri-aprire la richiesta con nuova data è la strategia
     * consigliata). Se BRT fallisce, salviamo comunque la nuova data e logghiamo
     * per follow-up admin.
     */
    public function reschedulePickup(\App\Http\Requests\ReschedulePickupRequest $request, Order $order): JsonResponse
    {
        Gate::authorize('manageShipment', $order);

        $blockedStatuses = [
            Order::IN_TRANSIT,
            Order::OUT_FOR_DELIVERY,
            Order::DELIVERED,
            Order::IN_GIACENZA,
            Order::RETURNED,
            Order::REFUSED,
            Order::CANCELLED,
            Order::REFUNDED,
        ];

        $rawStatus = $order->getRawOriginal('status');
        if (in_array($rawStatus, $blockedStatuses, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Non è possibile modificare la data di ritiro: spedizione già in corso o conclusa.',
            ], 422);
        }

        // Anche se il ritiro è stato già segnato come "completato" (pickup_status=done)
        // non permettiamo il cambio.
        if (($order->pickup_status ?? '') === 'done') {
            return response()->json([
                'success' => false,
                'message' => 'Il ritiro risulta già completato.',
            ], 422);
        }

        $validated = $request->validated();

        $newDate = $this->normalizePickupDateInput($validated['pickup_date']);
        if (! $newDate) {
            return response()->json([
                'success' => false,
                'message' => 'La data ritiro non è valida.',
            ], 422);
        }

        [$minDate, $maxDate] = $this->allowedReschedulingRange();
        if ($newDate->lt($minDate) || $newDate->gt($maxDate)) {
            return response()->json([
                'success' => false,
                'message' => "La data deve essere compresa tra {$minDate->format('d/m/Y')} e {$maxDate->format('d/m/Y')} (giorni lavorativi).",
            ], 422);
        }

        $pickupTimeSlots = ['09:00-12:00', '09:00-18:00', '14:00-18:00'];
        $timeSlot = $validated['pickup_time_slot'] ?? null;
        if ($timeSlot !== null && $timeSlot !== '' && ! in_array($timeSlot, $pickupTimeSlots, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Fascia oraria non valida.',
            ], 422);
        }

        $result = $this->execution->reschedulePickup($order, [
            'pickup_date' => $newDate->toDateString(),
            'pickup_time_slot' => $timeSlot,
            'pickup_notes' => $validated['pickup_notes'] ?? null,
        ]);
        $brtResult = is_array($result['brt_result'] ?? null) ? $result['brt_result'] : null;

        // Mail conferma al cliente (best-effort).
        try {
            $user = $order->user;
            if ($user?->email) {
                Mail::raw(
                    "Ciao {$user->name},\n\nLa data di ritiro dell'ordine #{$order->id} è stata aggiornata a {$newDate->format('d/m/Y')}"
                    .($timeSlot ? " (fascia {$timeSlot})" : '')
                    .".\n\nSe non hai richiesto questa modifica contattaci subito.\n\nSpediamoFacile",
                    function ($message) use ($user, $order) {
                        $message->to($user->email)
                            ->subject('Data di ritiro aggiornata - Ordine #'.$order->id);
                    }
                );
            }
        } catch (\Throwable $e) {
            Log::warning('reschedulePickup mail failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('Pickup rescheduled', [
            'order_id' => $order->id,
            'old_date' => $result['old_date'] ?? null,
            'new_date' => $newDate->format('Y-m-d'),
            'time_slot' => $timeSlot,
            'brt_success' => $brtResult['success'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->execution->getExecutionPayload($order->fresh()),
            'message' => 'Data di ritiro aggiornata.'
                .(($brtResult && ! ($brtResult['success'] ?? false))
                    ? ' Sincronizzazione con il corriere in corso, un operatore prenderà in carico la richiesta.'
                    : ''),
        ]);
    }

    /**
     * Calcola range minimo/massimo consentito per la ripianificazione
     * (+1 giorno lavorativo fino a +10 giorni lavorativi).
     */
    private function allowedReschedulingRange(): array
    {
        $min = now()->startOfDay()->addWeekday();
        $max = $min->copy();
        for ($i = 1; $i < 10; $i++) {
            $max->addWeekday();
        }

        return [$min, $max];
    }

    public function createBordero(Order $order): JsonResponse
    {
        Gate::authorize('manageShipment', $order);

        $result = $this->execution->createBordero($order);

        return response()->json([
            'success' => (bool) ($result['success'] ?? false),
            'data' => $this->execution->getExecutionPayload($order->fresh()),
            'bordero_reference' => $result['bordero_reference'] ?? null,
            'message' => $result['error'] ?? 'Bordero generato.',
        ], ($result['success'] ?? false) ? 200 : 422);
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

        return response()->json([
            'success' => (bool) ($result['success'] ?? false),
            'data' => $this->execution->getExecutionPayload($order->fresh()),
            'dispatch' => $result,
        ], ($result['success'] ?? false) ? 200 : 422);
    }

    private function normalizePickupDateInput(mixed $value): ?Carbon
    {
        $input = trim((string) ($value ?? ''));
        if ($input === '') {
            return null;
        }

        try {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $input)) {
                return Carbon::createFromFormat('Y-m-d', $input)->startOfDay();
            }

            if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $input)) {
                return Carbon::createFromFormat('d/m/Y', $input)->startOfDay();
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }
}
