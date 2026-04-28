<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class OrderAwaitingBankTransferMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Order $order;

    public array $bankTransferDetails;

    public function __construct(Order $order)
    {
        $this->order = $order;
        $this->bankTransferDetails = $this->resolveBankTransferDetails();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Istruzioni bonifico ordine #'.$this->order->id.' - SpediamoFacile',
        );
    }

    public function content(): Content
    {
        $this->order->loadMissing([
            'packages.originAddress',
            'packages.destinationAddress',
            'user',
        ]);

        return new Content(
            view: 'emails.order-awaiting-bank-transfer',
        );
    }

    public function headers(): Headers
    {
        $unsubscribeUrl = config('app.frontend_url').'/account/notifiche?unsubscribe=1';

        return new Headers(
            text: [
                'List-Unsubscribe' => '<'.$unsubscribeUrl.'>',
                'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }

    private function resolveBankTransferDetails(): array
    {
        $beneficiary = trim((string) Setting::get('bank_transfer_beneficiary', ''));
        $bankName = trim((string) Setting::get('bank_transfer_bank_name', ''));
        $iban = strtoupper(str_replace(' ', '', trim((string) Setting::get('bank_transfer_iban', ''))));
        $bic = strtoupper(str_replace(' ', '', trim((string) Setting::get('bank_transfer_bic', ''))));

        return [
            'beneficiary' => $beneficiary,
            'bank_name' => $bankName,
            'iban' => $iban,
            'bic' => $bic,
            'reference' => 'Ordine #'.$this->order->id,
            'support_email' => config('mail.from.address') ?: 'assistenza@spediamofacile.it',
        ];
    }
}
