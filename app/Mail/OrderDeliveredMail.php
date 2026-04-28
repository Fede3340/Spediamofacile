<?php

/**
 * EMAIL: ORDINE CONSEGNATO (M8)
 *
 * Inviata quando la spedizione risulta consegnata (webhook BRT "delivered").
 * Contiene data/ora consegna, CTA recensione + nuova spedizione, link reclami.
 *
 * DATI RICHIESTI:
 *   - Order $order   con campo delivered_at (Carbon|string) — fallback a now()
 *
 * USO TIPICO:
 *   Mail::to($order->user->email)->send(new OrderDeliveredMail($order));
 *
 * TEMPLATE:
 *   resources/views/emails/order-delivered.blade.php
 */

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class OrderDeliveredMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function envelope(): Envelope
    {
        $code = $this->order->code ?? ('SF-' . str_pad((string) $this->order->id, 6, '0', STR_PAD_LEFT));

        return new Envelope(
            subject: 'Consegnato! Ordine #' . $code,
        );
    }

    public function headers(): Headers
    {
        $unsubscribeUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/')
            . '/account/notifiche?unsubscribe=1';

        return new Headers(
            text: [
                'List-Unsubscribe' => '<' . $unsubscribeUrl . '>',
                'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            ],
        );
    }

    public function content(): Content
    {
        $this->order->loadMissing(['user']);

        return new Content(
            view: 'emails.order-delivered',
            with: [
                'order' => $this->order,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
