<?php

/**
 * EMAIL: ORDINE IN VIAGGIO (M8)
 *
 * Inviata quando il corriere prende in carico la spedizione.
 * Contiene tracking number, link tracking, ETA prevista.
 *
 * DATI RICHIESTI:
 *   - Order $order   con campi brt_tracking_number, brt_tracking_url
 *                    (opzionale) estimated_delivery_at
 *
 * USO TIPICO:
 *   Mail::to($order->user->email)->send(new OrderShippedMail($order));
 *
 * TRIGGER TIPICO:
 *   - Listener su evento ShipmentPickedUp / webhook BRT con stato "in_transit"
 *
 * TEMPLATE:
 *   resources/views/emails/order-shipped.blade.php
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

class OrderShippedMail extends Mailable implements ShouldQueue
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
            subject: 'Il tuo ordine #' . $code . ' è in viaggio',
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
            view: 'emails.order-shipped',
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
