<?php
namespace App\Jobs;

use App\Mail\VerificationEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendVerificationEmailJob
{
    protected $user;

    /**
     * Crea il job con l'utente a cui inviare l'email.
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Esegue il job: invia l'email con il codice di verifica a 6 cifre.
     */
    public function handle(): void
    {
        $code = $this->user->verification_code;

        if (!$code) {
            Log::warning('Tentativo di invio email di verifica senza codice.', [
                'user_id' => $this->user->id,
            ]);
            return;
        }

        Mail::to($this->user->email)->send(
            new VerificationEmail($code)
        );
    }

    public static function dispatchSync($user): void
    {
        (new self($user))->handle();
    }
}
