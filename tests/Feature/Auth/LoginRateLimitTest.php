<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * P0.3 — anti brute-force login.
 * Verifica il rate limiter "login-by-email" registrato in AppServiceProvider:
 * 5 tentativi al minuto per chiave (email|IP) → 6° tentativo restituisce 429.
 */
class LoginRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Pulizia counter per evitare interferenze cross-test.
        RateLimiter::clear('login-by-email');
    }

    public function test_user_rate_limited_after_5_failed_login_attempts(): void
    {
        $email = 'bruteforce-victim@example.com';

        User::factory()->create([
            'email' => $email,
            'password' => bcrypt('CorrectHorseBatteryStaple!2026'),
            'email_verified_at' => now(),
        ]);

        $payload = [
            'email' => $email,
            'password' => 'wrong-password',
        ];

        // 5 tentativi consentiti — credenziali errate, ma niente 429.
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->postJson('/api/custom-login', $payload);
            $this->assertNotSame(
                429,
                $response->getStatusCode(),
                "Il tentativo #{$i} non dovrebbe essere rate-limited (atteso < 5 fallimenti)."
            );
        }

        // 6° tentativo → 429 Too Many Requests.
        $response = $this->postJson('/api/custom-login', $payload);
        $response->assertStatus(429);
    }
}
