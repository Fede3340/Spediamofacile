<?php
namespace App\Http\Controllers\Checkout;

use App\Http\Controllers\Controller;

use App\Events\OrderPaid;
use App\Models\Order;
use App\Models\Setting;
use App\Models\StripeWebhookEvent;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    private function decodeSnapshotMetadata(mixed $value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function clearCartForOrder(Order $order): void
    {
        $packageIds = $order->packages()->pluck('packages.id')->filter()->values();

        if ($packageIds->isEmpty()) {
            return;
        }

        DB::table('cart_user')
            ->where('user_id', $order->user_id)
            ->whereIn('package_id', $packageIds->all())
            ->delete();
    }

    private function syncSubmissionContextFromIntent(Order $order, object $intent): bool
    {
        $metadata = is_object($intent->metadata ?? null)
            ? (array) $intent->metadata
            : (array) ($intent->metadata ?? []);

        $updates = [];

        foreach (['client_submission_id', 'pricing_signature'] as $field) {
            $incoming = $metadata[$field] ?? null;
            $current = $order->getAttribute($field);

            if (! filled($incoming)) {
                continue;
            }

            if (filled($current) && (string) $current !== (string) $incoming) {
                Log::warning('Ignoring Stripe webhook with mismatched submission metadata', [
                    'order_id' => $order->id,
                    'field' => $field,
                    'order_value' => $current,
                    'intent_value' => $incoming,
                ]);

                return false;
            }

            if (! filled($current)) {
                $updates[$field] = (string) $incoming;
            }
        }

        $incomingVersion = $metadata['pricing_snapshot_version'] ?? null;
        $currentVersion = $order->getAttribute('pricing_snapshot_version');
        if (filled($incomingVersion)) {
            if (filled($currentVersion) && (int) $currentVersion !== (int) $incomingVersion) {
                Log::warning('Ignoring Stripe webhook with mismatched snapshot version', [
                    'order_id' => $order->id,
                    'order_value' => $currentVersion,
                    'intent_value' => $incomingVersion,
                ]);

                return false;
            }

            if (! filled($currentVersion)) {
                $updates['pricing_snapshot_version'] = (int) $incomingVersion;
            }
        }

        $incomingSnapshot = $this->decodeSnapshotMetadata($metadata['quote_snapshot'] ?? null);
        $currentSnapshot = $order->getAttribute('pricing_snapshot');
        if (is_array($incomingSnapshot) && ! empty($incomingSnapshot)) {
            if (filled($currentSnapshot) && $currentSnapshot !== $incomingSnapshot) {
                Log::warning('Ignoring Stripe webhook with mismatched pricing snapshot', [
                    'order_id' => $order->id,
                ]);

                return false;
            }

            if (empty($currentSnapshot)) {
                $updates['pricing_snapshot'] = $incomingSnapshot;
            }
        }

        if (! empty($updates)) {
            $order->forceFill($updates)->save();
        }

        return true;
    }

    protected function getWebhookSecret(): ?string
    {
        $secret = trim((string) (
            Setting::get('stripe_webhook_secret')
            ?: config('services.stripe.webhook_secret')
        ));

        return $secret !== '' ? $secret : null;
    }

    /**
     * Eventi che modificano dati e richiedono controllo di idempotenza.
     * Gli eventi in questa lista vengono registrati nella tabella stripe_webhook_events
     * per evitare che Stripe retries creino duplicati.
     */
    private const IDEMPOTENT_EVENT_TYPES = [
        'payment_intent.succeeded',
        'payment_intent.payment_failed',
    ];

    // Funzione principale che riceve e gestisce tutte le notifiche da Stripe
    public function handle(Request $request)
    {
        // Prima di tutto, verifichiamo che la notifica venga davvero da Stripe
        // (per sicurezza, per evitare che qualcuno finga di essere Stripe)
        $event = $this->verifySignature($request);

        // ── Idempotenza a livello di evento Stripe ──────────────────
        // Stripe puo' ritentare lo stesso webhook (timeout, errore di rete).
        // L'ID evento (evt_...) resta lo stesso nei retry. Registriamo
        // gli ID processati per evitare di rielaborare lo stesso evento.
        if (in_array($event->type, self::IDEMPOTENT_EVENT_TYPES, true)) {
            if (! StripeWebhookEvent::markAsProcessed($event->id, $event->type)) {
                Log::info('Stripe webhook event already processed, skipping', [
                    'event_id' => $event->id,
                    'event_type' => $event->type,
                ]);

                return response()->json(['received' => true, 'skipped' => 'already_processed']);
            }
        }

        // In base al tipo di evento, chiamiamo la funzione giusta
        // "match" e' come un selettore: sceglie cosa fare in base al tipo di notifica
        match ($event->type) {
            'payment_intent.succeeded' => $this->paymentSucceeded($event),     // Pagamento riuscito
            'payment_intent.payment_failed' => $this->paymentFailed($event),   // Pagamento fallito

            'account.updated' => $this->accountUpdated($event),                // Account Stripe aggiornato
            'account.application.deauthorized' => $this->accountDisconnected($event), // Account scollegato

            default => null, // Per tutti gli altri eventi, non facciamo nulla
        };

        // Rispondiamo a Stripe per confermare che abbiamo ricevuto la notifica
        return response()->json(['received' => true]);
    }

    // Verifica che la notifica venga davvero da Stripe controllando la "firma" digitale
    // Se la firma non e' valida, blocchiamo tutto con un errore
    protected function verifySignature(Request $request)
    {
        $payload = $request->getContent();           // Il contenuto della notifica
        $sigHeader = $request->header('Stripe-Signature'); // La firma inviata da Stripe
        $secret = $this->getWebhookSecret(); // La nostra chiave segreta per verificare

        if (! $secret) {
            abort(Response::HTTP_SERVICE_UNAVAILABLE, 'Stripe webhook non configurato');
        }

        try {
            // Stripe verifica che il contenuto corrisponda alla firma
            return Webhook::constructEvent(
                $payload,
                $sigHeader,
                $secret
            );
        } catch (UnexpectedValueException $e) {
            // Il contenuto della notifica non e' valido
            abort(Response::HTTP_BAD_REQUEST, 'Invalid payload');
        } catch (SignatureVerificationException $e) {
            // La firma non corrisponde: qualcuno potrebbe star cercando di ingannarci
            abort(Response::HTTP_BAD_REQUEST, 'Invalid signature');
        }
    }

    // Gestisce l'evento "pagamento riuscito"
    // Quando un cliente paga con successo, aggiorniamo l'ordine e salviamo i dettagli della transazione
    protected function paymentSucceeded($event)
    {
        $intent = $event->data->object;

        // Recuperiamo l'identificativo dell'ordine dai dati aggiuntivi (metadata) del pagamento
        $orderId = (int) ($intent->metadata->order_id ?? 0);

        if ($orderId <= 0) {
            return;
        }

        // Cerchiamo l'ordine nel database
        $order = Order::where('id', $orderId)->first();

        // Se l'ordine non esiste, non facciamo nulla
        if (! $order) {
            return;
        }

        // ── Idempotenza a livello di payment_intent ─────────────────
        // Se l'ordine ha gia' registrato questo payment_intent con una transazione
        // succeeded, non c'e' nulla da fare. Questo e' un secondo livello di
        // protezione oltre all'event-level idempotency in handle().
        if (
            $order->stripe_payment_intent_id === $intent->id
            && $order->hasSuccessfulTransactionForExternalId($intent->id)
            && $order->isPostPaymentState()
        ) {
            Log::info('Stripe paymentSucceeded: order already fully processed', [
                'order_id' => $order->id,
                'payment_intent_id' => $intent->id,
            ]);

            return;
        }

        $transaction = null;
        $dispatchOrderPaid = false;
        $shouldClearCart = false;

        DB::transaction(function () use ($order, $intent, &$transaction, &$dispatchOrderPaid, &$shouldClearCart) {
            $lockedOrder = Order::query()->lockForUpdate()->find($order->id);

            if (! $lockedOrder) {
                return;
            }

            if (! $this->syncSubmissionContextFromIntent($lockedOrder, $intent)) {
                return;
            }

            if ((int) $intent->amount !== $lockedOrder->payableTotalCents()) {
                $intentAmount = (int) $intent->amount;
                $orderAmount = $lockedOrder->payableTotalCents();
                $mismatchPercent = $orderAmount > 0
                    ? abs($intentAmount - $orderAmount) / $orderAmount * 100
                    : 100;

                Log::critical('Stripe webhook amount mismatch detected', [
                    'order_id' => $lockedOrder->id,
                    'payment_intent_id' => $intent->id,
                    'intent_amount' => $intentAmount,
                    'order_amount' => $orderAmount,
                    'gross_subtotal_cents' => $lockedOrder->grossSubtotalCents(),
                    'discount_amount_cents' => $lockedOrder->discountAmountCents(),
                    'mismatch_percent' => round($mismatchPercent, 2),
                ]);

                // Se la differenza supera l'1%, segna l'ordine come anomalia di pagamento
                if ($mismatchPercent > 1) {
                    $lockedOrder->status = 'payment_anomaly';
                    $lockedOrder->save();
                }

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

                return;
            }

            if ($lockedOrder->isAwaitingPayment()) {
                $lockedOrder->status = Order::COMPLETED;
            }

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
                'status' => 'succeeded',
                'total' => $intent->amount,
                'type' => $intent->payment_method_types[0] ?? 'unknown',
                'provider_status' => $intent->status,
            ]);

            $dispatchOrderPaid = ! $wasAlreadySucceeded;
            $shouldClearCart = true;
        });

        if ($shouldClearCart) {
            $this->clearCartForOrder($order);
        }

        if ($dispatchOrderPaid && $transaction) {
            event(new OrderPaid($order->fresh(), $transaction));
        }
    }

    // Gestisce l'evento "pagamento fallito"
    // Quando un pagamento non va a buon fine, salviamo il motivo dell'errore
    protected function paymentFailed($event)
    {
        $intent = $event->data->object;

        $orderId = (int) ($intent->metadata->order_id ?? 0);

        if ($orderId <= 0) {
            return;
        }

        $order = Order::where('id', $orderId)->first();

        if (! $order) {
            return;
        }

        // ── Idempotenza: se esiste gia' una transazione failed per questo intent, skip ──
        $existingFailedTransaction = $order->transactions()
            ->where('ext_id', $intent->id)
            ->where('status', 'failed')
            ->exists();

        if ($existingFailedTransaction && $order->rawStatus() === Order::PAYMENT_FAILED) {
            Log::info('Stripe paymentFailed: already recorded for this intent', [
                'order_id' => $order->id,
                'payment_intent_id' => $intent->id,
            ]);

            return;
        }

        // Cerchiamo di capire perche' il pagamento e' fallito
        // Proviamo a recuperare il messaggio di errore da diverse fonti
        $failureMessage = $intent->last_payment_error->message
                  ?? $intent->charges->data[0]->failure_message
                  ?? 'Payment failed';

        $failureCode = $intent->last_payment_error->code
                  ?? $intent->charges->data[0]->failure_code
                  ?? null;

        DB::transaction(function () use ($order, $intent, $failureCode, $failureMessage) {
            $lockedOrder = Order::query()->lockForUpdate()->find($order->id);

            if (! $lockedOrder) {
                return;
            }

            if ($lockedOrder->isAwaitingPayment() || $lockedOrder->stripe_payment_intent_id === $intent->id) {
                $lockedOrder->status = Order::PAYMENT_FAILED;
                $lockedOrder->payment_method = 'stripe';
                $lockedOrder->stripe_payment_intent_id = $intent->id;
                $lockedOrder->save();
            } else {
                Log::warning('Ignoring Stripe failed intent for non-payable order', [
                    'order_id' => $lockedOrder->id,
                    'payment_intent_id' => $intent->id,
                    'current_payment_intent_id' => $lockedOrder->stripe_payment_intent_id,
                    'status' => $lockedOrder->rawStatus(),
                ]);
            }

            // Salviamo i dettagli della transazione fallita, incluso il motivo dell'errore
            Transaction::updateOrCreate([
                'ext_id' => $intent->id,
            ], [
                'order_id' => $lockedOrder->id,
                'status' => 'failed',
                'total' => $intent->amount,
                'type' => $intent->payment_method_types[0] ?? 'unknown',
                'provider_status' => $intent->status,
                'failure_code' => $failureCode,
                'failure_message' => $failureMessage,
            ]);
        });
    }

    /**
     * Trova un utente a partire dallo stripe_account_id.
     *
     * Dato che il campo e' cifrato at-rest (cast 'encrypted' con IV random),
     * non possiamo usare User::where('stripe_account_id', $id). Effettuiamo
     * quindi una scansione in memoria ristretta ai soli Partner Pro che hanno
     * un account Stripe configurato. Il numero e' limitato (ordine delle centinaia
     * al massimo) e i webhook Stripe account.updated/deauthorized sono rari,
     * quindi l'overhead e' accettabile. Si usa chunkById per evitare carichi
     * memory eccessivi in caso di crescita.
     */
    protected function findUserByStripeAccountId(?string $stripeAccountId): ?User
    {
        if (! $stripeAccountId) {
            return null;
        }

        $found = null;

        User::query()
            ->where('role', 'Partner Pro')
            ->whereNotNull('stripe_account_id')
            ->chunkById(200, function ($users) use ($stripeAccountId, &$found) {
                foreach ($users as $u) {
                    if ($u->stripe_account_id === $stripeAccountId) {
                        $found = $u;
                        return false; // interrompe chunkById
                    }
                }
            });

        return $found;
    }

    // Gestisce l'evento "account Stripe aggiornato"
    // Quando un Partner Pro completa o modifica il suo profilo Stripe,
    // aggiorniamo le informazioni nel nostro database
    protected function accountUpdated($event)
    {
        $intent = $event->data->object;

        $stripeAccountId = $intent->id;

        // Cerchiamo l'utente che ha questo account Stripe
        // Lookup decriptato lato app (vedi findUserByStripeAccountId).
        $user = $this->findUserByStripeAccountId($stripeAccountId);

        if (! $user) {
            return;
        }

        // Aggiorniamo le informazioni sulle capacita' dell'account Stripe
        // (es. se puo' ricevere pagamenti, se puo' fare prelievi, ecc.)
        $user->stripe_charges_enabled = $intent->charges_enabled;
        $user->stripe_payouts_enabled = $intent->payouts_enabled;
        $user->stripe_capabilities = json_encode($intent->capabilities);
        $user->stripe_requirements = json_encode($intent->requirements);
        $user->stripe_details_submitted = $intent->details_submitted;

        $user->save();
    }

    // Gestisce l'evento "account Stripe disconnesso"
    // Quando un utente scollega il suo account Stripe, rimuoviamo tutti i dati di collegamento
    protected function accountDisconnected($event)
    {
        $account = $event->data->object;
        $stripeAccountId = $account->id ?? null;

        if (! $stripeAccountId) {
            return;
        }

        // Lookup decriptato lato app (vedi findUserByStripeAccountId).
        $user = $this->findUserByStripeAccountId($stripeAccountId);

        if (! $user) {
            return;
        }

        // Resettiamo tutti i campi legati a Stripe, come se l'utente non avesse mai collegato il suo account
        $user->stripe_account_id = null;
        $user->stripe_charges_enabled = false;
        $user->stripe_payouts_enabled = false;
        $user->stripe_details_submitted = false;
        $user->stripe_capabilities = null;
        $user->stripe_requirements = null;
        $user->save();

        // Registriamo nei log che l'account e' stato disconnesso (utile per debug)
        Log::info('Stripe account disconnected', ['user_id' => $user->id, 'account_id' => $stripeAccountId]);
    }
}
