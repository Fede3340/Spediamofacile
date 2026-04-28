<?php

/**
 * REQUEST: VALIDAZIONE INDIRIZZO UTENTE (RUBRICA)
 *
 * Valida i dati inviati quando l'utente salva un nuovo indirizzo
 * nella sua rubrica personale o ne modifica uno esistente.
 *
 * I campi obbligatori sono: nome, via, citta' e CAP.
 * Tutti gli altri sono opzionali (l'utente puo' compilarli dopo).
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserAddressStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regole di validazione per l'indirizzo in rubrica.
     *
     * Quando `profile_type = 'business'` si attivano validazioni dedicate
     * sui campi fiscali aziendali (ragione sociale, P.IVA, SDI, PEC).
     */
    public function rules(): array
    {
        return [
            'type' => 'nullable|string',                    // Tipo (privato/azienda) - opzionale
            'name' => 'required|string',                    // Nome (obbligatorio)
            'additional_information' => 'nullable|string',  // Info aggiuntive - opzionale
            'address' => 'required|string',                 // Via/piazza (obbligatorio)
            'number_type' => 'nullable|string',             // Tipo numero civico - opzionale
            'address_number' => 'nullable|string',          // Numero civico - opzionale
            'intercom_code' => 'nullable|string',           // Codice citofono - opzionale
            'country' => 'nullable|string',                 // Nazione - opzionale
            'city' => 'required|string',                    // Citta' (obbligatorio)
            'postal_code' => 'required|string',             // CAP (obbligatorio)
            'province' => 'nullable|string',                // Sigla provincia - opzionale
            'province_name' => 'nullable|string',           // Nome provincia - opzionale
            'telephone_number' => 'nullable|string',        // Telefono - opzionale
            'email' => 'nullable|string',                   // Email - opzionale
            'default' => 'nullable',                        // Se impostare come predefinito - opzionale

            // Profilo: privato o business. I campi fiscali sottostanti
            // sono obbligatori solo quando profile_type = 'business'.
            'profile_type' => 'nullable|string|in:private,business',

            // Campi business (condizionali su profile_type = 'business')
            'company_name' => 'required_if:profile_type,business|nullable|string|max:120',
            'vat_number' => 'required_if:profile_type,business|nullable|string|min:8|max:20',
            'sdi_code' => 'nullable|string|size:7',
            'pec_email' => 'nullable|email|max:120',
        ];
    }

    /**
     * Messaggi di errore in italiano, specifici per i campi business.
     * Gli altri campi usano i messaggi globali di resources/lang/it/validation.php.
     */
    public function messages(): array
    {
        return [
            'company_name.required_if' => 'La ragione sociale è obbligatoria per i profili business.',
            'vat_number.required_if' => 'La partita IVA è obbligatoria per i profili business.',
            'vat_number.min' => 'La partita IVA deve contenere almeno :min caratteri.',
            'vat_number.max' => 'La partita IVA non può superare :max caratteri.',
            'sdi_code.size' => 'Il codice SDI deve essere di :size caratteri.',
            'pec_email.email' => 'La PEC deve essere un indirizzo email valido.',
            'profile_type.in' => 'Il tipo profilo deve essere "private" o "business".',
        ];
    }
}
