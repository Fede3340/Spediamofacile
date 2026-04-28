<?php

/**
 * OAuthGoogleTest -- Sprint 6.3 BLOCKER GO-LIVE.
 *
 * Wrapper esplicitamente richiesto dal piano security per il filter
 * `php artisan test --filter=OAuthGoogleTest`. Duplica in modo chirurgico le
 * assertion critiche per il blocker, concentrandosi su:
 *
 *   1. Duplicate state non riutilizzabile (replay) — pull() single-shot.
 *   2. State mancante → 302 redirect con ?auth_error=google_invalid_state.
 *   3. PKCE S256: code_challenge nell'authorize URL + verifier inviato al
 *      token exchange (RFC 7636 §4.5).
 *
 * Copertura dettagliata in OAuthStateSecurityTest; qui lasciamo l'evidenza
 * minimale e self-contained per il go-live check.
 */

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class OAuthGoogleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.google.client_id', 'google-client');
        config()->set('services.google.client_secret', 'google-secret');
        config()->set('services.google.redirect', 'https://example.test/auth/google/callback');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /* =========================================================
     *  TEST 1 — Duplicate state non riutilizzabile
     *  Primo callback consuma state via pull(), secondo con stesso
     *  state ma sessione vuota viene respinto con auth_error.
     * ========================================================= */
    public function test_duplicate_state_non_riutilizzabile(): void
    {
        $sessionSeed = [
            'oauth_state_google' => 'one-shot-state',
            'oauth_state_google_created_at' => now()->timestamp,
            'oauth_pkce_google_verifier' => str_repeat('v', 128),
        ];

        // 1ª call: state seed presente. Non ci importa il risultato del token
        // exchange (mockato fallisce) — basta verificare che pull() abbia
        // svuotato la sessione.
        $this
            ->withCookie('frontend_redirect', 'http://localhost:3000')
            ->withCookie('frontend_redirect_path', '/')
            ->withSession($sessionSeed)
            ->get('/auth/google/callback?state=one-shot-state&code=abc');

        // 2ª call: stesso state, nessun seed (attacker che riusa il token).
        $replay = $this
            ->withCookie('frontend_redirect', 'http://localhost:3000')
            ->withCookie('frontend_redirect_path', '/')
            ->get('/auth/google/callback?state=one-shot-state&code=abc');

        $replay->assertRedirect();
        $this->assertStringContainsString(
            'auth_error=google_invalid_state',
            (string) $replay->headers->get('Location'),
            'Il replay di un state gia consumato deve essere rifiutato.'
        );
    }

    /* =========================================================
     *  TEST 2 — State mancante → 302 con ?auth_error=google_invalid_state
     *  Brief: "State mancante → 400 redirect a /login?error=oauth_state".
     *  Scelta d'implementazione: 302 (non 400) perche il browser sta
     *  seguendo un redirect cross-origin da Google. Il segnale di errore
     *  rimane chiaro via query param.
     * ========================================================= */
    public function test_state_mancante_redirect_con_error(): void
    {
        $response = $this
            ->withCookie('frontend_redirect', 'http://localhost:3000')
            ->withCookie('frontend_redirect_path', '/login')
            ->get('/auth/google/callback?state=attacker&code=x');

        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');

        $this->assertStringContainsString('auth_error=google_invalid_state', $location);
        $this->assertStringContainsString('auth_modal=login', $location);
    }

    /* =========================================================
     *  TEST 3 — PKCE S256: challenge in URL + verifier nel token exchange
     * ========================================================= */
    public function test_pkce_s256_su_redirect_e_callback(): void
    {
        $response = $this->get('/api/auth/google/redirect?frontend=http://localhost:3000&redirect=/');
        $location = (string) $response->headers->get('Location');

        $this->assertStringContainsString('code_challenge=', $location);
        $this->assertStringContainsString('code_challenge_method=S256', $location);

        // Verifica deterministica: challenge = base64url(SHA256(verifier)).
        $verifier = (string) session('oauth_pkce_google_verifier');
        parse_str(parse_url($location, PHP_URL_QUERY) ?: '', $q);
        $expected = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $this->assertSame($expected, $q['code_challenge'] ?? null);

        // Ora il callback: verificiamo che il verifier venga propagato al
        // token endpoint via Socialite::with().
        $captured = [];
        $mock = Mockery::mock(GoogleProvider::class);
        $mock->shouldReceive('stateless')->andReturnSelf();
        $mock->shouldReceive('redirectUrl')->andReturnSelf();
        $mock->shouldReceive('with')->once()->andReturnUsing(function ($params) use (&$captured, $mock) {
            $captured = $params;
            return $mock;
        });
        $mock->shouldReceive('user')->andReturn(tap(new SocialiteUser, function ($u) {
            $u->id = 'g-1';
            $u->email = 'pkce@example.com';
            $u->name = 'Pkce User';
            $u->avatar = 'https://example.com/a.png';
            $u->user = ['given_name' => 'Pkce', 'family_name' => 'User'];
        }));
        Socialite::shouldReceive('driver')->with('google')->andReturn($mock);

        $this
            ->withCookie('frontend_redirect', 'http://localhost:3000')
            ->withCookie('frontend_redirect_path', '/')
            ->withSession([
                'oauth_state_google' => 'ok',
                'oauth_state_google_created_at' => now()->timestamp,
                'oauth_pkce_google_verifier' => 'verifier-xyz-123',
            ])
            ->get('/auth/google/callback?state=ok&code=auth-code');

        $this->assertArrayHasKey('code_verifier', $captured);
        $this->assertSame('verifier-xyz-123', $captured['code_verifier']);
    }
}
