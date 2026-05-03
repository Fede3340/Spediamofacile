<?php

namespace App\Services\Shipping;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Helper coordinator del controller spedizione: validazioni pickup,
 * range reschedule, mail notifica. Logica BRT/persistenza resta in
 * App\Services\ShipmentExecutionService (single source of truth idempotency).
 */
class ShipmentExecutionService
{
    public const PICKUP_TIME_SLOTS = ['09:00-12:00', '09:00-18:00', '14:00-18:00'];

    public const BLOCKED_RESCHEDULE_STATUSES = [
        Order::IN_TRANSIT, Order::OUT_FOR_DELIVERY, Order::DELIVERED, Order::IN_GIACENZA,
        Order::RETURNED, Order::REFUSED, Order::CANCELLED, Order::REFUNDED,
    ];

    public function guardRescheduleStatus(Order $order): ?string
    {
        if (in_array($order->getRawOriginal('status'), self::BLOCKED_RESCHEDULE_STATUSES, true)) {
            return 'Non è possibile modificare la data di ritiro: spedizione già in corso o conclusa.';
        }

        return ($order->pickup_status ?? '') === 'done' ? 'Il ritiro risulta già completato.' : null;
    }

    public function pickupDateValidator(Request $request): \Closure
    {
        return function (string $attr, mixed $value, \Closure $fail) use ($request): void {
            $enabled = (bool) data_get($request->input('pickup_request', []), 'enabled', false);
            $normalized = $this->normalizePickupDateInput($value);
            if ($normalized === null) {
                $enabled && $fail('La data ritiro non è valida.');

                return;
            }
            if ($normalized->isBefore(now()->startOfDay())) {
                $fail('La data ritiro deve essere oggi o futura.');
            }
        };
    }

    public function pickupTimeSlotValidator(Request $request): \Closure
    {
        return function (string $attr, mixed $value, \Closure $fail) use ($request): void {
            $enabled = (bool) data_get($request->input('pickup_request', []), 'enabled', false);
            $timeSlot = trim((string) ($value ?? ''));
            if ($timeSlot === '') {
                $enabled && $fail('Seleziona una fascia oraria valida.');

                return;
            }
            if (! in_array($timeSlot, self::PICKUP_TIME_SLOTS, true)) {
                $fail('La fascia oraria selezionata non è valida.');
            }
        };
    }

    public function notifyPickupRescheduled(Order $order, Carbon $newDate, ?string $timeSlot): void
    {
        try {
            $user = $order->user;
            if (! $user?->email) {
                return;
            }
            $body = "Ciao {$user->name},\n\nLa data di ritiro dell'ordine #{$order->id} è stata aggiornata a {$newDate->format('d/m/Y')}"
                .($timeSlot ? " (fascia {$timeSlot})" : '')
                .".\n\nSe non hai richiesto questa modifica contattaci subito.\n\nSpediamoFacile";
            Mail::raw($body, fn ($msg) => $msg->to($user->email)->subject('Data di ritiro aggiornata - Ordine #'.$order->id));
        } catch (\Throwable $e) {
            Log::warning('reschedulePickup mail failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }

    public function allowedReschedulingRange(): array
    {
        $min = now()->startOfDay()->addWeekday();
        $max = $min->copy();
        for ($i = 1; $i < 10; $i++) {
            $max->addWeekday();
        }

        return [$min, $max];
    }

    public function normalizePickupDateInput(mixed $value): ?Carbon
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
