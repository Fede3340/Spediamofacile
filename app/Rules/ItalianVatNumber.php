<?php

/**
 * RULE: ItalianVatNumber — validazione P.IVA italiana con checksum.
 *
 * Applica l'algoritmo ufficiale del MEF per le P.IVA a 11 cifre:
 *   1. Si prendono le prime 10 cifre del codice.
 *   2. Si sommano tutte le cifre in posizione dispari (indice 0, 2, 4, 6, 8).
 *   3. Si raddoppia ogni cifra in posizione pari e, se il risultato è >= 10,
 *      si sottrae 9 (equivalente a sommare le cifre del prodotto).
 *   4. La somma totale + cifra di controllo (undicesima cifra) deve essere
 *      multipla di 10.
 *
 * Referenze: DPR 29/09/1973 n. 605, art. 36-bis DL 633/72.
 *
 * Uso:
 *   'vat_number' => ['required', new ItalianVatNumber()],
 */

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ItalianVatNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('La P.IVA non è valida.');

            return;
        }

        // Rimuove spazi, eventuale prefisso "IT" e uppercase
        $vat = strtoupper(preg_replace('/\s+/', '', $value) ?? '');
        if (str_starts_with($vat, 'IT')) {
            $vat = substr($vat, 2);
        }

        if (! preg_match('/^\d{11}$/', $vat)) {
            $fail('La P.IVA deve contenere esattamente 11 cifre numeriche.');

            return;
        }

        if (! $this->isChecksumValid($vat)) {
            $fail('La P.IVA non è valida (cifra di controllo errata).');
        }
    }

    /**
     * Algoritmo checksum ministeriale per P.IVA italiana.
     */
    private function isChecksumValid(string $vat): bool
    {
        $sum = 0;

        for ($i = 0; $i < 10; $i++) {
            $digit = (int) $vat[$i];

            if ($i % 2 === 0) {
                // Posizioni dispari (1ª, 3ª, ...): sommate così come sono
                $sum += $digit;
            } else {
                // Posizioni pari (2ª, 4ª, ...): raddoppiate; se >=10 si sottrae 9
                $doubled = $digit * 2;
                $sum += $doubled > 9 ? $doubled - 9 : $doubled;
            }
        }

        $controlDigit = (10 - ($sum % 10)) % 10;

        return $controlDigit === (int) $vat[10];
    }
}
