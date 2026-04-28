<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\Package;
use App\Models\PackageAddress;
use App\Models\Service;
use App\Models\User;
use App\Services\Brt\AddressNormalizer;
use App\Services\Brt\BrtConfig;
use App\Services\Brt\BrtPayloadBuilder;
use App\Services\Brt\ErrorTranslator;
use App\Services\Brt\ShipmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Test delle validazioni early-return di Brt\ShipmentService.
 * Non facciamo chiamate HTTP a BRT: testiamo solo i percorsi di validazione
 * che ritornano prima di invocare il client.
 */
class ShipmentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_shipment_fallisce_se_ordine_senza_pacchi(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        // Nessun Package attached

        $service = $this->makeService();
        $result = $service->createShipment($order);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Nessun collo', $result['error']);
    }

    public function test_create_shipment_fallisce_con_indirizzi_mittente_incompleti(): void
    {
        $order = $this->createOrderWithPackage([
            'origin' => ['name' => '', 'address' => '', 'postal_code' => '', 'city' => '', 'province' => ''],
        ]);

        $service = $this->makeService();
        $result = $service->createShipment($order);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Dati mittente mancanti', $result['error']);
    }

    public function test_create_shipment_fallisce_con_peso_zero(): void
    {
        $order = $this->createOrderWithPackage([
            'package' => ['weight' => '0'],
        ]);

        $service = $this->makeService();
        $result = $service->createShipment($order);

        $this->assertFalse($result['success']);
        // Il messaggio puo' essere su payload requirements o dimensioni
        $this->assertMatchesRegularExpression(
            '/(peso|Dati spedizione)/i',
            $result['error'],
        );
    }

    public function test_brt_payload_builder_sanitize_preserva_campi_permessi(): void
    {
        $builder = new BrtPayloadBuilder();

        $sanitized = $builder->sanitizeCreateData([
            'senderCustomerCode' => 1020108,
            'consigneeCompanyName' => 'Cliente Test',
            'consigneeCity' => 'Milano',
            'numberOfParcels' => 2,
            'weightKG' => 5,
        ]);

        $this->assertSame(1020108, $sanitized['senderCustomerCode']);
        $this->assertSame('Cliente Test', $sanitized['consigneeCompanyName']);
        $this->assertSame('Milano', $sanitized['consigneeCity']);
        $this->assertSame(2, $sanitized['numberOfParcels']);
        $this->assertSame(5, $sanitized['weightKG']);
    }

    public function test_create_shipment_moltiplica_il_peso_per_la_quantita_dei_colli(): void
    {
        config()->set('services.brt.api_url', 'https://brt.example.test/rest/v1/shipments');
        config()->set('services.brt.client_id', '1020108');
        config()->set('services.brt.password', 'secret');
        config()->set('services.brt.departure_depot', 27);
        config()->set('services.brt.verify_ssl', false);

        Http::fake([
            'https://brt.example.test/rest/v1/shipments/shipment' => Http::response([
                'createResponse' => [
                    'executionMessage' => [
                        'code' => 0,
                        'message' => 'OK',
                    ],
                    'parcelNumberFrom' => 'TRACK-123',
                    'labels' => [
                        'label' => [
                            [
                                'parcelID' => 'PARCEL-123',
                                'stream' => base64_encode('%PDF-1.4 fake label'),
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $order = $this->createOrderWithPackage([
            'package' => [
                'quantity' => 3,
                'weight' => '2',
            ],
        ]);

        $service = $this->makeService();
        $result = $service->createShipment($order);

        $this->assertTrue($result['success']);

        Http::assertSent(function (Request $request): bool {
            $createData = $request->data()['createData'] ?? [];

            return str_ends_with($request->url(), '/shipment')
                && ($createData['numberOfParcels'] ?? null) === 3
                && ($createData['weightKG'] ?? null) === 6;
        });
    }

    private function makeService(): ShipmentService
    {
        return new ShipmentService(
            new BrtConfig(),
            new AddressNormalizer(),
            new ErrorTranslator(),
        );
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
        $order->refresh();

        return $order;
    }
}
