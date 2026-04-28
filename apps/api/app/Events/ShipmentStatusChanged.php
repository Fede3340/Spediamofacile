<?php
namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class ShipmentStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // L'ordine il cui stato e' cambiato
    public $order;
    // Lo stato precedente (es. "processing")
    public $oldStatus;
    // Il nuovo stato (es. "in_transit")
    public $newStatus;

    /**
     * Crea una nuova istanza dell'evento.
     * Riceve l'ordine, lo stato vecchio e quello nuovo.
     */
    public function __construct(Order $order, string $oldStatus, string $newStatus)
    {
        $this->order = $order;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    /**
     * Canali su cui l'evento potrebbe essere trasmesso in tempo reale.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
