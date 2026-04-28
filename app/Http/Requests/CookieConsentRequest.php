<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per POST /api/cookie-consent (audit trail GDPR consenso cookie).
 * Endpoint pubblico — accetta sia formato legacy (type=all/necessary)
 * sia granulare (analytics/marketing/functional boolean).
 */
class CookieConsentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'       => ['nullable', 'string', 'in:all,necessary'],
            'analytics'  => ['nullable', 'boolean'],
            'marketing'  => ['nullable', 'boolean'],
            'functional' => ['nullable', 'boolean'],
        ];
    }
}
