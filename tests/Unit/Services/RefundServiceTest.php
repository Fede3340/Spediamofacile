<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WalletMovement;
use App\Services\RefundService;
use App\Services\StripeConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\StripeClient;
use Tests\TestCase;

class RefundServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_ordine_gia_cancellato_e_ineligibile(): void
    {
        $order = Order::factory()->create(['status' => 'cancelled', 'subtotal' => 1000]);

        $service = $this->makeService();
        $result = $service->calculateEligibility($order);

        $this->assertFalse($result['eligible']);
        $this->assertSame('already_cancelled', $result['type']);
    }

    public function test_ordine_in_transit_non_e_rimborsabile(): void
    {
        $order = Order::factory()->create(['status' => Order::IN_TRANSIT, 'subtotal' => 1000]);

        $service = $this->makeService();
        $result = $service->calculateEligibility($order);

        $this->assertFalse($result['eligible']);
        $this->assertSame('in_transit', $result['type']);
    }

    public function test_ordine_pending_e_annullabile_senza_rimborso(): void
    {
        $order = Order::factory()->create(['status' => Order::PENDING, 'subtotal' => 1000]);

        $service = $this->makeService();
        $result = $service->calculateEligibility($order);

        $this->assertTrue($result['eligible']);
        $this->assertSame('cancel_unpaid', $result['type']);
        $this->assertSame(0, $result['refund_amount_cents']);
        $this->assertSame(0, $result['commission_cents']);
    }

    public function test_ordine_completato_applica_commissione_cancellazione(): void
    {
        $order = Order::factory()->create(['status' => Order::COMPLETED, 'subtotal' => 1000]);

        $service = $this->makeService();
        $result = $service->calculateEligibility($order);

        $this->assertTrue($result['eligible']);
        $this->assertSame('refund_with_commission', $result['type']);
        // 1000 - 200 (CANCELLATION_FEE_CENTS) = 800 rimborsabili
        $this->assertSame(800, $result['refund_amount_cents']);
        $this->assertSame(RefundService::CANCELLATION_FEE_CENTS, $result['commission_cents']);
    }

    public function test_ordine_delivered_non_e_rimborsabile(): void
    {
        $order = Order::factory()->create(['status' => 'delivered', 'subtotal' => 1000]);

        $service = $this->makeService();
        $result = $service->calculateEligibility($order);

        $this->assertFalse($result['eligible']);
        $this->assertSame('not_refundable', $result['type']);
    }

    public function test_process_wallet_refund_crea_movimento_wallet_credito(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'subtotal' => 1000,
        ]);

        $service = $this->makeService();
        $service->processWalletRefund($order, 800);

        $movement = WalletMovement::query()
            ->where('user_id', $user->id)
            ->where('source', 'refund')
            ->first();

        $this->assertNotNull($movement, 'Deve essere creato un WalletMovement di tipo refund');
        $this->assertSame('credit', $movement->type);
        $this->assertSame(8.0, (float) $movement->amount);
        $this->assertSame((string) $order->id, $movement->reference);
    }

    private function makeService(): RefundService
    {
        // Mocka StripeClient per evitare chiamate esterne; per i test di eligibility e wallet
        // non viene invocato, quindi un mock semplice basta.
        $stripeClient = \Mockery::mock(StripeClient::class);

        return new RefundService(
            new StripeConfigService(),
            $stripeClient,
        );
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
