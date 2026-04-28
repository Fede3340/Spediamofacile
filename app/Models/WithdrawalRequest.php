<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithdrawalRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_COMPLETED = 'completed';

    /**
     * Campi compilabili dall'esterno.
     */
    protected $fillable = [
        'user_id',       // ID dell'utente Pro che richiede il prelievo
        'amount',        // Importo richiesto (es. 50.00)
        'currency',      // Valuta (es. "EUR")
        'status',        // Stato: "pending", "approved", "rejected", "completed"
        'admin_notes',   // Note dell'amministratore (es. motivo del rifiuto)
        'reviewed_at',   // Data e ora della revisione da parte dell'admin
        'reviewed_by',   // ID dell'admin che ha revisionato la richiesta
    ];

    // Conversioni automatiche dei tipi
    protected $casts = [
        'amount' => 'decimal:2',      // Importo con 2 decimali
        'reviewed_at' => 'datetime',   // Data di revisione come oggetto data
    ];

    // Relazione: la richiesta appartiene a UN utente (il Partner Pro)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Relazione: la richiesta e' stata revisionata da UN admin
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
