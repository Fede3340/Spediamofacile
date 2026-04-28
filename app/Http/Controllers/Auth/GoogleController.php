<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

use App\Models\User;
use App\Support\AuthUiCookie;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;

class GoogleController extends Controller
{
    /** TTL state/PKCE in minuti. RFC 6749 raccomanda breve durata. */
    private const STATE_TTL_MINUTES = 10;

    private const SESSION_STATE_KEY = 'oauth_state_google';
    private const SESSION_STATE_CREATED_KEY = 'oauth_state_google_created_at';
    private const SESSION_PKCE_VERIFIER_KEY = 'oauth_pkce_google_verifier';

    private function isGoogleConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }

    private function statefulHosts(): array
    {
        $stateful = config('sanctum.stateful', []);
        $items = is_array($stateful) ? $stateful : explode(',', (string) $stateful);

        return collect($items)
            ->map(fn ($item) => trim(strtolower((string) $item)))
            ->filter()
            ->map(fn ($item) => explode(':', $item)[0])
            ->values()
            ->all();
    }

    private function fallbackFrontendUrl(): string
    {
        return rtrim((string) config('app.frontend_url', config('app.url')), '/');
    }

    private function resolveAllowedFrontendUrl(?string $frontend): string
    {
        $fallback = $this->fallbackFrontendUrl();
        $value = trim((string) $frontend);

        if (! filter_var($value, FILTER_VALIDATE_URL)) {
            return $fallback;
        }

        $parts = parse_url($value);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            return $fallback;
        }

        $fallbackHost = strtolower((string) parse_url($fallback, PHP_URL_HOST));
        $stateful = $this->statefulHosts();

        $allowed = array_unique(array_filter([
            $fallbackHost,
            ...$stateful,
            'localhost',
            '127.0.0.1',
        ]));

        $isAllowed = in_array($host, $allowed, true) || str_ends_with($host, '.trycloudflare.com');

        return $isAllowed ? rtrim($value, '/') : $fallback;
    }

    private function normalizeRedirectPath(?string $redirectPath): string
    {
        $path = trim((string) $redirectPath);

        return str_starts_with($path, '/') ? $path : '/';
    }

    private function buildFrontendUrl(string $frontendUrl, string $redirectPath, array $query = []): string
    {
        $base = $frontendUrl.$redirectPath;
        if (empty($query)) {
            return $base;
        }

        $glue = str_contains($base, '?') ? '&' : '?';

        return $base.$glue.http_build_query($query);
    }

    private function redirectWithFrontendError(string $frontendUrl, string $redirectPath, string $error)
    {
        return redirect($this->buildFrontendUrl($frontendUrl, $redirectPath, [
            'auth_modal' => 'login',
            'auth_error' => $error,
        ]));
    }

    /**
     * Genera un code_verifier PKCE (RFC 7636 §4.1): 43-128 char unreserved ASCII.
     * Str::random usa alphanum (0-9A-Za-z), valido come subset di unreserved.
     */
    private function generatePkceVerifier(): string
    {
        return Str::random(128);
    }

    /**
     * Calcola il code_challenge PKCE S256 (RFC 7636 §4.2): base64url(SHA256(verifier)),
     * senza padding `=`. Google richiede "S256" method (plain è deprecato).
     */
    private function computePkceChallenge(string $verifier): string
    {
        return rtrim(
            strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'),
            '='
        );
    }

    /**
     * Logga un evento di sicurezza (state mismatch, expired, missing).
     * Forward a Sentry previsto in Sprint W1.1.
     */
    private function logSecurityEvent(Request $request, string $reason, array $context = []): void
    {
        Log::channel('security')->warning('oauth.google.'.$reason, array_merge([
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'timestamp' => now()->toIso8601String(),
        ], $context));
    }

    // Reindirizza l'utente alla pagina di accesso di Google.
    public function redirectToGoogle(Request $request)
    {
        $frontend = $this->resolveAllowedFrontendUrl((string) $request->query('frontend', ''));
        $redirectPath = $this->normalizeRedirectPath((string) $request->query('redirect', '/'));

        if (! $this->isGoogleConfigured()) {
            return $this->redirectWithFrontendError($frontend, $redirectPath, 'google_unavailable');
        }

        // Generiamo state CSRF + PKCE verifier/challenge PRIMA di costruire l'URL.
        $state = Str::random(40);
        $codeVerifier = $this->generatePkceVerifier();
        $codeChallenge = $this->computePkceChallenge($codeVerifier);

        // Salviamo state e verifier nella SESSIONE server-side. Il session id e'
        // bound al cookie di sessione del browser → CSRF protection solida (RFC 6749).
        $request->session()->put(self::SESSION_STATE_KEY, $state);
        $request->session()->put(self::SESSION_STATE_CREATED_KEY, now()->timestamp);
        $request->session()->put(self::SESSION_PKCE_VERIFIER_KEY, $codeVerifier);

        $redirectUri = config('services.google.redirect');

        /** @var GoogleProvider $google */
        $google = Socialite::driver('google');
        // stateless(): bypass Socialite internal state (gestiamo noi). with(): param
        // extra nell'authorize URL — inclusi code_challenge/method per PKCE e il
        // nostro state manuale (Socialite stateless non aggiunge state da solo).
        $response = $google
            ->stateless()
            ->redirectUrl($redirectUri)
            ->with([
                'prompt' => 'select_account consent',
                'state' => $state,
                'code_challenge' => $codeChallenge,
                'code_challenge_method' => 'S256',
            ])
            ->redirect();

        $intent = trim((string) $request->query('intent', 'login'));
        $referral = trim((string) $request->query('ref', ''));
        $userType = trim((string) $request->query('user_type', ''));

        // TTL cookie di contesto allineato allo state (10 min). Cambiato da 15.
        return $response
            ->withCookie(cookie('frontend_redirect', $frontend, self::STATE_TTL_MINUTES, '/', null, false, false))
            ->withCookie(cookie('frontend_redirect_path', $redirectPath, self::STATE_TTL_MINUTES, '/', null, false, false))
            ->withCookie(cookie('frontend_social_intent', $intent === 'register' ? 'register' : 'login', self::STATE_TTL_MINUTES, '/', null, false, false))
            ->withCookie(cookie('frontend_social_referral', $referral !== '' ? strtoupper($referral) : '', self::STATE_TTL_MINUTES, '/', null, false, false))
            ->withCookie(cookie('frontend_social_user_type', in_array($userType, ['privato', 'commerciante'], true) ? $userType : 'privato', self::STATE_TTL_MINUTES, '/', null, false, false));
    }

    public function handleGoogleCallback(Request $request)
    {
        $frontendUrl = $this->resolveAllowedFrontendUrl((string) ($request->cookie('frontend_redirect') ?: $this->fallbackFrontendUrl()));
        $redirectPath = $this->normalizeRedirectPath((string) ($request->cookie('frontend_redirect_path') ?: '/'));

        if (! $this->isGoogleConfigured()) {
            return $this->clearSocialState(
                $this->redirectWithFrontendError($frontendUrl, $redirectPath, 'google_unavailable'),
                $request
            );
        }

        // === STEP 1: Validazione state (CSRF) ===
        // pull() legge e rimuove atomically → replay impossibile sullo stesso session id.
        $expectedState = (string) $request->session()->pull(self::SESSION_STATE_KEY, '');
        $stateCreatedAt = (int) $request->session()->pull(self::SESSION_STATE_CREATED_KEY, 0);
        $codeVerifier = (string) $request->session()->pull(self::SESSION_PKCE_VERIFIER_KEY, '');
        $receivedState = trim((string) $request->query('state', ''));

        if ($expectedState === '' || $receivedState === '') {
            $this->logSecurityEvent($request, 'state.missing', [
                'expected_empty' => $expectedState === '',
                'received_empty' => $receivedState === '',
            ]);

            return $this->clearSocialState(
                $this->redirectWithFrontendError($frontendUrl, $redirectPath, 'google_invalid_state'),
                $request
            );
        }

        // Confronto timing-safe
        if (! hash_equals($expectedState, $receivedState)) {
            $this->logSecurityEvent($request, 'state.mismatch');

            return $this->clearSocialState(
                $this->redirectWithFrontendError($frontendUrl, $redirectPath, 'google_invalid_state'),
                $request
            );
        }

        // Scadenza state: previene replay a lungo termine
        if ($stateCreatedAt <= 0 || Carbon::createFromTimestamp($stateCreatedAt)->addMinutes(self::STATE_TTL_MINUTES)->isPast()) {
            $this->logSecurityEvent($request, 'state.expired', [
                'age_seconds' => $stateCreatedAt > 0 ? now()->timestamp - $stateCreatedAt : null,
            ]);

            return $this->clearSocialState(
                $this->redirectWithFrontendError($frontendUrl, $redirectPath, 'google_invalid_state'),
                $request
            );
        }

        if ($codeVerifier === '') {
            // PKCE verifier mancante → richiesta non è stata originata dal nostro redirectToGoogle.
            $this->logSecurityEvent($request, 'pkce.verifier_missing');

            return $this->clearSocialState(
                $this->redirectWithFrontendError($frontendUrl, $redirectPath, 'google_invalid_state'),
                $request
            );
        }

        // === STEP 2: Token exchange con PKCE ===
        try {
            $redirectUri = config('services.google.redirect');
            /** @var GoogleProvider $google */
            $google = Socialite::driver('google');
            // code_verifier va inviato al token endpoint (RFC 7636 §4.5).
            // Socialite::with() propaga param sia a authorize URL sia a token request.
            $googleUser = $google
                ->stateless()
                ->redirectUrl($redirectUri)
                ->with(['code_verifier' => $codeVerifier])
                ->user();
        } catch (\Exception $e) {
            return $this->clearSocialState(
                $this->redirectWithFrontendError($frontendUrl, $redirectPath, 'google_failed'),
                $request
            );
        }

        $googleEmail = $googleUser->getEmail();
        if (! $googleEmail) {
            return $this->clearSocialState(
                $this->redirectWithFrontendError($frontendUrl, $redirectPath, 'google_email_missing'),
                $request
            );
        }

        $user = User::where('email', $googleEmail)->first();

        if ($user) {
            $dirty = false;
            if (! $user->google_id) {
                $user->google_id = $googleUser->getId();
                $dirty = true;
            }
            if (! $user->avatar && $googleUser->getAvatar()) {
                $user->avatar = $googleUser->getAvatar();
                $dirty = true;
            }
            if (! $user->email_verified_at) {
                $user->email_verified_at = now();
                $dirty = true;
            }
            if ($dirty) {
                $user->save();
            }
        } else {
            $socialIntent = $request->cookie('frontend_social_intent');
            $referralCode = strtoupper(trim((string) $request->cookie('frontend_social_referral', '')));
            $userType = trim((string) $request->cookie('frontend_social_user_type', 'privato'));
            $validatedReferral = null;

            if ($socialIntent === 'register' && $referralCode !== '') {
                $validatedReferral = User::where('referral_code', $referralCode)
                    ->where('role', 'Partner Pro')
                    ->value('referral_code');
            }

            $user = new User([
                'email' => $googleEmail,
                'name' => $googleUser->user['given_name'] ?? $googleUser->getName(),
                'surname' => $googleUser->user['family_name'] ?? '',
                'telephone_number' => '',
                'email_verified_at' => now(),
                'password' => Str::random(16),
                'avatar' => $googleUser->getAvatar(),
                'user_type' => in_array($userType, ['privato', 'commerciante'], true) ? $userType : 'privato',
            ]);
            $user->role = 'User';
            $user->google_id = $googleUser->getId();
            if ($validatedReferral !== null) {
                $user->referred_by = $validatedReferral;
            }
            $user->save();
        }

        Auth::login($user, true);
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return redirect($frontendUrl.$redirectPath)
            ->withCookie(AuthUiCookie::issueForUser($user, true))
            ->withoutCookie('frontend_redirect')
            ->withoutCookie('frontend_redirect_path')
            ->withoutCookie('frontend_social_intent')
            ->withoutCookie('frontend_social_referral')
            ->withoutCookie('frontend_social_user_type');
    }

    /**
     * Pulisce residui di stato OAuth in sessione (difesa in profondita') + cookie di contesto.
     * Chiamato su ogni branch di errore per evitare stati "appesi".
     */
    private function clearSocialState($response, Request $request)
    {
        if ($request->hasSession()) {
            $request->session()->forget([
                self::SESSION_STATE_KEY,
                self::SESSION_STATE_CREATED_KEY,
                self::SESSION_PKCE_VERIFIER_KEY,
            ]);
        }

        return $response
            ->withoutCookie('frontend_redirect')
            ->withoutCookie('frontend_redirect_path')
            ->withoutCookie('frontend_social_intent')
            ->withoutCookie('frontend_social_referral')
            ->withoutCookie('frontend_social_user_type');
    }
}
