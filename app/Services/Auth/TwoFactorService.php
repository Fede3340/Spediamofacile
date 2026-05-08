<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * P1.1 — Servizio TOTP (Time-based One-Time Password) RFC 6238.
 *
 * Implementazione pure PHP (nessuna dipendenza esterna). Compatibile con
 * Google Authenticator, Authy, 1Password, Microsoft Authenticator e qualsiasi
 * client conforme a RFC 6238 (HMAC-SHA1, 6 cifre, periodo 30s).
 *
 * Se in futuro `pragmarx/google2fa-laravel` viene installato via composer,
 * il servizio puo' essere rifattorizzato per delegare a quel pacchetto;
 * l'API pubblica resta invariata.
 *
 * SICUREZZA:
 *  - generateSecret: usa random_bytes (CSPRNG) per 20 byte → 32 char base32.
 *  - verifyCode: confronto a tempo costante (hash_equals) per evitare timing.
 *  - Window di tolleranza: ±1 step (30s) per gestire clock skew.
 */
class TwoFactorService
{
    /** Numero di cifre del codice TOTP (Google Authenticator usa 6). */
    private const CODE_DIGITS = 6;

    /** Periodo TOTP in secondi (RFC 6238 raccomanda 30). */
    private const TIME_STEP = 30;

    /** Window di tolleranza per clock skew: 1 = ±30s = 3 finestre testate. */
    private const VALIDATION_WINDOW = 1;

    /** Issuer mostrato in Google Authenticator e simili. */
    private const ISSUER = 'SpediamoFacile';

    /** Alfabeto base32 standard (RFC 4648). */
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Genera un secret base32 random di 32 caratteri (160 bit di entropia).
     */
    public function generateSecret(): string
    {
        $bytes = random_bytes(20); // 20 byte raw = 32 char base32

        return $this->base32Encode($bytes);
    }

    /**
     * Costruisce l'URL otpauth:// per il QR code.
     * Formato: otpauth://totp/{Issuer}:{email}?secret={secret}&issuer={Issuer}
     */
    public function getQrCodeUrl(User $user, string $secret): string
    {
        $label = rawurlencode(self::ISSUER.':'.$user->email);
        $params = http_build_query([
            'secret' => $secret,
            'issuer' => self::ISSUER,
            'algorithm' => 'SHA1',
            'digits' => self::CODE_DIGITS,
            'period' => self::TIME_STEP,
        ]);

        return "otpauth://totp/{$label}?{$params}";
    }

    /**
     * Verifica un codice TOTP contro un secret base32.
     * Tollera ±VALIDATION_WINDOW step (default ±30s) per gestire clock skew.
     */
    public function verifyCode(string $secret, string $code): bool
    {
        // Normalizza il code: rimuove spazi e caratteri non numerici
        $code = preg_replace('/\s+/', '', $code);
        if (! preg_match('/^\d{'.self::CODE_DIGITS.'}$/', $code)) {
            return false;
        }

        $secretRaw = $this->base32Decode($secret);
        if ($secretRaw === '') {
            return false;
        }

        $currentStep = (int) floor(time() / self::TIME_STEP);

        // Test la finestra corrente +/- VALIDATION_WINDOW
        for ($i = -self::VALIDATION_WINDOW; $i <= self::VALIDATION_WINDOW; $i++) {
            $expected = $this->generateCodeForStep($secretRaw, $currentStep + $i);
            if (hash_equals($expected, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Genera array di recovery codes formato XXXXX-XXXXX (10 char hex separati da dash).
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            // 5 byte hex = 10 char per gruppo, due gruppi separati da dash
            $codes[] = strtoupper(bin2hex(random_bytes(5))).'-'.strtoupper(bin2hex(random_bytes(5)));
        }

        return $codes;
    }

    /**
     * Genera un singolo TOTP code per un dato step temporale (RFC 6238).
     */
    private function generateCodeForStep(string $secretRaw, int $step): string
    {
        // Pack step come 8-byte big-endian
        $stepBytes = pack('J', $step);

        $hmac = hash_hmac('sha1', $stepBytes, $secretRaw, true);

        // Dynamic truncation (RFC 4226)
        $offset = ord($hmac[strlen($hmac) - 1]) & 0x0F;
        $binary = (ord($hmac[$offset]) & 0x7F) << 24
            | (ord($hmac[$offset + 1]) & 0xFF) << 16
            | (ord($hmac[$offset + 2]) & 0xFF) << 8
            | (ord($hmac[$offset + 3]) & 0xFF);

        $code = $binary % (10 ** self::CODE_DIGITS);

        return str_pad((string) $code, self::CODE_DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Encode bytes raw → stringa base32 (RFC 4648).
     */
    private function base32Encode(string $data): string
    {
        if ($data === '') {
            return '';
        }

        $binary = '';
        foreach (str_split($data) as $char) {
            $binary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $output = '';
        foreach (str_split($binary, 5) as $chunk) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            $output .= self::BASE32_ALPHABET[bindec($chunk)];
        }

        return $output;
    }

    /**
     * Decode stringa base32 → bytes raw. Restituisce '' se input invalido.
     */
    private function base32Decode(string $secret): string
    {
        $secret = strtoupper(preg_replace('/[^A-Z2-7]/', '', $secret) ?? '');
        if ($secret === '') {
            return '';
        }

        $binary = '';
        foreach (str_split($secret) as $char) {
            $position = strpos(self::BASE32_ALPHABET, $char);
            if ($position === false) {
                return '';
            }
            $binary .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }

        $output = '';
        foreach (str_split($binary, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $output .= chr(bindec($chunk));
            }
        }

        return $output;
    }
}
