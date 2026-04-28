<?php

namespace Tests\Feature\SavedShipments;

use App\Models\Package;
use App\Models\PackageAddress;
use App\Models\Service;
use App\Models\User;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SavedShipmentControllerTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'origin_address' => [
                'type' => 'privato',
                'name' => 'Mario Rossi',
                'address' => 'Via Roma',
                'number_type' => 'civico',
                'address_number' => '10',
                'country' => 'Italia',
                'city' => 'Milano',
                'postal_code' => '20121',
                'province' => 'MI',
                'telephone_number' => '3331234567',
            ],
            'destination_address' => [
                'type' => 'privato',
                'name' => 'Luigi Verdi',
                'address' => 'Via Napoli',
                'number_type' => 'civico',
                'address_number' => '5',
                'country' => 'Italia',
                'city' => 'Roma',
                'postal_code' => '00185',
                'province' => 'RM',
                'telephone_number' => '3339876543',
            ],
            'services' => [
                'service_type' => 'Nessuno',
                'date' => '',
                'time' => '',
            ],
            'packages' => [
                [
                    'package_type' => 'Pacco',
                    'quantity' => 1,
                    'weight' => 5,
                    'first_size' => 30,
                    'second_size' => 20,
                    'third_size' => 15,
                ],
            ],
        ], $overrides);
    }

    public function test_store_saved_shipment_reprices_packages_server_side(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/saved-shipments', $this->validPayload([
            'packages' => [
                [
                    'package_type' => 'Pacco',
                    'quantity' => 1,
                    'weight' => 5,
                    'first_size' => 30,
                    'second_size' => 20,
                    'third_size' => 15,
                    'weight_price' => 999,
                    'volume_price' => 888,
                    'single_price' => 777,
                ],
            ],
        ]));

        $response->assertSuccessful();

        $package = Package::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame(1190, (int) $package->single_price);
        $this->assertSame(11.9, (float) $package->weight_price);
        $this->assertSame(8.9, (float) $package->volume_price);
    }

    public function test_add_to_cart_reprices_saved_shipment_before_copying(): void
    {
        $user = User::factory()->create();
        $origin = PackageAddress::factory()->create([
            'city' => 'Milano',
            'postal_code' => '20121',
        ]);
        $destination = PackageAddress::factory()->create([
            'city' => 'Roma',
            'postal_code' => '00185',
        ]);
        $service = Service::create([
            'service_type' => 'Nessuno',
            'date' => '',
            'time' => '',
        ]);

        $savedPackage = Package::create([
            'package_type' => 'Pacco',
            'quantity' => 1,
            'weight' => 5,
            'first_size' => 30,
            'second_size' => 20,
            'third_size' => 15,
            'weight_price' => 999.0,
            'volume_price' => 888.0,
            'single_price' => 777700,
            'origin_address_id' => $origin->id,
            'destination_address_id' => $destination->id,
            'service_id' => $service->id,
            'user_id' => $user->id,
        ]);

        DB::table('saved_shipments')->insert([
            'user_id' => $user->id,
            'package_id' => $savedPackage->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson('/api/saved-shipments/add-to-cart', [
            'package_ids' => [$savedPackage->id],
        ]);

        $response->assertSuccessful();
        $response->assertJsonPath('moved', 1);

        $copiedPackage = Package::query()
            ->where('user_id', $user->id)
            ->whereKeyNot($savedPackage->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(1190, (int) $copiedPackage->single_price);
        $this->assertSame(11.9, (float) $copiedPackage->weight_price);
        $this->assertSame(8.9, (float) $copiedPackage->volume_price);
        $this->assertTrue(
            DB::table('cart_user')
                ->where('user_id', $user->id)
                ->where('package_id', $copiedPackage->id)
                ->exists()
        );
    }

    public function test_update_requires_saved_shipment_row(): void
    {
        $user = User::factory()->create();
        $origin = PackageAddress::factory()->create();
        $destination = PackageAddress::factory()->create();
        $service = Service::create([
            'service_type' => 'Nessuno',
            'date' => '',
            'time' => '',
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

        $this->actingAs($user)
            ->putJson("/api/saved-shipments/{$package->id}", [
                'package_type' => 'Pacco',
            ])
            ->assertStatus(404)
            ->assertJsonFragment(['message' => 'Pacco non trovato nelle spedizioni salvate']);

        $package->refresh();
        $this->assertSame(1, (int) $package->quantity);
    }

    public function test_destroy_requires_saved_shipment_row(): void
    {
        $user = User::factory()->create();
        $origin = PackageAddress::factory()->create();
        $destination = PackageAddress::factory()->create();
        $service = Service::create([
            'service_type' => 'Nessuno',
            'date' => '',
            'time' => '',
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

        $this->actingAs($user)
            ->deleteJson("/api/saved-shipments/{$package->id}")
            ->assertStatus(404)
            ->assertJsonFragment(['message' => 'Pacco non trovato nelle spedizioni salvate']);

        $this->assertDatabaseHas('packages', ['id' => $package->id]);
    }

    public function test_destroy_saved_shipment_fails_if_package_in_order(): void
    {
        $user = User::factory()->create();
        $origin = PackageAddress::factory()->create();
        $destination = PackageAddress::factory()->create();
        $service = Service::create([
            'service_type' => 'Nessuno',
            'date' => '',
            'time' => '',
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

        DB::table('saved_shipments')->insert([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $order = Order::factory()->create(['user_id' => $user->id]);
        DB::table('package_order')->insert([
            'order_id' => $order->id,
            'package_id' => $package->id,
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->deleteJson("/api/saved-shipments/{$package->id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('packages', ['id' => $package->id]);
        $this->assertTrue(
            DB::table('saved_shipments')
                ->where('user_id', $user->id)
                ->where('package_id', $package->id)
                ->exists()
        );
    }
}
