<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleOAuthService
{
    public const STATE_TTL_MINUTES = 10;

    public const SESSION_STATE_KEY = 'oauth_state_google';

    public const SESSION_STATE_CREATED_KEY = 'oauth_state_google_created_at';

    public const SESSION_PKCE_VERIFIER_KEY = 'oauth_pkce_google_verifier';

    public function isGoogleConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }

    public function fallbackFrontendUrl(): string
    {
        return rtrim((string) config('app.frontend_url', config('app.url')), '/');
    }

    public function resolveAllowedFrontendUrl(?string $frontend): string
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
        $allowed = array_unique(array_filter([
            $fallbackHost,
            ...$this->statefulHosts(),
            'localhost',
            '127.0.0.1',
        ]));

        $isAllowed = in_array($host, $allowed, true) || str_ends_with($host, '.trycloudflare.com');

        return $isAllowed ? rtrim($value, '/') : $fallback;
    }

    public function normalizeRedirectPath(?string $redirectPath): string
    {
        $path = trim((string) $redirectPath);

        return str_starts_with($path, '/') ? $path : '/';
    }

    public function buildFrontendUrl(string $frontendUrl, string $redirectPath, array $query = []): string
    {
        $base = $frontendUrl.$redirectPath;
        if (empty($query)) {
            return $base;
        }

        $glue = str_contains($base, '?') ? '&' : '?';

        return $base.$glue.http_build_query($query);
    }

    /**
     * Genera un code_verifier PKCE (RFC 7636 §4.1): 43-128 char unreserved ASCII.
     */
    public function generatePkceVerifier(): string
    {
        return Str::random(128);
    }

    /**
     * Calcola il code_challenge PKCE S256 (RFC 7636 §4.2): base64url(SHA256(verifier)).
     */
    public function computePkceChallenge(string $verifier): string
    {
        return rtrim(
            strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'),
            '='
        );
    }

    public function logSecurityEvent(Request $request, string $reason, array $context = []): void
    {
        Log::channel('security')->warning('oauth.google.'.$reason, array_merge([
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'timestamp' => now()->toIso8601String(),
        ], $context));
    }

    /**
     * Valida lo state OAuth contro replay/CSRF/expiry.
     * Restituisce ['ok' => true] in caso di successo o ['ok' => false, 'reason' => string, 'error' => string].
     */
    public function validateState(string $expectedState, string $receivedState, int $stateCreatedAt, string $codeVerifier): array
    {
        if ($expectedState === '' || $receivedState === '') {
            return [
                'ok' => false,
                'reason' => 'state.missing',
                'error' => 'google_invalid_state',
                'context' => [
                    'expected_empty' => $expectedState === '',
                    'received_empty' => $receivedState === '',
                ],
            ];
        }

        if (! hash_equals($expectedState, $receivedState)) {
            return [
                'ok' => false,
                'reason' => 'state.mismatch',
                'error' => 'google_invalid_state',
                'context' => [],
            ];
        }

        if ($stateCreatedAt <= 0 || Carbon::createFromTimestamp($stateCreatedAt)->addMinutes(self::STATE_TTL_MINUTES)->isPast()) {
            return [
                'ok' => false,
                'reason' => 'state.expired',
                'error' => 'google_invalid_state',
                'context' => [
                    'age_seconds' => $stateCreatedAt > 0 ? now()->timestamp - $stateCreatedAt : null,
                ],
            ];
        }

        if ($codeVerifier === '') {
            return [
                'ok' => false,
                'reason' => 'pkce.verifier_missing',
                'error' => 'google_invalid_state',
                'context' => [],
            ];
        }

        return ['ok' => true];
    }

    /**
     * Aggiorna utente esistente con dati Google (id/avatar/email_verified) o crea nuovo utente.
     */
    public function upsertUserFromGoogle(\Laravel\Socialite\Contracts\User $googleUser, array $registrationContext = []): User
    {
        $googleEmail = $googleUser->getEmail();
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

            return $user;
        }

        $intent = $registrationContext['intent'] ?? null;
        $referralCode = strtoupper(trim((string) ($registrationContext['referral'] ?? '')));
        $userType = trim((string) ($registrationContext['user_type'] ?? 'privato'));
        $validatedReferral = null;

        if ($intent === 'register' && $referralCode !== '') {
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

        return $user;
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
}
