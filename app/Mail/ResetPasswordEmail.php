<?php

/**
 * EMAIL: RECUPERO PASSWORD
 *
 * Questa classe rappresenta l'email che viene inviata a un utente
 * quando richiede di recuperare la sua password dimenticata.
 *
 * L'email contiene un token (codice segreto temporaneo) e l'email dell'utente.
 * Con questi dati, il frontend costruisce il link per la pagina dove
 * l'utente puo' scegliere una nuova password.
 *
 * Il contenuto dell'email e' definito nel template: resources/views/emails/passwordReset.blade.php
 */

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordEmail extends Mailable
{
    use Queueable, SerializesModels;

    // Token segreto per il recupero password (scade dopo un certo tempo)
    public $token;
    // Email dell'utente che vuole recuperare la password
    public $email;

    /**
     * Crea l'email con il token di recupero e l'email dell'utente.
     */
    public function __construct($token, $email)
    {
        $this->token = $token;
        $this->email = $email;
    }

    /**
     * Configura l'oggetto (subject) dell'email.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recupero password - SpediamoFacile',
        );
    }

    /**
     * Definisce il contenuto dell'email.
     * Usa il template Markdown "passwordReset" e gli passa token ed email.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.passwordReset',
            with: [
                'token' => $this->token,
                'email' => $this->email,
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
