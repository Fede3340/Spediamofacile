<?php

/**
 * REQUEST: VALIDAZIONE INDIRIZZO DI FATTURAZIONE
 *
 * Valida i dati inviati quando l'utente crea o modifica un indirizzo di
 * fatturazione. La validazione è "condizionale" in base al flag is_business:
 *   - is_business = true  → richiede company_name + vat_number (checksum) + sdi_code|pec_email
 *   - is_business = false → richiede fiscal_code (16 char, checksum)
 *
 * Campi comuni sempre obbligatori:
 *   name (referente), address, city, province_name, postal_code
 *
 * Default:
 *   - sdi_code = "0000000" se non specificato (placeholder privato)
 *   - country = "IT"
 */

namespace App\Http\Requests;

use App\Rules\ItalianFiscalCode;
use App\Rules\ItalianVatNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BillingAddressStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Pre-processing: normalizza input prima della validazione.
     * - is_business string "true"/"false" → bool
     * - vat_number: rimuove prefisso "IT" e spazi
     * - fiscal_code/sdi_code: upper + trim
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_business' => filter_var($this->input('is_business', false), FILTER_VALIDATE_BOOLEAN),
            'vat_number' => $this->normalizeVat($this->input('vat_number')),
            'fiscal_code' => $this->normalizeUpper($this->input('fiscal_code')),
            'sdi_code' => $this->normalizeSdiCode($this->input('sdi_code')),
            'country' => strtoupper((string) $this->input('country', 'IT')),
        ]);
    }

    public function rules(): array
    {
        $isBusiness = (bool) $this->input('is_business', false);

        return [
            // Dati anagrafici di base
            'name' => 'required|string|max:150',
            'address' => 'required|string|max:200',
            'city' => 'required|string|max:100',
            'province_name' => 'required|string|max:50',
            'postal_code' => ['required', 'string', 'regex:/^\d{5}$/'],
            'country' => ['nullable', 'string', 'size:2'],

            // Flag azienda / privato
            'is_business' => 'required|boolean',

            // Ragione sociale: obbligatoria solo per aziende
            'company_name' => [Rule::requiredIf($isBusiness), 'nullable', 'string', 'max:200'],

            // P.IVA: obbligatoria e checksum solo se azienda
            'vat_number' => [
                Rule::requiredIf($isBusiness),
                'nullable',
                'string',
                new ItalianVatNumber(),
            ],

            // Codice fiscale: obbligatorio se privato; opzionale se azienda (ma validato se presente)
            'fiscal_code' => [
                Rule::requiredIf(! $isBusiness),
                'nullable',
                'string',
                new ItalianFiscalCode(),
            ],

            // Codice SDI: 7 char alfanumerici; default "0000000". Almeno uno tra sdi e PEC se azienda.
            'sdi_code' => ['nullable', 'string', 'regex:/^[A-Z0-9]{7}$/'],

            // PEC: email valida (alternativa a sdi_code per aziende)
            'pec_email' => ['nullable', 'email', 'max:150'],
        ];
    }

    /**
     * Validazione aggiuntiva: per aziende almeno uno tra sdi_code (non default)
     * e pec_email deve essere fornito.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $isBusiness = (bool) $this->input('is_business', false);
            if (! $isBusiness) {
                return;
            }

            $sdi = strtoupper((string) $this->input('sdi_code', '0000000'));
            $pec = trim((string) $this->input('pec_email', ''));

            $sdiProvided = $sdi !== '' && $sdi !== '0000000';
            $pecProvided = $pec !== '';

            if (! $sdiProvided && ! $pecProvided) {
                $validator->errors()->add(
                    'sdi_code',
                    'Per le aziende serve un Codice SDI valido o una PEC (almeno uno dei due).'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'postal_code.regex' => 'Il CAP deve essere di 5 cifre.',
            'sdi_code.regex' => 'Il Codice SDI deve essere di 7 caratteri alfanumerici maiuscoli.',
            'company_name.required' => 'La ragione sociale è obbligatoria per le aziende.',
            'vat_number.required' => 'La P.IVA è obbligatoria per le aziende.',
            'fiscal_code.required' => 'Il codice fiscale è obbligatorio per i privati.',
        ];
    }

    private function normalizeVat(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $normalized = strtoupper(preg_replace('/\s+/', '', $value) ?? '');
        if (str_starts_with($normalized, 'IT')) {
            $normalized = substr($normalized, 2);
        }

        return $normalized !== '' ? $normalized : null;
    }

    private function normalizeUpper(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return strtoupper(preg_replace('/\s+/', '', $value) ?? '');
    }

    private function normalizeSdiCode(mixed $value): string
    {
        $normalized = strtoupper(preg_replace('/\s+/', '', (string) $value) ?? '');

        return $normalized !== '' ? $normalized : '0000000';
    }
}
