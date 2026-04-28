<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Validazione per PATCH /api/users/{user}.
 * Usa UserPolicy.update per l'autorizzazione (centralizzato vs check inline 403).
 *
 * Regole condizionali: validate solo i campi presenti nel payload (partial update).
 * Password policy: min 8 + lower + upper + digit + special, conferma obbligatoria.
 */
class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('user'));
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;
        $rules = [];

        if ($this->filled('name')) {
            $rules['name'] = ['nullable', 'string', 'max:255'];
        }

        if ($this->filled('surname')) {
            $rules['surname'] = ['nullable', 'string', 'max:255'];
        }

        if ($this->filled('email')) {
            $rules['email'] = ['required', 'string', 'email:rfc', 'max:255', 'unique:users,email,'.$userId];
        }

        if ($this->filled('telephone_number')) {
            $rules['telephone_number'] = ['nullable', 'string', 'max:50'];
        }

        if ($this->filled('password')) {
            $rules['password'] = [
                'string',
                'min:8',
                'confirmed',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[^a-zA-Z0-9\s]/',
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'email.email' => "L'email non è valida.",
            'email.unique' => "Questa email è già usata da un altro account.",
            'password.min' => 'La password deve essere lunga almeno 8 caratteri.',
            'password.confirmed' => 'Le due password non corrispondono.',
            'password.regex' => 'La password deve contenere maiuscola, minuscola, numero e simbolo.',
        ];
    }
}
