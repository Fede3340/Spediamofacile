<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per POST /api/forgot-password (richiesta reset password).
 * Endpoint pubblico (rate-limited 5/min dalla route).
 * Anti-enumerazione: la response è uniforme indipendentemente dall'esistenza dell'email.
 */
class ForgotPasswordRequest extends FormRequest
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

    public function messages(): array
    {
        return [
            'email.required' => "L'email è obbligatoria.",
            'email.email' => "L'email non è valida.",
        ];
    }
}
