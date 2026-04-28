<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EnsureTestUserSeeder extends Seeder
{
    public function run(): void
    {
        // In seeding la protezione da assegnazione massiva è disattivata di default, ma teniamo comunque robusto.
        User::unguard();

        // SQLite: leggere schema tabella users
        $columns = DB::select('PRAGMA table_info(users)');

        // Valori certi per i campi che hai già visto obbligatori
        $payload = [
            'name' => 'Test',
            'surname' => 'Test',
            'telephone_number' => '0000000000',
            'role' => 'user',
            'password' => bcrypt('Password123!'),
        ];

        $ignoredColumns = ['id', 'email', 'created_at', 'updated_at', 'email_verified_at', 'remember_token'];

        foreach ($columns as $column) {
            $name = $column->name;
            $isRequired = ((int) $column->notnull === 1);
            $hasNoDefault = ($column->dflt_value === null);

            if ($isRequired && $hasNoDefault && ! array_key_exists($name, $payload) && ! in_array($name, $ignoredColumns, true)) {
                $type = strtolower((string) $column->type);

                // euristiche semplici per non violare NOT NULL
                if (str_ends_with($name, '_id')) {
                    $payload[$name] = 1;
                } elseif (str_starts_with($name, 'is_') || str_starts_with($name, 'has_')) {
                    $payload[$name] = 0;
                } elseif (str_contains($type, 'int')) {
                    $payload[$name] = 0;
                } elseif (str_contains($type, 'date') || str_contains($type, 'time')) {
                    $payload[$name] = now();
                } else {
                    $payload[$name] = 'test';
                }
            }
        }

        User::updateOrCreate(['email' => 'test@prova.it'], $payload);
    }
}
