<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->firstName(),
            'surname' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'telephone_number' => fake()->phoneNumber(),
            'role' => 'Cliente',
            'email_verified_at' => now(),
            'password' => 'password', // User model 'hashed' cast handles hashing
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function partnerPro(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'Partner Pro',
            'referral_code' => strtoupper(Str::random(8)),
        ]);
    }

    /**
     * P1.1 — Stato admin con 2FA gia' confermato.
     * Usare nei test che hanno bisogno di superare il middleware RequireTwoFactor.
     * Il secret e' un valore base32 fittizio, sufficiente perche' il middleware
     * controlla solo `two_factor_confirmed_at`, non la validita' del secret.
     */
    public function adminWithTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'Admin',
            'two_factor_secret' => 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP',
            'two_factor_recovery_codes' => ['AAAAA-BBBBB', 'CCCCC-DDDDD'],
            'two_factor_confirmed_at' => now(),
        ]);
    }
}
