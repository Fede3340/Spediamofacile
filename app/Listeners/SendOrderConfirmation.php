<?php
namespace App\Listeners;

use App\Events\OrderPaid;
use App\Mail\OrderConfirmationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOrderConfirmation implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Number of times the queued listener may be attempted.
     */
    public int $tries = 3;

    public function __construct()
    {
        //
    }

    /**
     * Gestisce l'evento: invia email di conferma ordine all'utente.
     *
     * Carica la relazione utente se non gia' presente e invia
     * l'email OrderConfirmationMail con i dettagli dell'ordine.
     */
    public function handle(OrderPaid $event): void
    {
        try {
            $event->order->loadMissing('user');

            if ($event->order->user && $event->order->user->email) {
                Mail::to($event->order->user->email)
                    ->queue(new OrderConfirmationMail($event->order));

                Log::info('Order confirmation email sent', [
                    'order_id' => $event->order->id,
                    'user_id' => $event->order->user->id,
                ]);
            }
        } catch (\Exception $e) {
            // Se l'invio email fallisce, registra l'errore ma non blocca il flusso
            Log::error('Failed to send order confirmation email', [
                'order_id' => $event->order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
