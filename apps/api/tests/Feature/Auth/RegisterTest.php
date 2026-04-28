<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper: dati di registrazione validi completi.
     * La password rispetta tutte le regole: min 8, maiuscola, minuscola, numero, simbolo.
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

    // T11.1.3 - Registrazione valida
    public function test_user_can_register_with_valid_data(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/custom-register', $this->validRegistrationData());

        $response->assertCreated()
            ->assertJson(['success' => true]);
        $this->assertDatabaseHas('users', ['email' => 'mario@example.com']);
    }

    // T11.1.4 - Email duplicata: anti-enumeration (Sprint 6.4).
    // La risposta deve essere identica a una registrazione nuova (201 success),
    // SENZA creare un secondo utente ne' sovrascrivere quello esistente.
    public function test_register_with_duplicate_email_returns_generic_success(): void
    {
        Mail::fake();

        $existing = User::factory()->create([
            'email' => 'existing@example.com',
            'name' => 'Giuseppe',
        ]);

        $response = $this->postJson('/api/custom-register', $this->validRegistrationData([
            'email' => 'existing@example.com',
            'email_confirmation' => 'existing@example.com',
        ]));

        // Risposta identica alla registrazione di una nuova email
        $response->assertCreated()
            ->assertJson(['success' => true]);

        // L'utente esistente non e' stato sovrascritto
        $this->assertDatabaseHas('users', [
            'email' => 'existing@example.com',
            'name' => 'Giuseppe',
        ]);
        $this->assertEquals(1, User::where('email', 'existing@example.com')->count());

        // Nessuna email di verifica inviata al duplicato
        Mail::assertNothingQueued();
    }

    // T11.1.5 - Password corta (meno di 8 caratteri)
    public function test_register_fails_with_short_password(): void
    {
        $response = $this->postJson('/api/custom-register', $this->validRegistrationData([
            'password' => 'Ab1!',
            'password_confirmation' => 'Ab1!',
        ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    // T11.1.5b - Password senza requisiti di complessita' (manca maiuscola/numero/simbolo)
    public function test_register_fails_with_weak_password(): void
    {
        $response = $this->postJson('/api/custom-register', $this->validRegistrationData([
            'password' => 'passwordsemplice',
            'password_confirmation' => 'passwordsemplice',
        ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    // T11.1.8 - Role forzato a User (mass assignment protection)
    public function test_register_forces_user_role(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/custom-register', $this->validRegistrationData([
            'email' => 'hacker@example.com',
            'email_confirmation' => 'hacker@example.com',
            'role' => 'Admin',
        ]));

        // The RegisterRequest allows 'User', 'Cliente', 'Partner Pro' but NOT 'Admin'
        // so this should fail validation. But even if 'Partner Pro' is sent,
        // the controller forces role to 'User'.
        // With 'Admin' the request should be rejected at validation level (422).
        $response->assertUnprocessable();
    }

    // T11.1.8b - Role forzato a User anche con ruolo accettato dalla validazione
    public function test_register_forces_user_role_even_with_partner_pro(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/custom-register', $this->validRegistrationData([
            'email' => 'sneaky@example.com',
            'email_confirmation' => 'sneaky@example.com',
            'role' => 'Partner Pro',
        ]));

        $response->assertCreated();

        // Even if role='Partner Pro' passes validation, controller forces 'User'
        $user = User::where('email', 'sneaky@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('User', $user->role);
    }

    // T11.1.6 - Codice referral valido (Partner Pro)
    public function test_register_with_valid_referral_code(): void
    {
        Mail::fake();

        $pro = User::factory()->partnerPro()->create();

        $response = $this->postJson('/api/custom-register', $this->validRegistrationData([
            'email' => 'nuovo@example.com',
            'email_confirmation' => 'nuovo@example.com',
            'referral_code' => $pro->referral_code,
            'referred_by' => $pro->referral_code,
        ]));

        $response->assertCreated();
        $user = User::where('email', 'nuovo@example.com')->first();
        $this->assertEquals($pro->referral_code, $user->referred_by);
    }

    // T11.1.6b - Codice referral non valido viene ignorato
    public function test_register_with_invalid_referral_code_ignores_it(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/custom-register', $this->validRegistrationData([
            'email' => 'nuovo@example.com',
            'email_confirmation' => 'nuovo@example.com',
            'referred_by' => 'FAKECODE',
        ]));

        $response->assertCreated();
        $user = User::where('email', 'nuovo@example.com')->first();
        $this->assertNull($user->referred_by);
    }

    // Campi obbligatori
    public function test_register_requires_all_fields(): void
    {
        $response = $this->postJson('/api/custom-register', []);
        $response->assertUnprocessable();
    }

    // Email deve essere confermata (email_confirmation)
    public function test_register_fails_without_email_confirmation(): void
    {
        $response = $this->postJson('/api/custom-register', $this->validRegistrationData([
            'email_confirmation' => 'different@example.com',
        ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    // Password deve essere confermata (password_confirmation)
    public function test_register_fails_without_password_confirmation(): void
    {
        $response = $this->postJson('/api/custom-register', $this->validRegistrationData([
            'password_confirmation' => 'DifferentPass1!',
        ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }
}
