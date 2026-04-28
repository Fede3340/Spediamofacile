<?php

namespace App\Mail;

use App\Models\ReferralUsage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReferralUsedMail extends Mailable
{
    use Queueable, SerializesModels;

    public ReferralUsage $usage;

    public function __construct(ReferralUsage $usage)
    {
        $this->usage = $usage;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nuovo utilizzo del tuo codice referral',
        );
    }

    /**
     * Aggiunge header List-Unsubscribe per conformita' GDPR.
     */
    public function headers(): \Illuminate\Mail\Mailables\Headers
    {
        $unsubscribeUrl = config('app.frontend_url') . '/account/notifiche?unsubscribe=1';

        return new \Illuminate\Mail\Mailables\Headers(
            text: [
                'List-Unsubscribe' => '<' . $unsubscribeUrl . '>',
                'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.referral-used',
        );
    }
}
