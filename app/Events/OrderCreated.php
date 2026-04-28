<?php
namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class OrderCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // L'ordine appena creato - viene passato a tutti i listener
    public $order;

    /**
     * Crea una nuova istanza dell'evento.
     * Riceve l'ordine appena creato come parametro.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Canali su cui l'evento potrebbe essere trasmesso in tempo reale.
     * (Attualmente non usato per broadcasting, ma predisposto per il futuro)
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
