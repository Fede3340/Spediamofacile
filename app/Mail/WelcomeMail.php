<?php

/**
 * EMAIL: BENVENUTO
 *
 * Inviata immediatamente dopo la registrazione di un nuovo utente.
 * Contiene messaggio di benvenuto, vantaggi sintetici e CTA verso l'area account.
 *
 * DATI RICHIESTI:
 *   - User $user           Utente appena registrato (deve avere ->name e ->email)
 *   - string $accountUrl   (opzionale) URL alla dashboard account; default: frontend_url/account
 *
 * USO TIPICO:
 *   Mail::to($user->email)->send(new WelcomeMail($user));
 *
 * TEMPLATE:
 *   resources/views/emails/welcome.blade.php
 */

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public User $user;

    public string $accountUrl;

    public function __construct(User $user, ?string $accountUrl = null)
    {
        $this->user = $user;
        $this->accountUrl = $accountUrl
            ?? rtrim((string) config('app.frontend_url', config('app.url')), '/') . '/account';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Benvenuto in SpedizioneFacile!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome',
            with: [
                'name' => $this->user->name ?? 'utente',
                'url' => $this->accountUrl,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
