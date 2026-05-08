<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\Auth\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * P1.1 — Test enrollment 2FA TOTP per ruoli admin.
 *
 * Copre:
 *  - enable: genera secret + QR url e li ritorna
 *  - confirm: verifica un TOTP valido e attiva 2FA + recovery codes
 *  - middleware: admin senza 2FA non puo' accedere a /api/admin/*
 *  - middleware: admin con 2FA confermato passa correttamente
 *  - disable: richiede password e azzera i campi
 *  - recovery: consuma un recovery code una sola volta
 */
class TwoFactorEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_enable_returns_secret_and_qr_url(): void
    {
        $user = User::factory()->create([
            'role' => 'Admin',
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/2fa/enable');

        $response->assertOk()
            ->assertJsonStructure(['secret', 'qr_url']);

        $secret = $response->json('secret');
        $this->assertIsString($secret);
        $this->assertMatchesRegularExpression('/^[A-Z2-7]{32}$/', $secret);
        $this->assertStringStartsWith('otpauth://totp/SpediamoFacile', $response->json('qr_url'));

        $user->refresh();
        $this->assertNotEmpty($user->two_factor_secret);
        // /enable salva il secret ma NON imposta confirmed_at (va via /confirm)
        $this->assertNull($user->two_factor_confirmed_at);
    }

    public function test_confirm_with_valid_code_marks_2fa_confirmed(): void
    {
        $user = User::factory()->create([
            'role' => 'Admin',
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user);

        // Primo step: enable per generare il secret
        $enable = $this->postJson('/api/2fa/enable')->assertOk();
        $secret = $enable->json('secret');

        // Generiamo un codice TOTP valido per il momento corrente con lo stesso service
        $service = app(TwoFactorService::class);
        $code = $this->currentTotpFor($service, $secret);

        $response = $this->postJson('/api/2fa/confirm', ['code' => $code]);

        $response->assertOk()
            ->assertJsonStructure(['recovery_codes']);

        $recoveryCodes = $response->json('recovery_codes');
        $this->assertIsArray($recoveryCodes);
        $this->assertCount(8, $recoveryCodes);

        $user->refresh();
        $this->assertNotNull($user->two_factor_confirmed_at);
        $this->assertTrue($user->hasTwoFactorEnabled());
        $this->assertCount(8, $user->two_factor_recovery_codes);
    }

    public function test_admin_without_2fa_gets_403_on_admin_endpoints(): void
    {
        $admin = User::factory()->create([
            'role' => 'Admin',
            'email_verified_at' => now(),
            // NIENTE 2FA setup
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/dashboard');

        $response->assertStatus(403)
            ->assertJsonPath('code', '2FA_REQUIRED');
    }

    public function test_admin_with_2fa_confirmed_passes_admin_endpoints(): void
    {
        $admin = User::factory()->create([
            'role' => 'Admin',
            'email_verified_at' => now(),
            'two_factor_confirmed_at' => now(),
        ]);

        Sanctum::actingAs($admin);

        // Non ci interessa il payload del dashboard — solo che il middleware
        // RequireTwoFactor non blocchi piu'.
        $response = $this->getJson('/api/admin/dashboard');

        $this->assertNotSame(403, $response->status(), 'Admin con 2FA attivo non dovrebbe essere bloccato.');
    }

    public function test_disable_requires_correct_password(): void
    {
        $user = User::factory()->create([
            'role' => 'Admin',
            'email_verified_at' => now(),
            'password' => 'secret123',
            'two_factor_confirmed_at' => now(),
            'two_factor_secret' => 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP',
            'two_factor_recovery_codes' => ['AAAAA-BBBBB'],
        ]);

        Sanctum::actingAs($user);

        // Password sbagliata → 422
        $this->postJson('/api/2fa/disable', ['current_password' => 'wrong'])
            ->assertStatus(422);

        // Password corretta → 200 e campi azzerati
        $this->postJson('/api/2fa/disable', ['current_password' => 'secret123'])
            ->assertOk();

        $user->refresh();
        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_recovery_codes);
        $this->assertNull($user->two_factor_confirmed_at);
        $this->assertFalse($user->hasTwoFactorEnabled());
    }

    public function test_recovery_code_is_consumed_once(): void
    {
        $code = 'AAAAA-BBBBB';
        $user = User::factory()->create([
            'role' => 'Admin',
            'email_verified_at' => now(),
            'two_factor_confirmed_at' => now(),
            'two_factor_secret' => 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP',
            'two_factor_recovery_codes' => [$code, 'CCCCC-DDDDD'],
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/2fa/recovery', ['recovery_code' => $code])
            ->assertOk()
            ->assertJsonPath('verified', true)
            ->assertJsonPath('remaining_codes', 1);

        // Secondo uso dello stesso codice → 422
        $this->postJson('/api/2fa/recovery', ['recovery_code' => $code])
            ->assertStatus(422);
    }

    /**
     * Genera un codice TOTP valido per il momento corrente usando lo stesso
     * algoritmo del TwoFactorService (per chiudere il loop test → verifica).
     */
    private function currentTotpFor(TwoFactorService $service, string $secret): string
    {
        $reflection = new \ReflectionClass($service);
        $decode = $reflection->getMethod('base32Decode');
        $decode->setAccessible(true);
        $generate = $reflection->getMethod('generateCodeForStep');
        $generate->setAccessible(true);

        $secretRaw = $decode->invoke($service, $secret);
        $step = (int) floor(time() / 30);

        return $generate->invoke($service, $secretRaw, $step);
    }
}
