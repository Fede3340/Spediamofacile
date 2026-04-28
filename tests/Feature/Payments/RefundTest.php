<?php

namespace Tests\Feature\Payments;

use App\Models\Order;
use App\Models\Package;
use App\Models\User;
use App\Models\WalletMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefundTest extends TestCase
{
    use RefreshDatabase;

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                            */
    /* ------------------------------------------------------------------ */

    /**
     * Create an order with a package for the given user.
     */
    private function createOrderForUser(
        User $user,
        string $status = 'completed',
        int $subtotalCents = 890,
        string $paymentMethod = 'wallet',
    ): Order {
        $order = Order::factory()->create([
            'user_id'        => $user->id,
            'status'         => $status,
            'subtotal'       => $subtotalCents,
            'payment_method' => $paymentMethod,
        ]);

        $package = Package::factory()->create([
            'user_id'      => $user->id,
            'single_price' => $subtotalCents,
        ]);
        Order::attachPackage($order->id, $package->id, 1);

        return $order;
    }

    /* ================================================================== */
    /*  T11.7.1: Check refund eligibility                                  */
    /* ================================================================== */
    public function test_refund_eligibility_completed_order(): void
    {
        $user  = User::factory()->create();
        $order = $this->createOrderForUser($user, 'completed', 890);

        $response = $this->actingAs($user)
            ->getJson("/api/orders/{$order->id}/refund-eligibility");

        $response->assertSuccessful();
        $response->assertJsonPath('eligible', true);
        $response->assertJsonPath('type', 'refund_with_commission');

        // Commission should be 200 cents (2 EUR)
        $response->assertJsonPath('commission_cents', 200);

        // Refund = subtotal - commission = 890 - 200 = 690
        $response->assertJsonPath('refund_amount_cents', 690);
    }

    public function test_refund_eligibility_pending_order(): void
    {
        $user  = User::factory()->create();
        $order = $this->createOrderForUser($user, 'pending', 890);

        $response = $this->actingAs($user)
            ->getJson("/api/orders/{$order->id}/refund-eligibility");

        $response->assertSuccessful();
        $response->assertJsonPath('eligible', true);
        $response->assertJsonPath('type', 'cancel_unpaid');
        $response->assertJsonPath('refund_amount_cents', 0);
        $response->assertJsonPath('commission_cents', 0);
    }

    public function test_refund_eligibility_in_transit_not_eligible(): void
    {
        $user  = User::factory()->create();
        $order = $this->createOrderForUser($user, 'in_transit', 890);

        $response = $this->actingAs($user)
            ->getJson("/api/orders/{$order->id}/refund-eligibility");

        $response->assertSuccessful();
        $response->assertJsonPath('eligible', false);
        $response->assertJsonPath('type', 'in_transit');
    }

    public function test_refund_eligibility_already_cancelled(): void
    {
        $user  = User::factory()->create();
        $order = $this->createOrderForUser($user, 'cancelled', 890);

        $response = $this->actingAs($user)
            ->getJson("/api/orders/{$order->id}/refund-eligibility");

        $response->assertSuccessful();
        $response->assertJsonPath('eligible', false);
        $response->assertJsonPath('type', 'already_cancelled');
    }

    public function test_refund_eligibility_other_user_returns_403(): void
    {
        $owner    = User::factory()->create();
        $intruder = User::factory()->create();
        $order    = $this->createOrderForUser($owner, 'completed', 890);

        $this->actingAs($intruder)
            ->getJson("/api/orders/{$order->id}/refund-eligibility")
            ->assertStatus(403);
    }

    /* ================================================================== */
    /*  T11.7.4: Commission of 2 EUR deducted from refund                  */
    /* ================================================================== */
    public function test_cancellation_with_2eur_commission_deducted(): void
    {
        $user  = User::factory()->create();
        // Order of 890 cents (8.90 EUR), paid with wallet
        $order = $this->createOrderForUser($user, 'completed', 890, 'wallet');

        $response = $this->actingAs($user)
            ->postJson("/api/orders/{$order->id}/cancel", [
                'reason' => 'Test cancellation',
            ]);

        $response->assertSuccessful();
        $response->assertJsonPath('success', true);

        // Commission is always 2.00 EUR
        $response->assertJsonPath('commission', '2,00');

        // Refund amount = 8.90 - 2.00 = 6.90 EUR
        $response->assertJsonPath('refund_amount', '6,90');

        // Refund method should be wallet (since payment was wallet)
        $response->assertJsonPath('refund_method', 'wallet');

        // Order status updated to 'refunded'
        $order->refresh();
        $this->assertEquals('refunded', $order->getAttributes()['status']);

        // Cancellation fee recorded
        $this->assertEquals(200, $order->cancellation_fee);

        // WalletMovement credit created for refund
        $this->assertDatabaseHas('wallet_movements', [
            'user_id' => $user->id,
            'type'    => 'credit',
            'amount'  => '6.90',
            'source'  => 'refund',
        ]);
    }

    public function test_cancellation_pending_order_no_refund(): void
    {
        $user  = User::factory()->create();
        $order = $this->createOrderForUser($user, 'pending', 890, 'wallet');

        $response = $this->actingAs($user)
            ->postJson("/api/orders/{$order->id}/cancel");

        $response->assertSuccessful();
        $response->assertJsonPath('success', true);

        // No refund for unpaid order
        $response->assertJsonPath('refund_amount', '0,00');
        $response->assertJsonPath('commission', '0,00');

        // Order status should be 'cancelled' (not 'refunded')
        $order->refresh();
        $this->assertEquals('cancelled', $order->getAttributes()['status']);

        // No wallet movement created
        $this->assertDatabaseMissing('wallet_movements', [
            'user_id' => $user->id,
            'source'  => 'refund',
        ]);
    }

    public function test_cancellation_subtotal_less_than_commission(): void
    {
        $user  = User::factory()->create();
        // Order of 150 cents (1.50 EUR) < commission of 200 cents (2.00 EUR)
        $order = $this->createOrderForUser($user, 'completed', 150, 'wallet');

        $response = $this->actingAs($user)
            ->postJson("/api/orders/{$order->id}/cancel");

        $response->assertSuccessful();

        // Refund should be max(0, 150-200) = 0
        $response->assertJsonPath('refund_amount', '0,00');
    }

    public function test_cancellation_in_transit_fails(): void
    {
        $user  = User::factory()->create();
        $order = $this->createOrderForUser($user, 'in_transit', 890);

        $this->actingAs($user)
            ->postJson("/api/orders/{$order->id}/cancel")
            ->assertStatus(422);
    }

    public function test_cancellation_other_user_returns_403(): void
    {
        $owner    = User::factory()->create();
        $intruder = User::factory()->create();
        $order    = $this->createOrderForUser($owner, 'completed', 890);

        $this->actingAs($intruder)
            ->postJson("/api/orders/{$order->id}/cancel")
            ->assertStatus(403);
    }
}
