<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\StripeCheckoutSession;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class InertiaCheckoutController extends Controller
{
    public function carrello(Request $request): Response
    {
        return Inertia::render('Checkout/Carrello', [
            'items' => [],
            'total' => '0,00 €',
        ]);
    }

    /**
     * Redirect a Stripe Checkout hosted (PCI scope ridotto: Stripe gestisce
     * UI carta + 3DS, noi non vediamo PAN). Il return arriva via webhook.
     */
    public function startStripeCheckout(Request $request, StripeCheckoutSession $stripe): RedirectResponse
    {
        $orderId = (int) $request->input('order_id');
        $order = Order::where('id', $orderId)->where('user_id', $request->user()->id)->firstOrFail();

        $url = $stripe->create(
            $order,
            url('/checkout/return'),
            url('/checkout/cancel'),
        );

        return redirect()->away($url);
    }

    /**
     * Return da Stripe-hosted: verifica session_id, mostra success.
     * Il pagamento effettivo è confermato dal webhook (idempotency).
     */
    public function return(Request $request, StripeCheckoutSession $stripe): Response
    {
        $sessionId = $request->query('session_id');
        if (! $sessionId) return Inertia::render('Checkout/Success', ['order' => ['id' => 0, 'total' => '—']]);

        $session = $stripe->retrieve($sessionId);
        $orderId = (int) ($session->client_reference_id ?? 0);
        $order = $orderId ? Order::find($orderId) : null;

        return Inertia::render('Checkout/Success', [
            'order' => $order ? [
                'id' => $order->id,
                'total' => number_format($order->payable_total_cents / 100, 2, ',', '.') . ' €',
                'tracking' => $order->tracking_number,
            ] : ['id' => 0, 'total' => '—', 'tracking' => null],
        ]);
    }

    public function cancel(): Response
    {
        return Inertia::render('Checkout/Carrello', [
            'items' => [],
            'total' => '0,00 €',
            'flash' => ['info' => 'Pagamento annullato. Puoi riprovare quando vuoi.'],
        ]);
    }

    public function success(Request $request): Response
    {
        $orderId = (int) $request->query('order_id', 0);
        $order = $orderId ? Order::find($orderId) : null;
        return Inertia::render('Checkout/Success', [
            'order' => $order ? [
                'id' => $order->id,
                'total' => number_format($order->payable_total_cents / 100, 2, ',', '.') . ' €',
                'tracking' => $order->tracking_number,
            ] : ['id' => 0, 'total' => '—', 'tracking' => null],
        ]);
    }
}
