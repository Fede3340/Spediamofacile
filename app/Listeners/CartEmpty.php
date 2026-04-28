<?php
namespace App\Listeners;

use App\Cart\Cart;
use App\Events\OrderCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class CartEmpty
{
    // Il carrello dell'utente (viene iniettato automaticamente da Laravel)
    protected $cart;

    /**
     * Il costruttore riceve il carrello come parametro.
     * Laravel lo fornisce automaticamente grazie al sistema di "dependency injection".
     */
    public function __construct(Cart $cart)
    {
        $this->cart = $cart;
    }

    /**
     * Gestisce l'evento: svuota il carrello dell'utente.
     * Viene chiamato automaticamente quando l'evento OrderCreated viene lanciato.
     */
    public function handle(OrderCreated $event): void
    {
        $this->cart->empty();
    }
}
