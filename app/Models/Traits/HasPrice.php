<?php
namespace App\Models\Traits;
use App\Cart\MyMoney;
use Illuminate\Database\Eloquent\Builder;

trait HasPrice {

    // Converte automaticamente il prezzo in un oggetto MyMoney
    // quando viene letto dal database
    public function getPriceAttribute($value) {
        return new MyMoney($value);
    }

    // Restituisce il prezzo gia' formattato come stringa leggibile
    // Esempio: "12,50 EUR"
    public function getFormattedPriceAttribute() {
        return $this->price->formatted();
    }
}
