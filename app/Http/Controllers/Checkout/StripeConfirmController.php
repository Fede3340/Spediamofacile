<?php

// CRITICAL: vedi CLAUDE.md "Eccezioni documentate" — non modificare logica idempotency
// senza E2E gating Stripe (carta test 4242 4242 4242 4242 09/30 123).
// Estratto da StripeCheckoutController per separare creazione intent da conferma 3DS.

namespace App\Http\Controllers\Checkout;

use App\Events\OrderPaid;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrderPaidRequest;
use App\Models\Order;
use App\Models\Transaction;
use App\Services\CheckoutSubmissionContextService;
use App\Services\StripePaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Conferma post-3DS del PaymentIntent Stripe e finalizza l'ordine:
 * verifica server-side dello status reale dell'intent, transazione + status,
 * dispatch OrderPaid (idempotente), pulizia carrello.
 *
 * Endpoints:
 *  - POST /api/stripe/order-paid                (ordine da carrello)
 *  - POST /api/stripe/existing-order-paid       (ordine esistente)
 */
class StripeConfirmController extends Controller
{
    use StripeCheckoutHelpers;

    public function __construct(
        private readonly StripePaymentService $stripe,
        private readonly CheckoutSubmissionContextService $submissionContext,
    ) {}

    public function orderPaid(OrderPaidRequest $request)
    {
        $order = Order::findOrFail($request->order_id);
        if ($unauthorized = $this->ensureOrderOwnership($order)) {
            return $unauthorized;
        }

        try {
            $result = $this->stripe->retrieveAndVerifyPayment($request->ext_id, $order);
        } catch (\RuntimeException $e) {
            Log::warning('Stripe retrieveAndVerifyPayment failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'Verifica del pagamento non riuscita. Riprova o contatta il supporto.'], 422);
        }

        $intent = $result['intent'];
        $transaction = null;
        $dispatchOrderPaid = false;
        $response = ['success' => true];
        $statusCode = 200;

        DB::transaction(function () use ($order, $intent, $result, $request, &$transaction, &$dispatchOrderPaid, &$response, &$statusCode) {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($contextError = $this->syncSubmissionContextOnOrder($lockedOrder, $this->submissionContextFromRequest($request))) {
                $response = $contextError->getData(true);
                $statusCode = $contextError->getStatusCode();

                return;
            }

            if ($lockedOrder->hasSuccessfulTransactionForExternalId($intent->id)) {
                $this->syncStripePaymentState($lockedOrder, $intent->id);
                $transaction = $lockedOrder->transactions()
                    ->where('ext_id', $intent->id)
                    ->where('status', 'succeeded')
                    ->latest('id')
                    ->first();

                return;
            }

            if (! $lockedOrder->isAwaitingPayment()) {
                $response = ['error' => 'Ordine non più pagabile.'];
                $statusCode = 422;

                return;
            }

            $lockedOrder->status = $intent->status === 'succeeded' ? Order::COMPLETED : Order::PAYMENT_FAILED;
            $lockedOrder->payment_method = 'stripe';
            $lockedOrder->stripe_payment_intent_id = $intent->id;
            $lockedOrder->save();

            $existingTransaction = $lockedOrder->transactions()
                ->where('ext_id', $intent->id)
                ->first();
            $wasAlreadySucceeded = $existingTransaction?->status === 'succeeded';

            $transaction = Transaction::updateOrCreate([
                'ext_id' => $intent->id,
            ], [
                'order_id' => $lockedOrder->id,
                'type' => $result['payment_type'],
                'status' => $intent->status,
                'provider_status' => $intent->status,
                'total' => $intent->amount,
            ]);

            if ($intent->status !== 'succeeded') {
                $response = ['success' => false];
                $statusCode = 402;

                return;
            }

            $dispatchOrderPaid = ! $wasAlreadySucceeded;
        });

        if ($statusCode !== 200) {
            return response()->json($response, $statusCode);
        }

        if ($dispatchOrderPaid && $transaction) {
            event(new OrderPaid($order->fresh(), $transaction));
        }

        $this->clearCartPackagesForOrder($order->fresh());

        return response()->json(['success' => true]);
    }
}
