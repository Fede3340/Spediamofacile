<?php

/**
 * RegisterController -- Verifica codice email e reinvio codice di verifica.
 *
 * Estratto da CustomLoginController: gestisce verifyCode e resendVerificationEmail.
 * Queste funzioni completano il flusso di registrazione/attivazione account.
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

use App\Http\Requests\RegisterRequest;
use App\Jobs\SendVerificationEmailJob;
use App\Models\User;
use App\Services\GuestCartMergeService;
use App\Support\AuthUiCookie;
use App\Utils\CustomResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RegisterController extends Controller
{
    public function __construct(
        private readonly GuestCartMergeService $guestCartMerge,
    ) {}

    /**
     * Registrazione con risposta anti-enumeration (Sprint 6.4).
     *
     * Se l'email e' gia' registrata, NON restituiamo 422 "email gia' in uso"
     * (enumeration oracle). Rispondiamo con lo stesso messaggio di successo
     * di una registrazione normale, SENZA creare un utente ne' inviare email.
     *
     * Ref: https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html
     */
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        if (User::where('email', $data['email'])->exists()) {
            Log::info('Tentativo di registrazione con email duplicata.', [
                'email' => $data['email'],
                'ip' => $request->ip(),
            ]);

            return CustomResponse::setSuccessResponse(
                'Registrazione completata! Inserisci il codice di verifica a 6 cifre inviato alla tua email.',
                Response::HTTP_CREATED
            );
        }

        $data['telephone_number'] = $data['prefix'] . ' ' . $data['telephone_number'];
        unset($data['prefix']);

        if (!empty($data['referred_by'])) {
            $referralCode = strtoupper($data['referred_by']);
            $proUser = User::where('referral_code', $referralCode)
                ->where('role', 'Partner Pro')
                ->first();
            $data['referred_by'] = $proUser ? $referralCode : null;
        }

        try {
            DB::beginTransaction();

            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            unset($data['role'], $data['email_confirmation'], $data['password_confirmation']);

            $user = new User($data);
            $user->role = 'User';
            if (!empty($data['referred_by'])) {
                $user->referred_by = $data['referred_by'];
            }
            $user->verification_code = $code;
            $user->verification_code_expires_at = now()->addMinutes(30);
            $user->save();

            try {
                SendVerificationEmailJob::dispatchSync($user);
            } catch (\Throwable $mailException) {
                Log::warning('Email di verifica non inviata.', [
                    'user_id' => $user->id,
                    'error' => $mailException->getMessage(),
                ]);
            }

            DB::commit();

            return CustomResponse::setSuccessResponse(
                'Registrazione completata! Inserisci il codice di verifica a 6 cifre inviato alla tua email.',
                Response::HTTP_CREATED
            );
        } catch (\Throwable $exception) {
            DB::rollBack();

            Log::error('Errore registrazione.', [
                'error' => $exception->getMessage(),
            ]);

            return CustomResponse::setFailResponse('Registrazione non completata. Riprova tra qualche minuto.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Verifica il codice a 6 cifre e attiva l'account.
     * Chiamata quando l'utente inserisce il codice ricevuto via email.
     */
    public function verifyCode(\App\Http\Requests\VerifyCodeRequest $request)
    {
        $user = User::where('email', $request->email)->first();
        $guestCart = $request->hasSession() ? $request->session()->get('cart', []) : [];

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return CustomResponse::setFailResponse('Credenziali non corrette.', Response::HTTP_UNAUTHORIZED);
        }

        if ($user->email_verified_at) {
            return CustomResponse::setSuccessResponse('Account già verificato. Puoi accedere.', Response::HTTP_OK);
        }

        // Controlla se il codice e' stato invalidato per troppi tentativi errati
        $attemptKey = 'verify_attempts_' . $user->id;
        $attempts = (int) Cache::get($attemptKey, 0);

        if ($attempts >= 5) {
            $user->update([
                'verification_code' => null,
                'verification_code_expires_at' => null,
            ]);
            Cache::forget($attemptKey);

            return CustomResponse::setFailResponse(
                'Troppi tentativi errati. Il codice è stato invalidato. Richiedi un nuovo codice.',
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if ($user->verification_code !== $request->code) {
            Cache::put($attemptKey, $attempts + 1, now()->addMinutes(30));

            $remaining = 4 - $attempts;
            return CustomResponse::setFailResponse(
                'Codice di verifica non valido. Tentativi rimasti: ' . max(0, $remaining) . '.',
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // Codice corretto: resetta il contatore dei tentativi
        Cache::forget($attemptKey);

        $verificationExpiresAt = $user->verification_code_expires_at
            ? Carbon::parse($user->verification_code_expires_at)
            : null;

        if ($verificationExpiresAt && $verificationExpiresAt->isPast()) {
            $newCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $user->update([
                'verification_code' => $newCode,
                'verification_code_expires_at' => now()->addMinutes(30),
            ]);

            try {
                SendVerificationEmailJob::dispatchSync($user);
            } catch (\Throwable $e) {
                Log::warning('Invio email nuovo codice fallito.', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            }

            return CustomResponse::setFailResponse('Codice scaduto. Un nuovo codice di verifica è stato inviato alla tua email.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Tutto ok: verifichiamo l'account e cancelliamo il codice
        $user->update([
            'email_verified_at' => now(),
            'verification_code' => null,
            'verification_code_expires_at' => null,
        ]);

        Auth::login($user, (bool) $request->boolean('remember'));

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        try {
            $this->guestCartMerge->merge($guestCart, $user);
            if ($request->hasSession()) {
                $request->session()->forget('cart');
            }
        } catch (\Exception $e) {
            Log::warning('Guest cart merge failed after verification', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Account verificato con successo!',
            'user' => $user,
        ])->cookie(AuthUiCookie::issueForUser($user, (bool) $request->boolean('remember')));
    }

    /**
     * Reinvia il codice di verifica via email.
     * Usato quando l'utente non ha ricevuto il codice o quando e' scaduto.
     */
    public function resendVerificationEmail(\App\Http\Requests\ResendVerificationEmailRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        // Per sicurezza, non riveliamo se l'email esiste o meno nel database
        if (! $user) {
            return CustomResponse::setSuccessResponse('Se l\'account esiste, abbiamo inviato un nuovo codice.', Response::HTTP_OK);
        }

        if ($user->email_verified_at) {
            return CustomResponse::setFailResponse('Questa email risulta già verificata. Puoi accedere normalmente.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->update([
            'verification_code' => $code,
            'verification_code_expires_at' => now()->addMinutes(30),
        ]);

        try {
            SendVerificationEmailJob::dispatchSync($user);
        } catch (\Throwable $e) {
            Log::warning('Invio email fallito.', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }

        return CustomResponse::setSuccessResponse('Nuovo codice di verifica inviato alla tua email.', Response::HTTP_OK);
    }
}
