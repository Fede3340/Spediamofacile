<?php
namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    /**
     * Campi compilabili dall'esterno.
     */
    protected $fillable = [
        'user_id',       // ID dell'utente che fa la richiesta
        'company_name',  // Nome dell'azienda
        'vat_number',    // Partita IVA dell'azienda
        'message',       // Messaggio/motivazione dell'utente
        'status',        // Stato: "pending" (in attesa), "approved" (approvata), "rejected" (rifiutata)
        'reviewed_at',   // Data e ora della revisione da parte dell'admin
    ];

    // Converte automaticamente la data di revisione in un oggetto data
    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    // Relazione: la richiesta appartiene a UN utente
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
