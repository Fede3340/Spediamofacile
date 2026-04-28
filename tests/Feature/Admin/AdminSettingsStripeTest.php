<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminSettingsStripeTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create([
            'role' => 'Admin',
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($admin);

        return $admin;
    }

    public function test_admin_settings_mirror_stripe_keys_to_legacy_entries(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/admin/settings', [
            'stripe_public_key' => 'pk_test_1234567890ABCDE',
            'stripe_secret_key' => 'sk_test_1234567890ABCDE',
            'stripe_webhook_secret' => 'whsec_1234567890ABCDE',
            'support_email' => 'support@example.com',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->assertSame('pk_test_1234567890ABCDE', Setting::get('stripe_public_key'));
        $this->assertSame('pk_test_1234567890ABCDE', Setting::get('stripe_key'));
        $this->assertSame('sk_test_1234567890ABCDE', Setting::get('stripe_secret_key'));
        $this->assertSame('sk_test_1234567890ABCDE', Setting::get('stripe_secret'));
        $this->assertSame('whsec_1234567890ABCDE', Setting::get('stripe_webhook_secret'));
    }

    public function test_admin_settings_reject_invalid_stripe_public_key(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/admin/settings', [
            'stripe_public_key' => 'not-a-real-key',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Publishable Key non valida. Usa una chiave completa pk_test_... o pk_live_....');

        $this->assertNull(Setting::get('stripe_public_key'));
        $this->assertNull(Setting::get('stripe_key'));
    }

    public function test_admin_settings_reject_invalid_webhook_secret(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/admin/settings', [
            'stripe_webhook_secret' => 'invalid-webhook',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Webhook Secret non valido. Usa il valore completo whsec_... fornito da Stripe.');

        $this->assertNull(Setting::get('stripe_webhook_secret'));
    }
}
