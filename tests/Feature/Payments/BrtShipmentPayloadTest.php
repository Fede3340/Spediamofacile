<?php

namespace Tests\Feature\Payments;

use App\Models\Order;
use App\Models\Package;
use App\Models\PackageAddress;
use App\Models\User;
use App\Services\BrtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BrtShipmentPayloadTest extends TestCase
{
    use RefreshDatabase;

    private const UNSUPPORTED_SENDER_OVERRIDE_FIELDS = [
        'senderCompanyName',
        'senderAddress',
        'senderZIPCode',
        'senderZipCode',
        'senderCity',
        'senderProvinceAbbreviation',
        'senderCountryAbbreviationISOAlpha2',
        'senderContactName',
        'senderTelephone',
        'senderEMail',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.brt.api_url', 'https://brt.example.test/rest/v1/shipments');
        config()->set('services.brt.client_id', '1020108');
        config()->set('services.brt.password', 'secret');
        config()->set('services.brt.departure_depot', 27);
        config()->set('services.brt.verify_ssl', false);
    }

    public function test_create_shipment_omits_legacy_sender_override_fields(): void
    {
        Http::fake([
            'https://brt.example.test/rest/v1/shipments/shipment' => Http::response($this->successfulShipmentResponse(), 200),
        ]);

        $order = $this->makeOrderWithAddresses();

        $result = app(BrtService::class)->createShipment($order);

        $this->assertTrue($result['success'] ?? false);

        Http::assertSent(function (Request $request): bool {
            $createData = $request->data()['createData'] ?? [];

            return str_ends_with($request->url(), '/shipment')
                && ($createData['senderCustomerCode'] ?? null) === 1020108
                && ($createData['consigneeCompanyName'] ?? null) === 'Destinatario Test'
                && $this->payloadOmitsUnsupportedSenderOverrides($createData);
        });
    }

    public function test_test_create_shipment_omits_legacy_sender_override_fields(): void
    {
        Http::fake([
            'https://brt.example.test/rest/v1/shipments/shipment' => Http::response($this->successfulShipmentResponse(), 200),
        ]);

        $result = app(BrtService::class)->testCreateShipment([
            'sender_name' => 'Mittente Test',
            'sender_address' => 'Via Roma 10',
            'sender_zip' => '00118',
            'sender_city' => 'Roma',
            'sender_province' => 'RM',
            'sender_country' => 'IT',
            'consignee_name' => 'Destinatario Test',
            'consignee_address' => 'Via Milano 22',
            'consignee_zip' => '20121',
            'consignee_city' => 'Milano',
            'consignee_province' => 'MI',
            'consignee_country' => 'IT',
            'consignee_phone' => '3337654321',
            'consignee_email' => 'customer@example.test',
            'parcels' => 1,
            'weight_kg' => 5,
        ]);

        $this->assertTrue($result['success'] ?? false);

        Http::assertSent(function (Request $request): bool {
            $createData = $request->data()['createData'] ?? [];

            return str_ends_with($request->url(), '/shipment')
                && ($createData['senderCustomerCode'] ?? null) === 1020108
                && ($createData['consigneeCompanyName'] ?? null) === 'Destinatario Test'
                && $this->payloadOmitsUnsupportedSenderOverrides($createData);
        });
    }

    private function makeOrderWithAddresses(): Order
    {
        $user = User::factory()->create([
            'email' => 'customer@example.test',
        ]);

        $origin = PackageAddress::factory()->state([
            'type' => 'Partenza',
            'name' => 'Mittente Test',
            'address' => 'Via Roma',
            'address_number' => '10',
            'postal_code' => '00118',
            'city' => 'Roma',
            'province' => 'RM',
            'country' => 'Italia',
            'telephone_number' => '3331234567',
            'email' => 'origin@example.test',
        ])->create();

        $destination = PackageAddress::factory()->state([
            'type' => 'Destinazione',
            'name' => 'Destinatario Test',
            'address' => 'Via Milano',
            'address_number' => '22',
            'postal_code' => '20121',
            'city' => 'Milano',
            'province' => 'MI',
            'country' => 'Italia',
            'telephone_number' => '3337654321',
            'email' => 'customer@example.test',
        ])->create();

        $order = Order::factory()->processing()->create([
            'user_id' => $user->id,
        ]);

        $package = Package::factory()->create([
            'user_id' => $user->id,
            'origin_address_id' => $origin->id,
            'destination_address_id' => $destination->id,
            'weight' => 5,
            'first_size' => 30,
            'second_size' => 20,
            'third_size' => 15,
        ]);

        Order::attachPackage($order->id, $package->id, 1);

        return $order->fresh();
    }

    private function payloadOmitsUnsupportedSenderOverrides(array $createData): bool
    {
        foreach (self::UNSUPPORTED_SENDER_OVERRIDE_FIELDS as $field) {
            if (array_key_exists($field, $createData)) {
                return false;
            }
        }

        return true;
    }

    private function successfulShipmentResponse(): array
    {
        return [
            'createResponse' => [
                'executionMessage' => [
                    'code' => 0,
                    'message' => 'BRT has received shipment information properly',
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
        ];
    }
}
