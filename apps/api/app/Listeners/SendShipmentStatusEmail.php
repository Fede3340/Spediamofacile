<?php
namespace App\Listeners;

use App\Events\ShipmentStatusChanged;
use App\Mail\ShipmentStatusUpdateMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendShipmentStatusEmail
{
    public function __construct()
    {
        //
    }

    /**
     * Gestisce l'evento: invia email di aggiornamento stato all'utente.
     *
     * Esclude le transizioni gia' coperte da altre email:
     * - pending -> processing: gestita da OrderConfirmationMail in MarkOrderProcessing
     * - processing -> in_transit: gestita da ShipmentLabelMail in GenerateBrtLabel
     *
     * Invia per transizioni rilevanti come:
     * - in_transit -> delivered (consegna avvenuta)
     * - in_transit -> in_giacenza (pacco in giacenza)
     * - qualsiasi -> completed (ordine completato)
     * - qualsiasi -> cancelled (ordine annullato)
     * - qualsiasi -> refunded (ordine rimborsato)
     */
    public function handle(ShipmentStatusChanged $event): void
    {
        // Non inviare se lo stato non e' effettivamente cambiato
        if ($event->oldStatus === $event->newStatus) {
            return;
        }

        // Transizioni gia' coperte da altre email specifiche
        $excludedTransitions = [
            'pending_processing',    // Coperta da OrderConfirmationMail
            'processing_in_transit', // Coperta da ShipmentLabelMail
        ];

        $transitionKey = $event->oldStatus . '_' . $event->newStatus;
        if (in_array($transitionKey, $excludedTransitions)) {
            return;
        }

        try {
            $event->order->loadMissing('user');

            if ($event->order->user && $event->order->user->email) {
                Mail::to($event->order->user->email)
                    ->queue(new ShipmentStatusUpdateMail(
                        $event->order,
                        $event->oldStatus,
                        $event->newStatus
                    ));

                Log::info('Shipment status email sent', [
                    'order_id' => $event->order->id,
                    'old_status' => $event->oldStatus,
                    'new_status' => $event->newStatus,
                ]);
            }
        } catch (\Exception $e) {
            // Se l'invio email fallisce, registra l'errore ma non blocca il flusso
            Log::error('Failed to send shipment status email', [
                'order_id' => $event->order->id,
                'old_status' => $event->oldStatus,
                'new_status' => $event->newStatus,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
