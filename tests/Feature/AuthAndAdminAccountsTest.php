<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthAndAdminAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_verification_route_marks_user_as_verified(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $response = $this->actingAs($user)->get($url);

        $response->assertRedirect();
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_admin_can_list_approve_and_delete_users(): void
    {
        $admin = User::factory()->create([
            'role' => 'Admin',
            'email_verified_at' => now(),
            // P1.1 — admin senza 2FA confermato verrebbe bloccato dal middleware RequireTwoFactor
            'two_factor_confirmed_at' => now(),
        ]);

        $pendingUser = User::factory()->unverified()->create();

        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/users')
            ->assertOk()
            ->assertJsonPath('data.0.id', User::query()->latest('created_at')->first()->id);

        $this->patchJson("/api/admin/users/{$pendingUser->id}/approve")
            ->assertOk();

        $this->assertNotNull($pendingUser->fresh()->email_verified_at);

        $toDelete = User::factory()->create();

        $this->deleteJson("/api/admin/users/{$toDelete->id}")
            ->assertOk();

        // GDPR compliance: utenti vengono soft-deleted (non hard-deleted) per
        // mantenere l'integrita' di ordini/fatture storiche. La riga resta in DB
        // con deleted_at popolato.
        $this->assertSoftDeleted('users', ['id' => $toDelete->id]);
    }
}
