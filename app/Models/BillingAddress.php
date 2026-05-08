<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $name
 * @property bool $is_business
 * @property string|null $company_name
 * @property string|null $fiscal_code
 * @property string|null $vat_number
 * @property string|null $sdi_code
 * @property string|null $pec_email
 * @property string|null $address
 * @property string|null $city
 * @property string|null $province_name
 * @property string|null $postal_code
 * @property string|null $country
 */
class BillingAddress extends Model
{
    /**
     * Campi compilabili dall'esterno.
     */
    protected $fillable = [
        'name',            // Nome/cognome referente (persona fisica, anche se azienda)
        'is_business',     // true se azienda, false se privato
        'company_name',    // Ragione sociale (solo aziende)
        'fiscal_code',     // Codice fiscale
        'vat_number',      // Partita IVA
        'sdi_code',        // Codice destinatario SDI 7 char
        'pec_email',       // PEC alternativa
        'address',         // Via/piazza/corso
        'city',            // Città
        'province_name',   // Nome/sigla della provincia
        'postal_code',     // CAP
        'country',         // Paese ISO alpha-2 (default IT)
    ];

    protected $casts = [
        'is_business' => 'boolean',
    ];

    protected $attributes = [
        'is_business' => false,
        'sdi_code' => '0000000',
        'country' => 'IT',
    ];

    /**
     * Mutator: normalizza vat_number rimuovendo prefisso "IT" e spazi.
     */
    public function setVatNumberAttribute(?string $value): void
    {
        if ($value === null) {
            $this->attributes['vat_number'] = null;

            return;
        }

        $normalized = strtoupper(preg_replace('/\s+/', '', $value) ?? '');
        if (str_starts_with($normalized, 'IT')) {
            $normalized = substr($normalized, 2);
        }

        $this->attributes['vat_number'] = $normalized !== '' ? $normalized : null;
    }

    /**
     * Mutator: uppercase il codice fiscale senza spazi.
     */
    public function setFiscalCodeAttribute(?string $value): void
    {
        if ($value === null) {
            $this->attributes['fiscal_code'] = null;

            return;
        }

        $normalized = strtoupper(preg_replace('/\s+/', '', $value) ?? '');
        $this->attributes['fiscal_code'] = $normalized !== '' ? $normalized : null;
    }

    /**
     * Mutator: uppercase il codice SDI e fallback "0000000" se vuoto.
     */
    public function setSdiCodeAttribute(?string $value): void
    {
        $normalized = strtoupper(preg_replace('/\s+/', '', (string) $value) ?? '');
        $this->attributes['sdi_code'] = $normalized !== '' ? $normalized : '0000000';
    }
}
