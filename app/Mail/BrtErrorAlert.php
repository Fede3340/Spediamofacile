<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BrtErrorAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public string $errorMessage
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Errore BRT - Ordine #{$this->order->id}",
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtml(),
        );
    }

    private function buildHtml(): string
    {
        $orderId = $this->order->id;
        /** @var User|null $user */
        $user = $this->order->user;
        $userName = $user ? "{$user->name} {$user->surname}" : 'N/D';
        $userEmail = $user->email ?? 'N/D';
        $error = e($this->errorMessage);
        $date = now()->format('d/m/Y H:i');

        return <<<HTML
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <div style="background: #dc2626; padding: 20px; border-radius: 12px 12px 0 0;">
                <h1 style="color: #fff; margin: 0; font-size: 20px;">Errore generazione etichetta BRT</h1>
            </div>
            <div style="background: #fff; padding: 24px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 12px 12px;">
                <p style="margin: 0 0 16px; color: #374151;">La generazione automatica dell'etichetta BRT e' fallita dopo 3 tentativi.</p>
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 16px;">
                    <tr><td style="padding: 8px 0; color: #6b7280; width: 140px;">Ordine</td><td style="padding: 8px 0; font-weight: bold; color: #111827;">#$orderId</td></tr>
                    <tr><td style="padding: 8px 0; color: #6b7280;">Utente</td><td style="padding: 8px 0; color: #111827;">$userName ($userEmail)</td></tr>
                    <tr><td style="padding: 8px 0; color: #6b7280;">Data</td><td style="padding: 8px 0; color: #111827;">$date</td></tr>
                </table>
                <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 16px; margin-bottom: 16px;">
                    <p style="margin: 0; color: #991b1b; font-weight: bold; font-size: 13px;">Errore:</p>
                    <p style="margin: 8px 0 0; color: #b91c1c; font-size: 14px;">$error</p>
                </div>
                <p style="margin: 0; color: #6b7280; font-size: 13px;">Accedi al pannello admin per gestire l'ordine manualmente.</p>
            </div>
        </div>
        HTML;
    }
}
