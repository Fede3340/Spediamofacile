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

class OrderPaymentFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // L'ordine il cui pagamento e' fallito
    public $order;

    /**
     * Crea una nuova istanza dell'evento.
     * Riceve l'ordine con il pagamento fallito.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
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
