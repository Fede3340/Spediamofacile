<?php

namespace Tests\Feature\Orders;

use App\Events\ShipmentStatusChanged;
use App\Models\BrtWebhookEvent;
use App\Models\Order;
use App\Services\Brt\TrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class BrtWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_tracking_webhook_updates_order_through_order_centric_lifecycle_service(): void
    {
        Event::fake([ShipmentStatusChanged::class]);

        $order = Order::factory()->create([
            'status' => Order::PROCESSING,
            'brt_parcel_id' => 'PARCEL-WEBHOOK-1',
        ]);

        $tracking = Mockery::mock(TrackingService::class);
        $tracking->shouldReceive('mapCarrierStatus')
            ->once()
            ->with('IN_TRANSIT', 'Ritirato dal corriere')
            ->andReturn(Order::IN_TRANSIT);
        app()->instance(TrackingService::class, $tracking);

        $this->postJson('/webhooks/brt/tracking', [
            'parcelId' => 'PARCEL-WEBHOOK-1',
            'status' => 'IN_TRANSIT',
            'timestamp' => '2026-04-23T10:15:00Z',
            'description' => 'Ritirato dal corriere',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('order_id', $order->id)
            ->assertJsonPath('old_status', Order::PROCESSING)
            ->assertJsonPath('new_status', Order::IN_TRANSIT);

        $order->refresh();

        $this->assertSame(Order::IN_TRANSIT, $order->rawStatus());
        $this->assertNotNull($order->brt_last_tracking_check);
        $this->assertDatabaseHas('brt_webhook_events', [
            'parcel_id' => 'PARCEL-WEBHOOK-1',
            'status' => 'IN_TRANSIT',
            'event_timestamp' => '2026-04-23T10:15:00Z',
        ]);
        Event::assertDispatched(ShipmentStatusChanged::class, function (ShipmentStatusChanged $event) use ($order) {
            return $event->order->is($order)
                && $event->oldStatus === Order::PROCESSING
                && $event->newStatus === Order::IN_TRANSIT;
        });
    }

    public function test_tracking_webhook_deduplicates_same_event_fingerprint(): void
    {
        $order = Order::factory()->create([
            'status' => Order::PROCESSING,
            'brt_parcel_id' => 'PARCEL-WEBHOOK-2',
        ]);

        $tracking = Mockery::mock(TrackingService::class);
        $tracking->shouldReceive('mapCarrierStatus')
            ->once()
            ->with('IN_TRANSIT', '')
            ->andReturn(Order::IN_TRANSIT);
        app()->instance(TrackingService::class, $tracking);

        $payload = [
            'parcelId' => 'PARCEL-WEBHOOK-2',
            'status' => 'IN_TRANSIT',
            'timestamp' => '2026-04-23T10:20:00Z',
        ];

        $this->postJson('/webhooks/brt/tracking', $payload)
            ->assertOk()
            ->assertJsonPath('success', true);

        $second = $this->postJson('/webhooks/brt/tracking', $payload);

        $second->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('skipped', 'already_processed');

        $this->assertSame(1, BrtWebhookEvent::count());

        $order->refresh();
        $this->assertSame(Order::IN_TRANSIT, $order->rawStatus());
    }
}
