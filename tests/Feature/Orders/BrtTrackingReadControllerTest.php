<?php

namespace Tests\Feature\Orders;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BrtTrackingReadControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_tracking_search_finds_order_by_sf_reference_and_builds_fallback_tracking_url(): void
    {
        $order = Order::factory()->create([
            'status' => Order::LABEL_GENERATED,
            'brt_parcel_id' => 'PARCEL-READ-1',
            'brt_tracking_number' => 'TRACK-READ-1',
            'brt_tracking_url' => null,
        ]);

        $this->getJson('/api/tracking/search?code=SF-'.$order->id)
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('order_id', $order->id)
            ->assertJsonPath('raw_status', Order::LABEL_GENERATED)
            ->assertJsonPath('status', 'Etichetta generata')
            ->assertJsonPath('brt_parcel_id', 'PARCEL-READ-1')
            ->assertJsonPath('brt_tracking_number', 'TRACK-READ-1')
            ->assertJsonPath('brt_tracking_url', 'https://vas.brt.it/vas/sped_det_show.hsm?refnr=TRACK-READ-1');
    }

    public function test_authenticated_order_tracking_uses_canonical_tracking_url_fallback(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::IN_TRANSIT,
            'brt_parcel_id' => 'PARCEL-READ-2',
            'brt_tracking_number' => null,
            'brt_tracking_url' => null,
        ]);

        $this->getJson("/api/brt/tracking/{$order->id}")
            ->assertOk()
            ->assertJsonPath('parcel_id', 'PARCEL-READ-2')
            ->assertJsonPath('tracking_number', null)
            ->assertJsonPath('tracking_url', 'https://vas.brt.it/vas/sped_det_show.hsm?refnr=PARCEL-READ-2')
            ->assertJsonPath('status', Order::IN_TRANSIT);
    }
}
