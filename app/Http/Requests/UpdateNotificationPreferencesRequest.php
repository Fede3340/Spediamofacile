<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per PATCH /api/notification-preferences (preferenze notifiche utente).
 * Tutti i flag opt-in opzionali (sometimes); GDPR registra timestamp lato controller.
 */
class UpdateNotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'referral_site_enabled' => ['sometimes', 'boolean'],
            'referral_email_enabled' => ['sometimes', 'boolean'],
            'referral_sms_enabled' => ['sometimes', 'boolean'],
            'sms_order_updates' => ['sometimes', 'boolean'],
            'sms_marketing' => ['sometimes', 'boolean'],
            'push_order_updates' => ['sometimes', 'boolean'],
            'push_marketing' => ['sometimes', 'boolean'],
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:32'],
        ];
    }
}
