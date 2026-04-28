<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per POST /api/custom-login.
 * Endpoint pubblico (rate-limited 30/min dalla route).
 */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => "L'email è obbligatoria.",
            'email.email' => "L'email non è valida.",
            'password.required' => 'La password è obbligatoria.',
        ];
    }
}
