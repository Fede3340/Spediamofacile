<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletMovement extends Model
{
    use HasFactory;

    /**
     * Campi compilabili dall'esterno.
     */
    protected $fillable = [
        'user_id',          // ID dell'utente proprietario del portafoglio
        'type',             // Tipo: "credit" (entrata/ricarica) o "debit" (uscita/pagamento)
        'amount',           // Importo del movimento (es. 10.50)
        'currency',         // Valuta (es. "EUR")
        'status',           // Stato: "confirmed" (confermato) o "pending" (in attesa)
        'idempotency_key',  // Chiave univoca per evitare movimenti duplicati
        'reference',        // Riferimento (es. ID dell'ordine pagato)
        'description',      // Descrizione leggibile del movimento (es. "Ricarica portafoglio")
        'source',           // Fonte del movimento (es. "stripe", "referral", "admin")
    ];

    /**
     * Campi nascosti nelle risposte JSON.
     * La chiave di idempotenza e' un dato interno del server e non deve
     * essere esposta al frontend.
     */
    protected $hidden = [
        'idempotency_key',
    ];

    // Converte automaticamente l'importo in un numero con 2 decimali
    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // Relazione: ogni movimento appartiene a UN utente
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
