<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\Package;
use App\Models\PackageAddress;
use App\Models\Service;
use App\Models\User;
use App\Services\BrtService;
use App\Services\ShipmentDocumentDispatcher;
use App\Services\ShipmentExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ShipmentExecutionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_ritiro_al_piano_does_not_enable_home_pickup_without_pickup_request(): void
    {
        $order = $this->createOrderWithService('ritiro_al_piano');
        $brt = Mockery::mock(BrtService::class);
        $brt->shouldReceive('requestHomePickup')->never();

        $result = $this->executionService($brt)->requestPickup($order);

        $this->assertTrue($result['success']);
        $this->assertSame('not_requested', $result['status']);
        $this->assertSame('not_requested', $order->refresh()->pickup_status);
    }

    public function test_explicit_pickup_request_still_books_pickup_with_ritiro_al_piano_selected(): void
    {
        $pickupDate = now()->addWeekdays(2)->format('Y-m-d');
        $order = $this->createOrderWithService('ritiro_al_piano', [
            'service_data' => [
                'pickup_request' => [
                    'enabled' => true,
                    'date' => $pickupDate,
                    'time_slot' => '09:00-12:00',
                    'notes' => 'Citofono 12',
                ],
            ],
        ]);

        $brt = Mockery::mock(BrtService::class);
        $brt->shouldReceive('requestHomePickup')
            ->once()
            ->withArgs(fn (Order $candidateOrder, array $pickupRequest) =>
                $candidateOrder->is($order)
                && ($pickupRequest['date'] ?? null) === $pickupDate
                && ($pickupRequest['time_slot'] ?? null) === '09:00-12:00'
                && ($pickupRequest['notes'] ?? null) === 'Citofono 12'
            )
            ->andReturn([
                'success' => true,
                'status' => 'requested',
                'pickup_reference' => 'PU-TEST-001',
            ]);

        $result = $this->executionService($brt)->requestPickup($order);

        $this->assertTrue($result['success']);
        $this->assertSame('requested', $order->refresh()->pickup_status);
        $this->assertSame('PU-TEST-001', $order->pickup_reference);
    }

    public function test_legacy_exact_ritiro_service_can_enable_pickup(): void
    {
        $pickupDate = now()->addWeekdays(2)->format('Y-m-d');
        $order = $this->createOrderWithService('Ritiro a domicilio', [
            'date' => $pickupDate,
            'time' => '10:00-13:00',
        ]);

        $brt = Mockery::mock(BrtService::class);
        $brt->shouldReceive('requestHomePickup')
            ->once()
            ->withArgs(fn (Order $candidateOrder, array $pickupRequest) =>
                $candidateOrder->is($order)
                && ($pickupRequest['date'] ?? null) === $pickupDate
                && ($pickupRequest['time_slot'] ?? null) === '10:00-13:00'
            )
            ->andReturn([
                'success' => true,
                'status' => 'requested',
                'pickup_reference' => 'PU-LEGACY-001',
            ]);

        $result = $this->executionService($brt)->requestPickup($order);

        $this->assertTrue($result['success']);
        $this->assertSame('requested', $order->refresh()->pickup_status);
    }

    private function createOrderWithService(string $serviceType, array $serviceOverrides = []): Order
    {
        $user = User::factory()->create();
        $origin = PackageAddress::factory()->create(['type' => 'Partenza']);
        $destination = PackageAddress::factory()->create(['type' => 'Destinazione']);
        $service = Service::create(array_merge([
            'service_type' => $serviceType,
            'date' => '',
            'time' => '',
            'service_data' => [],
        ], $serviceOverrides));
        $package = Package::factory()->create([
            'user_id' => $user->id,
            'origin_address_id' => $origin->id,
            'destination_address_id' => $destination->id,
            'service_id' => $service->id,
        ]);
        $order = Order::factory()->create(['user_id' => $user->id]);

        Order::attachPackage($order->id, $package->id, 1);

        return $order->fresh();
    }

    private function executionService(BrtService $brt): ShipmentExecutionService
    {
        return new ShipmentExecutionService(
            $brt,
            Mockery::mock(ShipmentDocumentDispatcher::class),
        );
    }
}
