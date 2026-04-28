<?php

namespace Tests\Unit\Services;

use App\Events\ShipmentStatusChanged;
use App\Models\Order;
use App\Services\Brt\TrackingService;
use App\Services\OrderBrtTrackingLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class OrderBrtTrackingLifecycleServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_order_from_carrier_updates_order_and_dispatches_event(): void
    {
        Event::fake([ShipmentStatusChanged::class]);

        $order = Order::factory()->create([
            'status' => Order::PROCESSING,
            'brt_parcel_id' => 'PARCEL-123',
        ]);

        $tracking = Mockery::mock(TrackingService::class);
        $tracking->shouldReceive('getTrackingStatus')
            ->once()
            ->withArgs(fn (Order $candidateOrder) => $candidateOrder->is($order))
            ->andReturn([
                'status' => Order::IN_TRANSIT,
                'brt_event' => 'PICKED_UP',
                'description' => 'Ritirato dal corriere',
                'error' => null,
            ]);
        app()->instance(TrackingService::class, $tracking);

        $service = app(OrderBrtTrackingLifecycleService::class);
        $result = $service->syncOrderFromCarrier($order);

        $this->assertSame('updated', $result['outcome']);
        $this->assertSame(Order::PROCESSING, $result['old_status']);
        $this->assertSame(Order::IN_TRANSIT, $result['new_status']);

        $order->refresh();

        $this->assertSame(Order::IN_TRANSIT, $order->rawStatus());
        $this->assertNotNull($order->brt_last_tracking_check);
        Event::assertDispatched(ShipmentStatusChanged::class, function (ShipmentStatusChanged $event) use ($order) {
            return $event->order->is($order)
                && $event->oldStatus === Order::PROCESSING
                && $event->newStatus === Order::IN_TRANSIT;
        });
    }

    public function test_apply_webhook_status_update_blocks_regression_from_final_state(): void
    {
        Event::fake([ShipmentStatusChanged::class]);

        $order = Order::factory()->create([
            'status' => Order::DELIVERED,
            'brt_parcel_id' => 'PARCEL-FINAL',
        ]);

        $tracking = Mockery::mock(TrackingService::class);
        $tracking->shouldReceive('mapCarrierStatus')
            ->once()
            ->with('IN_TRANSIT', '')
            ->andReturn(Order::IN_TRANSIT);
        app()->instance(TrackingService::class, $tracking);

        $service = app(OrderBrtTrackingLifecycleService::class);
        $result = $service->applyWebhookStatusUpdate('PARCEL-FINAL', 'IN_TRANSIT');

        $this->assertSame('blocked_final_state', $result['outcome']);

        $order->refresh();

        $this->assertSame(Order::DELIVERED, $order->rawStatus());
        $this->assertNull($order->brt_last_tracking_check);
        Event::assertNotDispatched(ShipmentStatusChanged::class);
    }
}
