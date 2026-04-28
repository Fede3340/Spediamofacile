<?php
namespace App\Events;

use App\Models\Order;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class OrderPaid
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // L'ordine che e' stato pagato
    public $order;
    // I dati della transazione di pagamento (importo, metodo, stato...)
    public $transaction;


    /**
     * Crea una nuova istanza dell'evento.
     * Riceve l'ordine pagato e la transazione di pagamento.
     */
    public function __construct(Order $order, $transaction)
    {
        $this->order = $order;
        $this->transaction = $transaction;
    }
}
