<?php

namespace App\Services;

use App\Models\Order;
use Stripe\Checkout\Session;
use Stripe\Stripe;

/**
 * Stripe Checkout Session (hosted) — riduce PCI scope.
 * Sostituisce Stripe Elements custom: redirect a Stripe-hosted checkout,
 * ritorno via webhook (idempotency invariata in StripeWebhookController).
 */
class StripeCheckoutSession
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Crea una Checkout Session per un Order.
     * @return string URL hosted Stripe a cui fare redirect
     */
    public function create(Order $order, string $successUrl, string $cancelUrl): string
    {
        $session = Session::create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $order->payable_total_cents,
                    'product_data' => [
                        'name' => "Spedizione SpediamoFacile #{$order->id}",
                        'description' => 'Servizio di spedizione BRT',
                    ],
                ],
                'quantity' => 1,
            ]],
            'client_reference_id' => (string) $order->id,
            'customer_email' => $order->user?->email,
            'success_url' => $successUrl . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
            ],
            'idempotency_key' => 'order_' . $order->id . '_' . $order->updated_at?->timestamp,
        ]);

        return $session->url;
    }

    public function retrieve(string $sessionId): Session
    {
        return Session::retrieve($sessionId);
    }
}
