<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

class BrtWebhookEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'fingerprint',
        'parcel_id',
        'status',
        'event_timestamp',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    /**
     * Calcola il fingerprint univoco di un evento BRT.
     */
    public static function fingerprintFor(string $parcelId, string $status, string $timestamp): string
    {
        return hash('sha256', $parcelId . '|' . $status . '|' . $timestamp);
    }

    /**
     * Tenta di registrare un evento come processato.
     * Ritorna true se e' stato registrato (prima volta), false se duplicato.
     */
    public static function markAsProcessed(string $parcelId, string $status, string $timestamp): bool
    {
        try {
            static::create([
                'fingerprint' => static::fingerprintFor($parcelId, $status, $timestamp),
                'parcel_id' => $parcelId,
                'status' => $status,
                'event_timestamp' => $timestamp,
                'processed_at' => now(),
            ]);

            return true;
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), 'UNIQUE')) {
                return false;
            }

            throw $e;
        }
    }

    /**
     * Pulisce eventi piu' vecchi di N giorni (da chiamare nello scheduler).
     */
    public static function pruneOlderThan(int $days = 14): int
    {
        return static::where('processed_at', '<', now()->subDays($days))->delete();
    }
}
