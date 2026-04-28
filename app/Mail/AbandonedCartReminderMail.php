<?php

/**
 * EMAIL: REMINDER CARRELLO ABBANDONATO (F15)
 *
 * Inviata agli utenti con carrello non completato da 24h.
 * Include un CTA che riporta l'utente al preventivo con token di ripresa,
 * cosi' il frontend puo' ricostruire la selezione senza doverla rifare.
 */

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class AbandonedCartReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public User $user;

    /** @var string Token opaco per riprendere il carrello dal frontend. */
    public string $resumeToken;

    /** @var int Numero di pacchi rimasti nel carrello. */
    public int $itemCount;

    public function __construct(User $user, string $resumeToken, int $itemCount)
    {
        $this->user = $user;
        $this->resumeToken = $resumeToken;
        $this->itemCount = $itemCount;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Hai lasciato qualcosa nel carrello - SpediamoFacile',
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
        $frontend = rtrim((string) config('app.frontend_url', config('app.url')), '/');
        $resumeUrl = $frontend . '/preventivo?resume=' . urlencode($this->resumeToken);

        return new Content(
            view: 'emails.abandoned-cart',
            with: [
                'user'      => $this->user,
                'itemCount' => $this->itemCount,
                'resumeUrl' => $resumeUrl,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
