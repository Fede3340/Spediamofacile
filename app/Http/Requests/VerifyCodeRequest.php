<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per POST /api/verify-code (verifica OTP 6 cifre + login post-registrazione).
 * Endpoint pubblico (rate-limited 5/min dalla route).
 */
class VerifyCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc'],
            'code' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.size' => 'Il codice deve essere di esattamente 6 cifre.',
        ];
    }
}
