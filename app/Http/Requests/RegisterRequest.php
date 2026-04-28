<?php

/**
 * REQUEST: VALIDAZIONE REGISTRAZIONE UTENTE
 *
 * Valida tutti i dati inviati dall'utente quando si registra sul sito.
 * Controlla che tutti i campi obbligatori siano presenti e validi.
 *
 * Regole particolari per la password:
 * - Minimo 8 caratteri
 * - Almeno una lettera minuscola
 * - Almeno una lettera maiuscola
 * - Almeno un numero
 * - Almeno un simbolo speciale (@$!%*?&#^)
 * - Deve essere confermata (scritta due volte uguale)
 *
 * L'email deve essere unica (non gia' registrata) e confermata.
 * I messaggi di errore sono tutti in italiano per l'utente.
 */

namespace App\Http\Requests;

use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regole di validazione per la registrazione.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',              // Nome obbligatorio
            'surname' => 'required|string|max:255',            // Cognome obbligatorio
            'prefix' => 'string|required',                     // Prefisso telefonico (es. +39)
            'telephone_number' => 'required|string|max:255',   // Numero di telefono
            // Email: NON usiamo "unique:users" qui per evitare enumeration email
            // (errore 422 distinguibile). Il controllo di duplicato viene fatto
            // nel CustomRegisterController, che restituisce sempre una risposta
            // generica di successo senza rivelare se l'email e' gia' registrata.
            // Ref: Sprint 6.4 anti-enumeration (OWASP Auth Cheatsheet).
            'email' => 'required|string|email|max:255|confirmed',
            // Password policy uniforme: vedi App\Rules\StrongPassword
            // (>=10 char, 1 maiuscola, 1 numero, 1 simbolo, no blocklist, no email/nome).
            'password' => [
                'required',
                'string',
                'confirmed',
                new StrongPassword([
                    'email' => $this->input('email'),
                    'name' => $this->input('name'),
                    'surname' => $this->input('surname'),
                ]),
            ],
            'role' => 'required|string|in:User,Cliente,Partner Pro', // Accetta le label del frontend (il controller forza sempre "User")
            'referred_by' => 'nullable|string|max:8',          // Codice referral opzionale
            'user_type' => 'nullable|string|in:privato,commerciante', // Tipo account: privato o azienda
            'privacy_accepted' => 'required|accepted',         // Consenso privacy obbligatorio (GDPR Art. 7)
        ];
    }

    /**
     * Messaggi di errore personalizzati in italiano.
     * Vengono mostrati all'utente quando un campo non e' valido.
     */
    public function messages() {
        return [
            'name.required' => 'Il nome è obbligatorio.',
            'surname.required' => 'Il cognome è obbligatorio.',
            'telephone_number.required' => 'Il numero di telefono è obbligatorio.',

            'email.required' => 'L\'indirizzo email è obbligatorio.',
            'email.email' => 'Devi inserire un indirizzo email valido.',
            'email.max' => 'L\'indirizzo email non può superare i 255 caratteri.',
            // 'email.unique' rimosso: l'unicita' viene gestita dal controller con
            // risposta anti-enumeration (vedi CustomRegisterController::register).
            'email.confirmed' => 'La conferma dell\'email non corrisponde.',

            'password.required' => 'La password è obbligatoria.',
            'password.string' => 'La password deve essere una stringa valida.',
            'password.confirmed' => 'La conferma della password non corrisponde.',

            'role.required' => 'Il tipo di account è obbligatorio.',
        ];
    }
}
