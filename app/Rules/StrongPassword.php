<?php

/**
 * RULE: StrongPassword
 *
 * F-bonus (audit BRT 2026-04-18) — Password policy uniforme su registrazione e
 * reset password.
 *
 * REQUISITI:
 *   - >= 10 caratteri
 *   - >= 1 maiuscola
 *   - >= 1 numero
 *   - >= 1 simbolo
 *   - non in blocklist password comuni
 *   - non contiene email/nome utente (se forniti via opzioni)
 *
 * USO:
 *   $request->validate([
 *       'password' => ['required', 'confirmed', new StrongPassword(['email' => $request->email])],
 *   ]);
 */

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    /** Password notoriamente compromesse: lista breve, OWASP top + IT. */
    private const BLOCKLIST = [
        'password', 'password1', 'password123', 'p@ssword', 'p@ssw0rd',
        '12345678', '123456789', '1234567890', 'qwerty', 'qwertyuiop',
        'admin', 'admin123', 'letmein', 'welcome', 'iloveyou',
        'spedizionefacile', 'spediamofacile', 'changeme', 'abc12345',
        'juventus', 'inter1908', 'milan1899', 'roma1927', 'napoli1926',
    ];

    public function __construct(private array $context = [])
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('La password deve essere una stringa.');
            return;
        }

        if (mb_strlen($value) < 10) {
            $fail('La password deve essere di almeno 10 caratteri.');
            return;
        }

        if (! preg_match('/[A-Z]/u', $value)) {
            $fail('La password deve contenere almeno una lettera maiuscola.');
            return;
        }

        if (! preg_match('/[0-9]/u', $value)) {
            $fail('La password deve contenere almeno un numero.');
            return;
        }

        if (! preg_match('/[^A-Za-z0-9]/u', $value)) {
            $fail('La password deve contenere almeno un simbolo (es. !@#$%).');
            return;
        }

        $needle = mb_strtolower($value);
        if (in_array($needle, self::BLOCKLIST, true)) {
            $fail('Questa password è troppo comune. Scegline una più sicura.');
            return;
        }

        // Evita password che contengono email o nome utente come prefisso/suffisso ovvio.
        foreach (['email', 'name', 'surname', 'username'] as $key) {
            $candidate = $this->context[$key] ?? null;
            if (! is_string($candidate) || mb_strlen($candidate) < 3) {
                continue;
            }
            $token = mb_strtolower(strtok($candidate, '@'));
            if ($token && str_contains($needle, $token)) {
                $fail('La password non può contenere il tuo nome o la tua email.');
                return;
            }
        }
    }
}
