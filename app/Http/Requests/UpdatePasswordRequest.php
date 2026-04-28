<?php

/**
 * REQUEST: VALIDAZIONE AGGIORNAMENTO PASSWORD
 *
 * Valida i dati inviati quando un utente reimposta la sua password
 * dopo aver richiesto il recupero password.
 *
 * Servono tre campi:
 * - L'email dell'utente
 * - Il token di reset (codice segreto ricevuto via email)
 * - La nuova password (con conferma)
 */

namespace App\Http\Requests;

use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regole di validazione per il reset della password.
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email',          // Email dell'utente (obbligatoria)
            'resetToken' => 'required|string',     // Token di reset ricevuto via email
            // Password policy uniforme con la registrazione (vedi StrongPassword).
            'password' => [
                'required',
                'confirmed',
                new StrongPassword(['email' => $this->input('email')]),
            ],
        ];
    }

    /**
     * Messaggi di errore personalizzati in italiano.
     */
    public function messages(): array
    {
        return [
            'password.required' => 'La password è obbligatoria.',
            'password.confirmed' => 'La conferma della password non corrisponde.',
        ];
    }
}
