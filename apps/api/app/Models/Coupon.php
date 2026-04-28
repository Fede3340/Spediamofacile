<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class Coupon extends Model
{
    use HasFactory;

    /**
     * Campi compilabili dall'esterno.
     */
    protected $fillable = [
        'code',                          // Codice del coupon (es. "SCONTO10", "WELCOME20")
        'stripe_connected_account_id',   // ID dell'account Stripe collegato
        'percentage',                    // Percentuale di sconto (es. 10 = 10% di sconto)
        'active',                        // Se il coupon e' attivo e utilizzabile
        'expires_at',                    // Data di scadenza (null = mai)
        'max_uses',                      // Limite globale di utilizzi (null = illimitato)
        'max_uses_per_user',             // Limite per singolo utente (null = illimitato)
        'uses_count',                    // Contatore globale utilizzi effettuati
    ];

    /**
     * Cast dei campi per conversione automatica.
     */
    protected $casts = [
        'active' => 'boolean',
        'expires_at' => 'datetime',
        'max_uses' => 'integer',
        'max_uses_per_user' => 'integer',
        'uses_count' => 'integer',
    ];

    // ── Relazioni ─────────────────────────────────────────────

    /**
     * Utenti che hanno usato questo coupon (pivot coupon_user).
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'coupon_user')
            ->withPivot('order_id', 'used_at');
    }

    // ── Query scopes ──────────────────────────────────────────

    /**
     * Scope: solo coupon utilizzabili (attivo, non scaduto, sotto i limiti).
     */
    public function scopeUsable($query)
    {
        return $query
            ->where('active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('max_uses')
                  ->orWhereColumn('uses_count', '<', 'max_uses');
            });
    }

    // ── Metodi di istanza ─────────────────────────────────────

    /**
     * Verifica se il coupon e' ancora valido per un determinato utente.
     * Ritorna [bool $valido, string|null $motivoErrore].
     */
    public function validateForUser(?int $userId): array
    {
        if (! $this->active) {
            return [false, 'Coupon non attivo.'];
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return [false, 'Coupon scaduto.'];
        }

        if ($this->max_uses !== null && $this->uses_count >= $this->max_uses) {
            return [false, 'Coupon esaurito.'];
        }

        if ($userId && $this->max_uses_per_user !== null) {
            $userUsageCount = $this->users()
                ->where('user_id', $userId)
                ->count();

            if ($userUsageCount >= $this->max_uses_per_user) {
                return [false, 'Hai gia\' usato questo coupon il numero massimo di volte.'];
            }
        }

        return [true, null];
    }

    /**
     * Registra un utilizzo del coupon (incrementa contatore + pivot).
     * Da chiamare DOPO il pagamento, non durante il calcolo.
     *
     * SEC-NEW-07: race-safety.
     * Wrappato in DB::transaction + lockForUpdate per evitare che richieste
     * concorrenti dello stesso utente (o di piu' utenti su un coupon a uso
     * limitato) superino i limiti configurati.
     *
     * Ritorna true se l'utilizzo e' stato registrato, false se i limiti
     * (max_uses globale o max_uses_per_user) erano gia' raggiunti.
     */
    public function recordUsage(int $userId, ?int $orderId = null): bool
    {
        return (bool) DB::transaction(function () use ($userId, $orderId) {
            // Lock pessimistico sulla riga del coupon: le query concorrenti
            // attendono fino al commit di questa transazione.
            /** @var self|null $coupon */
            $coupon = self::query()->lockForUpdate()->find($this->id);

            if (! $coupon) {
                return false;
            }

            if ($orderId !== null) {
                $alreadyRecordedForOrder = $coupon->users()
                    ->wherePivot('order_id', $orderId)
                    ->exists();

                if ($alreadyRecordedForOrder) {
                    return true;
                }
            }

            // Limite globale
            if ($coupon->max_uses !== null && $coupon->uses_count >= $coupon->max_uses) {
                return false;
            }

            // Limite per utente (conta gli utilizzi gia' registrati nel pivot)
            if ($coupon->max_uses_per_user !== null) {
                $userUses = $coupon->users()
                    ->where('coupon_user.user_id', $userId)
                    ->count();

                if ($userUses >= $coupon->max_uses_per_user) {
                    return false;
                }
            }

            // Registra: pivot + contatore globale (entrambi dentro il lock)
            $coupon->users()->attach($userId, [
                'order_id' => $orderId,
                'used_at' => now(),
            ]);

            $coupon->increment('uses_count');

            // Sincronizza lo stato dell'istanza chiamante
            $this->uses_count = $coupon->uses_count;

            return true;
        });
    }
}
