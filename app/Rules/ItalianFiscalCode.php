<?php

/**
 * RULE: ItalianFiscalCode — validazione codice fiscale italiano (CF).
 *
 * Supporta:
 *   - CF persona fisica (16 caratteri alfanumerici, pattern standard)
 *   - CF persona giuridica (11 cifre, coincide con P.IVA per i non forfettari)
 *
 * Controlli eseguiti:
 *   - Pattern regex standard per CF persona fisica
 *   - Per CF 11 cifre: controlla pattern numerico (validazione checksum
 *     avviene su ItalianVatNumber quando usato come P.IVA).
 *   - Checksum lettera di controllo per CF 16 char (tabella ufficiale MEF).
 *
 * Uso:
 *   'fiscal_code' => ['required', new ItalianFiscalCode()],
 */

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ItalianFiscalCode implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('Il codice fiscale non è valido.');

            return;
        }

        $cf = strtoupper(preg_replace('/\s+/', '', $value) ?? '');

        // CF persona giuridica: 11 cifre
        if (preg_match('/^\d{11}$/', $cf)) {
            return;
        }

        // CF persona fisica: 16 caratteri (pattern standard 6+2+1+2+1+3+1)
        if (! preg_match('/^[A-Z]{6}\d{2}[A-Z]\d{2}[A-Z]\d{3}[A-Z]$/', $cf)) {
            $fail('Il codice fiscale deve avere 16 caratteri (persona fisica) o 11 cifre (persona giuridica).');

            return;
        }

        if (! $this->isChecksumValid($cf)) {
            $fail('Il codice fiscale non è valido (lettera di controllo errata).');
        }
    }

    /**
     * Checksum CF persona fisica — tabella ufficiale MEF.
     */
    private function isChecksumValid(string $cf): bool
    {
        // Valori per caratteri in posizione dispari (1ª, 3ª, ... 15ª)
        $oddValues = [
            '0' => 1, '1' => 0, '2' => 5, '3' => 7, '4' => 9,
            '5' => 13, '6' => 15, '7' => 17, '8' => 19, '9' => 21,
            'A' => 1, 'B' => 0, 'C' => 5, 'D' => 7, 'E' => 9,
            'F' => 13, 'G' => 15, 'H' => 17, 'I' => 19, 'J' => 21,
            'K' => 2, 'L' => 4, 'M' => 18, 'N' => 20, 'O' => 11,
            'P' => 3, 'Q' => 6, 'R' => 8, 'S' => 12, 'T' => 14,
            'U' => 16, 'V' => 10, 'W' => 22, 'X' => 25, 'Y' => 24, 'Z' => 23,
        ];

        // Valori per caratteri in posizione pari (2ª, 4ª, ... 14ª)
        $evenValues = [
            '0' => 0, '1' => 1, '2' => 2, '3' => 3, '4' => 4,
            '5' => 5, '6' => 6, '7' => 7, '8' => 8, '9' => 9,
            'A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4,
            'F' => 5, 'G' => 6, 'H' => 7, 'I' => 8, 'J' => 9,
            'K' => 10, 'L' => 11, 'M' => 12, 'N' => 13, 'O' => 14,
            'P' => 15, 'Q' => 16, 'R' => 17, 'S' => 18, 'T' => 19,
            'U' => 20, 'V' => 21, 'W' => 22, 'X' => 23, 'Y' => 24, 'Z' => 25,
        ];

        $sum = 0;
        for ($i = 0; $i < 15; $i++) {
            $char = $cf[$i];
            if ($i % 2 === 0) {
                // Posizioni dispari (1ª, 3ª, ...)
                $sum += $oddValues[$char] ?? 0;
            } else {
                $sum += $evenValues[$char] ?? 0;
            }
        }

        $expected = chr(ord('A') + ($sum % 26));

        return $expected === $cf[15];
    }
}
