<?php

namespace App\Http\Controllers\Checkout;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\StripeWebhookEvent;
use App\Services\Stripe\Webhook\AccountDisconnectedHandler;
use App\Services\Stripe\Webhook\AccountUpdatedHandler;
use App\Services\Stripe\Webhook\PaymentFailedHandler;
use App\Services\Stripe\Webhook\PaymentSucceededHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;
use UnexpectedValueException;

/**
 * Dispatcher dei webhook Stripe. La logica per ogni event-type vive in
 * app/Services/Stripe/Webhook/*Handler. Questo controller fa solo:
 *  1. verifica firma
 *  2. controllo idempotency a livello di event-id
 *  3. routing al giusto handler
 */
class StripeWebhookController extends Controller
{
    /**
     * Eventi che modificano dati e richiedono controllo di idempotenza.
     * Stripe puo' ritentare lo stesso webhook (timeout, errore di rete);
     * registriamo gli ID processati per evitare duplicazioni.
     */
    private const IDEMPOTENT_EVENT_TYPES = [
        'payment_intent.succeeded',
        'payment_intent.payment_failed',
    ];

    public function __construct(
        private readonly PaymentSucceededHandler $paymentSucceeded,
        private readonly PaymentFailedHandler $paymentFailed,
        private readonly AccountUpdatedHandler $accountUpdated,
        private readonly AccountDisconnectedHandler $accountDisconnected,
    ) {}

    public function handle(Request $request)
    {
        $event = $this->verifySignature($request);

        $requiresIdempotency = in_array($event->type, self::IDEMPOTENT_EVENT_TYPES, true);

        if ($requiresIdempotency && StripeWebhookEvent::wasAlreadyProcessed($event->id)) {
            Log::info('Stripe webhook event already processed, skipping', [
                'event_id' => $event->id,
                'event_type' => $event->type,
            ]);

            return response()->json(['received' => true, 'skipped' => 'already_processed']);
        }

        $handled = match ($event->type) {
            'payment_intent.succeeded' => $this->paymentSucceeded->handle($event),
            'payment_intent.payment_failed' => $this->paymentFailed->handle($event),
            'account.updated' => $this->accountUpdated->handle($event),
            'account.application.deauthorized' => $this->accountDisconnected->handle($event),
            default => true,
        };

        if ($requiresIdempotency) {
            if (! $handled) {
                Log::warning('Stripe webhook handler did not complete; asking Stripe to retry', [
                    'event_id' => $event->id,
                    'event_type' => $event->type,
                ]);

                return response()->json(['received' => false, 'retry' => 'handler_not_completed'], 500);
            }

            StripeWebhookEvent::markAsProcessed($event->id, $event->type);
        }

        return response()->json(['received' => true]);
    }

    protected function verifySignature(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret = $this->getWebhookSecret();

        if (! $secret) {
            abort(Response::HTTP_SERVICE_UNAVAILABLE, 'Stripe webhook non configurato');
        }

        try {
            return Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (UnexpectedValueException $e) {
            abort(Response::HTTP_BAD_REQUEST, 'Invalid payload');
        } catch (SignatureVerificationException $e) {
            abort(Response::HTTP_BAD_REQUEST, 'Invalid signature');
        }
    }

    protected function getWebhookSecret(): ?string
    {
        $secret = trim((string) (
            Setting::get('stripe_webhook_secret')
            ?: config('services.stripe.webhook_secret')
        ));

        return $secret !== '' ? $secret : null;
    }
}
