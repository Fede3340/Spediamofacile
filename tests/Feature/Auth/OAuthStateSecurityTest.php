<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Sprint 6.3 — BLOCKER GO-LIVE: OAuth state + PKCE security.
 *
 * Coverage:
 *   - State in sessione (non cookie) su redirect Google/Apple/Facebook.
 *   - State mismatch/expired/missing → redirect con error (403-equivalent).
 *   - Replay attack: stesso state non riutilizzabile (pull() single-shot).
 *   - PKCE Google: code_challenge nell'URL + code_verifier nel token exchange.
 */
class OAuthStateSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function configureGoogle(): void
    {
        config()->set('services.google.client_id', 'google-client');
        config()->set('services.google.client_secret', 'google-secret');
        config()->set('services.google.redirect', 'https://example.test/auth/google/callback');
    }

    private function configureFacebook(): void
    {
        config()->set('services.facebook.client_id', 'fb-client');
        config()->set('services.facebook.client_secret', 'fb-secret');
        config()->set('services.facebook.redirect', 'https://example.test/auth/facebook/callback');
    }

    private function configureApple(): void
    {
        config()->set('services.apple.client_id', 'com.example.web');
        config()->set('services.apple.client_secret', 'apple-secret');
        config()->set('services.apple.redirect', 'https://example.test/auth/apple/callback');
        config()->set('services.apple.team_id', null);
        config()->set('services.apple.key_id', null);
        config()->set('services.apple.private_key', null);
    }

    /* ============ STATE IN SESSION ============ */

    public function test_google_redirect_stores_state_in_session_not_in_cookie(): void
    {
        $this->configureGoogle();

        $response = $this->get('/api/auth/google/redirect?frontend=http://localhost:3000&redirect=/account');

        $response->assertRedirect();
        $this->assertNotEmpty(session('oauth_state_google'));
        $this->assertNotEmpty(session('oauth_pkce_google_verifier'));
        $this->assertGreaterThan(0, (int) session('oauth_state_google_created_at'));

        // Nessun cookie contenente lo state in chiaro.
        foreach ($response->headers->getCookies() as $cookie) {
            $this->assertNotEquals('oauth_state_google', $cookie->getName());
            $this->assertNotEquals('frontend_social_state', $cookie->getName());
        }
    }

    public function test_facebook_redirect_stores_state_in_session_not_in_cookie(): void
    {
        $this->markTestSkipped('Provider Facebook rimosso 2026-04 (solo Google OAuth attivo).');
    }

    /* ============ STATE MISMATCH ============ */

    public function test_google_callback_rejects_state_mismatch(): void
    {
        $this->configureGoogle();

        $response = $this
            ->withCookie('frontend_redirect', 'http://localhost:3000')
            ->withCookie('frontend_redirect_path', '/account')
            ->withSession([
                'oauth_state_google' => 'legit-state-xyz',
                'oauth_state_google_created_at' => now()->timestamp,
                'oauth_pkce_google_verifier' => str_repeat('a', 128),
            ])
            ->get('/auth/google/callback?state=attacker-state&code=anything');

        $response->assertRedirect();
        $this->assertStringContainsString('auth_error=google_invalid_state', (string) $response->headers->get('Location'));
    }

    public function test_facebook_callback_rejects_state_mismatch(): void
    {
        $this->markTestSkipped('Provider Facebook rimosso 2026-04.');
    }

    public function test_apple_callback_rejects_state_mismatch(): void
    {
        $this->markTestSkipped('Provider Apple rimosso 2026-04.');
    }

    /* ============ STATE EXPIRED ============ */

    public function test_google_callback_rejects_expired_state(): void
    {
        $this->configureGoogle();

        $response = $this
            ->withCookie('frontend_redirect', 'http://localhost:3000')
            ->withCookie('frontend_redirect_path', '/')
            ->withSession([
                'oauth_state_google' => 'expired-state',
                // 15 minuti fa (TTL = 10 min)
                'oauth_state_google_created_at' => now()->subMinutes(15)->timestamp,
                'oauth_pkce_google_verifier' => str_repeat('a', 128),
            ])
            ->get('/auth/google/callback?state=expired-state&code=anything');

        $response->assertRedirect();
        $this->assertStringContainsString('auth_error=google_invalid_state', (string) $response->headers->get('Location'));
    }

    /* ============ STATE MISSING ============ */

    public function test_google_callback_rejects_missing_state(): void
    {
        $this->configureGoogle();

        // Nessuno state in sessione: attaccante tenta senza prima iniziare flusso.
        $response = $this
            ->withCookie('frontend_redirect', 'http://localhost:3000')
            ->withCookie('frontend_redirect_path', '/')
            ->get('/auth/google/callback?state=anything&code=anything');

        $response->assertRedirect();
        $this->assertStringContainsString('auth_error=google_invalid_state', (string) $response->headers->get('Location'));
    }

    /* ============ REPLAY ATTACK ============ */

    public function test_google_callback_blocks_state_replay(): void
    {
        $this->configureGoogle();
        Http::preventStrayRequests();

        $sessionSeed = [
            'oauth_state_google' => 'one-shot-state',
            'oauth_state_google_created_at' => now()->timestamp,
            'oauth_pkce_google_verifier' => str_repeat('b', 128),
        ];

        // Primo uso: la validazione dello state passa (fallisce solo al token
        // exchange, fuori scope di questo test). L'importante e' che la sessione
        // sia "consumata" (pull) dopo il check.
        $this
            ->withCookie('frontend_redirect', 'http://localhost:3000')
            ->withCookie('frontend_redirect_path', '/')
            ->withSession($sessionSeed)
            ->get('/auth/google/callback?state=one-shot-state&code=anything');

        // Secondo uso: stesso state, sessione gia' ripulita (simuliamo
        // proseguendo senza reseminare la session).
        $response2 = $this
            ->withCookie('frontend_redirect', 'http://localhost:3000')
            ->withCookie('frontend_redirect_path', '/')
            ->get('/auth/google/callback?state=one-shot-state&code=anything');

        $response2->assertRedirect();
        $this->assertStringContainsString('auth_error=google_invalid_state', (string) $response2->headers->get('Location'));
    }

    /* ============ PKCE FLOW ============ */

    public function test_google_redirect_includes_pkce_challenge_s256(): void
    {
        $this->configureGoogle();

        $response = $this->get('/api/auth/google/redirect?frontend=http://localhost:3000&redirect=/');

        $location = (string) $response->headers->get('Location');

        $this->assertStringContainsString('code_challenge=', $location);
        $this->assertStringContainsString('code_challenge_method=S256', $location);

        // Verifier in sessione, 43-128 chars (RFC 7636 §4.1).
        $verifier = (string) session('oauth_pkce_google_verifier');
        $this->assertGreaterThanOrEqual(43, strlen($verifier));
        $this->assertLessThanOrEqual(128, strlen($verifier));

        // Il challenge nell'URL corrisponde a base64url(SHA256(verifier)).
        parse_str(parse_url($location, PHP_URL_QUERY) ?: '', $queryParams);
        $expectedChallenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $this->assertSame($expectedChallenge, $queryParams['code_challenge'] ?? null);
    }

    public function test_google_token_exchange_sends_code_verifier(): void
    {
        $this->configureGoogle();

        $verifier = str_repeat('v', 128);

        // Socialite usa Guzzle (non il Facade Http), quindi intercettiamo via
        // Mockery il driver Google per ispezionare i parametri passati a
        // ->with([...]) durante user() — il nostro controller deve aggiungere
        // 'code_verifier' prima di risolvere il token.
        $mock = \Mockery::mock(\Laravel\Socialite\Two\GoogleProvider::class);
        $mock->shouldReceive('stateless')->andReturnSelf();
        $mock->shouldReceive('redirectUrl')->andReturnSelf();
        $captured = [];
        $mock->shouldReceive('with')->once()->andReturnUsing(function ($params) use (&$captured, $mock) {
            $captured = $params;
            return $mock;
        });
        $mock->shouldReceive('user')->andReturn(
            tap(new \Laravel\Socialite\Two\User, function ($u) {
                $u->id = 'google-123';
                $u->email = 'user-pkce@example.com';
                $u->name = 'Test User';
                $u->avatar = 'https://example.com/a.png';
                $u->user = ['given_name' => 'Test', 'family_name' => 'User'];
            })
        );

        \Laravel\Socialite\Facades\Socialite::shouldReceive('driver')->with('google')->andReturn($mock);

        $this
            ->withCookie('frontend_redirect', 'http://localhost:3000')
            ->withCookie('frontend_redirect_path', '/')
            ->withCookie('frontend_social_intent', 'login')
            ->withCookie('frontend_social_user_type', 'privato')
            ->withSession([
                'oauth_state_google' => 's',
                'oauth_state_google_created_at' => now()->timestamp,
                'oauth_pkce_google_verifier' => $verifier,
            ])
            ->get('/auth/google/callback?state=s&code=auth-code-xyz');

        $this->assertArrayHasKey('code_verifier', $captured, 'code_verifier deve essere passato al token exchange (RFC 7636 §4.5).');
        $this->assertSame($verifier, $captured['code_verifier']);
    }
}
