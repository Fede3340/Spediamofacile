<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StripeWebhookEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'stripe_event_id',
        'event_type',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    /**
     * Tenta di registrare un evento come processato.
     * Restituisce true se l'evento e' stato registrato (prima volta),
     * false se era gia' stato processato (duplicato).
     */
    public static function markAsProcessed(string $stripeEventId, string $eventType): bool
    {
        try {
            static::create([
                'stripe_event_id' => $stripeEventId,
                'event_type' => $eventType,
                'processed_at' => now(),
            ]);

            return true;
        } catch (\Illuminate\Database\QueryException $e) {
            // Codice 23000 = violazione vincolo UNIQUE (l'evento era gia' stato processato)
            if (str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), 'UNIQUE')) {
                return false;
            }

            throw $e;
        }
    }

    /**
     * Verifica se un evento e' gia' stato processato.
     */
    public static function wasAlreadyProcessed(string $stripeEventId): bool
    {
        return static::where('stripe_event_id', $stripeEventId)->exists();
    }

    /**
     * Pulisce eventi piu' vecchi del numero di giorni specificato.
     * Da chiamare periodicamente (es. scheduler giornaliero).
     */
    public static function pruneOlderThan(int $days = 7): int
    {
        return static::where('processed_at', '<', now()->subDays($days))->delete();
    }
}
