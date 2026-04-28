<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per POST /api/resend-verification-email (reinvio codice OTP).
 * Endpoint pubblico (rate-limited dalla route).
 */
class ResendVerificationEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc'],
        ];
    }
}
