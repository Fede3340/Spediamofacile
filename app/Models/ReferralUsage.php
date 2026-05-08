<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $buyer_id
 * @property int $pro_user_id
 * @property string $referral_code
 * @property int $order_id
 * @property string $order_amount
 * @property string $discount_amount
 * @property string $commission_amount
 * @property string $status
 * @property-read User|null $buyer
 * @property-read User|null $proUser
 * @property-read Order|null $order
 */
class ReferralUsage extends Model
{
    /**
     * Campi compilabili dall'esterno.
     */
    protected $fillable = [
        'buyer_id',           // ID dell'acquirente che ha usato il codice
        'pro_user_id',        // ID del Partner Pro proprietario del codice
        'referral_code',      // Il codice referral utilizzato (es. "ABC123")
        'order_id',           // ID dell'ordine in cui e' stato usato
        'order_amount',       // Importo totale dell'ordine
        'discount_amount',    // Sconto applicato all'acquirente
        'commission_amount',  // Commissione guadagnata dal Partner Pro
        'status',             // Stato: "confirmed" (confermato) o "pending" (in attesa)
    ];

    // Converte automaticamente gli importi in numeri con 2 decimali
    protected $casts = [
        'order_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
    ];

    // Relazione: questo utilizzo e' stato fatto da UN acquirente
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    // Relazione: questo utilizzo riguarda il codice di UN Partner Pro
    public function proUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pro_user_id');
    }

    // Relazione: questo utilizzo e' collegato a UN ordine specifico
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
