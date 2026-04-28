<?php
namespace App\Listeners;

use App\Models\Order;
use App\Events\OrderPaid;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class MarkOrderProcessing
{
    public function __construct()
    {
        //
    }

    /**
     * Gestisce l'evento: aggiorna lo stato dell'ordine a "processing" (in lavorazione).
     */
    public function handle(OrderPaid $event): void
    {
        $event->order->refresh();

        if (in_array($event->order->status, [Order::IN_TRANSIT, Order::LABEL_GENERATED], true)) {
            return;
        }

        $event->order->update([
            'status' => Order::PROCESSING
        ]);
    }
}
