<?php

/**
 * EmailEnumerationSecurityTest -- Sprint 6.4 BLOCKER GO-LIVE.
 *
 * Verifica che gli endpoint auth pubblici non permettano all'attaccante
 * di distinguere email registrate da email inesistenti, ne' tramite:
 *   - differenze nel corpo della risposta (status code, messaggio, struttura)
 *   - differenze nei tempi di risposta (timing attack)
 *
 * Endpoint coperti:
 *   - POST /api/forgot-password
 *   - POST /api/custom-register
 *   - POST /api/custom-login
 *
 * Soglia timing: diff medio < 50 ms tra ramo "utente esiste" e "utente non esiste"
 * (l'OWASP non fissa un numero ma raccomanda "indistinguibile": 50 ms e' molto
 * al di sotto della latenza di rete tipica, quindi non misurabile dall'esterno).
 *
 * Ref: https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html#authentication-responses
 */

namespace Tests\Feature\Auth;

use App\Mail\ResetPasswordEmail;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class EmailEnumerationSecurityTest extends TestCase
{
    use RefreshDatabase;

    private const TIMING_TOLERANCE_MS = 50.0;
    private const TIMING_SAMPLES = 4;

    /**
     * Setup: disabilita i rate limit per permettere le misurazioni multiple
     * di timing sullo stesso endpoint. La protezione throttle e' gia'
     * verificata altrove; qui ci interessa solo il costo per-richiesta.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Sostituisce tutti i rate limiter registrati con un limite praticamente
        // illimitato, in modo che 10+ request consecutive non restituiscano 429.
        foreach (['api', 'login', 'register'] as $name) {
            RateLimiter::for($name, fn (Request $request) => Limit::none());
        }
        // Throttle middleware inline (es. throttle:5,1) ignora i limiter sopra:
        // usiamo withoutMiddleware nei test che ne hanno bisogno.
    }

    /**
     * Misura la durata media in millisecondi di un POST ripetuto N volte.
     * Scartiamo il primo sample (warm-up framework) e mediamo i successivi.
     */
    private function measureAverageMs(string $uri, array $payload, int $samples = self::TIMING_SAMPLES): float
    {
        // Warm-up (esclusa dalla media: carica autoloader/config)
        $this->postJson($uri, $payload);

        $durations = [];
        for ($i = 0; $i < $samples; $i++) {
            $start = microtime(true);
            $this->postJson($uri, $payload);
            $durations[] = (microtime(true) - $start) * 1000;
        }

        return array_sum($durations) / count($durations);
    }

    // ------------------------------------------------------------------
    // Forgot password
    // ------------------------------------------------------------------

    public function test_forgot_password_same_response_for_existing_and_missing_email(): void
    {
        Mail::fake();

        User::factory()->create(['email' => 'existing@example.com']);

        $responseExisting = $this->postJson('/api/forgot-password', [
            'email' => 'existing@example.com',
        ]);

        $responseMissing = $this->postJson('/api/forgot-password', [
            'email' => 'missing@example.com',
        ]);

        // Stesso status code
        $this->assertSame($responseExisting->status(), $responseMissing->status());
        $this->assertSame(200, $responseExisting->status());

        // Stesso corpo JSON (messaggio generico anti-enumeration)
        $this->assertSame($responseExisting->json(), $responseMissing->json());
        $this->assertSame(
            'Se l\'email è registrata riceverai un link di reset entro pochi minuti.',
            $responseExisting->json('message')
        );

        // Effetto reale: email inviata solo per l'account esistente
        Mail::assertQueued(ResetPasswordEmail::class, 1);
    }

    public function test_forgot_password_same_timing_diff_less_than_50ms(): void
    {
        Mail::fake();

        // Disabilitiamo il throttle inline "throttle:5,1" per poter eseguire
        // le misurazioni ripetute sullo stesso endpoint senza incappare in 429.
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        User::factory()->create(['email' => 'existing-timing@example.com']);

        $avgExisting = $this->measureAverageMs('/api/forgot-password', [
            'email' => 'existing-timing@example.com',
        ]);

        $avgMissing = $this->measureAverageMs('/api/forgot-password', [
            'email' => 'missing-timing@example.com',
        ]);

        $diffMs = abs($avgExisting - $avgMissing);

        $this->assertLessThan(
            self::TIMING_TOLERANCE_MS,
            $diffMs,
            sprintf(
                'Timing attack possibile: diff %.2f ms tra email esistente (%.2f ms) e inesistente (%.2f ms). Soglia: %.2f ms.',
                $diffMs,
                $avgExisting,
                $avgMissing,
                self::TIMING_TOLERANCE_MS
            )
        );
    }

    // ------------------------------------------------------------------
    // Register
    // ------------------------------------------------------------------

    /**
     * Dati di registrazione validi (allineato con RegisterTest::validRegistrationData).
     */
    private function validRegistrationData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Mario',
            'surname' => 'Rossi',
            'email' => 'mario@example.com',
            'email_confirmation' => 'mario@example.com',
            'prefix' => '+39',
            'telephone_number' => '3331234567',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => 'User',
            'privacy_accepted' => true,
        ], $overrides);
    }

    public function test_register_duplicate_email_no_enumeration(): void
    {
        Mail::fake();

        User::factory()->create(['email' => 'dup@example.com']);

        $responseDuplicate = $this->postJson('/api/custom-register', $this->validRegistrationData([
            'email' => 'dup@example.com',
            'email_confirmation' => 'dup@example.com',
        ]));

        $responseNew = $this->postJson('/api/custom-register', $this->validRegistrationData([
            'email' => 'nuovo@example.com',
            'email_confirmation' => 'nuovo@example.com',
        ]));

        // Stesso status code (201 Created in entrambi i casi)
        $this->assertSame($responseDuplicate->status(), $responseNew->status());
        $this->assertSame(201, $responseDuplicate->status());

        // Stesso flag success e messaggio
        $this->assertTrue($responseDuplicate->json('success'));
        $this->assertTrue($responseNew->json('success'));
        $this->assertSame(
            $responseDuplicate->json('message'),
            $responseNew->json('message')
        );

        // Side-effect verificabili SOLO dall'admin (non dall'attaccante):
        // il duplicato non crea un secondo utente
        $this->assertEquals(1, User::where('email', 'dup@example.com')->count());
        // Il duplicato NON riceve email di verifica
        Mail::assertSent(\App\Mail\VerificationEmail::class, fn ($m) => $m->hasTo('nuovo@example.com'));
        Mail::assertNotSent(\App\Mail\VerificationEmail::class, fn ($m) => $m->hasTo('dup@example.com'));
    }

    // ------------------------------------------------------------------
    // Login
    // ------------------------------------------------------------------

    public function test_login_missing_user_same_response_as_wrong_password(): void
    {
        $user = User::factory()->create([
            'email' => 'real@example.com',
            'password' => 'Password1!',
        ]);

        $responseWrongPassword = $this->postJson('/api/custom-login', [
            'email' => 'real@example.com',
            'password' => 'WrongPass1!',
        ]);

        $responseMissing = $this->postJson('/api/custom-login', [
            'email' => 'never-registered@example.com',
            'password' => 'WrongPass1!',
        ]);

        // Stesso status code (422 ValidationException)
        $this->assertSame($responseWrongPassword->status(), $responseMissing->status());
        $this->assertSame(422, $responseWrongPassword->status());

        // Stesso set di chiavi di errore
        $this->assertSame(
            array_keys((array) $responseWrongPassword->json('errors')),
            array_keys((array) $responseMissing->json('errors'))
        );

        // Messaggio generico identico, mai "utente non esiste"
        $errorMessage = $responseMissing->json('errors.email.0');
        $this->assertSame('Le credenziali non sono corrette.', $errorMessage);
        $this->assertSame(
            $responseWrongPassword->json('errors.email.0'),
            $responseMissing->json('errors.email.0')
        );
    }

    public function test_login_missing_user_same_timing_as_wrong_password(): void
    {
        // Disabilitiamo il throttle inline "throttle:10,1" per le misurazioni ripetute.
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        User::factory()->create([
            'email' => 'timing-real@example.com',
            'password' => 'Password1!',
        ]);

        $avgWrongPassword = $this->measureAverageMs('/api/custom-login', [
            'email' => 'timing-real@example.com',
            'password' => 'WrongPass1!',
        ]);

        $avgMissing = $this->measureAverageMs('/api/custom-login', [
            'email' => 'timing-missing@example.com',
            'password' => 'WrongPass1!',
        ]);

        $diffMs = abs($avgWrongPassword - $avgMissing);

        $this->assertLessThan(
            self::TIMING_TOLERANCE_MS,
            $diffMs,
            sprintf(
                'Timing attack possibile: diff %.2f ms tra login esistente+pwd errata (%.2f ms) e email inesistente (%.2f ms). Soglia: %.2f ms.',
                $diffMs,
                $avgWrongPassword,
                $avgMissing,
                self::TIMING_TOLERANCE_MS
            )
        );
    }
}
