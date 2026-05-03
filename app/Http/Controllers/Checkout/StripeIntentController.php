<?php

// CRITICAL: vedi CLAUDE.md "Eccezioni documentate" — non modificare logica idempotency
// senza E2E gating Stripe (carta test 4242 4242 4242 4242 09/30 123).
// Estratto da StripeCheckoutController per ridurre la classe sotto soglia 200 LOC.

namespace App\Http\Controllers\Checkout;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePaymentIntentRequest;
use App\Http\Requests\CreateStripePaymentRequest;
use App\Models\Order;
use App\Services\CheckoutSubmissionContextService;
use App\Services\StripePaymentService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Log;

/**
 * Crea PaymentIntent Stripe (3DS-ready) o off-session payment per ordini esistenti.
 * Idempotency-key derivata da client_submission_id o X-Idempotency-Key (vedi
 * StripeCheckoutHelpers::resolveStripeIdempotencyKey).
 *
 * Endpoints:
 *  - POST /api/stripe/create-payment-intent          (ordine da carrello)
 *  - POST /api/stripe/existing-order-payment-intent  (ordine esistente)
 *  - POST /api/stripe/create-payment                 (ordine da carrello, off-session)
 *  - POST /api/stripe/existing-order-payment         (ordine esistente, off-session)
 */
class StripeIntentController extends Controller
{
    use StripeCheckoutHelpers;

    public function __construct(
        private readonly StripePaymentService $stripe,
        private readonly CheckoutSubmissionContextService $submissionContext,
    ) {}

    public function createPayment(CreateStripePaymentRequest $request)
    {
        $order = Order::findOrFail($request->order_id);
        $user = $request->user();
        if ($unauthorized = $this->ensureOrderOwnership($order, $user?->id)) {
            return $unauthorized;
        }
        if ($contextError = $this->syncSubmissionContextOnOrder($order, $this->submissionContextFromRequest($request))) {
            return $contextError;
        }
        if ($notPayable = $this->ensureOrderPayable($order)) {
            return $notPayable;
        }
        if (! $this->stripe->isConfigured()) {
            return response()->json(['error' => 'Stripe non configurato.'], 503);
        }
        if (! $user?->customer_id) {
            return response()->json(['error' => 'No Stripe customer'], 400);
        }
        if (! $this->stripe->paymentMethodBelongsToUser($user, $request->payment_method_id)) {
            return response()->json(['error' => 'Non autorizzato.'], 403);
        }

        return response()->json($this->stripe->createOffSessionPayment(
            $order,
            $user,
            $request->currency,
            $request->payment_method_id,
            $this->resolveStripeIdempotencyKey($order, $request),
        ));
    }

    public function createPaymentIntent(CreatePaymentIntentRequest $request)
    {
        $order = Order::findOrFail($request->order_id);
        $user = $request->user();
        if ($unauthorized = $this->ensureOrderOwnership($order, $user?->id)) {
            return $unauthorized;
        }
        if ($contextError = $this->syncSubmissionContextOnOrder($order, $this->submissionContextFromRequest($request))) {
            return $contextError;
        }
        if ($notPayable = $this->ensureOrderPayable($order)) {
            return $notPayable;
        }
        if (! $this->stripe->isConfigured()) {
            return response()->json(['error' => 'Stripe non configurato.'], 503);
        }

        $amount = $order->payableTotalCents();
        if ($amount < 50) {
            return response()->json(['error' => 'Importo troppo basso per il pagamento.'], 422);
        }

        try {
            return response()->json($this->stripe->createPaymentIntent(
                $order,
                $user,
                $this->resolveStripeIdempotencyKey($order, $request),
            ));
        } catch (DecryptException $e) {
            // customer_id corrotto nel DB (APP_KEY ruotata): reset e riprova una volta.
            if ($user && $user->customer_id !== null) {
                Log::warning('customer_id decrypt failed during PaymentIntent, resetting', ['user_id' => $user->id]);
                $user->forceFill(['customer_id' => null])->saveQuietly();
                try {
                    return response()->json($this->stripe->createPaymentIntent(
                        $order,
                        $user->refresh(),
                        $this->resolveStripeIdempotencyKey($order, $request),
                    ));
                } catch (\Throwable $retryError) {
                    Log::error('PaymentIntent retry after decrypt reset failed', ['error' => $retryError->getMessage()]);
                }
            }

            return response()->json(['error' => 'Errore durante la creazione del pagamento. Riprova.'], 500);
        } catch (\Exception $e) {
            Log::error('PaymentIntent creation error', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Errore durante la creazione del pagamento. Riprova.'], 500);
        }
    }
}
