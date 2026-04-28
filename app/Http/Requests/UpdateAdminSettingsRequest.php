<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per PATCH /api/admin/settings (admin only).
 * Aggiorna chiavi globali del sito (Stripe, BRT, branding, COD).
 */
class UpdateAdminSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'stripe_public_key' => ['nullable', 'string', 'max:255'],
            'stripe_secret_key' => ['nullable', 'string', 'max:255'],
            'stripe_webhook_secret' => ['nullable', 'string', 'max:255'],
            'brt_customer_id' => ['nullable', 'string', 'max:100'],
            'brt_username' => ['nullable', 'string', 'max:100'],
            'brt_password' => ['nullable', 'string', 'max:255'],
            'site_name' => ['nullable', 'string', 'max:100'],
            'support_email' => ['nullable', 'string', 'email', 'max:255'],
            'cod_surcharge' => ['nullable', 'numeric', 'min:0', 'max:9999'],
        ];
    }
}
