<?php

/**
 * JOB: Invio email reminder carrello abbandonato (F15).
 *
 * Inviato per singolo utente. Il comando "carts:send-abandoned-reminders"
 * seleziona gli utenti candidati e dispatcha un job per ciascuno in coda.
 *
 * Retry: 2 tentativi (failure email non dev'essere bloccante).
 * Idempotenza: il comando aggiorna abandoned_cart_sent_at dopo aver
 * dispatchato, quindi un retry manuale non causa doppie spedizioni.
 */

namespace App\Jobs;

use App\Mail\AbandonedCartReminderMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAbandonedCartEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 30;

    public int $userId;
    public string $resumeToken;
    public int $itemCount;

    public function __construct(int $userId, string $resumeToken, int $itemCount)
    {
        $this->userId = $userId;
        $this->resumeToken = $resumeToken;
        $this->itemCount = $itemCount;
    }

    /**
     * Invia l'email usando la Mail dedicata.
     * Se l'utente non e' piu' raggiungibile, logga e scarta il job.
     */
    public function handle(): void
    {
        $user = User::find($this->userId);

        if (! $user || empty($user->email)) {
            Log::info('[AbandonedCart] skip: utente non trovato o senza email', [
                'user_id' => $this->userId,
            ]);
            return;
        }

        Mail::to($user->email)->send(
            new AbandonedCartReminderMail($user, $this->resumeToken, $this->itemCount)
        );
    }
}
