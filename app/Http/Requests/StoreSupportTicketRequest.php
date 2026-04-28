<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione per POST /api/support-tickets (utente autenticato apre ticket dall'area account).
 * Auth gestita dal middleware `auth:sanctum` sulla route.
 */
class StoreSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required' => "L'oggetto è obbligatorio.",
            'message.required' => 'Il messaggio è obbligatorio.',
            'message.max' => 'Il messaggio non può superare i 5000 caratteri.',
        ];
    }
}
