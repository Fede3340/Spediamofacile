<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    /**
     * Campi compilabili dall'esterno.
     */
    protected $fillable = ['key', 'value'];

    /**
     * Chiavi che contengono segreti e devono essere cifrate in DB.
     * Qualsiasi chiave in questa lista viene salvata con Crypt::encryptString()
     * e letta con Crypt::decryptString(), con fallback su plaintext per
     * backward-compatibility con valori inseriti prima della cifratura.
     */
    protected static array $encryptedKeys = [
        'stripe_secret',
        'stripe_secret_key',
        'stripe_webhook_secret',
        'brt_password',
    ];

    /**
     * Legge il valore di un'impostazione dal database.
     * Se l'impostazione non esiste, restituisce il valore predefinito.
     * Per le chiavi in $encryptedKeys, decifra automaticamente con fallback su plaintext.
     *
     * Esempio: Setting::get('stripe_test_mode', 'false')
     */
    public static function get(string $key, $default = null): ?string
    {
        // Cache 1h: Setting::get() era chiamato N volte per request (Stripe, BRT, mail config).
        // Cache key namespace "setting:" per evitare collisioni con altre chiavi.
        $cacheKey = 'setting:'.$key;

        $cached = Cache::remember($cacheKey, 3600, function () use ($key) {
            $setting = static::where('key', $key)->first();
            if (! $setting) {
                return null;
            }

            return ['value' => $setting->value, 'encrypted' => in_array($key, static::$encryptedKeys, true)];
        });

        if ($cached === null) {
            return $default;
        }

        if ($cached['encrypted'] && $cached['value']) {
            try {
                return Crypt::decryptString($cached['value']);
            } catch (DecryptException $e) {
                // Fallback: valore ancora in plaintext (pre-cifratura).
                // Verra' cifrato al prossimo set().
                return $cached['value'];
            }
        }

        return $cached['value'];
    }

    /**
     * Salva o aggiorna un'impostazione nel database.
     * Se la chiave esiste gia', aggiorna il valore. Altrimenti la crea.
     * Per le chiavi in $encryptedKeys, cifra automaticamente il valore.
     *
     * Esempio: Setting::set('stripe_test_mode', 'true')
     */
    public static function set(string $key, ?string $value): void
    {
        $storeValue = $value;

        if (in_array($key, static::$encryptedKeys, true) && $value) {
            $storeValue = Crypt::encryptString($value);
        }

        static::updateOrCreate(['key' => $key], ['value' => $storeValue]);

        // Invalida cache: il prossimo Setting::get() rileggerà dal DB.
        Cache::forget('setting:'.$key);
    }
}
