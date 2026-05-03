<?php

/**
 * FILE: StripeAccountEncryptionTest.php
 * SCOPO: Verifica la cifratura at-rest dei campi Stripe sensibili (Sprint 6.1).
 *
 * CHECK:
 *   - Il cast 'encrypted' cifra stripe_account_id e customer_id al save().
 *   - Il valore grezzo nel DB NON e' il plaintext (non contiene 'acct_'/'cus_').
 *   - Il valore grezzo cambia a ogni save (IV random → ciphertext diverso).
 *   - L'accessor restituisce il plaintext corretto dopo reload da DB.
 *   - Il webhook accountUpdated trova l'utente anche con valore cifrato.
 *   - La migration di backfill e' idempotente e reversibile.
 */

namespace Tests\Feature\Payments;

use App\Models\User;
use App\Services\Stripe\Webhook\AccountUpdatedHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StripeAccountEncryptionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function stripe_account_id_is_encrypted_at_rest(): void
    {
        $plaintext = 'acct_1ProTest12345ABCDE';

        $user = User::factory()->partnerPro()->create();
        $user->stripe_account_id = $plaintext;
        $user->save();

        // Leggo il valore raw dal DB bypassando il cast Eloquent.
        $raw = DB::table('users')->where('id', $user->id)->value('stripe_account_id');

        $this->assertNotNull($raw, 'Il valore non deve essere NULL dopo il save');
        $this->assertNotSame($plaintext, $raw, 'Il valore RAW nel DB deve essere cifrato, non plaintext');
        $this->assertStringNotContainsString('acct_', $raw, 'Il ciphertext non deve contenere il prefisso plaintext');

        // Il plaintext si deve poter recuperare decifrando manualmente.
        $this->assertSame($plaintext, Crypt::decryptString($raw));
    }

    /** @test */
    public function customer_id_is_encrypted_at_rest(): void
    {
        $plaintext = 'cus_ABCDE12345XYZ';

        $user = User::factory()->create();
        $user->customer_id = $plaintext;
        $user->save();

        $raw = DB::table('users')->where('id', $user->id)->value('customer_id');

        $this->assertNotNull($raw);
        $this->assertNotSame($plaintext, $raw);
        $this->assertStringNotContainsString('cus_', $raw);
        $this->assertSame($plaintext, Crypt::decryptString($raw));
    }

    /** @test */
    public function accessor_returns_decrypted_plaintext(): void
    {
        $plaintext = 'acct_1AccessorTest99999';

        $user = User::factory()->partnerPro()->create();
        $user->stripe_account_id = $plaintext;
        $user->save();

        // Reload da DB (fresh istanza, cast applicato).
        $reloaded = User::findOrFail($user->id);

        $this->assertSame($plaintext, $reloaded->stripe_account_id);
    }

    /** @test */
    public function encryption_is_non_deterministic(): void
    {
        // Due save dello stesso plaintext producono ciphertexts diversi (IV random).
        $plaintext = 'acct_1SameValueButDifferentCipher';

        $userA = User::factory()->partnerPro()->create();
        $userA->stripe_account_id = $plaintext;
        $userA->save();

        $userB = User::factory()->partnerPro()->create();
        $userB->stripe_account_id = $plaintext;
        $userB->save();

        $rawA = DB::table('users')->where('id', $userA->id)->value('stripe_account_id');
        $rawB = DB::table('users')->where('id', $userB->id)->value('stripe_account_id');

        $this->assertNotSame($rawA, $rawB, 'Laravel encrypted cast deve usare IV random (non deterministico)');
        $this->assertSame($plaintext, Crypt::decryptString($rawA));
        $this->assertSame($plaintext, Crypt::decryptString($rawB));
    }

    /** @test */
    public function stripe_account_id_stays_hidden_in_json(): void
    {
        $user = User::factory()->partnerPro()->create();
        $user->stripe_account_id = 'acct_HiddenFromJson';
        $user->customer_id = 'cus_HiddenFromJson';
        $user->save();

        $payload = $user->toArray();

        $this->assertArrayNotHasKey('stripe_account_id', $payload);
        $this->assertArrayNotHasKey('customer_id', $payload);
    }

    /** @test */
    public function webhook_lookup_by_stripe_account_id_works_with_encryption(): void
    {
        $plaintext = 'acct_1WebhookLookupTest';

        $user = User::factory()->partnerPro()->create();
        $user->stripe_account_id = $plaintext;
        $user->save();

        // Invochiamo il helper protected tramite reflection sul handler che lo usa.
        $handler = app(AccountUpdatedHandler::class);
        $ref = new \ReflectionMethod($handler, 'findUserByStripeAccountId');
        $ref->setAccessible(true);

        $found = $ref->invoke($handler, $plaintext);

        $this->assertNotNull($found, 'Il lookup deve trovare l\'utente anche se il campo e\' cifrato');
        $this->assertSame($user->id, $found->id);

        $notFound = $ref->invoke($handler, 'acct_NonExistent');
        $this->assertNull($notFound);
    }

    /** @test */
    public function backfill_migration_encrypts_legacy_plaintext_and_is_reversible(): void
    {
        // Skip post FASE 10 squash: la migration di backfill e' stata applicata e
        // poi rimossa dal disco (schema:dump --prune). Lo schema baseline include
        // gia' le colonne cifrate, quindi questo test caratterizza un comportamento
        // storico non piu' riproducibile in test.
        $this->markTestSkipped('Migration squashed nello schema baseline (FASE 10 squash 2026-04-27).');

        // Creiamo due utenti e scriviamo i valori in plaintext RAW (simulando dati legacy
        // presenti prima dell'aggiunta del cast 'encrypted').
        $plaintextAccount = 'acct_1LegacyPlaintext12345';
        $plaintextCustomer = 'cus_LegacyPlaintextZZZ';

        $userPro = User::factory()->partnerPro()->create();
        DB::table('users')->where('id', $userPro->id)->update([
            'stripe_account_id' => $plaintextAccount,
            'customer_id' => $plaintextCustomer,
        ]);

        // Stato pre-migration: RAW = plaintext.
        $this->assertSame(
            $plaintextAccount,
            DB::table('users')->where('id', $userPro->id)->value('stripe_account_id')
        );

        // Eseguo up() della migration direttamente.
        $migrationPath = database_path('migrations/2026_04_20_000000_encrypt_existing_stripe_account_ids.php');
        $this->assertFileExists($migrationPath);
        $migration = require $migrationPath;
        $migration->up();

        // Post-up: RAW != plaintext, decrypt = plaintext.
        $rawAccount = DB::table('users')->where('id', $userPro->id)->value('stripe_account_id');
        $rawCustomer = DB::table('users')->where('id', $userPro->id)->value('customer_id');

        $this->assertNotSame($plaintextAccount, $rawAccount);
        $this->assertNotSame($plaintextCustomer, $rawCustomer);
        $this->assertSame($plaintextAccount, Crypt::decryptString($rawAccount));
        $this->assertSame($plaintextCustomer, Crypt::decryptString($rawCustomer));

        // Idempotenza: rilanciare up() non deve ri-cifrare (doppia cifratura corromperebbe).
        $migration->up();
        $rawAccountAfter = DB::table('users')->where('id', $userPro->id)->value('stripe_account_id');
        $this->assertSame(
            $plaintextAccount,
            Crypt::decryptString($rawAccountAfter),
            'La migration deve essere idempotente: un secondo up() non deve ri-cifrare'
        );

        // Down: ripristino plaintext.
        $migration->down();
        $rawAccountDown = DB::table('users')->where('id', $userPro->id)->value('stripe_account_id');
        $rawCustomerDown = DB::table('users')->where('id', $userPro->id)->value('customer_id');

        $this->assertSame($plaintextAccount, $rawAccountDown);
        $this->assertSame($plaintextCustomer, $rawCustomerDown);
    }

    /** @test */
    public function backfill_skips_null_values(): void
    {
        $this->markTestSkipped('Migration squashed nello schema baseline (FASE 10 squash 2026-04-27).');
    }
}
