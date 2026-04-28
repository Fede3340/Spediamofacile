<?php

namespace Tests\Feature\Orders;

use App\Events\ShipmentStatusChanged;
use App\Models\Order;
use App\Services\Brt\TrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class SyncBrtTrackingCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_command_updates_order_via_order_centric_tracking_lifecycle(): void
    {
        Event::fake([ShipmentStatusChanged::class]);

        $order = Order::factory()->create([
            'status' => Order::PROCESSING,
            'brt_parcel_id' => 'PARCEL-COMMAND-1',
        ]);

        $tracking = Mockery::mock(TrackingService::class);
        $tracking->shouldReceive('getTrackingStatus')
            ->once()
            ->withArgs(fn (Order $candidateOrder) => $candidateOrder->is($order))
            ->andReturn([
                'status' => Order::OUT_FOR_DELIVERY,
                'brt_event' => 'OUT_FOR_DELIVERY',
                'description' => 'In consegna',
                'error' => null,
            ]);
        app()->instance(TrackingService::class, $tracking);

        $this->artisan('orders:sync-tracking', [
            '--order' => $order->id,
        ])->assertExitCode(0);

        $order->refresh();

        $this->assertSame(Order::OUT_FOR_DELIVERY, $order->rawStatus());
        $this->assertNotNull($order->brt_last_tracking_check);
        Event::assertDispatched(ShipmentStatusChanged::class, function (ShipmentStatusChanged $event) use ($order) {
            return $event->order->is($order)
                && $event->oldStatus === Order::PROCESSING
                && $event->newStatus === Order::OUT_FOR_DELIVERY;
        });
    }
}
