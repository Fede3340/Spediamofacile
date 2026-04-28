<?php

/**
 * EMAIL: ETICHETTA DI SPEDIZIONE
 *
 * Questa classe rappresenta l'email che viene inviata all'utente
 * con l'etichetta di spedizione BRT in allegato (file PDF).
 *
 * L'utente deve stampare questa etichetta e attaccarla sul pacco
 * prima di consegnarlo al corriere BRT.
 *
 * L'email contiene:
 * - L'oggetto con il numero dell'ordine
 * - Il contenuto definito nel template: resources/views/emails/shipment-label.blade.php
 * - L'etichetta BRT come allegato PDF (se disponibile)
 */

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class ShipmentLabelMail extends Mailable
{
    use Queueable, SerializesModels;

    // L'ordine di cui inviare l'etichetta
    public Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Configura l'oggetto dell'email.
     * Include il numero dell'ordine per facile identificazione.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'SpediamoFacile - Etichetta spedizione ordine #' . $this->order->id,
        );
    }

    /**
     * Definisce il contenuto dell'email usando il template Markdown "shipment-label".
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.shipment-label',
        );
    }

    /**
     * Allega l'etichetta BRT come file PDF.
     * L'etichetta e' salvata nel database come stringa base64
     * e viene convertita in un vero file PDF per l'allegato.
     */
    public function attachments(): array
    {
        $attachments = [];

        if ($this->order->brt_label_base64) {
            $attachments[] = Attachment::fromData(
                fn () => base64_decode($this->order->brt_label_base64, true) ?: '',
                'etichetta-brt-' . $this->order->id . '.pdf'
            )->withMime('application/pdf');
        }

        return $attachments;
    }
}
