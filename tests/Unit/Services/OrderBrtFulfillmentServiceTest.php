<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\Package;
use App\Models\PackageAddress;
use App\Models\Service;
use App\Models\User;
use App\Services\OrderBrtFulfillmentService;
use App\Services\ShipmentExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class OrderBrtFulfillmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private function createOrderWithPackage(array $orderOverrides = [], array $serviceOverrides = []): Order
    {
        $user = User::factory()->create();
        $origin = PackageAddress::factory()->create();
        $destination = PackageAddress::factory()->create();
        $service = Service::create(array_merge([
            'service_type' => 'Nessuno',
            'date' => '',
            'time' => '',
            'service_data' => [],
        ], $serviceOverrides));

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

        $order = Order::factory()->processing()->create(array_merge([
            'user_id' => $user->id,
            'subtotal' => 1190,
        ], $orderOverrides));

        Order::attachPackage($order->id, $package->id, 1);

        return $order->fresh();
    }

    public function test_build_automatic_shipment_options_derives_cod_pudo_insurance_and_appointment(): void
    {
        $order = $this->createOrderWithPackage(
            [
                'is_cod' => true,
                'cod_amount' => 1500,
                'cod_payment_type' => 'AS',
                'brt_pudo_id' => 'PUDO-123',
                'insurance_amount_cents' => 2990,
            ],
            [
                'service_data' => [
                    'flags' => ['consegna_appuntamento'],
                ],
            ]
        );

        $service = new OrderBrtFulfillmentService();

        $options = $service->buildAutomaticShipmentOptions($order);

        $this->assertSame([
            'is_cod' => true,
            'cod_amount' => 1500,
            'cod_payment_type' => 'AS',
            'pudo_id' => 'PUDO-123',
            'insurance_amount' => 2990,
            'delivery_appointment' => true,
        ], $options);
    }

    public function test_apply_shipment_result_persists_canonical_brt_fields_and_manual_overrides(): void
    {
        $order = $this->createOrderWithPackage([
            'brt_pudo_id' => null,
            'is_cod' => false,
            'cod_amount' => null,
        ]);

        $service = new OrderBrtFulfillmentService();

        $updated = $service->applyShipmentResult($order, [
            'parcel_id' => 'PARCEL-123',
            'numeric_sender_reference' => 'SENDER-123',
            'tracking_url' => 'https://tracking.example.test/PARCEL-123',
            'label_base64' => base64_encode('%PDF-1.4 fake label'),
            'tracking_number' => 'TRACK-123',
            'parcel_number_to' => '2',
            'departure_depot' => 'MI',
            'arrival_terminal' => 'RM',
            'arrival_depot' => 'ROMA',
            'delivery_zone' => 'Z1',
            'series_number' => 'SER-9',
            'service_type' => 'EXP',
            'all_labels' => ['L1', 'L2'],
            'raw_response' => ['ok' => true],
        ], [
            'brt_pudo_id' => 'PUDO-999',
            'is_cod' => true,
            'cod_amount' => 2200,
            'cod_payment_type' => 'BM',
        ]);

        $this->assertSame('PARCEL-123', $updated->brt_parcel_id);
        $this->assertSame('SENDER-123', $updated->brt_numeric_sender_reference);
        $this->assertSame('https://tracking.example.test/PARCEL-123', $updated->brt_tracking_url);
        $this->assertSame('TRACK-123', $updated->brt_tracking_number);
        $this->assertSame('2', $updated->brt_parcel_number_to);
        $this->assertSame('MI', $updated->brt_departure_depot);
        $this->assertSame('RM', $updated->brt_arrival_terminal);
        $this->assertSame('ROMA', $updated->brt_arrival_depot);
        $this->assertSame('Z1', $updated->brt_delivery_zone);
        $this->assertSame('SER-9', $updated->brt_series_number);
        $this->assertSame('EXP', $updated->brt_service_type);
        $this->assertSame(['L1', 'L2'], $updated->brt_all_labels);
        $this->assertSame(['ok' => true], $updated->brt_raw_response);
        $this->assertSame('PUDO-999', $updated->brt_pudo_id);
        $this->assertTrue($updated->is_cod);
        $this->assertSame(2200, $updated->cod_amount);
        $this->assertSame('BM', $updated->cod_payment_type);
        $this->assertSame(Order::LABEL_GENERATED, $updated->rawStatus());
        $this->assertNull($updated->brt_error);
        $this->assertNotEmpty($updated->brt_label_base64);
    }

    public function test_run_post_label_flow_marks_documents_failed_when_execution_throws(): void
    {
        $order = $this->createOrderWithPackage([
            'documents_status' => 'pending',
            'execution_error' => 'Errore precedente',
        ]);

        $execution = Mockery::mock(ShipmentExecutionService::class);
        $execution->shouldReceive('runAutomaticPostLabelFlow')
            ->once()
            ->andThrow(new \RuntimeException('BRT execution exploded'));

        app()->instance(ShipmentExecutionService::class, $execution);

        $service = new OrderBrtFulfillmentService();

        $updated = $service->runPostLabelFlow(
            $order,
            'Post-elaborazione documenti fallita dopo test',
            'Test log message'
        );

        $this->assertSame('failed', $updated->documents_status);
        $this->assertStringContainsString('Errore precedente', (string) $updated->execution_error);
        $this->assertStringContainsString('Post-elaborazione documenti fallita dopo test: BRT execution exploded', (string) $updated->execution_error);
    }
}
