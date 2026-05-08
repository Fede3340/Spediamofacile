<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * P1.2 (compliance) — verifica che il middleware admin.audit produca
 * automaticamente una riga audit_logs per ogni mutation admin 2xx.
 */
class AdminActionsAreAuditedTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create([
            'role' => 'Admin',
            'email_verified_at' => now(),
            // P1.1 — admin senza 2FA confermato verrebbe bloccato dal middleware RequireTwoFactor
            'two_factor_confirmed_at' => now(),
        ]);

        Sanctum::actingAs($admin);

        return $admin;
    }

    public function test_admin_post_settings_writes_audit_row(): void
    {
        $admin = $this->actingAsAdmin();

        $response = $this->postJson('/api/admin/settings', [
            'support_email' => 'support@example.com',
        ]);

        $response->assertOk();

        $log = AuditLog::query()->latest('id')->first();
        $this->assertNotNull($log, 'Nessuna riga audit_logs scritta dal middleware.');
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame('admin', $log->actor_type);
        $this->assertStringStartsWith('admin.post.', $log->action);
        $this->assertSame('POST', $log->context['method']);
        $this->assertSame('api/admin/settings', $log->context['path']);
        $this->assertSame(200, $log->context['status']);
    }

    public function test_admin_get_does_not_create_audit_row(): void
    {
        $this->actingAsAdmin();

        $before = AuditLog::query()->count();

        $response = $this->getJson('/api/admin/contact-messages');
        $response->assertOk();

        $this->assertSame($before, AuditLog::query()->count(), 'Le GET non devono produrre audit.');
    }

    public function test_failed_admin_mutation_does_not_create_audit_row(): void
    {
        $this->actingAsAdmin();

        $before = AuditLog::query()->count();

        // Stripe key invalida → 422 → NON deve loggare.
        $response = $this->postJson('/api/admin/settings', [
            'stripe_public_key' => 'not-a-real-key',
        ]);
        $response->assertStatus(422);

        $this->assertSame($before, AuditLog::query()->count(), 'Le risposte non-2xx non devono produrre audit.');
    }
}
