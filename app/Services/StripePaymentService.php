<?php
namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class StripePaymentService
{
    private StripeConfigService $configService;

    private StripeClient $stripe;

    public function __construct(StripeConfigService $configService, StripeClient $stripe)
    {
        $this->configService = $configService;
        $this->stripe = $stripe;
    }

    public function getStripeSecret(): ?string
    {
        return $this->configService->getSecret();
    }

    public function isConfigured(): bool
    {
        return ! empty($this->getStripeSecret());
    }

    private function client(): StripeClient
    {
        return $this->stripe;
    }

    // ── Customer management ──────────────────────────────────────

    /**
     * Crea un profilo cliente su Stripe per l'utente, o restituisce quello esistente.
     */
    public function createOrGetCustomer(User $user): string
    {
        if (! $user->customer_id) {
            $stripe = $this->client();
            $customer = $stripe->customers->create([
                'email' => $user->email,
                'name' => $user->name . ' ' . $user->surname,
            ]);

            $user->customer_id = $customer->id;
            $user->save();
        }

        return $user->customer_id;
    }

    public function paymentMethodBelongsToUser(User $user, string $paymentMethodId): bool
    {
        if (! $user->customer_id) {
            return false;
        }

        $stripe = $this->client();
        $paymentMethod = $stripe->paymentMethods->retrieve($paymentMethodId);

        return (string) ($paymentMethod->customer ?? '') === (string) $user->customer_id;
    }

    // ── Payment intents ──────────────────────────────────────────

    /**
     * Crea un PaymentIntent per il checkout con carta.
     *
     * @return array{client_secret: string, payment_intent_id: string}
     * @throws \Exception
     */
    public function createPaymentIntent(Order $order, User $user, ?string $idempotencyKey = null): array
    {
        $stripe = $this->client();
        $customerId = $this->createOrGetCustomer($user);

        $amount = $order->payableTotalCents();

        $paymentIntent = $stripe->paymentIntents->create([
            'amount' => $amount,
            'currency' => 'eur',
            'customer' => (string) $customerId,
            'metadata' => $this->orderMetadata($order),
            'automatic_payment_methods' => ['enabled' => true],
        ], $this->stripeRequestOptions($order, $idempotencyKey));

        return [
            'client_secret' => $paymentIntent->client_secret,
            'payment_intent_id' => $paymentIntent->id,
        ];
    }

    /**
     * Crea e conferma un pagamento con carta gia' salvata (off-session).
     */
    public function createOffSessionPayment(Order $order, User $user, string $currency, string $paymentMethodId, ?string $idempotencyKey = null): array
    {
        $stripe = $this->client();
        $customerId = $user->customer_id;

        if (! $customerId) {
            throw new \RuntimeException('Nessun profilo Stripe associato.');
        }

        $paymentMethod = $stripe->paymentMethods->retrieve((string) $paymentMethodId);
        if ((string) ($paymentMethod->customer ?? '') !== (string) $customerId) {
            throw new \RuntimeException('Metodo di pagamento non autorizzato.');
        }

        $paymentIntent = $stripe->paymentIntents->create([
            'amount' => $order->payableTotalCents(),
            'currency' => (string) $currency,
            'customer' => (string) $customerId,
            'payment_method' => (string) $paymentMethodId,
            'confirm' => true,
            'off_session' => true,
            'metadata' => $this->orderMetadata($order),
        ], $this->stripeRequestOptions($order, $idempotencyKey));

        $order->payment_method = 'stripe';
        $order->stripe_payment_intent_id = $paymentIntent->id;
        $order->save();

        return [
            'payment_intent_id' => $paymentIntent->id,
            'status' => $paymentIntent->status,
        ];
    }

    /**
     * Crea e conferma una ricarica wallet con carta salvata.
     */
    public function createWalletTopUpPayment(User $user, int $amountCents, string $paymentMethodId, ?string $idempotencyKey = null): array
    {
        $stripe = $this->client();
        $customerId = $this->createOrGetCustomer($user);

        $paymentIntent = $stripe->paymentIntents->create([
            'amount' => $amountCents,
            'currency' => 'eur',
            'customer' => (string) $customerId,
            'payment_method' => (string) $paymentMethodId,
            'confirm' => true,
            'off_session' => true,
            'metadata' => [
                'type' => 'wallet_topup',
                'user_id' => (string) $user->id,
                'amount_cents' => (string) $amountCents,
            ],
        ], $this->walletTopUpRequestOptions($user, $amountCents, $paymentMethodId, $idempotencyKey));

        return [
            'payment_intent_id' => $paymentIntent->id,
            'status' => $paymentIntent->status,
        ];
    }

    /**
     * Verifica un pagamento completato e restituisce i dati necessari.
     *
     * @return array{intent: \Stripe\PaymentIntent, payment_type: string}
     */
    public function retrieveAndVerifyPayment(string $paymentIntentId, Order $order): array
    {
        $stripe = $this->client();
        $intent = $stripe->paymentIntents->retrieve($paymentIntentId);

        // Verifica che il payment intent corrisponda all'ordine
        if (isset($intent->metadata['order_id']) && (int) $intent->metadata['order_id'] !== $order->id) {
            throw new \RuntimeException('Payment intent non corrisponde all\'ordine.');
        }

        $metadata = is_object($intent->metadata ?? null)
            ? (array) $intent->metadata
            : (array) ($intent->metadata ?? []);

        foreach (['client_submission_id', 'pricing_signature'] as $field) {
            $orderValue = $order->getAttribute($field);
            $intentValue = $metadata[$field] ?? null;

            if (filled($orderValue) && filled($intentValue) && (string) $orderValue !== (string) $intentValue) {
                throw new \RuntimeException('Metadati dell\'ordine non corrispondono al payment intent.');
            }
        }

        $orderVersion = $order->getAttribute('pricing_snapshot_version');
        $intentVersion = $metadata['pricing_snapshot_version'] ?? null;
        if (filled($orderVersion) && filled($intentVersion) && (int) $orderVersion !== (int) $intentVersion) {
            throw new \RuntimeException('Versione dello snapshot non corrisponde al payment intent.');
        }

        $orderSnapshot = $order->getAttribute('pricing_snapshot');
        $intentSnapshot = $this->decodeSnapshotMetadata($metadata['quote_snapshot'] ?? null);
        if (is_array($orderSnapshot) && $orderSnapshot !== [] && is_array($intentSnapshot) && $orderSnapshot !== $intentSnapshot) {
            throw new \RuntimeException('Snapshot del preventivo non corrisponde al payment intent.');
        }

        // Verifica che l'importo corrisponda
        if ((int) $intent->amount !== $order->payableTotalCents()) {
            throw new \RuntimeException('Importo non corrisponde.');
        }

        // Determina il tipo di pagamento
        $type = $intent->payment_method
            ? $stripe->paymentMethods->retrieve($intent->payment_method)->type
            : $intent->payment_method_types[0] ?? 'unknown';

        return [
            'intent' => $intent,
            'payment_type' => $type,
        ];
    }

    // ── SetupIntent (card saving) ────────────────────────────────

    /**
     * Crea un SetupIntent per salvare una nuova carta.
     */
    public function createSetupIntent(User $user): array
    {
        $stripe = $this->client();
        $customerId = $this->createOrGetCustomer($user);

        $intent = $stripe->setupIntents->create([
            'customer' => $customerId,
            'payment_method_types' => ['card'],
        ]);

        return ['client_secret' => $intent->client_secret];
    }

    // ── Payment methods (cards) ──────────────────────────────────

    /**
     * Lista delle carte salvate dall'utente.
     */
    public function listPaymentMethods(User $user): array
    {
        if (! $user->customer_id) {
            return ['data' => [], 'default' => null];
        }

        $stripe = $this->client();

        $pmList = $stripe->paymentMethods->all([
            'customer' => $user->customer_id,
            'type' => 'card',
        ]);

        $customer = $stripe->customers->retrieve($user->customer_id);
        $defaultPm = $customer->invoice_settings->default_payment_method ?? null;

        $cards = array_map(function ($pm) use ($defaultPm) {
            return [
                'id' => $pm->id,
                'holder_name' => $pm->billing_details->name,
                'brand' => $pm->card->brand,
                'last4' => $pm->card->last4,
                'exp_month' => $pm->card->exp_month,
                'exp_year' => $pm->card->exp_year,
                'default' => $pm->id === $defaultPm,
            ];
        }, $pmList->data);

        usort($cards, fn ($a, $b) => $b['default'] <=> $a['default']);

        return ['data' => $cards, 'default' => $defaultPm];
    }

    /**
     * Imposta una carta come metodo di pagamento predefinito (attach + update).
     * Verifica che il metodo di pagamento non appartenga gia' a un altro cliente prima di collegarlo.
     */
    public function setDefaultPaymentMethod(User $user, string $paymentMethodId): array
    {
        $stripe = $this->client();

        // Verifica che il payment method non sia gia' collegato a un altro customer.
        // Se e' gia' collegato all'utente corrente, procediamo comunque (idempotente).
        $pm = $stripe->paymentMethods->retrieve($paymentMethodId);
        $pmCustomer = (string) ($pm->customer ?? '');
        if ($pmCustomer !== '' && $pmCustomer !== (string) $user->customer_id) {
            throw new \RuntimeException('Metodo di pagamento non autorizzato.');
        }

        $stripe->paymentMethods->attach($paymentMethodId, [
            'customer' => $user->customer_id,
        ]);

        $stripe->customers->update($user->customer_id, [
            'invoice_settings' => [
                'default_payment_method' => $paymentMethodId,
            ],
        ]);

        return ['success' => true, 'default' => $paymentMethodId];
    }

    /**
     * Cambia la carta predefinita (per carte gia' collegate al customer dell'utente).
     * Verifica che il payment method appartenga all'utente autenticato prima di impostarlo.
     */
    public function changeDefaultPaymentMethod(User $user, string $paymentMethodId): array
    {
        if (! $user->customer_id) {
            throw new \RuntimeException('Nessun profilo Stripe associato.');
        }

        $stripe = $this->client();

        // Verifica ownership: il PM deve essere collegato al customer di questo utente.
        $pm = $stripe->paymentMethods->retrieve($paymentMethodId);
        if ((string) ($pm->customer ?? '') !== (string) $user->customer_id) {
            throw new \RuntimeException('Metodo di pagamento non autorizzato.');
        }

        $customer = $stripe->customers->update($user->customer_id, [
            'invoice_settings' => [
                'default_payment_method' => $paymentMethodId,
            ],
        ]);

        return [
            'success' => true,
            'default' => $customer->invoice_settings->default_payment_method,
        ];
    }

    /**
     * Elimina una carta salvata (verifica ownership prima del detach).
     */
    public function deleteCard(User $user, string $paymentMethodId): void
    {
        $stripe = $this->client();

        $pm = $stripe->paymentMethods->retrieve($paymentMethodId);
        if ($pm->customer !== $user->customer_id) {
            throw new \RuntimeException('Non autorizzato.');
        }

        $stripe->paymentMethods->detach($paymentMethodId);
    }

    /**
     * Recupera i dettagli della carta predefinita.
     */
    public function getDefaultPaymentMethod(User $user): ?array
    {
        if (! $user->customer_id) {
            return null;
        }

        $stripe = $this->client();
        $customer = $stripe->customers->retrieve($user->customer_id);
        $defaultPm = $customer->invoice_settings->default_payment_method ?? null;

        if (! $defaultPm) {
            return null;
        }

        $pm = $stripe->paymentMethods->retrieve($defaultPm);

        return [
            'id' => $pm->id,
            'holder_name' => $pm->billing_details->name,
            'brand' => $pm->card->brand,
            'last4' => $pm->card->last4,
            'exp_month' => $pm->card->exp_month,
            'exp_year' => $pm->card->exp_year,
            'default' => true,
        ];
    }

    private function formatOrderScopedIdempotencyKey(Order $order, string $seed): string
    {
        $normalizedSeed = preg_replace('/[^A-Za-z0-9._-]+/', '-', trim($seed)) ?: 'attempt';

        return substr('order_'.$order->id.'_'.$normalizedSeed, 0, 255);
    }

    private function stripeRequestOptions(Order $order, ?string $idempotencyKey): array
    {
        if (filled($idempotencyKey)) {
            return ['idempotency_key' => $this->formatOrderScopedIdempotencyKey($order, $idempotencyKey)];
        }

        $submissionId = trim((string) $order->client_submission_id);
        if ($submissionId !== '') {
            return ['idempotency_key' => $this->formatOrderScopedIdempotencyKey($order, $submissionId)];
        }

        return ['idempotency_key' => $this->formatOrderScopedIdempotencyKey($order, 'fallback')];
    }

    public function resolveWalletTopUpIdempotencyKey(User $user, int $amountCents, string $paymentMethodId, ?string $idempotencyKey = null): string
    {
        $seed = trim((string) $idempotencyKey);

        if ($seed === '') {
            $normalizedPaymentMethod = preg_replace('/[^A-Za-z0-9._-]+/', '-', trim($paymentMethodId)) ?: 'payment-method';
            $seed = implode('_', [
                'amount'.$amountCents,
                'payment_method_'.$normalizedPaymentMethod,
            ]);
        }

        return $this->formatWalletScopedIdempotencyKey($user, $seed);
    }

    private function walletTopUpRequestOptions(User $user, int $amountCents, string $paymentMethodId, ?string $idempotencyKey): array
    {
        return [
            'idempotency_key' => $this->resolveWalletTopUpIdempotencyKey($user, $amountCents, $paymentMethodId, $idempotencyKey),
        ];
    }

    private function formatWalletScopedIdempotencyKey(User $user, string $seed): string
    {
        $normalizedSeed = preg_replace('/[^A-Za-z0-9._-]+/', '-', trim($seed)) ?: 'attempt';

        return substr('wallet_'.$user->id.'_'.$normalizedSeed, 0, 255);
    }

    private function orderMetadata(Order $order): array
    {
        $metadata = [
            'order_id' => (string) $order->id,
            'gross_subtotal_cents' => (string) $order->grossSubtotalCents(),
            'discount_amount_cents' => (string) $order->discountAmountCents(),
            'payable_total_cents' => (string) $order->payableTotalCents(),
        ];

        foreach (['client_submission_id', 'pricing_signature', 'pricing_snapshot_version'] as $field) {
            $value = $order->getAttribute($field);

            if (filled($value)) {
                $metadata[$field] = (string) $value;
            }
        }

        $snapshot = $order->getAttribute('pricing_snapshot');
        if (is_array($snapshot) && $snapshot !== []) {
            $encodedSnapshot = json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if ($encodedSnapshot !== false && strlen($encodedSnapshot) <= 500) {
                $metadata['quote_snapshot'] = $encodedSnapshot;
            }
        }

        return $metadata;
    }

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
}
