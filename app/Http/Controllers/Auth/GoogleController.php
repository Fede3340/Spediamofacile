<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\GoogleOAuthService;
use App\Support\AuthUiCookie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;

class GoogleController extends Controller
{
    public function __construct(private readonly GoogleOAuthService $oauth) {}

    public function redirectToGoogle(Request $request)
    {
        $frontend = $this->oauth->resolveAllowedFrontendUrl((string) $request->query('frontend', ''));
        $redirectPath = $this->oauth->normalizeRedirectPath((string) $request->query('redirect', '/'));

        if (! $this->oauth->isGoogleConfigured()) {
            return $this->redirectWithFrontendError($frontend, $redirectPath, 'google_unavailable');
        }

        $state = Str::random(40);
        $codeVerifier = $this->oauth->generatePkceVerifier();
        $codeChallenge = $this->oauth->computePkceChallenge($codeVerifier);

        $request->session()->put(GoogleOAuthService::SESSION_STATE_KEY, $state);
        $request->session()->put(GoogleOAuthService::SESSION_STATE_CREATED_KEY, now()->timestamp);
        $request->session()->put(GoogleOAuthService::SESSION_PKCE_VERIFIER_KEY, $codeVerifier);

        $redirectUri = config('services.google.redirect');

        /** @var GoogleProvider $google */
        $google = Socialite::driver('google');
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

        $ttl = GoogleOAuthService::STATE_TTL_MINUTES;

        return $response
            ->withCookie(cookie('frontend_redirect', $frontend, $ttl, '/', null, false, false))
            ->withCookie(cookie('frontend_redirect_path', $redirectPath, $ttl, '/', null, false, false))
            ->withCookie(cookie('frontend_social_intent', $intent === 'register' ? 'register' : 'login', $ttl, '/', null, false, false))
            ->withCookie(cookie('frontend_social_referral', $referral !== '' ? strtoupper($referral) : '', $ttl, '/', null, false, false))
            ->withCookie(cookie('frontend_social_user_type', in_array($userType, ['privato', 'commerciante'], true) ? $userType : 'privato', $ttl, '/', null, false, false));
    }

    public function handleGoogleCallback(Request $request)
    {
        $frontendUrl = $this->oauth->resolveAllowedFrontendUrl((string) ($request->cookie('frontend_redirect') ?: $this->oauth->fallbackFrontendUrl()));
        $redirectPath = $this->oauth->normalizeRedirectPath((string) ($request->cookie('frontend_redirect_path') ?: '/'));

        if (! $this->oauth->isGoogleConfigured()) {
            return $this->clearSocialState(
                $this->redirectWithFrontendError($frontendUrl, $redirectPath, 'google_unavailable'),
                $request
            );
        }

        $expectedState = (string) $request->session()->pull(GoogleOAuthService::SESSION_STATE_KEY, '');
        $stateCreatedAt = (int) $request->session()->pull(GoogleOAuthService::SESSION_STATE_CREATED_KEY, 0);
        $codeVerifier = (string) $request->session()->pull(GoogleOAuthService::SESSION_PKCE_VERIFIER_KEY, '');
        $receivedState = trim((string) $request->query('state', ''));

        $validation = $this->oauth->validateState($expectedState, $receivedState, $stateCreatedAt, $codeVerifier);
        if (! $validation['ok']) {
            $this->oauth->logSecurityEvent($request, $validation['reason'], $validation['context']);

            return $this->clearSocialState(
                $this->redirectWithFrontendError($frontendUrl, $redirectPath, $validation['error']),
                $request
            );
        }

        try {
            $redirectUri = config('services.google.redirect');
            /** @var GoogleProvider $google */
            $google = Socialite::driver('google');
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

        if (! $googleUser->getEmail()) {
            return $this->clearSocialState(
                $this->redirectWithFrontendError($frontendUrl, $redirectPath, 'google_email_missing'),
                $request
            );
        }

        $user = $this->oauth->upsertUserFromGoogle($googleUser, [
            'intent' => $request->cookie('frontend_social_intent'),
            'referral' => (string) $request->cookie('frontend_social_referral', ''),
            'user_type' => (string) $request->cookie('frontend_social_user_type', 'privato'),
        ]);

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

    private function redirectWithFrontendError(string $frontendUrl, string $redirectPath, string $error)
    {
        return redirect($this->oauth->buildFrontendUrl($frontendUrl, $redirectPath, [
            'auth_modal' => 'login',
            'auth_error' => $error,
        ]));
    }

    private function clearSocialState($response, Request $request)
    {
        if ($request->hasSession()) {
            $request->session()->forget([
                GoogleOAuthService::SESSION_STATE_KEY,
                GoogleOAuthService::SESSION_STATE_CREATED_KEY,
                GoogleOAuthService::SESSION_PKCE_VERIFIER_KEY,
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
