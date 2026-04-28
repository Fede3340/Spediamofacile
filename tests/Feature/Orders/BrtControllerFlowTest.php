<?php

namespace Tests\Feature\Orders;

use App\Models\Order;
use App\Models\Package;
use App\Models\PackageAddress;
use App\Models\Service;
use App\Models\User;
use App\Services\Brt\ShipmentService;
use App\Services\ShipmentExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class BrtControllerFlowTest extends TestCase
{
    use RefreshDatabase;

    private function createOrderForUser(User $user, array $orderOverrides = []): Order
    {
        $origin = PackageAddress::factory()->create();
        $destination = PackageAddress::factory()->create();
        $service = Service::create([
            'service_type' => 'Nessuno',
            'date' => '',
            'time' => '',
            'service_data' => [],
        ]);

        $package = Package::create([
            'package_type' => 'Pacco',
            'quantity' => 1,
            'weight' => 5,
            'first_size' => 30,
            'second_size' => 20,
            'third_size' => 15,
            'single_price' => 1190,
            'origin_address_id' => $origin->id,
            'destination_address_id' => $destination->id,
            'service_id' => $service->id,
            'user_id' => $user->id,
        ]);

        $order = Order::factory()->create(array_merge([
            'user_id' => $user->id,
            'status' => Order::COMPLETED,
            'subtotal' => 1190,
        ], $orderOverrides));

        Order::attachPackage($order->id, $package->id, 1);

        return $order;
    }

    /**
     * @return array<string, mixed>
     */
    private function shipmentSuccessPayload(): array
    {
        return [
            'success' => true,
            'parcel_id' => 'PARCEL-321',
            'numeric_sender_reference' => 'SENDER-321',
            'tracking_url' => 'https://tracking.example.test/PARCEL-321',
            'label_base64' => base64_encode('%PDF-1.4 fake label'),
            'tracking_number' => 'TRACK-321',
            'raw_response' => ['ok' => true],
        ];
    }

    public function test_owner_can_create_brt_shipment_and_persist_canonical_fields(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $order = $this->createOrderForUser($user, [
            'status' => Order::PROCESSING,
        ]);

        $shipment = Mockery::mock(ShipmentService::class);
        $shipment->shouldReceive('createShipment')
            ->once()
            ->withArgs(function (Order $candidateOrder, array $options) use ($order) {
                return $candidateOrder->is($order)
                    && ($options['is_cod'] ?? null) === true
                    && ($options['cod_amount'] ?? null) === 2500
                    && ($options['pudo_id'] ?? null) === 'PUDO-123'
                    && ($options['notes'] ?? null) === 'Consegna al vicino';
            })
            ->andReturn($this->shipmentSuccessPayload());
        app()->instance(ShipmentService::class, $shipment);

        $execution = Mockery::mock(ShipmentExecutionService::class);
        $execution->shouldReceive('runAutomaticPostLabelFlow')
            ->once()
            ->withArgs(fn (Order $candidateOrder) => $candidateOrder->id === $order->id);
        app()->instance(ShipmentExecutionService::class, $execution);

        $this->postJson('/api/brt/create-shipment', [
            'order_id' => $order->id,
            'is_cod' => true,
            'cod_amount' => 2500,
            'pudo_id' => 'PUDO-123',
            'notes' => 'Consegna al vicino',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('parcel_id', 'PARCEL-321')
            ->assertJsonPath('order_status', Order::LABEL_GENERATED);

        $order->refresh();

        $this->assertSame('PARCEL-321', $order->brt_parcel_id);
        $this->assertSame('SENDER-321', $order->brt_numeric_sender_reference);
        $this->assertSame('https://tracking.example.test/PARCEL-321', $order->brt_tracking_url);
        $this->assertSame('TRACK-321', $order->brt_tracking_number);
        $this->assertSame('PUDO-123', $order->brt_pudo_id);
        $this->assertTrue($order->is_cod);
        $this->assertSame(2500, $order->cod_amount);
        $this->assertSame(Order::LABEL_GENERATED, $order->rawStatus());
    }

    public function test_admin_can_regenerate_label_through_the_same_order_centric_boundary(): void
    {
        config()->set('services.brt.client_id', 'test-client-id');

        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'Admin'])->save();
        Sanctum::actingAs($admin);

        $order = $this->createOrderForUser($admin, [
            'is_cod' => true,
            'cod_amount' => 1800,
            'brt_pudo_id' => 'PUDO-ADMIN-1',
        ]);

        $shipment = Mockery::mock(ShipmentService::class);
        $shipment->shouldReceive('createShipment')
            ->once()
            ->withArgs(function (Order $candidateOrder, array $options) use ($order) {
                return $candidateOrder->is($order)
                    && ($options['is_cod'] ?? null) === true
                    && ($options['cod_amount'] ?? null) === 1800
                    && ($options['pudo_id'] ?? null) === 'PUDO-ADMIN-1';
            })
            ->andReturn($this->shipmentSuccessPayload());
        app()->instance(ShipmentService::class, $shipment);

        $execution = Mockery::mock(ShipmentExecutionService::class);
        $execution->shouldReceive('runAutomaticPostLabelFlow')
            ->once()
            ->withArgs(fn (Order $candidateOrder) => $candidateOrder->id === $order->id);
        app()->instance(ShipmentExecutionService::class, $execution);

        $this->postJson("/api/admin/orders/{$order->id}/regenerate-label")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.parcel_id', 'PARCEL-321');

        $order->refresh();

        $this->assertSame('PARCEL-321', $order->brt_parcel_id);
        $this->assertSame('PUDO-ADMIN-1', $order->brt_pudo_id);
        $this->assertTrue($order->is_cod);
        $this->assertSame(1800, $order->cod_amount);
        $this->assertSame(Order::LABEL_GENERATED, $order->rawStatus());
    }
}
