<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class TestUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Admin account
        User::updateOrCreate(
            ['email' => 'admin@spediamofacile.it'],
            [
                'name' => 'Admin',
                'surname' => 'SpediamoFacile',
                'telephone_number' => '+39 3331234567',
                'password' => 'Password1!',
                'role' => 'Admin',
                'email_verified_at' => now(),
            ]
        );

        // Client account
        User::updateOrCreate(
            ['email' => 'cliente@test.it'],
            [
                'name' => 'Mario',
                'surname' => 'Rossi',
                'telephone_number' => '+39 3339876543',
                'password' => 'Password1!',
                'role' => 'Cliente',
                'email_verified_at' => now(),
            ]
        );

        // Partner Pro account
        User::updateOrCreate(
            ['email' => 'pro@test.it'],
            [
                'name' => 'Luca',
                'surname' => 'Bianchi',
                'telephone_number' => '+39 3335556677',
                'password' => 'Password1!',
                'role' => 'Partner Pro',
                'email_verified_at' => now(),
            ]
        );
    }
}
