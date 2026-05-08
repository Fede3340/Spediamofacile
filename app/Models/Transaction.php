<?php

namespace App\Models;

use App\Cart\MyMoney;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_id
 * @property MyMoney|int $total
 * @property string|null $ext_id
 * @property string $type
 * @property string $status
 * @property string|null $provider_status
 * @property string|null $failure_code
 * @property string|null $failure_message
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Order|null $order
 */
class Transaction extends Model
{
    use HasFactory;

    /**
     * Campi compilabili dall'esterno.
     */
    protected $fillable = [
        'order_id',         // ID dell'ordine a cui si riferisce questo pagamento
        'total',            // Importo totale del pagamento (in centesimi)
        'ext_id',           // ID esterno del pagamento su Stripe (per rintracciarlo)
        'type',             // Metodo di pagamento usato (card, bank_transfer, wallet)
        'status',           // Stato della transazione (es. "succeeded", "failed")
        'provider_status',  // Stato dettagliato dal provider di pagamento (Stripe)
        'failure_code',     // Codice errore se il pagamento e' fallito
        'failure_message',   // Messaggio di errore leggibile se il pagamento e' fallito
    ];

    /**
     * Traduce il tipo di metodo di pagamento in italiano.
     * Usato per mostrare all'utente "Carta" invece di "card".
     */
    public function getPaymentMethod(string $type): string
    {
        $methods = [
            'card' => 'Carta',
            'bank_transfer' => 'Bonifico',
            'wallet' => 'Portafoglio',
        ];

        return $methods[$type] ?? $type;
    }

    /**
     * Quando leggi il totale della transazione, viene automaticamente
     * convertito in un oggetto MyMoney per gestire la formattazione
     * dei prezzi (es. da centesimi a "12,50 EUR").
     */
    public function getTotalAttribute($total): MyMoney
    {
        return new MyMoney($total);
    }

    /**
     * Setter mutator: accetta sia int (centesimi grezzi) che MyMoney
     * e persiste sempre il valore in centesimi.
     */
    public function setTotalAttribute(MyMoney|int $value): void
    {
        $this->attributes['total'] = $value instanceof MyMoney ? (int) $value->amount() : $value;
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
