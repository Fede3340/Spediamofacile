<?php

namespace Tests\Feature\Auth;

use App\Mail\ResetPasswordEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_request_returns_generic_success_for_registered_email(): void
    {
        Mail::fake();

        User::factory()->create([
            'email' => 'utente@example.com',
        ]);

        $response = $this->postJson('/api/forgot-password', [
            'email' => 'utente@example.com',
        ]);

        $response->assertOk()->assertJson([
            'success' => true,
            'message' => 'Se l\'email è registrata riceverai un link di reset entro pochi minuti.',
        ]);

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'utente@example.com',
        ]);

        Mail::assertQueued(ResetPasswordEmail::class, 1);
    }

    public function test_password_reset_request_returns_same_success_for_unknown_email_without_creating_token(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/forgot-password', [
            'email' => 'missing@example.com',
        ]);

        $response->assertOk()->assertJson([
            'success' => true,
            'message' => 'Se l\'email è registrata riceverai un link di reset entro pochi minuti.',
        ]);

        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'missing@example.com',
        ]);

        Mail::assertNothingQueued();
    }
}
