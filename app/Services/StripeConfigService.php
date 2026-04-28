<?php
namespace App\Services;

use App\Models\Setting;

class StripeConfigService
{
    /**
     * Recupera la chiave segreta di Stripe.
     * Ordine di priorita': DB (stripe_secret) → DB (stripe_secret_key) → .env
     */
    public function getSecret(): ?string
    {
        return Setting::get('stripe_secret')
            ?: Setting::get('stripe_secret_key')
            ?: config('services.stripe.secret');
    }

    /**
     * Recupera la chiave pubblica di Stripe.
     * Ordine di priorita': DB (stripe_key) → DB (stripe_public_key) → .env
     */
    public function getPublicKey(): ?string
    {
        $key = trim((string) (Setting::get('stripe_key')
            ?: Setting::get('stripe_public_key')
            ?: config('services.stripe.key')));

        return $key ?: null;
    }

    /**
     * Verifica se Stripe e' configurato correttamente (entrambe le chiavi presenti).
     */
    public function isConfigured(): bool
    {
        $key = $this->getPublicKey();
        $secret = $this->getSecret();

        if (empty($key) || empty($secret)) {
            return false;
        }

        return !str_contains($key, 'placeholder') && !str_contains($secret, 'placeholder');
    }
}
