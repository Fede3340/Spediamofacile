<?php
namespace App\Listeners;

use App\Models\Order;
use App\Events\OrderPaymentFailed;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class MarkOrderPaymentFailed
{
    public function __construct()
    {
        //
    }

    /**
     * Gestisce l'evento: aggiorna lo stato dell'ordine a "payment_failed".
     * L'ordine viene preso direttamente dall'evento ricevuto.
     */
    public function handle(OrderPaymentFailed $event): void
    {
        $event->order->update([
            'status' => Order::PAYMENT_FAILED
        ]);
    }
}
