<?php

/**
 * EMAIL: VERIFICA INDIRIZZO EMAIL
 *
 * Questa classe rappresenta l'email che viene inviata a un utente
 * dopo la registrazione per verificare che il suo indirizzo email sia valido.
 *
 * L'email contiene un codice a 6 cifre che l'utente deve inserire
 * nella pagina di login per attivare il suo account.
 *
 * Il contenuto dell'email e' definito nel template: resources/views/emails/verificationEmail.blade.php
 */

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerificationEmail extends Mailable
{
    use Queueable, SerializesModels;

    // Il codice di verifica a 6 cifre
    public $code;

    /**
     * Crea l'email con il codice di verifica.
     */
    public function __construct(string $code)
    {
        $this->code = $code;
    }

    /**
     * Configura l'oggetto (subject) dell'email.
     * E' quello che l'utente vede nella sua casella di posta come titolo.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Il tuo codice di verifica SpediamoFacile',
        );
    }

    /**
     * Definisce il contenuto dell'email.
     * Usa il template Markdown "verificationEmail" e gli passa il codice di verifica.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.verificationEmail',
            with: [
                'code' => $this->code,
            ],
        );
    }

    /**
     * Allegati dell'email (nessuno in questo caso).
     */
    public function attachments(): array
    {
        return [];
    }
}
