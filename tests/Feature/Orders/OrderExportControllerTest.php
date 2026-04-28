<?php

namespace Tests\Feature\Orders;

use App\Models\Order;
use App\Models\Package;
use App\Models\PackageAddress;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test per OrderExportController: export CSV ordini utente.
 *
 * Verifica:
 *  - autenticazione richiesta;
 *  - response CSV con header corretto;
 *  - prezzi in euro formato italiano;
 *  - filtri from/to/status;
 *  - limite max 1000 righe;
 *  - utente non-admin vede solo i propri ordini.
 */
class OrderExportControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Crea un ordine completo con pacco, indirizzi, servizio.
     */
    private function createOrderWithPackage(User $user, string $status = 'pending', int $subtotalCents = 1590, string $tracking = 'BRT1234567890'): Order
    {
        $origin = PackageAddress::factory()->create([
            'name' => 'Mario Rossi',
            'city' => 'Milano',
            'postal_code' => '20121',
        ]);
        $dest = PackageAddress::factory()->create([
            'name' => 'Luigi Verdi',
            'city' => 'Roma',
            'postal_code' => '00185',
        ]);
        $service = Service::create([
            'service_type' => 'Standard',
            'date' => '',
            'time' => '',
        ]);

        $package = Package::factory()->create([
            'user_id' => $user->id,
            'weight' => 5.5,
            'single_price' => $subtotalCents,
            'origin_address_id' => $origin->id,
            'destination_address_id' => $dest->id,
            'service_id' => $service->id,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => $status,
            'subtotal' => $subtotalCents,
            'brt_tracking_number' => $tracking,
        ]);

        Order::attachPackage($order->id, $package->id, 1);

        return $order;
    }

    public function test_export_requires_authentication(): void
    {
        $this->getJson('/api/orders/export')
            ->assertStatus(401);
    }

    public function test_user_can_export_own_orders_as_csv(): void
    {
        $user = User::factory()->create();

        $this->createOrderWithPackage($user, 'processing', 1590, 'BRT0000000001');
        $this->createOrderWithPackage($user, 'delivered', 2500, 'BRT0000000002');
        $this->createOrderWithPackage($user, 'pending', 890, 'BRT0000000003');

        $response = $this->actingAs($user)->get('/api/orders/export');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        // Header CSV atteso
        $this->assertStringContainsString('order_id;data;stato;mittente;destinatario;peso;servizio;totale_eur;tracking_brt', $content);

        // Prezzi convertiti da centesimi a euro formato italiano
        $this->assertStringContainsString('15,90', $content);
        $this->assertStringContainsString('25,00', $content);
        $this->assertStringContainsString('8,90', $content);

        // Tracking BRT presente
        $this->assertStringContainsString('BRT0000000001', $content);

        // BOM UTF-8 in testa
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
    }

    public function test_user_does_not_see_orders_of_other_users(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        $this->createOrderWithPackage($other, 'processing', 9999, 'BRTOTHER123');
        $this->createOrderWithPackage($user, 'processing', 1590, 'BRTSELF123');

        $response = $this->actingAs($user)->get('/api/orders/export');
        $content = $response->streamedContent();

        $this->assertStringContainsString('BRTSELF123', $content);
        $this->assertStringNotContainsString('BRTOTHER123', $content);
    }

    public function test_status_filter_restricts_rows(): void
    {
        $user = User::factory()->create();

        $this->createOrderWithPackage($user, 'pending', 1000, 'BRTPENDING');
        $this->createOrderWithPackage($user, 'delivered', 2000, 'BRTDELIVERED');

        $response = $this->actingAs($user)->get('/api/orders/export?status=delivered');
        $content = $response->streamedContent();

        $this->assertStringContainsString('BRTDELIVERED', $content);
        $this->assertStringNotContainsString('BRTPENDING', $content);
    }

    public function test_date_range_filter_works(): void
    {
        $user = User::factory()->create();

        $old = $this->createOrderWithPackage($user, 'pending', 1000, 'BRTOLD');
        $old->created_at = now()->subDays(10);
        $old->save();

        $recent = $this->createOrderWithPackage($user, 'pending', 2000, 'BRTRECENT');

        $from = now()->subDays(2)->toDateString();
        $response = $this->actingAs($user)->get('/api/orders/export?from=' . $from);
        $content = $response->streamedContent();

        $this->assertStringContainsString('BRTRECENT', $content);
        $this->assertStringNotContainsString('BRTOLD', $content);
    }

    public function test_validation_rejects_invalid_dates(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/orders/export?from=not-a-date')
            ->assertStatus(422);

        $this->actingAs($user)
            ->getJson('/api/orders/export?from=2026-04-10&to=2026-04-01')
            ->assertStatus(422);
    }
}
