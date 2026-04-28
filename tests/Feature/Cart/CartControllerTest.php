<?php

namespace Tests\Feature\Cart;

use App\Models\Package;
use App\Models\PackageAddress;
use App\Models\Service;
use App\Models\User;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CartControllerTest extends TestCase
{
    use RefreshDatabase;

    /* ------------------------------------------------------------------ */
    /*  Helper: payload completo per POST /api/cart (PackageStoreRequest)  */
    /* ------------------------------------------------------------------ */
    private function validCartPayload(array $overrides = []): array
    {
        return array_merge([
            'origin_address' => [
                'type'               => 'privato',
                'name'               => 'Mario Rossi',
                'address'            => 'Via Roma',
                'number_type'        => 'civico',
                'address_number'     => '10',
                'country'            => 'Italia',
                'city'               => 'Milano',
                'postal_code'        => '20121',
                'province'           => 'MI',
                'telephone_number'   => '3331234567',
            ],
            'destination_address' => [
                'type'               => 'privato',
                'name'               => 'Luigi Verdi',
                'address'            => 'Via Napoli',
                'number_type'        => 'civico',
                'address_number'     => '5',
                'country'            => 'Italia',
                'city'               => 'Roma',
                'postal_code'        => '00185',
                'province'           => 'RM',
                'telephone_number'   => '3339876543',
            ],
            'services' => [
                'service_type' => 'Nessuno',
                'date'         => '',
                'time'         => '',
            ],
            'packages' => [
                [
                    'package_type' => 'Pacco',
                    'quantity'     => 1,
                    'weight'       => 5,
                    'first_size'   => 30,
                    'second_size'  => 20,
                    'third_size'   => 15,
                ],
            ],
        ], $overrides);
    }

    /* ------------------------------------------------------------------ */
    /*  Helper: inserire manualmente un pacco nel carrello di un utente   */
    /* ------------------------------------------------------------------ */
    private function makeAddressFixture(string $kind): PackageAddress
    {
        $isOrigin = $kind === 'origin';

        return PackageAddress::factory()->create([
            'type' => 'privato',
            'name' => $isOrigin ? 'Mario Rossi' : 'Luigi Verdi',
            'address' => $isOrigin ? 'Via Roma' : 'Via Napoli',
            'number_type' => 'civico',
            'address_number' => $isOrigin ? '10' : '5',
            'country' => 'Italia',
            'city' => $isOrigin ? 'Milano' : 'Roma',
            'postal_code' => $isOrigin ? '20121' : '00185',
            'province' => $isOrigin ? 'MI' : 'RM',
            'telephone_number' => $isOrigin ? '3331234567' : '3339876543',
        ]);
    }

    private function makePackageForUser(User $user, int $singlePriceCents = 1190): Package
    {
        $origin      = $this->makeAddressFixture('origin');
        $destination = $this->makeAddressFixture('destination');
        $service     = Service::create([
            'service_type' => 'Nessuno',
            'date'         => '',
            'time'         => '',
        ]);

        $package = Package::create([
            'package_type'           => 'Pacco',
            'quantity'               => 1,
            'weight'                 => 5,
            'first_size'             => 30,
            'second_size'            => 20,
            'third_size'             => 15,
            'weight_price'           => 11.9,
            'volume_price'           => 8.9,
            'single_price'           => $singlePriceCents,
            'origin_address_id'      => $origin->id,
            'destination_address_id' => $destination->id,
            'service_id'             => $service->id,
            'user_id'                => $user->id,
        ]);

        return $package;
    }

    private function seedCartForUser(User $user, int $singlePriceCents = 1190): Package
    {
        $package = $this->makePackageForUser($user, $singlePriceCents);

        DB::table('cart_user')->insert([
            'user_id'    => $user->id,
            'package_id' => $package->id,
        ]);

        return $package;
    }

    /* ================================================================== */
    /*  T11.2.0: Cart requires authentication                             */
    /* ================================================================== */
    public function test_cart_requires_authentication(): void
    {
        $this->getJson('/api/cart')->assertStatus(401);
        $this->postJson('/api/cart', $this->validCartPayload())->assertStatus(401);
    }

    /* ================================================================== */
    /*  T11.2.1: Add package to cart                                       */
    /* ================================================================== */
    public function test_add_package_to_cart(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/cart', $this->validCartPayload());

        $response->assertSuccessful();

        // Package record created in DB
        $this->assertDatabaseHas('packages', [
            'user_id'      => $user->id,
            'package_type' => 'Pacco',
            'weight'       => 5,
        ]);

        // cart_user pivot row exists
        $this->assertTrue(
            DB::table('cart_user')
                ->where('user_id', $user->id)
                ->exists()
        );
    }

    public function test_add_package_to_cart_reprices_from_server_rules(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/cart', $this->validCartPayload([
                'packages' => [
                    [
                        'package_type' => 'Pacco',
                        'quantity' => 1,
                        'weight' => 5,
                        'first_size' => 30,
                        'second_size' => 20,
                        'third_size' => 15,
                        'single_price' => 9999,
                    ],
                ],
            ]));

        $response->assertSuccessful();

        $package = Package::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame(1190, (int) $package->single_price);
        $this->assertSame(11.9, (float) $package->weight_price);
        $this->assertSame(8.9, (float) $package->volume_price);
    }

    public function test_cart_store_rejects_pudo_without_pickup_point(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/cart', $this->validCartPayload([
                'delivery_mode' => 'pudo',
                'pudo' => [
                    'name' => 'BRT Point',
                ],
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['pudo.pudo_id']);
    }

    public function test_cart_store_accepts_selected_pudo_alias_and_persists_delivery_mode_context(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/cart', $this->validCartPayload([
                'delivery_mode' => 'pudo',
                'selected_pudo' => [
                    'pudo_id' => 'BRT-POINT-002',
                    'name' => 'BRT Point Milano Nord',
                    'address' => 'Via del Punto 22',
                    'city' => 'Milano',
                    'zip_code' => '20121',
                    'province' => 'MI',
                ],
            ]))
            ->assertSuccessful();

        $package = Package::query()->with('service')->where('user_id', $user->id)->firstOrFail();
        $serviceData = $package->service?->service_data ?? [];

        $this->assertSame('pudo', data_get($serviceData, 'delivery_mode'));
        $this->assertSame('BRT-POINT-002', data_get($serviceData, 'pudo.pudo_id'));
    }

    /* ================================================================== */
    /*  T11.2.2: Get cart contents                                         */
    /* ================================================================== */
    public function test_get_cart_contents(): void
    {
        $user    = User::factory()->create();
        $package = $this->seedCartForUser($user);

        $response = $this->actingAs($user)->getJson('/api/cart');

        $response->assertSuccessful();

        // Response has meta with subtotal, empty flag and address_groups
        $response->assertJsonStructure([
            'data',
            'meta' => ['empty', 'subtotal', 'total', 'address_groups'],
        ]);

        // meta.empty should be false
        $response->assertJsonPath('meta.empty', false);
    }

    public function test_empty_cart_returns_empty_flag(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/cart');

        $response->assertSuccessful();
        $response->assertJsonPath('meta.empty', true);
    }

    /* ================================================================== */
    /*  T11.2.3: Remove from cart                                          */
    /* ================================================================== */
    public function test_remove_package_from_cart(): void
    {
        $user    = User::factory()->create();
        $package = $this->seedCartForUser($user);

        $response = $this->actingAs($user)
            ->deleteJson("/api/cart/{$package->id}");

        $response->assertSuccessful();
        $response->assertJsonFragment(['message' => 'Spedizione rimossa dal carrello']);

        // Pivot row removed
        $this->assertFalse(
            DB::table('cart_user')
                ->where('user_id', $user->id)
                ->where('package_id', $package->id)
                ->exists()
        );

        // Package itself deleted
        $this->assertDatabaseMissing('packages', ['id' => $package->id]);
    }

    /* ================================================================== */
    /*  T11.2.4: Update quantity                                           */
    /* ================================================================== */
    public function test_update_quantity(): void
    {
        $user    = User::factory()->create();
        $package = $this->seedCartForUser($user, 1190); // 1 x 1190 cents

        $response = $this->actingAs($user)
            ->patchJson("/api/cart/{$package->id}/quantity", [
                'quantity' => 3,
            ]);

        $response->assertSuccessful();
        $response->assertJsonPath('quantity', 3);

        // Unit price = 1190, new total = 1190 * 3 = 3570
        $response->assertJsonPath('single_price', 3570);
    }

    public function test_update_quantity_reprices_from_server_rules_even_if_existing_total_is_stale(): void
    {
        $user = User::factory()->create();
        $package = $this->seedCartForUser($user, 9999);

        $response = $this->actingAs($user)
            ->patchJson("/api/cart/{$package->id}/quantity", [
                'quantity' => 3,
            ]);

        $response->assertSuccessful();
        $response->assertJsonPath('quantity', 3);
        $response->assertJsonPath('single_price', 3570);

        $package->refresh();
        $this->assertSame(3570, (int) $package->single_price);
        $this->assertSame(11.9, (float) $package->weight_price);
        $this->assertSame(8.9, (float) $package->volume_price);
    }

    public function test_update_quantity_returns_404_when_package_is_not_in_cart(): void
    {
        $user = User::factory()->create();
        $package = $this->makePackageForUser($user);

        $this->actingAs($user)
            ->patchJson("/api/cart/{$package->id}/quantity", [
                'quantity' => 3,
            ])
            ->assertStatus(404)
            ->assertJsonFragment(['message' => 'Pacco non trovato nel carrello']);

        $package->refresh();
        $this->assertSame(1, (int) $package->quantity);
        $this->assertDatabaseMissing('cart_user', [
            'user_id' => $user->id,
            'package_id' => $package->id,
        ]);
    }

    public function test_update_quantity_validation_rejects_zero(): void
    {
        $user    = User::factory()->create();
        $package = $this->seedCartForUser($user);

        $this->actingAs($user)
            ->patchJson("/api/cart/{$package->id}/quantity", ['quantity' => 0])
            ->assertStatus(422);
    }

    public function test_destroy_returns_404_when_package_is_not_in_cart(): void
    {
        $user = User::factory()->create();
        $package = $this->makePackageForUser($user);

        $this->actingAs($user)
            ->deleteJson("/api/cart/{$package->id}")
            ->assertStatus(404)
            ->assertJsonFragment(['message' => 'Pacco non trovato nel carrello']);

        $this->assertDatabaseHas('packages', [
            'id' => $package->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseMissing('cart_user', [
            'user_id' => $user->id,
            'package_id' => $package->id,
        ]);
    }

    public function test_remove_package_from_cart_fails_if_package_in_order(): void
    {
        $user = User::factory()->create();
        $package = $this->seedCartForUser($user);
        $order = Order::factory()->create(['user_id' => $user->id]);

        DB::table('package_order')->insert([
            'order_id' => $order->id,
            'package_id' => $package->id,
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->deleteJson("/api/cart/{$package->id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('packages', ['id' => $package->id]);
        $this->assertTrue(
            DB::table('cart_user')
                ->where('user_id', $user->id)
                ->where('package_id', $package->id)
                ->exists()
        );
    }

    public function test_merge_identical_keeps_saved_shipment_backed_package_as_master(): void
    {
        $user = User::factory()->create();
        $safePackage = $this->makePackageForUser($user, 1190);
        $protectedPackage = $this->makePackageForUser($user, 1190);

        DB::table('cart_user')->insert([
            [
                'user_id' => $user->id,
                'package_id' => $safePackage->id,
            ],
            [
                'user_id' => $user->id,
                'package_id' => $protectedPackage->id,
            ],
        ]);
        DB::table('saved_shipments')->insert([
            'user_id' => $user->id,
            'package_id' => $protectedPackage->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->postJson('/api/cart/merge')
            ->assertOk()
            ->assertJsonPath('merged', 1);

        $protectedPackage->refresh();
        $this->assertSame(2, (int) $protectedPackage->quantity);
        $this->assertSame(2380, (int) $protectedPackage->single_price);
        $this->assertDatabaseHas('saved_shipments', [
            'user_id' => $user->id,
            'package_id' => $protectedPackage->id,
        ]);
        $this->assertDatabaseHas('cart_user', [
            'user_id' => $user->id,
            'package_id' => $protectedPackage->id,
        ]);
        $this->assertDatabaseMissing('packages', [
            'id' => $safePackage->id,
        ]);
    }

    public function test_merge_identical_keeps_order_linked_package_as_master(): void
    {
        $user = User::factory()->create();
        $safePackage = $this->makePackageForUser($user, 1190);
        $protectedPackage = $this->makePackageForUser($user, 1190);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::PENDING,
        ]);

        DB::table('cart_user')->insert([
            [
                'user_id' => $user->id,
                'package_id' => $safePackage->id,
            ],
            [
                'user_id' => $user->id,
                'package_id' => $protectedPackage->id,
            ],
        ]);
        DB::table('package_order')->insert([
            'order_id' => $order->id,
            'package_id' => $protectedPackage->id,
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->postJson('/api/cart/merge')
            ->assertOk()
            ->assertJsonPath('merged', 1);

        $protectedPackage->refresh();
        $this->assertSame(2, (int) $protectedPackage->quantity);
        $this->assertSame(2380, (int) $protectedPackage->single_price);
        $this->assertDatabaseHas('package_order', [
            'order_id' => $order->id,
            'package_id' => $protectedPackage->id,
        ]);
        $this->assertDatabaseHas('cart_user', [
            'user_id' => $user->id,
            'package_id' => $protectedPackage->id,
        ]);
        $this->assertDatabaseMissing('packages', [
            'id' => $safePackage->id,
        ]);
    }

    /* ================================================================== */
    /*  Correct data structure verification                                */
    /* ================================================================== */
    public function test_store_validates_required_fields(): void
    {
        $user = User::factory()->create();

        // Missing packages entirely
        $this->actingAs($user)
            ->postJson('/api/cart', [
                'origin_address' => ['name' => 'x'],
            ])
            ->assertStatus(422);
    }
}
