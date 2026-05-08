<?php

/**
 * LoginController -- Login con email/password, conferma password admin.
 *
 * Estratto da CustomLoginController: gestisce login, confirmPassword e logout-related helpers.
 * Gestisce verifica codice 6 cifre inline (se email non verificata) e trasferimento carrello ospite.
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\BuildsSessionPayload;
use App\Http\Requests\ConfirmPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Jobs\SendVerificationEmailJob;
use App\Models\User;
use App\Services\GuestCartMergeService;
use App\Support\AuthUiCookie;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends Controller
{
    use BuildsSessionPayload;

    public function __construct(
        private readonly GuestCartMergeService $guestCartMerge,
    ) {}

    /** Sessione corrente (ex AuthSessionController). */
    public function session()
    {
        return response()->json([
            'data' => $this->buildSessionPayload(),
        ]);
    }

    private function resolveUserFromEmail(string $email): ?User
    {
        $normalized = trim(mb_strtolower($email));
        $user = User::where('email', $normalized)->first();

        if ($user) {
            return $user;
        }

        $candidate = preg_replace('/@spedizionefacile\.it$/i', '@spediamofacile.it', $normalized);

        if (! $candidate || $candidate === $normalized) {
            return null;
        }

        return User::where('email', $candidate)->first();
    }

    /**
     * Hash bcrypt "dummy" cache-ato in memoria, usato per mantenere costante
     * il tempo di risposta quando l'email non esiste (Sprint 6.4 anti-enumeration).
     * E' generato on-demand con Hash::make(...) cosi' eredita il cost di bcrypt
     * configurato per l'ambiente (es. 12 in prod, 4 in testing): confrontare un
     * hash con cost diverso dagli hash degli utenti reali reintrodurrebbe il
     * timing signal.
     *
     * Ref: https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html#authentication-responses
     */
    private static ?string $timingSafeDummyHash = null;

    private function timingSafeDummyHash(): string
    {
        if (self::$timingSafeDummyHash === null) {
            self::$timingSafeDummyHash = Hash::make('dummy-password-for-timing-attack-prevention');
        }

        return self::$timingSafeDummyHash;
    }

    /**
     * login -- Verifica credenziali, gestisce verifica email e trasferisce carrello ospite.
     *
     * 1) Valida credenziali  2) Se email non verificata: genera/invia codice, return 403
     * 3) Auth::login  4) Rigenera sessione  5) Trasferisce pacchi da sessione a DB
     *
     * Anti-enumerazione (Sprint 6.4): se l'email non esiste, eseguiamo comunque
     * un Hash::check contro un hash bcrypt dummy per mantenere costante il tempo
     * di risposta. L'errore e' generico ("credenziali non corrette") e non rivela
     * mai se l'email e' registrata.
     */
    public function login(LoginRequest $request)
    {
        $user = $this->resolveUserFromEmail((string) $request->email);
        $guestCart = $request->hasSession() ? $request->session()->get('cart', []) : [];

        // Anti-timing: se l'utente non esiste facciamo comunque un Hash::check
        // contro un hash dummy. bcrypt a cost 12 impiega ~80-150 ms sia per il
        // dummy che per un hash reale, quindi i due rami hanno costo simile.
        $passwordValid = $user
            ? Hash::check($request->password, $user->password)
            : Hash::check($request->password, $this->timingSafeDummyHash());

        if (! $user || ! $passwordValid) {
            // Una sola chiave per evitare il default Laravel "(and 1 more error)" nel campo message.
            throw ValidationException::withMessages([
                'email' => ['Le credenziali non sono corrette.'],
            ]);
        }

        if (! $user->email_verified_at) {
            $verificationExpiresAt = $user->verification_code_expires_at
                ? Carbon::parse($user->verification_code_expires_at)
                : null;

            if (! $user->verification_code || ($verificationExpiresAt && $verificationExpiresAt->isPast())) {
                $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $user->update([
                    'verification_code' => $code,
                    'verification_code_expires_at' => now()->addMinutes(30),
                ]);

                try {
                    SendVerificationEmailJob::dispatchSync($user);
                } catch (\Throwable $e) {
                    Log::warning('Invio email codice verifica fallito durante login.', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'success' => false,
                'requires_verification' => true,
                'message' => 'Account non verificato. Inserisci il codice di verifica a 6 cifre inviato alla tua email.',
            ], 403);
        }

        Auth::login($user, (bool) $request->remember);

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        try {
            $this->guestCartMerge->merge($guestCart, $user);
            if ($request->hasSession()) {
                $request->session()->forget('cart');
            }
        } catch (\Exception $e) {
            Log::warning('Guest cart merge failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }

        return response()->json($user)->cookie(AuthUiCookie::issueForUser($user, (bool) $request->boolean('remember')));
    }

    /**
     * Conferma la password dell'admin (fuori flusso standard).
     */
    public function confirmPassword(ConfirmPasswordRequest $request)
    {
        $user = $request->user();

        if (! $user?->isAdmin()) {
            abort(Response::HTTP_FORBIDDEN, 'Solo un amministratore può confermare l\'accesso fuori flusso.');
        }

        if (! Hash::check((string) $request->password, (string) $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['La password inserita non è corretta.'],
            ]);
        }

        if ($request->hasSession()) {
            $request->session()->put('auth.password_confirmed_at', now()->timestamp);
        }

        return response()->json([
            'success' => true,
            'confirmed_at' => now()->toIso8601String(),
        ]);
    }
}
