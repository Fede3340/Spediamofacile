<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * P1.1 — Controller per gestione 2FA TOTP.
 *
 * Endpoints (tutti sotto auth:sanctum):
 *  - POST /api/2fa/enable   → genera secret + QR url, salva su user (NOT confirmed)
 *  - POST /api/2fa/confirm  → verifica code, conferma 2FA, restituisce recovery codes
 *  - POST /api/2fa/disable  → richiede password, azzera campi 2FA
 *  - POST /api/2fa/challenge → re-challenge (es. dopo timeout sessione)
 *  - POST /api/2fa/recovery → verifica recovery code, lo rimuove dal pool
 */
class TwoFactorController extends Controller
{
    public function __construct(
        private readonly TwoFactorService $twoFactor,
    ) {}

    /**
     * Genera un nuovo secret + QR url. Salva il secret sull'utente ma
     * NON imposta `two_factor_confirmed_at` (richiede conferma successiva).
     */
    public function enable(Request $request): JsonResponse
    {
        $user = $request->user();

        $secret = $this->twoFactor->generateSecret();

        // Assegnazione esplicita (non in $fillable)
        $user->two_factor_secret = $secret;
        $user->two_factor_confirmed_at = null;
        $user->two_factor_recovery_codes = null;
        $user->save();

        return response()->json([
            'secret' => $secret,
            'qr_url' => $this->twoFactor->getQrCodeUrl($user, $secret),
        ]);
    }

    /**
     * Conferma il setup 2FA verificando un codice TOTP. Genera recovery codes.
     */
    public function confirm(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (empty($user->two_factor_secret)) {
            throw ValidationException::withMessages([
                'code' => 'Devi prima abilitare il 2FA chiamando /api/2fa/enable.',
            ]);
        }

        if (! $this->twoFactor->verifyCode($user->two_factor_secret, $data['code'])) {
            throw ValidationException::withMessages([
                'code' => 'Codice 2FA non valido.',
            ]);
        }

        $recoveryCodes = $this->twoFactor->generateRecoveryCodes();

        $user->two_factor_confirmed_at = now();
        $user->two_factor_recovery_codes = $recoveryCodes;
        $user->save();

        return response()->json([
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    /**
     * Disabilita 2FA dopo verifica password. Azzera i 3 campi 2FA.
     */
    public function disable(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Password non corretta.',
            ]);
        }

        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        return response()->json(['message' => '2FA disabilitato.']);
    }

    /**
     * Re-challenge: verifica un codice TOTP per un utente che ha gia' 2FA attivo.
     * Usato per re-autenticare dopo timeout sessione critica.
     */
    public function challenge(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! $user->hasTwoFactorEnabled()) {
            throw ValidationException::withMessages([
                'code' => '2FA non attivo per questo utente.',
            ]);
        }

        if (! $this->twoFactor->verifyCode($user->two_factor_secret, $data['code'])) {
            throw ValidationException::withMessages([
                'code' => 'Codice 2FA non valido.',
            ]);
        }

        return response()->json(['verified' => true]);
    }

    /**
     * Verifica un recovery code e lo rimuove dal pool (usato una sola volta).
     */
    public function recovery(Request $request): JsonResponse
    {
        $data = $request->validate([
            'recovery_code' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! $user->hasTwoFactorEnabled()) {
            throw ValidationException::withMessages([
                'recovery_code' => '2FA non attivo per questo utente.',
            ]);
        }

        $codes = $user->two_factor_recovery_codes ?? [];
        $submitted = strtoupper(trim($data['recovery_code']));

        $found = false;
        $remaining = [];
        foreach ($codes as $code) {
            if (! $found && hash_equals(strtoupper($code), $submitted)) {
                $found = true; // rimosso dal pool
                continue;
            }
            $remaining[] = $code;
        }

        if (! $found) {
            throw ValidationException::withMessages([
                'recovery_code' => 'Recovery code non valido o gia\' usato.',
            ]);
        }

        $user->two_factor_recovery_codes = $remaining;
        $user->save();

        return response()->json([
            'verified' => true,
            'remaining_codes' => count($remaining),
        ]);
    }
}
