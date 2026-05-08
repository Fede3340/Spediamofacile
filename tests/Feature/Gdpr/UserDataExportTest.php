<?php

namespace Tests\Feature\Gdpr;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P1.3 — copertura endpoint canonico /api/me/export-data:
 * scarica i dati personali dell'utente come allegato JSON e
 * registra una riga gdpr.export.download in audit_logs.
 */
class UserDataExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_download_own_data(): void
    {
        $user = User::factory()->create([
            'email' => 'pippo@example.com',
        ]);

        $response = $this->actingAs($user)->get('/api/me/export-data');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment;', (string) $disposition);
        $this->assertStringContainsString('spediamofacile-export-'.$user->id.'-', (string) $disposition);

        $payload = json_decode($response->streamedContent(), true);
        $this->assertIsArray($payload);
        $this->assertSame('pippo@example.com', $payload['profile']['email'] ?? null);
        $this->assertArrayHasKey('orders', $payload);
        $this->assertArrayHasKey('addresses', $payload);
        $this->assertArrayHasKey('cookie_consents', $payload);
    }

    public function test_guest_cannot_download_data(): void
    {
        $this->getJson('/api/me/export-data')->assertUnauthorized();
    }

    public function test_user_export_does_not_leak_other_users_data(): void
    {
        $alice = User::factory()->create(['email' => 'alice@example.com']);
        User::factory()->create(['email' => 'bob@example.com']);

        $payload = json_decode(
            $this->actingAs($alice)->get('/api/me/export-data')->streamedContent(),
            true,
        );

        $this->assertSame($alice->id, $payload['profile']['id']);
        $this->assertSame('alice@example.com', $payload['profile']['email']);
        $serialized = json_encode($payload);
        $this->assertStringNotContainsString('bob@example.com', (string) $serialized);
    }

    public function test_export_creates_audit_log_entry(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/api/me/export-data')->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'gdpr.export.download',
            'target_type' => 'User',
            'target_id' => $user->id,
        ]);
    }
}
