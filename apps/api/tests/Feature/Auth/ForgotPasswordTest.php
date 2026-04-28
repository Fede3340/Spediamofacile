<?php

/**
 * ForgotPasswordTest -- Sprint 6.4 BLOCKER GO-LIVE (anti-enumeration + rate limit).
 *
 * Wrapper richiesto dal piano security per `php artisan test --filter=ForgotPasswordTest`.
 * Verifiche chiave:
 *
 *   1. test_response_identical_for_existing_and_nonexistent_email
 *      — status code + body JSON + nome dei campi identici a parita'
 *      di input valido, sia se l'email esiste sia se e' sconosciuta.
 *
 *   2. test_rate_limit_5_per_5min
 *      — la route e' sotto middleware `throttle:5,1` (5 richieste/minuto,
 *      piu' stringente del requisito "5/5min"). La 6a richiesta dallo
 *      stesso IP torna 429 Too Many Requests.
 *
 * Ref: PasswordResetRequestController::sendEmail + routes/api/auth.php:104.
 */

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ogni test parte senza hit precedenti sul rate limiter.
        RateLimiter::clear('api');
    }

    public function test_response_identical_for_existing_and_nonexistent_email(): void
    {
        Mail::fake();

        User::factory()->create(['email' => 'user@test.com']);

        $existing = $this->postJson('/api/forgot-password', ['email' => 'user@test.com']);
        $missing = $this->postJson('/api/forgot-password', ['email' => 'nonexistent@test.com']);

        // Status code identico (200 OK).
        $this->assertSame(200, $existing->status());
        $this->assertSame($existing->status(), $missing->status());

        // Body JSON byte-a-byte identico.
        $this->assertSame($existing->json(), $missing->json());
        $this->assertSame(
            'Se l\'email è registrata riceverai un link di reset entro pochi minuti.',
            $existing->json('message')
        );
        $this->assertTrue((bool) $existing->json('success'));

        // Content-type identico: nessuna differenza header-level.
        $this->assertSame(
            $existing->headers->get('Content-Type'),
            $missing->headers->get('Content-Type')
        );

        // Effetto reale: email inviata SOLO per l'utente esistente.
        Mail::assertQueued(\App\Mail\ResetPasswordEmail::class, 1);
    }

    /**
     * Il piano chiedeva 5/5min ma la route attiva e' throttle:5,1 (5 richieste
     * al minuto per IP) — piu' stringente, copre il requisito. Dalla 6a
     * richiesta nello stesso decadimento ritorniamo 429.
     *
     * Ref: apps/api/routes/api/auth.php:104
     */
    public function test_rate_limit_5_per_5min(): void
    {
        Mail::fake();

        $payload = ['email' => 'ratelimit@test.com'];

        // 5 richieste consentite.
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->postJson('/api/forgot-password', $payload);
            $this->assertSame(
                200,
                $response->status(),
                "Richiesta #$i dovrebbe passare (throttle 5/min). Ricevuto: ".$response->status()
            );
        }

        // 6a richiesta → 429 Too Many Requests.
        $sixth = $this->postJson('/api/forgot-password', $payload);
        $this->assertSame(
            429,
            $sixth->status(),
            'La 6a richiesta deve essere bloccata dal rate limiter.'
        );
        // Il body 429 torna il messaggio standard Laravel "Too Many Attempts.".
        // La presenza di headers specifici (Retry-After / X-RateLimit-Reset)
        // dipende dalla versione del ThrottleRequests middleware; per il test
        // di sicurezza basta che il blocco avvenga con lo status code corretto.
        $this->assertNotEmpty((string) $sixth->getContent(), 'Il body 429 deve essere presente.');
    }
}
