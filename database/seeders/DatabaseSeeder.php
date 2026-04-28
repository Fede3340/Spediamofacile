<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $accounts = [
            [
                'email' => 'admin@spediamofacile.it',
                'name' => 'Admin',
                'surname' => 'SpediamoFacile',
                'telephone_number' => '+39 000 0000000',
                'password' => 'Admin2026!',
                'role' => 'Admin',
            ],
            [
                'email' => 'cliente@spediamofacile.it',
                'name' => 'Luca',
                'surname' => 'Bianchi',
                'telephone_number' => '+39 333 7654321',
                'password' => 'Cliente2026!',
                'role' => 'Cliente',
            ],
            [
                'email' => 'prova@spediamofacile.it',
                'name' => 'Prova',
                'surname' => 'Test',
                'telephone_number' => '+39 333 1111111',
                'password' => 'Prova2026!',
                'role' => 'Cliente',
            ],
            [
                'email' => 'pro@spediamofacile.it',
                'name' => 'Mario',
                'surname' => 'Rossi',
                'telephone_number' => '+39 333 1234567',
                'password' => 'Partner2026!',
                'role' => 'Partner Pro',
            ],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'surname' => $account['surname'],
                    'telephone_number' => $account['telephone_number'],
                    'password' => $account['password'],
                    'role' => $account['role'],
                    'email_verified_at' => now(),
                ]
            );
        }

        // Seed fasce prezzo, articoli (guide + servizi) e punti PUDO
        $this->call([
            PriceBandSeeder::class,
            ArticleSeeder::class,
            PudoPointSeeder::class,
        ]);
    }
}
