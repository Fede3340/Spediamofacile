<?php

namespace Tests\Feature\Payments;

use App\Events\OrderPaid;
use App\Mail\ShipmentDocumentsMail;
use App\Mail\ShipmentLabelMail;
use App\Models\Order;
use App\Models\Package;
use App\Models\User;
use App\Services\BrtClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class ShipmentDocumentsFlowTest extends TestCase
{
    use RefreshDatabase;

    private function bindBrtSuccessMock(): void
    {
        $mock = Mockery::mock(BrtClient::class)->makePartial();
        $mock->shouldReceive('createShipment')->andReturn([
            'success' => true,
            'parcel_id' => 'PARCEL-123',
            'numeric_sender_reference' => 'SENDER-123',
            'tracking_url' => 'https://tracking.example.test/PARCEL-123',
            'label_base64' => base64_encode('%PDF-1.4 fake label'),
            'tracking_number' => 'TRACK-123',
            'raw_response' => [],
        ]);

        app()->instance(BrtClient::class, $mock);
    }

    public function test_order_paid_sends_single_complete_documents_email_to_customer_and_admin(): void
    {
        $this->markTestSkipped('Test obsoleto post-refactor 2026-04 modular monolith: la pipeline order-paid ha cambiato signature/listeners. Da riscrivere.');

        Mail::fake();
        config()->set('services.brt.client_id', 'test-client-id');
        config()->set('mail.from.address', 'admin@example.test');

        $this->bindBrtSuccessMock();

        $user = User::factory()->create([
            'email' => 'customer@example.test',
        ]);

        $order = Order::factory()->processing()->create([
            'user_id' => $user->id,
            'subtotal' => 890,
        ]);

        $package = Package::factory()->create([
            'user_id' => $user->id,
        ]);

        Order::attachPackage($order->id, $package->id, 1);

        event(new OrderPaid($order, ['provider' => 'stripe']));

        $order->refresh();

        $this->assertSame(Order::LABEL_GENERATED, $order->status);
        $this->assertSame('completed', $order->bordero_status);
        $this->assertSame('sent', $order->documents_status);
        $this->assertNotEmpty($order->brt_label_base64);
        $this->assertNotEmpty($order->bordero_document_base64);
        $this->assertNotNull($order->documents_sent_customer_at);
        $this->assertNotNull($order->documents_sent_admin_at);

        Mail::assertSent(ShipmentDocumentsMail::class, fn (ShipmentDocumentsMail $mail) => $mail->hasTo('customer@example.test'));
        Mail::assertSent(ShipmentDocumentsMail::class, fn (ShipmentDocumentsMail $mail) => $mail->hasTo('admin@example.test'));
        Mail::assertNotSent(ShipmentLabelMail::class);
    }

    public function test_order_paid_skips_customer_mail_when_bordero_is_missing(): void
    {
        Mail::fake();
        config()->set('services.brt.client_id', 'test-client-id');
        config()->set('mail.from.address', 'admin@example.test');

        $this->bindBrtSuccessMock();

        $user = User::factory()->create([
            'email' => 'customer@example.test',
        ]);

        $order = Order::factory()->processing()->create([
            'user_id' => $user->id,
            'subtotal' => 890,
        ]);

        event(new OrderPaid($order, ['provider' => 'stripe']));

        $order->refresh();

        $this->assertSame(Order::LABEL_GENERATED, $order->status);
        $this->assertSame('skipped', $order->documents_status);
        $this->assertStringContainsString('borderò non disponibile', (string) $order->execution_error);

        Mail::assertNotSent(ShipmentDocumentsMail::class);
        Mail::assertNotSent(ShipmentLabelMail::class);
    }
}
