<?php

namespace Tests\Feature\Characterization;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TEST DI CARATTERIZZAZIONE — Rimborso
 *
 * Questi test documentano il comportamento attuale delle regole di rimborso
 * nel RefundController e nella logica di eligibility.
 *
 * File sorgente: app/Http/Controllers/RefundController.php
 */
class RimborsoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Crea un ordine con un dato stato per i test di eligibility.
     */
    private function createOrder(User $user, string $status, int $subtotalCents = 2000, array $extra = []): Order
    {
        return Order::create(array_merge([
            'user_id' => $user->id,
            'subtotal' => $subtotalCents,
            'status' => $status,
        ], $extra));
    }

    // ========================================================================
    // COMMISSIONE
    // ========================================================================

    /**
     * TEST DI CARATTERIZZAZIONE — La commissione di annullamento e' 2 EUR (200 centesimi)
     *
     * Cosa verifica: la costante CANCELLATION_FEE_CENTS e' 200
     * Comportamento attuale: costante definita nel controller
     * File sorgente: app/Http/Controllers/RefundController.php:52
     */
    public function test_commissione_annullamento_e_200_centesimi(): void
    {
        $this->assertEquals(200, \App\Http\Controllers\Checkout\RefundController::CANCELLATION_FEE_CENTS);
    }

    // ========================================================================
    // ELIGIBILITA' RIMBORSO (checkRefundEligibility)
    // ========================================================================

    /**
     * TEST DI CARATTERIZZAZIONE — Ordine pending: annullabile senza rimborso
     *
     * Cosa verifica: ordini pending possono essere annullati ma non hanno rimborso (non pagati)
     * Comportamento attuale: eligible=true, refund_amount_cents=0, type=cancel_unpaid
     * File sorgente: app/Http/Controllers/RefundController.php:255-266
     */
    public function test_eligibility_pending_annullabile_senza_rimborso(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrder($user, 'pending', 2000);

        $response = $this->actingAs($user)
            ->getJson("/api/orders/{$order->id}/refund-eligibility");

        $response->assertOk();
        $response->assertJsonPath('eligible', true);
        $response->assertJsonPath('refund_amount_cents', 0);
        $response->assertJsonPath('commission_cents', 0);
        $response->assertJsonPath('type', 'cancel_unpaid');
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Ordine payment_failed: annullabile senza rimborso
     *
     * Cosa verifica: ordini payment_failed si comportano come i pending
     * Comportamento attuale: stessa logica di pending (in_array con PENDING e PAYMENT_FAILED)
     * File sorgente: app/Http/Controllers/RefundController.php:255
     */
    public function test_eligibility_payment_failed_annullabile_senza_rimborso(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrder($user, 'payment_failed', 2000);

        $response = $this->actingAs($user)
            ->getJson("/api/orders/{$order->id}/refund-eligibility");

        $response->assertOk();
        $response->assertJsonPath('eligible', true);
        $response->assertJsonPath('type', 'cancel_unpaid');
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Ordine processing: rimborsabile con commissione di 2 EUR
     *
     * Cosa verifica: ordini processing possono essere rimborsati, meno 200 centesimi di commissione
     * Comportamento attuale: eligible=true, refund = subtotal - 200, type=refund_with_commission
     * File sorgente: app/Http/Controllers/RefundController.php:284-302
     */
    public function test_eligibility_processing_rimborsabile_con_commissione(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrder($user, 'processing', 2000);

        $response = $this->actingAs($user)
            ->getJson("/api/orders/{$order->id}/refund-eligibility");

        $response->assertOk();
        $response->assertJsonPath('eligible', true);
        $response->assertJsonPath('refund_amount_cents', 1800); // 2000 - 200
        $response->assertJsonPath('commission_cents', 200);
        $response->assertJsonPath('type', 'refund_with_commission');
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Ordine completed: rimborsabile con commissione di 2 EUR
     *
     * Cosa verifica: ordini completed si comportano come processing per il rimborso
     * Comportamento attuale: stessa logica (in_array con COMPLETED e PROCESSING)
     * File sorgente: app/Http/Controllers/RefundController.php:284
     */
    public function test_eligibility_completed_rimborsabile_con_commissione(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrder($user, 'completed', 2000);

        $response = $this->actingAs($user)
            ->getJson("/api/orders/{$order->id}/refund-eligibility");

        $response->assertOk();
        $response->assertJsonPath('eligible', true);
        $response->assertJsonPath('refund_amount_cents', 1800);
        $response->assertJsonPath('commission_cents', 200);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Ordine in_transit: NON rimborsabile
     *
     * Cosa verifica: ordini in_transit non possono essere rimborsati (spedizione gia' partita)
     * Comportamento attuale: eligible=false, type=in_transit
     * File sorgente: app/Http/Controllers/RefundController.php:269-280
     */
    public function test_eligibility_in_transit_non_rimborsabile(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrder($user, 'in_transit', 2000);

        $response = $this->actingAs($user)
            ->getJson("/api/orders/{$order->id}/refund-eligibility");

        $response->assertOk();
        $response->assertJsonPath('eligible', false);
        $response->assertJsonPath('type', 'in_transit');
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Ordine cancelled: NON rimborsabile (gia' annullato)
     *
     * Cosa verifica: ordini gia' annullati non possono essere annullati di nuovo
     * Comportamento attuale: eligible=false, type=already_cancelled
     * File sorgente: app/Http/Controllers/RefundController.php:241-252
     */
    public function test_eligibility_cancelled_gia_annullato(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrder($user, 'cancelled', 2000);

        $response = $this->actingAs($user)
            ->getJson("/api/orders/{$order->id}/refund-eligibility");

        $response->assertOk();
        $response->assertJsonPath('eligible', false);
        $response->assertJsonPath('type', 'already_cancelled');
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Ordine refunded: NON rimborsabile (gia' rimborsato)
     *
     * Cosa verifica: ordini gia' rimborsati non possono essere rimborsati di nuovo
     * Comportamento attuale: stessa logica dei cancelled (in_array con cancelled e refunded)
     * File sorgente: app/Http/Controllers/RefundController.php:241
     */
    public function test_eligibility_refunded_gia_rimborsato(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrder($user, 'refunded', 2000);

        $response = $this->actingAs($user)
            ->getJson("/api/orders/{$order->id}/refund-eligibility");

        $response->assertOk();
        $response->assertJsonPath('eligible', false);
        $response->assertJsonPath('type', 'already_cancelled');
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Ordine delivered: NON rimborsabile
     *
     * Cosa verifica: ordini consegnati non possono essere rimborsati
     * Comportamento attuale: eligible=false, type=not_refundable
     * File sorgente: app/Http/Controllers/RefundController.php:305-316
     */
    public function test_eligibility_delivered_non_rimborsabile(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrder($user, 'delivered', 2000);

        $response = $this->actingAs($user)
            ->getJson("/api/orders/{$order->id}/refund-eligibility");

        $response->assertOk();
        $response->assertJsonPath('eligible', false);
        $response->assertJsonPath('type', 'not_refundable');
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Se subtotale < commissione, il rimborso e' 0
     *
     * Cosa verifica: se il subtotale e' inferiore alla commissione (200 cent), il rimborso e' 0
     * Comportamento attuale: refundAmountCents = max(0, subtotalCents - commissionCents)
     * File sorgente: app/Http/Controllers/RefundController.php:289
     */
    public function test_rimborso_zero_se_subtotale_minore_di_commissione(): void
    {
        $user = User::factory()->create();
        // Subtotale 100 centesimi (1 EUR), commissione 200 centesimi (2 EUR)
        $order = $this->createOrder($user, 'processing', 100);

        $response = $this->actingAs($user)
            ->getJson("/api/orders/{$order->id}/refund-eligibility");

        $response->assertOk();
        $response->assertJsonPath('eligible', true);
        $response->assertJsonPath('refund_amount_cents', 0);
        $response->assertJsonPath('commission_cents', 200);
    }

    // ========================================================================
    // ANNULLAMENTO EFFETTIVO (requestCancellation)
    // ========================================================================

    /**
     * TEST DI CARATTERIZZAZIONE — Annullamento ordine pending: stato diventa cancelled
     *
     * Cosa verifica: annullare un ordine pending lo mette in stato "cancelled" (non "refunded")
     * Comportamento attuale: se refundAmountCents == 0 -> status = CANCELLED
     * File sorgente: app/Http/Controllers/RefundController.php:176
     */
    public function test_annullamento_pending_stato_cancelled(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrder($user, 'pending', 2000);

        $response = $this->actingAs($user)
            ->postJson("/api/orders/{$order->id}/cancel");

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $order->refresh();
        $this->assertEquals('cancelled', $order->getAttributes()['status']);
        // Nessun rimborso per ordini non pagati
        $this->assertNull($order->refunded_at);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Annullamento ordine in_transit blocca con 422
     *
     * Cosa verifica: tentare di annullare un ordine in_transit restituisce errore 422
     * Comportamento attuale: calculateEligibility() ritorna eligible=false, blocca con 422
     * File sorgente: app/Http/Controllers/RefundController.php:109-113
     */
    public function test_annullamento_in_transit_blocca_con_422(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrder($user, 'in_transit', 2000);

        $response = $this->actingAs($user)
            ->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(422);

        // Lo stato NON deve cambiare
        $order->refresh();
        $this->assertEquals('in_transit', $order->getAttributes()['status']);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Annullamento ordine gia' annullato blocca con 422
     *
     * Cosa verifica: tentare di annullare un ordine gia' annullato restituisce errore 422
     * Comportamento attuale: calculateEligibility() ritorna eligible=false per cancelled
     * File sorgente: app/Http/Controllers/RefundController.php:241-252
     */
    public function test_annullamento_ordine_gia_annullato_blocca_con_422(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrder($user, 'cancelled', 2000);

        $response = $this->actingAs($user)
            ->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(422);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Annullamento 403 per utente non proprietario
     *
     * Cosa verifica: solo il proprietario dell'ordine puo' richiedere l'annullamento
     * Comportamento attuale: confronta order->user_id con auth()->id()
     * File sorgente: app/Http/Controllers/RefundController.php:98-101
     */
    public function test_annullamento_403_per_non_proprietario(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $order = $this->createOrder($otherUser, 'pending', 2000);

        $response = $this->actingAs($user)
            ->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(403);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Annullamento ordine processing con rimborso wallet
     *
     * Cosa verifica: annullare un ordine processing pagato con wallet crea un credito nel portafoglio
     * Comportamento attuale: se payment_method=wallet, processWalletRefund() crea WalletMovement
     * File sorgente: app/Http/Controllers/RefundController.php:154-157
     *
     * NOTA: questo test verifica il rimborso wallet. Il rimborso Stripe non e' testabile senza mock.
     */
    public function test_annullamento_processing_rimborso_wallet(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrder($user, 'processing', 2000, [
            'payment_method' => 'wallet',
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/orders/{$order->id}/cancel");

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $order->refresh();
        $this->assertEquals('refunded', $order->getAttributes()['status']);
        $this->assertEquals(1800, $order->refund_amount); // 2000 - 200 commissione
        $this->assertEquals(200, $order->cancellation_fee);
        $this->assertEquals('wallet', $order->refund_method);
        $this->assertNotNull($order->refunded_at);

        // Verifica che il WalletMovement sia stato creato
        $this->assertDatabaseHas('wallet_movements', [
            'user_id' => $user->id,
            'type' => 'credit',
            'amount' => 18.00, // 1800 centesimi = 18.00 EUR
            'source' => 'refund',
        ]);
    }

    /**
     * TEST DI CARATTERIZZAZIONE — Eligibility per utente non proprietario restituisce 403
     *
     * Cosa verifica: solo il proprietario o un admin puo' controllare l'eligibilita'
     * Comportamento attuale: confronta order->user_id con auth()->id() e user->isAdmin()
     * File sorgente: app/Http/Controllers/RefundController.php:76-78
     */
    public function test_eligibility_403_per_non_proprietario(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $order = $this->createOrder($otherUser, 'pending', 2000);

        $response = $this->actingAs($user)
            ->getJson("/api/orders/{$order->id}/refund-eligibility");

        $response->assertStatus(403);
    }
}
