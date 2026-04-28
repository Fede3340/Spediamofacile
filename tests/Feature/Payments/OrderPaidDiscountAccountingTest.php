<?php

namespace Tests\Feature\Payments;

use App\Events\OrderPaid;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\ReferralUsage;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WalletMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderPaidDiscountAccountingTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_paid_records_coupon_usage_once(): void
    {
        Mail::fake();
        Queue::fake();

        $user = User::factory()->create();
        $coupon = Coupon::factory()->create([
            'code' => 'SAVE10',
            'percentage' => 10,
            'active' => true,
            'uses_count' => 0,
        ]);
        $order = $this->createPaidOrderWithDiscount($user, [
            'type' => 'coupon',
            'code' => 'SAVE10',
            'discount_percent' => 10,
            'discount_amount' => 10.00,
            'subtotal_raw' => 100.00,
            'final_total_raw' => 90.00,
        ]);
        $transaction = Transaction::factory()->create(['order_id' => $order->id, 'total' => 9000]);

        event(new OrderPaid($order, $transaction));
        event(new OrderPaid($order->fresh(), $transaction));

        $this->assertSame(1, $coupon->fresh()->uses_count);
        $this->assertDatabaseCount('coupon_user', 1);
        $this->assertDatabaseHas('coupon_user', [
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'order_id' => $order->id,
        ]);
    }

    public function test_order_paid_records_referral_commission_once_without_making_it_spendable_wallet_credit(): void
    {
        Mail::fake();
        Queue::fake();

        $pro = User::factory()->partnerPro()->create([
            'referral_code' => 'PRO12345',
        ]);
        $buyer = User::factory()->create();
        $order = $this->createPaidOrderWithDiscount($buyer, [
            'type' => 'referral',
            'code' => 'PRO12345',
            'discount_percent' => 5,
            'discount_amount' => 5.00,
            'subtotal_raw' => 100.00,
            'final_total_raw' => 95.00,
            'pro_name' => $pro->name,
        ]);
        $transaction = Transaction::factory()->create(['order_id' => $order->id, 'total' => 9500]);

        event(new OrderPaid($order, $transaction));
        event(new OrderPaid($order->fresh(), $transaction));

        $this->assertSame(1, ReferralUsage::query()->where('order_id', $order->id)->count());
        $this->assertDatabaseHas('referral_usages', [
            'buyer_id' => $buyer->id,
            'pro_user_id' => $pro->id,
            'referral_code' => 'PRO12345',
            'order_id' => $order->id,
            'order_amount' => '100.00',
            'discount_amount' => '5.00',
            'commission_amount' => '5.00',
            'status' => 'confirmed',
        ]);
        $this->assertSame(1, WalletMovement::query()
            ->where('user_id', $pro->id)
            ->where('source', 'commission')
            ->where('reference', 'referral_order_'.$order->id)
            ->count());
        $this->assertSame(0.0, $pro->fresh()->walletBalance());
        $this->assertSame(5.0, $pro->fresh()->commissionBalance());
    }

    public function test_order_paid_without_discount_context_does_not_create_discount_accounting(): void
    {
        Mail::fake();
        Queue::fake();

        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::COMPLETED,
            'subtotal' => 10000,
            'pricing_snapshot' => ['total_cents' => 10000],
        ]);
        $transaction = Transaction::factory()->create(['order_id' => $order->id, 'total' => 10000]);

        event(new OrderPaid($order, $transaction));

        $this->assertDatabaseCount('coupon_user', 0);
        $this->assertDatabaseCount('referral_usages', 0);
        $this->assertDatabaseMissing('wallet_movements', [
            'source' => 'commission',
        ]);
    }

    private function createPaidOrderWithDiscount(User $user, array $discountContext): Order
    {
        return Order::factory()->create([
            'user_id' => $user->id,
            'status' => Order::COMPLETED,
            'subtotal' => 10000,
            'pricing_snapshot' => [
                'total_cents' => 10000,
                'discount_context' => $discountContext,
            ],
        ]);
    }
}
