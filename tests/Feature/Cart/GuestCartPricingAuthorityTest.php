<?php

namespace Tests\Feature\Cart;

use App\Models\Package;
use App\Models\PackageAddress;
use App\Models\Service;
use App\Models\User;
use App\Services\GuestCartMergeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GuestCartPricingAuthorityTest extends TestCase
{
    use RefreshDatabase;

    private function cartPayload(array $overrides = []): array
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

    public function test_guest_cart_store_reprices_packages_server_side(): void
    {
        $response = $this->postJson('/api/guest-cart', $this->cartPayload([
            'packages' => [
                [
                    'package_type' => 'Pacco',
                    'quantity' => 1,
                    'weight' => 5,
                    'first_size' => 30,
                    'second_size' => 20,
                    'third_size' => 15,
                    'weight_price' => 9999,
                    'volume_price' => 8888,
                    'single_price' => 7777,
                ],
            ],
        ]));

        $response->assertOk();
        $response->assertJsonPath('data.0.single_price', 1190);
    }

    public function test_login_merge_reprices_guest_cart_before_persisting(): void
    {
        $user = User::factory()->create([
            'email' => 'cliente@example.test',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $packageData = $this->cartPayload([
            'packages' => [
                [
                    'package_type' => 'Pacco',
                    'quantity' => 1,
                    'weight' => 5,
                    'first_size' => 30,
                    'second_size' => 20,
                    'third_size' => 15,
                    'single_price' => 1,
                ],
            ],
        ])['packages'];

        $guestCart = [
            [
                ...$packageData[0],
                'origin_address' => $this->cartPayload()['origin_address'],
                'destination_address' => $this->cartPayload()['destination_address'],
                'services' => $this->cartPayload()['services'],
            ],
        ];

        app(GuestCartMergeService::class)->merge($guestCart, $user);

        $package = Package::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame(1190, (int) $package->single_price);
        $this->assertDatabaseHas('cart_user', [
            'user_id' => $user->id,
            'package_id' => $package->id,
        ]);
    }

    public function test_login_merge_does_not_merge_guest_package_with_identical_package_outside_cart(): void
    {
        $user = User::factory()->create([
            'email' => 'cliente2@example.test',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

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
            'single_price' => 1190,
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

        $guestCart = [[
            ...$this->cartPayload()['packages'][0],
            'origin_address' => $this->cartPayload()['origin_address'],
            'destination_address' => $this->cartPayload()['destination_address'],
            'services' => $this->cartPayload()['services'],
        ]];

        app(GuestCartMergeService::class)->merge($guestCart, $user);

        $this->assertDatabaseHas('saved_shipments', [
            'user_id' => $user->id,
            'package_id' => $savedPackage->id,
        ]);

        $cartPackageIds = DB::table('cart_user')
            ->where('user_id', $user->id)
            ->pluck('package_id');

        $this->assertCount(1, $cartPackageIds);
        $this->assertNotSame($savedPackage->id, (int) $cartPackageIds->first());
        $this->assertSame(2, Package::query()->where('user_id', $user->id)->count());
    }
}
