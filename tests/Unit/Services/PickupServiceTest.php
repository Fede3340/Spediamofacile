<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\Package;
use App\Models\PackageAddress;
use App\Models\Service;
use App\Models\User;
use App\Services\Brt\AddressNormalizer;
use App\Services\Brt\BrtConfig;
use App\Services\Brt\PickupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PickupServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_pickup_moltiplica_il_peso_per_la_quantita_dei_colli(): void
    {
        config()->set('services.brt.api_url', 'https://brt.example.test/rest/v1/shipments');
        config()->set('services.brt.client_id', '1020108');
        config()->set('services.brt.password', 'secret');
        config()->set('services.brt.departure_depot', 27);
        config()->set('services.brt.verify_ssl', false);
        config()->set('services.brt.pickup_enabled', true);
        config()->set('services.brt.pickup_endpoint', 'https://brt.example.test/rest/v1/shipments/pickup');

        Http::fake([
            'https://brt.example.test/rest/v1/shipments/pickup' => Http::response([
                'executionMessage' => [
                    'code' => 0,
                    'message' => 'OK',
                ],
                'pickupConfirmationNumber' => 'PU-123',
            ], 200),
        ]);

        $order = $this->createOrderWithPackage([
            'package' => [
                'quantity' => 4,
                'weight' => '1.5',
            ],
        ]);

        $service = new PickupService(new BrtConfig(), new AddressNormalizer());
        $result = $service->requestPickup($order, [
            'enabled' => true,
            'date' => '2026-04-23',
            'time_slot' => '09:00-13:00',
        ]);

        $this->assertTrue($result['success']);

        Http::assertSent(function (Request $request): bool {
            $pickupData = $request->data()['pickupData'] ?? [];

            return str_ends_with($request->url(), '/pickup')
                && ($pickupData['numberOfParcels'] ?? null) === 4
                && ($pickupData['weightKG'] ?? null) === 6;
        });
    }

    public function test_request_pickup_fails_cleanly_when_pickup_endpoint_returns_404(): void
    {
        config()->set('services.brt.api_url', 'https://brt.example.test/rest/v1/shipments');
        config()->set('services.brt.client_id', '1020108');
        config()->set('services.brt.password', 'secret');
        config()->set('services.brt.departure_depot', 27);
        config()->set('services.brt.verify_ssl', false);
        config()->set('services.brt.pickup_enabled', true);
        config()->set('services.brt.pickup_endpoint', 'https://brt.example.test/rest/v1/shipments/pickup');

        Http::fake([
            'https://brt.example.test/rest/v1/shipments/pickup' => Http::response([
                'executionMessage' => [
                    'code' => -1,
                    'message' => 'Not found',
                ],
            ], 404),
        ]);

        $order = $this->createOrderWithPackage();

        $service = new PickupService(new BrtConfig(), new AddressNormalizer());
        $result = $service->requestPickup($order, [
            'enabled' => true,
            'date' => '2026-04-23',
            'time_slot' => '09:00-13:00',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('failed', $result['status']);
        $this->assertStringContainsString('Not found', $result['error']);

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/pickup'));
    }

    public function test_normalize_weight_value_supporta_peso_con_virgola_italiana(): void
    {
        $service = new PickupService(new BrtConfig(), new AddressNormalizer());
        $method = new \ReflectionMethod($service, 'normalizeWeightValue');
        $method->setAccessible(true);

        $this->assertSame(1.5, $method->invoke($service, '1,5'));
        $this->assertSame(1.5, $method->invoke($service, '1,5 kg'));
    }

    public function test_request_pickup_returns_manual_required_when_endpoint_is_not_configured(): void
    {
        config()->set('services.brt.api_url', 'https://brt.example.test/rest/v1/shipments');
        config()->set('services.brt.client_id', '1020108');
        config()->set('services.brt.password', 'secret');
        config()->set('services.brt.departure_depot', 27);
        config()->set('services.brt.verify_ssl', false);
        config()->set('services.brt.pickup_enabled', false);
        config()->set('services.brt.pickup_endpoint', null);

        Http::fake();

        $order = $this->createOrderWithPackage();

        $service = new PickupService(new BrtConfig(), new AddressNormalizer());
        $result = $service->requestPickup($order, [
            'enabled' => true,
            'date' => '2026-04-23',
            'time_slot' => '09:00-13:00',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('manual_required', $result['status']);
        $this->assertStringContainsString('endpoint pickup non configurato', $result['error']);

        Http::assertNothingSent();
    }

    public function test_request_pickup_usa_fascia_sicura_se_time_slot_e_malformato(): void
    {
        config()->set('services.brt.api_url', 'https://brt.example.test/rest/v1/shipments');
        config()->set('services.brt.client_id', '1020108');
        config()->set('services.brt.password', 'secret');
        config()->set('services.brt.departure_depot', 27);
        config()->set('services.brt.verify_ssl', false);
        config()->set('services.brt.pickup_enabled', true);
        config()->set('services.brt.pickup_endpoint', 'https://brt.example.test/rest/v1/shipments/pickup');

        Http::fake([
            'https://brt.example.test/rest/v1/shipments/pickup' => Http::response([
                'executionMessage' => [
                    'code' => 0,
                    'message' => 'OK',
                ],
                'pickupConfirmationNumber' => 'PU-125',
            ], 200),
        ]);

        $order = $this->createOrderWithPackage();

        $service = new PickupService(new BrtConfig(), new AddressNormalizer());
        $result = $service->requestPickup($order, [
            'enabled' => true,
            'date' => '2026-04-23',
            'time_slot' => 'mattina',
        ]);

        $this->assertTrue($result['success']);

        Http::assertSent(function (Request $request): bool {
            $pickupData = $request->data()['pickupData'] ?? [];

            return ($pickupData['pickupTimeSlotFrom'] ?? null) === '09:00'
                && ($pickupData['pickupTimeSlotTo'] ?? null) === '18:00';
        });
    }

    private function createOrderWithPackage(array $overrides = []): Order
    {
        $user = User::factory()->create();

        $originAttrs = array_merge([
            'type' => 'Partenza',
            'name' => 'Mittente Acme',
            'address' => 'Via Roma',
            'address_number' => '1',
            'postal_code' => '00100',
            'city' => 'Roma',
            'province' => 'RM',
            'country' => 'Italia',
            'telephone_number' => '3331234567',
            'email' => 'origin@example.test',
        ], $overrides['origin'] ?? []);

        $destAttrs = array_merge([
            'type' => 'Destinazione',
            'name' => 'Destinatario Beta',
            'address' => 'Via Milano',
            'address_number' => '2',
            'postal_code' => '20100',
            'city' => 'Milano',
            'province' => 'MI',
            'country' => 'Italia',
        ], $overrides['destination'] ?? []);

        $packageAttrs = array_merge([
            'user_id' => $user->id,
            'package_type' => 'Pacco',
            'quantity' => 1,
            'weight' => '5',
            'first_size' => 30,
            'second_size' => 20,
            'third_size' => 10,
            'single_price' => 1000,
        ], $overrides['package'] ?? []);

        $origin = PackageAddress::factory()->create($originAttrs);
        $destination = PackageAddress::factory()->create($destAttrs);
        $service = Service::create(['service_type' => 'Nessuno', 'date' => '', 'time' => '']);

        $package = Package::factory()->create(array_merge($packageAttrs, [
            'origin_address_id' => $origin->id,
            'destination_address_id' => $destination->id,
            'service_id' => $service->id,
        ]));

        $order = Order::factory()->create(['user_id' => $user->id]);
        Order::attachPackage($order->id, $package->id, 1);

        return $order->fresh();
    }
}
