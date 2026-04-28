<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShipmentDocumentsMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'SpediamoFacile - Documenti spedizione ordine #'.$this->order->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.shipment-documents',
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        if (! empty($this->order->brt_label_base64)) {
            $attachments[] = Attachment::fromData(
                fn () => base64_decode($this->order->brt_label_base64),
                'etichetta-brt-'.$this->order->id.'.pdf'
            )->withMime('application/pdf');
        }

        if (! empty($this->order->bordero_document_base64)) {
            $borderoMime = $this->order->bordero_document_mime ?: 'application/pdf';
            $defaultFilename = $borderoMime === 'application/pdf'
                ? ('bordero-'.$this->order->id.'.pdf')
                : ('bordero-'.$this->order->id.'.txt');

            $attachments[] = Attachment::fromData(
                fn () => base64_decode($this->order->bordero_document_base64),
                $this->order->bordero_document_filename ?: $defaultFilename
            )->withMime($borderoMime);
        }

        return $attachments;
    }
}
