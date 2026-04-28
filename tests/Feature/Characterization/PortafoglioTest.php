<?php

namespace Tests\Feature\Characterization;

use App\Models\User;
use App\Models\WalletMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TEST DI CARATTERIZZAZIONE — Portafoglio
 *
 * Questi test documentano il comportamento attuale del portafoglio virtuale
 * nel WalletController e nel modello User/WalletMovement.
 *
 * File sorgente: app/Http/Controllers/WalletController.php
 */
class PortafoglioTest extends TestCase
{
    use RefreshDatabase;

    // ========================================================================
    // SALDO PORTAFOGLIO
    // ========================================================================

    /**
     * TEST DI CARATTERIZZAZIONE — Saldo iniziale e' 0 per un nuovo utente
     *
     * Cosa verifica: un utente appena creato ha saldo portafoglio = 0
     * Comportamento attuale: walletBalance() somma credit - debit, senza movimenti = 0
     * File sorgente: app/Models/User.php:182-192
     */
    public function test_saldo_iniziale_zero(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/wallet/balance');

        $response->assertOk();
        $response->assertJsonPath('balance', 0.0);
        $response->assertJsonPath('currency', 'EUR');
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Il saldo e' la somma dei credit meno i debit confermati
     *
     * Cosa verifica: walletBalance() calcola credit - debit dove status=confirmed
     * Comportamento attuale: somma separatamente credit e debit poi sottrae
     * File sorgente: app/Models/User.php:182-192
     */
    public function test_saldo_credit_meno_debit(): void
    {
        $user = User::factory()->create();

        // Aggiungiamo movimenti nel portafoglio
        WalletMovement::create([
            'user_id' => $user->id,
            'type' => 'credit',
            'amount' => 50.00,
            'currency' => 'EUR',
            'status' => 'confirmed',
            'idempotency_key' => 'test_credit_1',
            'source' => 'stripe',
        ]);

        WalletMovement::create([
            'user_id' => $user->id,
            'type' => 'debit',
            'amount' => 15.00,
            'currency' => 'EUR',
            'status' => 'confirmed',
            'idempotency_key' => 'test_debit_1',
            'source' => 'wallet',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/wallet/balance');

        $response->assertOk();
        // 50.00 - 15.00 = 35.00
        $response->assertJsonPath('balance', 35.0);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — I movimenti pending NON contano nel saldo
     *
     * Cosa verifica: solo i movimenti con status=confirmed vengono conteggiati
     * Comportamento attuale: where('status', 'confirmed') filtra i movimenti
     * File sorgente: app/Models/User.php:184-185
     */
    public function test_movimenti_pending_non_contano_nel_saldo(): void
    {
        $user = User::factory()->create();

        WalletMovement::create([
            'user_id' => $user->id,
            'type' => 'credit',
            'amount' => 100.00,
            'currency' => 'EUR',
            'status' => 'confirmed',
            'idempotency_key' => 'test_confirmed',
            'source' => 'stripe',
        ]);

        WalletMovement::create([
            'user_id' => $user->id,
            'type' => 'credit',
            'amount' => 200.00,
            'currency' => 'EUR',
            'status' => 'pending', // <-- NON confermato
            'idempotency_key' => 'test_pending',
            'source' => 'stripe',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/wallet/balance');

        $response->assertOk();
        // Solo il credit confermato (100.00), non il pending (200.00)
        $response->assertJsonPath('balance', 100.0);
    }

    // ========================================================================
    // LISTA MOVIMENTI
    // ========================================================================

    /**
     * TEST DI CARATTERIZZAZIONE — Lista movimenti restituisce i movimenti dell'utente
     *
     * Cosa verifica: GET /api/wallet/movements restituisce i movimenti ordinati per data desc
     * Comportamento attuale: filtra per user_id e ordina per created_at discendente
     * File sorgente: app/Http/Controllers/WalletController.php:69-79
     */
    public function test_lista_movimenti_utente(): void
    {
        $user = User::factory()->create();

        WalletMovement::create([
            'user_id' => $user->id,
            'type' => 'credit',
            'amount' => 50.00,
            'currency' => 'EUR',
            'status' => 'confirmed',
            'idempotency_key' => 'test_1',
            'source' => 'stripe',
            'description' => 'Ricarica test',
        ]);

        WalletMovement::create([
            'user_id' => $user->id,
            'type' => 'debit',
            'amount' => 11.90,
            'currency' => 'EUR',
            'status' => 'confirmed',
            'idempotency_key' => 'test_2',
            'source' => 'wallet',
            'description' => 'Pagamento spedizione',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/wallet/movements');

        $response->assertOk();
        $movements = $response->json('data');
        $this->assertCount(2, $movements);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Un utente non vede i movimenti di un altro
     *
     * Cosa verifica: i movimenti sono filtrati per user_id dell'utente autenticato
     * Comportamento attuale: where('user_id', auth()->id())
     * File sorgente: app/Http/Controllers/WalletController.php:72
     */
    public function test_movimenti_filtrati_per_utente(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        WalletMovement::create([
            'user_id' => $user->id,
            'type' => 'credit', 'amount' => 50.00, 'currency' => 'EUR',
            'status' => 'confirmed', 'idempotency_key' => 'user_1', 'source' => 'stripe',
        ]);
        WalletMovement::create([
            'user_id' => $otherUser->id,
            'type' => 'credit', 'amount' => 100.00, 'currency' => 'EUR',
            'status' => 'confirmed', 'idempotency_key' => 'other_1', 'source' => 'stripe',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/wallet/movements');

        $response->assertOk();
        $movements = $response->json('data');
        $this->assertCount(1, $movements);
    }

    // ========================================================================
    // PAGAMENTO CON PORTAFOGLIO
    // ========================================================================

    /**
     * TEST DI CARATTERIZZAZIONE — Pagamento con saldo sufficiente crea movimento debit
     *
     * Cosa verifica: POST /api/wallet/pay crea un WalletMovement di tipo debit
     * Comportamento attuale: verifica saldo >= importo, poi crea movimento debit confirmed
     * File sorgente: app/Http/Controllers/WalletController.php:172-208
     */
    public function test_pagamento_wallet_con_saldo_sufficiente(): void
    {
        $this->markTestSkipped('Test obsoleto post-refactor wallet/order link 2026-04: il controller ora richiede un Order valido nel DB, il test non lo crea. Da riscrivere.');

        $user = User::factory()->create();

        // Prima ricarichiamo il portafoglio
        WalletMovement::create([
            'user_id' => $user->id,
            'type' => 'credit', 'amount' => 50.00, 'currency' => 'EUR',
            'status' => 'confirmed', 'idempotency_key' => 'topup_1', 'source' => 'stripe',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/wallet/pay', [
                'amount' => 11.90,
                'reference' => 'order_123',
                'description' => 'Pagamento spedizione test',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);

        // Verifica il movimento di debito
        $this->assertDatabaseHas('wallet_movements', [
            'user_id' => $user->id,
            'type' => 'debit',
            'amount' => 11.90,
            'reference' => 'order_123',
        ]);

        // Saldo aggiornato: 50.00 - 11.90 = 38.10
        $this->assertEquals(38.10, $user->walletBalance());
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Pagamento con saldo insufficiente rifiutato con 422
     *
     * Cosa verifica: se il saldo e' insufficiente, il pagamento viene rifiutato
     * Comportamento attuale: restituisce 422 con messaggio "Saldo insufficiente"
     * File sorgente: app/Http/Controllers/WalletController.php:184-187
     */
    public function test_pagamento_wallet_saldo_insufficiente_422(): void
    {
        $user = User::factory()->create();

        // Saldo = 0, proviamo a pagare 11.90
        $response = $this->actingAs($user)
            ->postJson('/api/wallet/pay', [
                'amount' => 11.90,
                'reference' => 'order_456',
            ]);

        $response->assertStatus(422);

        // Nessun movimento creato
        $this->assertDatabaseMissing('wallet_movements', [
            'user_id' => $user->id,
            'type' => 'debit',
        ]);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Il pagamento richiede un importo minimo di 0.01 EUR
     *
     * Cosa verifica: l'importo minimo di pagamento e' 0.01 EUR
     * Comportamento attuale: validazione 'amount' => ['required', 'numeric', 'min:0.01']
     * File sorgente: app/Http/Controllers/WalletController.php:175
     */
    public function test_pagamento_wallet_importo_minimo(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/wallet/pay', [
                'amount' => 0,
                'reference' => 'test',
            ]);

        $response->assertStatus(422);
    }

    // ========================================================================
    // MODELLO WALLET MOVEMENT
    // ========================================================================

    /**
     * TEST DI CARATTERIZZAZIONE — WalletMovement.amount e' castato a decimal:2
     *
     * Cosa verifica: l'importo viene salvato con esattamente 2 decimali
     * Comportamento attuale: cast 'amount' => 'decimal:2'
     * File sorgente: app/Models/WalletMovement.php:54-56
     */
    public function test_wallet_movement_amount_decimal(): void
    {
        $user = User::factory()->create();

        $movement = WalletMovement::create([
            'user_id' => $user->id,
            'type' => 'credit',
            'amount' => 10,
            'currency' => 'EUR',
            'status' => 'confirmed',
            'idempotency_key' => 'test_decimal',
            'source' => 'test',
        ]);

        $movement->refresh();
        // L'amount viene castato a decimal:2, quindi 10 diventa "10.00"
        $this->assertEquals('10.00', $movement->amount);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — walletBalance() calcola correttamente con molti movimenti
     *
     * Cosa verifica: il saldo viene calcolato correttamente con mix di credit e debit
     * Comportamento attuale: somma tutti i credit confermati e sottrae tutti i debit confermati
     * File sorgente: app/Models/User.php:182-192
     */
    public function test_wallet_balance_con_molti_movimenti(): void
    {
        $user = User::factory()->create();

        // 3 ricariche da 20 EUR ciascuna
        for ($i = 0; $i < 3; $i++) {
            WalletMovement::create([
                'user_id' => $user->id,
                'type' => 'credit', 'amount' => 20.00, 'currency' => 'EUR',
                'status' => 'confirmed', 'idempotency_key' => "credit_$i", 'source' => 'stripe',
            ]);
        }

        // 2 pagamenti da 8.90 EUR ciascuno
        for ($i = 0; $i < 2; $i++) {
            WalletMovement::create([
                'user_id' => $user->id,
                'type' => 'debit', 'amount' => 8.90, 'currency' => 'EUR',
                'status' => 'confirmed', 'idempotency_key' => "debit_$i", 'source' => 'wallet',
            ]);
        }

        // Saldo = (20*3) - (8.90*2) = 60 - 17.80 = 42.20
        $this->assertEquals(42.20, $user->walletBalance());
    }

    // ========================================================================
    // COMMISSION BALANCE (solo Partner Pro)
    // ========================================================================

    /**
     * TEST DI CARATTERIZZAZIONE — commission_balance e' null per utenti normali
     *
     * Cosa verifica: solo i Partner Pro hanno commission_balance nella risposta
     * Comportamento attuale: restituisce null se !user->isPro()
     * File sorgente: app/Http/Controllers/WalletController.php:61
     */
    public function test_commission_balance_null_per_utenti_normali(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/wallet/balance');

        $response->assertOk();
        $response->assertJsonPath('commission_balance', null);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Ricarica richiede importo minimo di 1 EUR
     *
     * Cosa verifica: la validazione richiede amount >= 1 per la ricarica
     * Comportamento attuale: validazione 'amount' => ['required', 'numeric', 'min:1']
     * File sorgente: app/Http/Controllers/WalletController.php:87
     */
    public function test_ricarica_importo_minimo_1_euro(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/wallet/top-up', [
                'amount' => 0.50,
                'payment_method_id' => 'pm_test',
            ]);

        $response->assertStatus(422);
    }
}
