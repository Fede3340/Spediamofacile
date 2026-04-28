<?php

/**
 * Test per InvoiceListController: GET /api/invoices.
 *
 * Coperture principali:
 *   - accesso anonimo bloccato (401);
 *   - utente senza alcuna fattura → lista vuota;
 *   - filtro corretto: vengono restituiti solo ordini con evidenza fattura
 *     (sdi_invoice_number, sdi_sent_at o billing_data.type='fattura');
 *   - paginazione 20 per pagina;
 *   - ordinamento dal piu' recente;
 *   - campi "derivati" (sdi_status inferito, amount_eur_formatted, download_url);
 *   - isolamento utente: non si vedono fatture di altri.
 */

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceListControllerTest extends TestCase
{
    use RefreshDatabase;

    /* ==========================================================
     *  Auth
     * ========================================================== */

    public function test_unauthenticated_user_receives_401(): void
    {
        $this->getJson('/api/invoices')->assertStatus(401);
    }

    /* ==========================================================
     *  Empty collection
     * ========================================================== */

    public function test_user_without_invoices_returns_empty_collection(): void
    {
        $user = User::factory()->create();

        // Ordini "puri" (nessuna evidenza fattura) non devono apparire.
        Order::factory()->count(2)->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'subtotal' => 1000,
        ]);

        $response = $this->actingAs($user)->getJson('/api/invoices');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    /* ==========================================================
     *  Filter: only orders with invoice evidence
     * ========================================================== */

    public function test_returns_only_orders_with_invoice_evidence(): void
    {
        $user = User::factory()->create();

        // 1) Ordine con numero fattura SDI → INCLUSO
        $withNumber = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'subtotal' => 2000,
            'sdi_invoice_number' => '2026/00001',
        ]);

        // 2) Ordine con SDI gia' inviato → INCLUSO
        $withSent = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'subtotal' => 3500,
            'sdi_sent_at' => now()->subDay(),
        ]);

        // 3) Ordine senza evidenza fattura → ESCLUSO
        Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'subtotal' => 890,
        ]);

        $response = $this->actingAs($user)->getJson('/api/invoices');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($withNumber->id, $ids);
        $this->assertContains($withSent->id, $ids);
    }

    public function test_includes_orders_with_billing_type_fattura(): void
    {
        $user = User::factory()->create();

        // Ordine con billing_data.type = 'fattura' ma senza SDI ancora → INCLUSO
        $withBillingType = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'subtotal' => 1500,
            'billing_data' => ['type' => 'fattura', 'vat' => '01234567890'],
        ]);

        // Ordine con billing_data.type = 'ricevuta' → ESCLUSO
        Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'subtotal' => 1500,
            'billing_data' => ['type' => 'ricevuta'],
        ]);

        $response = $this->actingAs($user)->getJson('/api/invoices');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $this->assertSame(
            $withBillingType->id,
            $response->json('data.0.id'),
        );
    }

    /* ==========================================================
     *  Pagination
     * ========================================================== */

    public function test_pagination_returns_20_items_per_page(): void
    {
        $user = User::factory()->create();

        // 25 fatture → ci aspettiamo 20 sulla pagina 1, 5 sulla pagina 2.
        for ($i = 1; $i <= 25; $i++) {
            Order::factory()->create([
                'user_id' => $user->id,
                'subtotal' => 100 * $i,
                'sdi_invoice_number' => sprintf('2026/%05d', $i),
            ]);
        }

        $page1 = $this->actingAs($user)->getJson('/api/invoices?page=1');
        $page1->assertStatus(200)->assertJsonCount(20, 'data');
        $this->assertSame(25, $page1->json('meta.total'));
        $this->assertSame(20, $page1->json('meta.per_page'));

        $page2 = $this->actingAs($user)->getJson('/api/invoices?page=2');
        $page2->assertStatus(200)->assertJsonCount(5, 'data');
    }

    /* ==========================================================
     *  Ordering + shape
     * ========================================================== */

    public function test_items_are_ordered_from_most_recent(): void
    {
        $user = User::factory()->create();

        $old = Order::factory()->create([
            'user_id' => $user->id,
            'subtotal' => 1000,
            'sdi_invoice_number' => '2026/00001',
            'created_at' => now()->subDays(10),
        ]);

        $recent = Order::factory()->create([
            'user_id' => $user->id,
            'subtotal' => 2000,
            'sdi_invoice_number' => '2026/00002',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/invoices');

        $response->assertStatus(200);

        $firstId = (int) $response->json('data.0.id');
        $secondId = (int) $response->json('data.1.id');

        $this->assertSame($recent->id, $firstId);
        $this->assertSame($old->id, $secondId);
    }

    public function test_response_shape_contains_expected_fields(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'subtotal' => 2000, // 20,00 EUR
            'sdi_invoice_number' => '2026/00042',
            'sdi_sent_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/invoices');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'order_id',
                        'invoice_number',
                        'issued_at',
                        'amount_cents',
                        'amount_eur_formatted',
                        'sdi_status',
                        'sdi_sent_at',
                        'download_url',
                    ],
                ],
            ]);

        $item = $response->json('data.0');
        $this->assertSame($order->id, (int) $item['id']);
        $this->assertSame('2026/00042', $item['invoice_number']);
        $this->assertSame(2000, (int) $item['amount_cents']);
        $this->assertStringContainsString('20,00', $item['amount_eur_formatted']);
        $this->assertSame('/api/orders/' . $order->id . '/invoice.pdf', $item['download_url']);
    }

    /* ==========================================================
     *  Derived sdi_status
     * ========================================================== */

    public function test_derived_sdi_status_when_column_not_set(): void
    {
        $user = User::factory()->create();

        // Ordine con numero e sdi_sent_at + accepted: atteso "accepted".
        $accepted = Order::factory()->create([
            'user_id' => $user->id,
            'subtotal' => 1000,
            'sdi_invoice_number' => '2026/00100',
            'sdi_sent_at' => now(),
            'sdi_accepted_at' => now(),
            'sdi_status' => null,
        ]);

        // Ordine con numero senza invio: atteso "pending".
        $pending = Order::factory()->create([
            'user_id' => $user->id,
            'subtotal' => 1000,
            'sdi_invoice_number' => '2026/00101',
            'sdi_status' => null,
            'created_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/invoices');
        $response->assertStatus(200);

        $byId = collect($response->json('data'))->keyBy('id');

        $this->assertSame('accepted', $byId->get($accepted->id)['sdi_status']);
        $this->assertSame('pending', $byId->get($pending->id)['sdi_status']);
    }

    /* ==========================================================
     *  Isolation
     * ========================================================== */

    public function test_user_does_not_see_invoices_of_other_users(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        Order::factory()->create([
            'user_id' => $owner->id,
            'subtotal' => 5000,
            'sdi_invoice_number' => '2026/SECRET',
        ]);

        $response = $this->actingAs($intruder)->getJson('/api/invoices');

        $response->assertStatus(200)->assertJsonCount(0, 'data');
    }
}
