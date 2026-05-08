<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P1.1 — Aggiunge colonne 2FA TOTP alla tabella users.
 *
 * Colonne:
 *  - two_factor_secret: secret base32 cifrato (cast 'encrypted' in User model)
 *  - two_factor_recovery_codes: array di codici di recupero, cifrato (cast 'encrypted:array')
 *  - two_factor_confirmed_at: timestamp di conferma (NULL = setup non completato)
 *
 * SICUREZZA: tutte e tre le colonne NON sono in $fillable (assignment esplicito only).
 * Il middleware 'RequireTwoFactor' blocca l'accesso ad endpoint admin se la colonna
 * `two_factor_confirmed_at` e' NULL per un utente con role='Admin'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable()->after('password');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
            ]);
        });
    }
};
