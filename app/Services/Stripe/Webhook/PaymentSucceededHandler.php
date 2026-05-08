<?php

namespace App\Services\Stripe\Webhook;

use App\Events\OrderPaid;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Handler webhook payment_intent.succeeded.
 *
 * STRIPE-CRITICAL: la logica di idempotency e' INVARIATA rispetto al controller.
 * Modifica solo la struttura del file, non il flusso transazionale.
 */
class PaymentSucceededHandler
{
    use StripeWebhookHelpersTrait;

    public function handle(object $event): bool
    {
        $intent = $event->data->object;
        $orderId = (int) ($intent->metadata->order_id ?? 0);

        if ($orderId <= 0) {
            return true;
        }

        $order = Order::where('id', $orderId)->first();

        if (! $order) {
            return false;
        }

        // Idempotenza a livello di payment_intent
        if (
            $order->stripe_payment_intent_id === $intent->id
            && $order->hasSuccessfulTransactionForExternalId($intent->id)
            && $order->isPostPaymentState()
        ) {
            Log::info('Stripe paymentSucceeded: order already fully processed', [
                'order_id' => $order->id,
                'payment_intent_id' => $intent->id,
            ]);

            return true;
        }

        $transaction = null;
        $dispatchOrderPaid = false;
        $shouldClearCart = false;
        $handled = false;

        DB::transaction(function () use ($order, $intent, &$transaction, &$dispatchOrderPaid, &$shouldClearCart, &$handled) {
            $lockedOrder = Order::query()->lockForUpdate()->find($order->id);

            if (! $lockedOrder) {
                return;
            }

            if (! $this->syncSubmissionContextFromIntent($lockedOrder, $intent)) {
                return;
            }

            if ((int) $intent->amount !== $lockedOrder->payableTotalCents()) {
                $this->logAmountMismatch($lockedOrder, $intent);

                $intentAmount = (int) $intent->amount;
                $orderAmount = $lockedOrder->payableTotalCents();
                $mismatchPercent = $orderAmount > 0
                    ? abs($intentAmount - $orderAmount) / $orderAmount * 100
                    : 100;

                if ($mismatchPercent > 1) {
                    $lockedOrder->status = 'payment_anomaly';
                    $lockedOrder->save();
                }

                $handled = true;

                return;
            }

            if ($lockedOrder->hasSuccessfulTransactionForExternalId($intent->id)) {
                if ($lockedOrder->isAwaitingPayment()) {
                    $lockedOrder->status = Order::COMPLETED;
                }

                $lockedOrder->payment_method = 'stripe';
                $lockedOrder->stripe_payment_intent_id = $intent->id;
                $lockedOrder->save();

                $transaction = $lockedOrder->transactions()
                    ->where('ext_id', $intent->id)
                    ->where('status', 'succeeded')
                    ->latest('id')
                    ->first();
                $shouldClearCart = true;
                $handled = true;

                return;
            }

            if (! $lockedOrder->isAwaitingPayment()
                && $lockedOrder->stripe_payment_intent_id !== $intent->id) {
                Log::warning('Ignoring unexpected Stripe success for non-payable order', [
                    'order_id' => $lockedOrder->id,
                    'payment_intent_id' => $intent->id,
                    'current_payment_intent_id' => $lockedOrder->stripe_payment_intent_id,
                    'status' => $lockedOrder->rawStatus(),
                ]);

                $handled = true;

                return;
            }

            if ($lockedOrder->isAwaitingPayment()) {
                $lockedOrder->status = Order::COMPLETED;
            }

            $lockedOrder->payment_method = 'stripe';
            $lockedOrder->stripe_payment_intent_id = $intent->id;
            $lockedOrder->save();

            /** @var Transaction|null $existingTransaction */
            $existingTransaction = $lockedOrder->transactions()
                ->where('ext_id', $intent->id)
                ->first();
            $wasAlreadySucceeded = $existingTransaction?->status === 'succeeded';

            $transaction = Transaction::updateOrCreate([
                'ext_id' => $intent->id,
            ], [
                'order_id' => $lockedOrder->id,
                'status' => 'succeeded',
                'total' => $intent->amount,
                'type' => $intent->payment_method_types[0] ?? 'unknown',
                'provider_status' => $intent->status,
            ]);

            $dispatchOrderPaid = ! $wasAlreadySucceeded;
            $shouldClearCart = true;
            $handled = true;
        });

        if (! $handled) {
            return false;
        }

        if ($shouldClearCart) {
            $this->clearCartForOrder($order);
        }

        if ($dispatchOrderPaid && $transaction) {
            event(new OrderPaid($order->fresh(), $transaction));
        }

        return true;
    }

    private function logAmountMismatch(Order $order, object $intent): void
    {
        $intentAmount = (int) $intent->amount;
        $orderAmount = $order->payableTotalCents();
        $mismatchPercent = $orderAmount > 0
            ? abs($intentAmount - $orderAmount) / $orderAmount * 100
            : 100;

        Log::critical('Stripe webhook amount mismatch detected', [
            'order_id' => $order->id,
            'payment_intent_id' => $intent->id,
            'intent_amount' => $intentAmount,
            'order_amount' => $orderAmount,
            'gross_subtotal_cents' => $order->grossSubtotalCents(),
            'discount_amount_cents' => $order->discountAmountCents(),
            'mismatch_percent' => round($mismatchPercent, 2),
        ]);
    }
}
