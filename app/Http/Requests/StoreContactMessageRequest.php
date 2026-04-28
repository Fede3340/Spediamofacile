<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione + sanitization per POST /api/contact (form pubblico contattaci).
 * Centralizza regole vs `$request->validate()` inline: testabile in isolamento + riusabile.
 */
class StoreContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // endpoint pubblico (rate-limited dalla route)
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'telephone_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Il nome è obbligatorio.',
            'surname.required' => 'Il cognome è obbligatorio.',
            'email.required' => "L'email è obbligatoria.",
            'email.email' => "L'email non è valida.",
            'message.required' => 'Il messaggio è obbligatorio.',
            'message.max' => 'Il messaggio non può superare i 5000 caratteri.',
        ];
    }
}
