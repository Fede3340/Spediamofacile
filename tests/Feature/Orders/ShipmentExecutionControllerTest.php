<?php

namespace Tests\Feature\Orders;

use App\Models\Order;
use App\Models\Package;
use App\Models\PackageAddress;
use App\Models\Service;
use App\Models\User;
use App\Services\ShipmentExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class ShipmentExecutionControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        return $user;
    }

    private function createOrderForUser(User $user): Order
    {
        $origin = PackageAddress::factory()->create();
        $destination = PackageAddress::factory()->create();
        $service = Service::create([
            'service_type' => 'Nessuno',
            'date' => '',
            'time' => '',
            'service_data' => [],
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

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::IN_TRANSIT,
            'subtotal' => 1190,
            'brt_parcel_id' => 'PARCEL-123',
        ]);

        Order::attachPackage($order->id, $package->id, 1);

        return $order;
    }

    public function test_show_returns_execution_payload_for_the_owner(): void
    {
        $user = $this->actingAsUser();
        $order = $this->createOrderForUser($user);

        $payload = [
            'shipment_status' => 'completed',
            'pickup_status' => 'requested',
            'pickup_requested_at' => '2026-04-03T10:15:00+02:00',
            'carrier_pickup_ref' => 'PU-123',
            'pickup_time_slot' => '09:00-12:00',
            'pickup_notes' => 'Lasciare al portone',
            'bordero_status' => 'completed',
            'carrier_bordero_ref' => 'BRD-123',
            'documents_status' => 'sent',
            'documents_sent_customer_at' => '2026-04-03T11:00:00+02:00',
            'documents_sent_admin_at' => '2026-04-03T11:05:00+02:00',
            'last_error' => null,
        ];

        $mock = Mockery::mock(ShipmentExecutionService::class);
        $mock->shouldReceive('getExecutionPayload')
            ->once()
            ->withArgs(fn (Order $candidateOrder) => $candidateOrder->is($order))
            ->andReturn($payload);
        app()->instance(ShipmentExecutionService::class, $mock);

        $this->getJson("/api/orders/{$order->id}/execution")
            ->assertOk()
            ->assertJsonPath('data.pickup_status', 'requested')
            ->assertJsonPath('data.pickup_time_slot', '09:00-12:00')
            ->assertJsonPath('data.documents_status', 'sent');
    }

    public function test_show_blocks_other_users_from_execution_payload(): void
    {
        $owner = User::factory()->create();
        $intruder = $this->actingAsUser();
        $order = $this->createOrderForUser($owner);

        $response = $this->getJson("/api/orders/{$order->id}/execution")
            ->assertStatus(403);

        $this->assertContains($response->json('message'), [
            'Non autorizzato.',
            'This action is unauthorized.',
        ]);

        $this->assertNotSame($owner->id, $intruder->id);
    }

    public function test_request_pickup_accepts_metadata_and_returns_refreshed_payload(): void
    {
        $user = $this->actingAsUser();
        $order = $this->createOrderForUser($user);

        $mock = Mockery::mock(ShipmentExecutionService::class);
        $mock->shouldReceive('requestPickup')
            ->once()
            ->withArgs(function (Order $candidateOrder, ?array $pickupRequest) use ($order) {
                return $candidateOrder->is($order)
                    && $pickupRequest !== null
                    && ($pickupRequest['enabled'] ?? null) === true
                    && ($pickupRequest['time_slot'] ?? null) === '09:00-12:00'
                    && ($pickupRequest['notes'] ?? null) === 'Lasciare al portone';
            })
            ->andReturn([
                'success' => true,
                'status' => 'requested',
                'pickup_reference' => 'PU-20260403-001',
            ]);
        $mock->shouldReceive('getExecutionPayload')
            ->once()
            ->andReturn([
                'shipment_status' => 'completed',
                'pickup_status' => 'requested',
                'pickup_requested_at' => '2026-04-03T10:15:00+02:00',
                'carrier_pickup_ref' => 'PU-20260403-001',
                'pickup_time_slot' => '09:00-12:00',
                'pickup_notes' => 'Lasciare al portone',
                'bordero_status' => 'pending',
                'carrier_bordero_ref' => null,
                'documents_status' => 'pending',
                'documents_sent_customer_at' => null,
                'documents_sent_admin_at' => null,
                'last_error' => null,
            ]);
        app()->instance(ShipmentExecutionService::class, $mock);

        $this->postJson("/api/orders/{$order->id}/pickup", [
            'pickup_request' => [
                'enabled' => true,
                'date' => now()->addDay()->format('Y-m-d'),
                'time_slot' => '09:00-12:00',
                'notes' => 'Lasciare al portone',
            ],
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.pickup_status', 'requested')
            ->assertJsonPath('data.carrier_pickup_ref', 'PU-20260403-001');
    }

    public function test_request_pickup_can_mark_order_as_not_requested_and_clear_legacy_pickup_fields(): void
    {
        $user = $this->actingAsUser();
        $order = $this->createOrderForUser($user);
        $service = $order->packages()->with('service')->firstOrFail()->service;

        $service->update([
            'date' => now()->addDay()->format('Y-m-d'),
            'time' => '09:00-12:00',
            'service_data' => [
                'pickup_request' => [
                    'enabled' => true,
                    'date' => now()->addDay()->format('Y-m-d'),
                    'time_slot' => '09:00-12:00',
                    'notes' => 'Citofono 12',
                ],
            ],
        ]);

        $order->forceFill([
            'pickup_status' => 'requested',
            'pickup_reference' => 'PU-OLD-001',
            'pickup_requested_at' => now(),
            'pickup_time_slot' => '09:00-12:00',
            'pickup_notes' => 'Citofono 12',
        ])->save();

        $this->postJson("/api/orders/{$order->id}/pickup", [
            'pickup_request' => [
                'enabled' => false,
            ],
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Ritiro segnato come non richiesto.')
            ->assertJsonPath('data.pickup_status', 'not_requested')
            ->assertJsonPath('data.pickup_enabled', false)
            ->assertJsonPath('data.pickup_date', '')
            ->assertJsonPath('data.pickup_time_slot', null)
            ->assertJsonPath('data.pickup_notes', null);

        $order->refresh();
        $service->refresh();

        $this->assertSame('not_requested', $order->pickup_status);
        $this->assertNull($order->pickup_reference);
        $this->assertNull($order->pickup_requested_at);
        $this->assertNull($order->pickup_time_slot);
        $this->assertNull($order->pickup_notes);
        $this->assertSame('', $service->date);
        $this->assertSame('', $service->time);
        $this->assertFalse((bool) data_get($service->service_data, 'pickup_request.enabled'));
        $this->assertSame('', data_get($service->service_data, 'pickup_request.date'));
        $this->assertSame('', data_get($service->service_data, 'pickup_request.time_slot'));
        $this->assertSame('', data_get($service->service_data, 'pickup_request.notes'));
    }

    public function test_request_pickup_rejects_invalid_manual_pickup_payload(): void
    {
        $user = $this->actingAsUser();
        $order = $this->createOrderForUser($user);

        $this->postJson("/api/orders/{$order->id}/pickup", [
            'pickup_request' => [
                'enabled' => true,
                'date' => 'ieri',
                'time_slot' => '08:00-09:00',
            ],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'pickup_request.date',
                'pickup_request.time_slot',
            ]);
    }

    public function test_reschedule_pickup_blocks_orders_already_in_transit(): void
    {
        $user = $this->actingAsUser();
        $order = $this->createOrderForUser($user);

        $this->patchJson("/api/orders/{$order->id}/pickup", [
            'pickup_date' => now()->addDays(2)->format('Y-m-d'),
            'pickup_time_slot' => '09:00-12:00',
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_reschedule_pickup_delegates_to_execution_service_and_returns_payload(): void
    {
        $user = $this->actingAsUser();
        $order = $this->createOrderForUser($user);
        $newPickupDate = now()->startOfDay()->addWeekdays(2)->format('Y-m-d');

        $order->forceFill([
            'status' => Order::LABEL_GENERATED,
            'pickup_status' => 'requested',
            'pickup_date' => now()->addDay(),
        ])->save();

        $mock = Mockery::mock(ShipmentExecutionService::class);
        $mock->shouldReceive('reschedulePickup')
            ->once()
            ->withArgs(function (Order $candidateOrder, array $payload) use ($order, $newPickupDate) {
                return $candidateOrder->is($order)
                    && ($payload['pickup_date'] ?? null) === $newPickupDate
                    && ($payload['pickup_time_slot'] ?? null) === '09:00-12:00'
                    && ($payload['pickup_notes'] ?? null) === 'Citofono 12';
            })
            ->andReturn([
                'success' => true,
                'old_date' => now()->addDay()->format('Y-m-d'),
                'pickup_date' => $newPickupDate,
                'pickup_time_slot' => '09:00-12:00',
                'pickup_notes' => 'Citofono 12',
                'brt_result' => [
                    'success' => true,
                    'pickup_reference' => 'PU-NEW-001',
                ],
                'brt_synced' => true,
            ]);
        $mock->shouldReceive('getExecutionPayload')
            ->once()
            ->andReturn([
                'shipment_status' => 'completed',
                'pickup_status' => 'requested',
                'pickup_requested_at' => '2026-04-23T10:15:00+02:00',
                'carrier_pickup_ref' => 'PU-NEW-001',
                'pickup_time_slot' => '09:00-12:00',
                'pickup_notes' => 'Citofono 12',
                'bordero_status' => 'pending',
                'carrier_bordero_ref' => null,
                'documents_status' => 'pending',
                'documents_sent_customer_at' => null,
                'documents_sent_admin_at' => null,
                'last_error' => null,
            ]);
        app()->instance(ShipmentExecutionService::class, $mock);

        $this->patchJson("/api/orders/{$order->id}/pickup", [
            'pickup_date' => $newPickupDate,
            'pickup_time_slot' => '09:00-12:00',
            'pickup_notes' => 'Citofono 12',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Data di ritiro aggiornata.')
            ->assertJsonPath('data.pickup_status', 'requested')
            ->assertJsonPath('data.carrier_pickup_ref', 'PU-NEW-001');
    }

    public function test_create_bordero_returns_bordero_reference_and_payload(): void
    {
        $user = $this->actingAsUser();
        $order = $this->createOrderForUser($user);

        $mock = Mockery::mock(ShipmentExecutionService::class);
        $mock->shouldReceive('createBordero')
            ->once()
            ->withArgs(fn (Order $candidateOrder) => $candidateOrder->is($order))
            ->andReturn([
                'success' => true,
                'bordero_reference' => 'BRD-20260403-001',
                'document_base64' => base64_encode('%PDF-1.4 fake bordero'),
                'document_mime' => 'application/pdf',
                'document_filename' => 'bordero.pdf',
            ]);
        $mock->shouldReceive('getExecutionPayload')
            ->once()
            ->andReturn([
                'shipment_status' => 'completed',
                'pickup_status' => 'requested',
                'pickup_requested_at' => null,
                'carrier_pickup_ref' => null,
                'pickup_time_slot' => null,
                'pickup_notes' => null,
                'bordero_status' => 'completed',
                'carrier_bordero_ref' => 'BRD-20260403-001',
                'documents_status' => 'pending',
                'documents_sent_customer_at' => null,
                'documents_sent_admin_at' => null,
                'last_error' => null,
            ]);
        app()->instance(ShipmentExecutionService::class, $mock);

        $this->postJson("/api/orders/{$order->id}/bordero")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('bordero_reference', 'BRD-20260403-001')
            ->assertJsonPath('data.bordero_status', 'completed');
    }

    public function test_download_bordero_returns_file_for_owner(): void
    {
        $user = $this->actingAsUser();
        $order = $this->createOrderForUser($user);
        $order->forceFill([
            'bordero_document_base64' => base64_encode('bordero-content'),
            'bordero_document_mime' => 'application/pdf',
            'bordero_document_filename' => 'bordero-123.pdf',
        ])->save();

        $response = $this->get("/api/orders/{$order->id}/bordero/download");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'attachment; filename="bordero-123.pdf"');
        $response->assertSee('bordero-content', false);
    }

    public function test_download_bordero_can_be_rendered_inline(): void
    {
        $user = $this->actingAsUser();
        $order = $this->createOrderForUser($user);
        $order->forceFill([
            'bordero_document_base64' => base64_encode('bordero-content'),
            'bordero_document_mime' => 'application/pdf',
            'bordero_document_filename' => 'bordero-123.pdf',
        ])->save();

        $response = $this->get("/api/orders/{$order->id}/bordero/download?inline=1");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'inline; filename="bordero-123.pdf"');
        $response->assertSee('bordero-content', false);
    }

    public function test_download_bordero_returns_404_when_missing(): void
    {
        $user = $this->actingAsUser();
        $order = $this->createOrderForUser($user);

        $this->getJson("/api/orders/{$order->id}/bordero/download")
            ->assertStatus(404)
            ->assertJsonPath('message', 'Bordero non disponibile.');
    }

    public function test_send_documents_returns_dispatch_result(): void
    {
        $user = $this->actingAsUser();
        $order = $this->createOrderForUser($user);

        $mock = Mockery::mock(ShipmentExecutionService::class);
        $mock->shouldReceive('sendDocuments')
            ->once()
            ->withArgs(fn (Order $candidateOrder) => $candidateOrder->is($order))
            ->andReturn([
                'success' => true,
                'message' => 'Documenti inviati correttamente.',
                'sent_to_customer' => true,
                'sent_to_admin' => true,
            ]);
        $mock->shouldReceive('getExecutionPayload')
            ->once()
            ->andReturn([
                'shipment_status' => 'completed',
                'pickup_status' => 'requested',
                'pickup_requested_at' => null,
                'carrier_pickup_ref' => null,
                'pickup_time_slot' => null,
                'pickup_notes' => null,
                'bordero_status' => 'completed',
                'carrier_bordero_ref' => 'BRD-20260403-001',
                'documents_status' => 'sent',
                'documents_sent_customer_at' => '2026-04-03T11:00:00+02:00',
                'documents_sent_admin_at' => '2026-04-03T11:05:00+02:00',
                'last_error' => null,
            ]);
        app()->instance(ShipmentExecutionService::class, $mock);

        $this->postJson("/api/orders/{$order->id}/send-documents")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('dispatch.message', 'Documenti inviati correttamente.')
            ->assertJsonPath('data.documents_status', 'sent');
    }
}
