<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Transaction;
use App\Models\WalletMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class RefundService
{
    public const CANCELLATION_FEE_CENTS = 200;

    private StripeConfigService $stripeConfig;

    private StripeClient $stripe;

    public function __construct(?StripeConfigService $stripeConfig = null, ?StripeClient $stripe = null)
    {
        $this->stripeConfig = $stripeConfig ?? app(StripeConfigService::class);
        $this->stripe = $stripe ?? app(StripeClient::class);
    }

    /**
     * Process a full order cancellation with optional refund.
     *
     * Runs inside a DB transaction with pessimistic locking to prevent double-refunds.
     * Returns a result array on success or throws RuntimeException / Exception on failure.
     *
     * @return array{refund_amount_cents: int, commission_cents: int, refund_method: string, brt_cancelled: bool}
     *
     * @throws \RuntimeException  Business-logic failure (order no longer cancellable).
     * @throws \Exception         Unexpected infrastructure failure.
     */
    public function processCancellation(Order $order, ?string $reason = null): array
    {
        return DB::transaction(function () use ($order, $reason) {
            // Re-load with pessimistic lock to serialise concurrent requests.
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            $eligibility = $this->calculateEligibility($order);

            if (! $eligibility['eligible']) {
                throw new \RuntimeException($eligibility['reason']);
            }

            $brtCancelled = $this->cancelBrtShipmentIfNeeded($order);

            $refundAmountCents = $eligibility['refund_amount_cents'];
            $commissionCents   = $eligibility['commission_cents'];
            $refundMethod      = 'wallet';

            if ($refundAmountCents > 0) {
                $refundMethod = $this->routeRefund($order, $refundAmountCents);

                Transaction::create([
                    'order_id' => $order->id,
                    'ext_id'   => 'refund_' . $order->id . '_' . now()->timestamp,
                    'type'     => 'refund_' . $refundMethod,
                    'status'   => 'succeeded',
                    'total'    => -$refundAmountCents,
                ]);
            }

            $order->status            = $refundAmountCents > 0 ? Order::REFUNDED : Order::CANCELLED;
            $order->refund_status     = $refundAmountCents > 0 ? 'completed' : 'none';
            $order->refund_amount     = $refundAmountCents;
            $order->refund_method     = $refundMethod;
            $order->refund_reason     = $reason ?? 'Annullamento richiesto dall\'utente';
            $order->refunded_at       = $refundAmountCents > 0 ? now() : null;
            $order->cancellation_fee  = $commissionCents;
            $order->save();

            Log::info('Order cancelled and refunded', [
                'order_id'           => $order->id,
                'refund_amount_cents' => $refundAmountCents,
                'commission_cents'   => $commissionCents,
                'refund_method'      => $refundMethod,
                'brt_cancelled'      => $brtCancelled,
            ]);

            return [
                'refund_amount_cents' => $refundAmountCents,
                'commission_cents'    => $commissionCents,
                'refund_method'       => $refundMethod,
                'brt_cancelled'       => $brtCancelled,
            ];
        });
    }

    /**
     * Cancel the BRT shipment tied to the order, if any.
     */
    private function cancelBrtShipmentIfNeeded(Order $order): bool
    {
        if (! $order->brt_numeric_sender_reference) {
            return false;
        }

        try {
            $brtService = new BrtService;
            $brtResult  = $brtService->deleteShipment((int) $order->brt_numeric_sender_reference);
            $success    = $brtResult['success'] ?? false;

            if (! $success) {
                Log::warning('BRT deleteShipment failed during cancellation', [
                    'order_id'       => $order->id,
                    'brt_reference'  => $order->brt_numeric_sender_reference,
                    'brt_error'      => $brtResult['error'] ?? 'Errore sconosciuto',
                ]);
            }

            return $success;
        } catch (\Exception $e) {
            Log::error('BRT deleteShipment exception during cancellation', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Route refund to the correct channel (Stripe or wallet).
     */
    private function routeRefund(Order $order, int $refundAmountCents): string
    {
        if ($order->payment_method === 'stripe' && $order->stripe_payment_intent_id) {
            $this->processStripeRefund($order, $refundAmountCents);

            return 'stripe';
        }

        $this->processWalletRefund($order, $refundAmountCents);

        return 'wallet';
    }

    public function calculateEligibility(Order $order): array
    {
        $status = $order->getAttributes()['status'] ?? $order->status;

        if (in_array($status, ['cancelled', 'refunded'])) {
            return $this->ineligible('L\'ordine e\' gia\' stato annullato o rimborsato.', $order, 'already_cancelled');
        }

        if (in_array($status, [Order::PENDING, Order::PAYMENT_FAILED])) {
            return $this->eligible('L\'ordine non e\' ancora stato pagato. Verra\' annullato senza rimborso.', $order, 'cancel_unpaid', 0, 0);
        }

        if ($status === Order::IN_TRANSIT) {
            return $this->ineligible('La spedizione e\' gia\' partita e in transito. Non e\' possibile richiedere il rimborso.', $order, 'in_transit');
        }

        if (in_array($status, [Order::COMPLETED, Order::PROCESSING])) {
            $subtotalCents = (int) $order->subtotal->amount();
            $commission = self::CANCELLATION_FEE_CENTS;
            $refund = max(0, $subtotalCents - $commission);
            return $this->eligible(
                'L\'ordine puo\' essere annullato. Verra\' applicata una commissione di annullamento di ' . number_format($commission / 100, 2, ',', '.') . ' EUR.',
                $order, 'refund_with_commission', $refund, $commission
            );
        }

        if (in_array($status, ['delivered', 'in_giacenza'])) {
            return $this->ineligible('L\'ordine e\' gia\' stato consegnato o e\' in giacenza. Non e\' possibile richiedere il rimborso.', $order, 'not_refundable');
        }

        return $this->ineligible('Non e\' possibile annullare questo ordine.', $order, 'unknown');
    }

    public function processStripeRefund(Order $order, int $amountCents): void
    {
        $secret = $this->stripeConfig->getSecret();
        if (!$secret) throw new \Exception('Stripe non configurato. Impossibile processare il rimborso.');

        $stripe = $this->stripe;

        try {
            // La idempotency key e' deterministica sull'ordine: anche se questa funzione venisse
            // chiamata due volte per lo stesso ordine (es. retry di rete), Stripe restituira' il
            // rimborso gia' creato invece di crearne uno secondo.
            $idempotencyKey = 'refund_order_' . $order->id . '_' . $amountCents;

            $refund = $stripe->refunds->create([
                'payment_intent' => $order->stripe_payment_intent_id,
                'amount' => $amountCents, 'reason' => 'requested_by_customer',
                'metadata' => ['order_id' => (string) $order->id, 'cancellation_fee_cents' => (string) self::CANCELLATION_FEE_CENTS],
            ], ['idempotency_key' => $idempotencyKey]);

            Log::info('Stripe refund processed', ['order_id' => $order->id, 'refund_id' => $refund->id, 'amount' => $amountCents, 'status' => $refund->status]);
            if ($refund->status === 'failed') throw new \Exception('Rimborso Stripe fallito. Status: ' . $refund->status);
        } catch (ApiErrorException $e) {
            Log::error('Stripe refund API error', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            throw new \Exception('Errore Stripe durante il rimborso: ' . $e->getMessage());
        }
    }

    public function processWalletRefund(Order $order, int $amountCents): void
    {
        WalletMovement::create([
            'user_id' => $order->user_id, 'type' => 'credit',
            'amount' => round($amountCents / 100, 2), 'currency' => 'EUR', 'status' => 'confirmed',
            'idempotency_key' => 'refund_' . $order->id . '_' . Str::uuid(),
            'reference' => (string) $order->id,
            'description' => 'Rimborso ordine #SF-' . str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
            'source' => 'refund',
        ]);
        Log::info('Wallet refund processed', ['order_id' => $order->id, 'user_id' => $order->user_id, 'amount_eur' => round($amountCents / 100, 2)]);
    }

    private function eligible(string $reason, Order $order, string $type, int $refund, int $commission): array
    {
        return [
            'eligible' => true, 'reason' => $reason,
            'refund_amount_cents' => $refund, 'commission_cents' => $commission,
            'refund_amount_eur' => number_format($refund / 100, 2, ',', '.'),
            'commission_eur' => number_format($commission / 100, 2, ',', '.'),
            'payment_method' => $order->payment_method, 'type' => $type,
        ];
    }

    private function ineligible(string $reason, Order $order, string $type): array
    {
        return [
            'eligible' => false, 'reason' => $reason,
            'refund_amount_cents' => 0, 'commission_cents' => 0,
            'refund_amount_eur' => '0,00', 'commission_eur' => '0,00',
            'payment_method' => $order->payment_method, 'type' => $type,
        ];
    }
}
