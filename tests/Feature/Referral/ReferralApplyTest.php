<?php

namespace Tests\Feature\Referral;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Package;
use App\Models\ReferralUsage;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\UserNotificationPreference;
use App\Models\WalletMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReferralUsedMail;
use Tests\TestCase;

class ReferralApplyTest extends TestCase
{
    use RefreshDatabase;

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                            */
    /* ------------------------------------------------------------------ */

    /**
     * Create a Partner Pro user with a known referral code.
     */
    private function createProUser(string $code = 'TESTPRO1'): User
    {
        $user = User::factory()->partnerPro()->create([
            'referral_code' => $code,
        ]);
        return $user;
    }

    /**
     * Create a pending order for a user.
     */
    private function createOrder(User $user, int $subtotalCents = 1190): Order
    {
        $order = Order::factory()->create([
            'user_id'  => $user->id,
            'status'   => 'pending',
            'subtotal' => $subtotalCents,
            'pricing_snapshot' => $this->referralPricingSnapshot($user, $subtotalCents),
        ]);

        $package = Package::factory()->create([
            'user_id'      => $user->id,
            'single_price' => $subtotalCents,
        ]);
        Order::attachPackage($order->id, $package->id, 1);

        return $order;
    }

    private function referralPricingSnapshot(User $user, int $subtotalCents): ?array
    {
        $code = strtoupper(trim((string) $user->referred_by));
        if ($code === '') {
            return null;
        }

        $subtotal = round($subtotalCents / 100, 2);
        $discount = round($subtotal * 0.05, 2);

        return [
            'total_cents' => $subtotalCents,
            'discount_context' => [
                'type' => 'referral',
                'code' => $code,
                'discount_percent' => 5,
                'discount_amount' => $discount,
                'subtotal_raw' => $subtotal,
                'final_total_raw' => max(0, round($subtotal - $discount, 2)),
            ],
        ];
    }

    private function createPaidOrder(User $user, int $subtotalCents = 1190): Order
    {
        $order = $this->createOrder($user, $subtotalCents);
        $order->forceFill([
            'status' => Order::COMPLETED,
            'payment_method' => 'stripe',
        ])->save();

        return $order->fresh();
    }

    /* ================================================================== */
    /*  T11.5.1: Validate referral code (POST /api/referral/validate)      */
    /* ================================================================== */
    public function test_validate_referral_code_success(): void
    {
        $proUser = $this->createProUser('ABCD1234');
        $buyer   = User::factory()->create();

        $response = $this->actingAs($buyer)
            ->postJson('/api/referral/validate', [
                'code' => 'ABCD1234',
            ]);

        $response->assertSuccessful();
        $response->assertJsonPath('valid', true);
        $response->assertJsonPath('discount_percent', 5);
        $response->assertJsonPath('pro_name', $proUser->name);
    }

    public function test_validate_invalid_referral_code_returns_404(): void
    {
        $buyer = User::factory()->create();

        $this->actingAs($buyer)
            ->postJson('/api/referral/validate', ['code' => 'INVALID1'])
            ->assertStatus(404)
            ->assertJsonPath('valid', false);
    }

    /* ================================================================== */
    /*  T11.5.2: Apply referral creates ReferralUsage + WalletMovement     */
    /* ================================================================== */
    public function test_apply_referral_uses_server_order_subtotal_and_ignores_client_amount(): void
    {
        $proUser = $this->createProUser('PROAPPLY');
        $buyer   = User::factory()->create(['referred_by' => 'PROAPPLY']);
        $order   = $this->createPaidOrder($buyer, 2000); // 20.00 EUR

        $response = $this->actingAs($buyer)
            ->postJson('/api/referral/apply', [
                'code'         => 'PROAPPLY',
                'order_id'     => $order->id,
                'order_amount' => 1.23,
            ]);

        $response->assertSuccessful();
        $response->assertJsonPath('success', true);

        // Discount = 5% of 20.00 = 1.00 (API returns integer when no decimal part)
        $response->assertJsonPath('discount_amount', 1);

        // ReferralUsage record created
        $this->assertDatabaseHas('referral_usages', [
            'buyer_id'       => $buyer->id,
            'pro_user_id'    => $proUser->id,
            'referral_code'  => 'PROAPPLY',
            'order_id'       => $order->id,
            'order_amount'   => '20.00',
            'discount_amount'=> '1.00',
            'commission_amount' => '1.00',
            'status'         => 'confirmed',
        ]);

        // WalletMovement (commission credit) created for the Pro user
        $this->assertDatabaseHas('wallet_movements', [
            'user_id'     => $proUser->id,
            'type'        => 'credit',
            'amount'      => '1.00',
            'status'      => 'confirmed',
            'source'      => 'commission',
        ]);
    }

    public function test_apply_referral_rejects_unpaid_orders(): void
    {
        $this->createProUser('PROUNPAI');
        $buyer = User::factory()->create(['referred_by' => 'PROUNPAI']);
        $order = $this->createOrder($buyer, 2000);

        $this->actingAs($buyer)
            ->postJson('/api/referral/apply', [
                'code'         => 'PROUNPAI',
                'order_id'     => $order->id,
                'order_amount' => 20.00,
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Ordine non ancora pagato.');

        $this->assertDatabaseCount('referral_usages', 0);
        $this->assertDatabaseCount('wallet_movements', 0);
    }

    public function test_apply_referral_rejects_orders_not_owned_by_buyer(): void
    {
        $this->createProUser('PROOWNED');
        $buyer = User::factory()->create(['referred_by' => 'PROOWNED']);
        $otherUser = User::factory()->create();
        $order = $this->createPaidOrder($otherUser, 2000);

        $this->actingAs($buyer)
            ->postJson('/api/referral/apply', [
                'code'         => 'PROOWNED',
                'order_id'     => $order->id,
                'order_amount' => 20.00,
            ])
            ->assertStatus(404)
            ->assertJsonPath('message', 'Ordine non trovato.');

        $this->assertDatabaseCount('referral_usages', 0);
        $this->assertDatabaseCount('wallet_movements', 0);
    }

    public function test_apply_referral_creates_in_app_notification_with_default_preferences(): void
    {
        Mail::fake();

        $proUser = $this->createProUser('PROMSG01');
        $buyer = User::factory()->create(['name' => 'Mario', 'referred_by' => 'PROMSG01']);
        $order = $this->createPaidOrder($buyer, 2000);

        $this->actingAs($buyer)
            ->postJson('/api/referral/apply', [
                'code' => 'PROMSG01',
                'order_id' => $order->id,
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('user_notification_preferences', [
            'user_id' => $proUser->id,
            'referral_site_enabled' => true,
            'referral_email_enabled' => false,
            'referral_sms_enabled' => false,
        ]);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $proUser->id,
            'type' => 'referral',
            'title' => 'Nuovo utilizzo del tuo referral',
        ]);

        Mail::assertNotQueued(ReferralUsedMail::class);
    }

    public function test_apply_referral_queues_email_when_preference_is_enabled(): void
    {
        Mail::fake();

        $proUser = $this->createProUser('PROEMAIL');
        UserNotificationPreference::create([
            'user_id' => $proUser->id,
            'referral_site_enabled' => true,
            'referral_email_enabled' => true,
            'referral_sms_enabled' => false,
        ]);

        $buyer = User::factory()->create(['referred_by' => 'PROEMAIL']);
        $order = $this->createPaidOrder($buyer, 2500);

        $this->actingAs($buyer)
            ->postJson('/api/referral/apply', [
                'code' => 'PROEMAIL',
                'order_id' => $order->id,
            ])
            ->assertSuccessful();

        Mail::assertQueued(ReferralUsedMail::class, function (ReferralUsedMail $mail) use ($proUser, $order) {
            return $mail->hasTo($proUser->email)
                && $mail->usage->order_id === $order->id;
        });

        $this->assertSame(
            1,
            UserNotification::query()
                ->where('user_id', $proUser->id)
                ->where('type', 'referral')
                ->count()
        );
    }

    /* ================================================================== */
    /*  T11.5.3: Self-referral blocked                                     */
    /* ================================================================== */
    public function test_self_referral_is_blocked(): void
    {
        $proUser = $this->createProUser('SELFCODE');

        // Validate endpoint
        $this->actingAs($proUser)
            ->postJson('/api/referral/validate', ['code' => 'SELFCODE'])
            ->assertStatus(422);

        // Apply endpoint
        $order = $this->createOrder($proUser);
        $this->actingAs($proUser)
            ->postJson('/api/referral/apply', [
                'code'         => 'SELFCODE',
                'order_id'     => $order->id,
                'order_amount' => 11.90,
            ])
            ->assertStatus(422);
    }

    /* ================================================================== */
    /*  T11.5.4: Atomicity - both records created or neither               */
    /* ================================================================== */
    public function test_apply_referral_atomicity(): void
    {
        $proUser = $this->createProUser('ATOMICCD');
        $buyer   = User::factory()->create(['referred_by' => 'ATOMICCD']);
        $order   = $this->createPaidOrder($buyer, 5000); // 50.00 EUR

        $this->actingAs($buyer)
            ->postJson('/api/referral/apply', [
                'code'         => 'ATOMICCD',
                'order_id'     => $order->id,
                'order_amount' => 50.00,
            ])
            ->assertSuccessful();

        // Both ReferralUsage AND WalletMovement exist
        $usageCount = ReferralUsage::where('order_id', $order->id)->count();
        $walletCount = WalletMovement::where('user_id', $proUser->id)
            ->where('source', 'commission')
            ->count();

        $this->assertEquals(1, $usageCount);
        $this->assertEquals(1, $walletCount);
    }

    public function test_apply_referral_rejects_already_referralized_orders(): void
    {
        $proUser = $this->createProUser('DUPLORD1');
        $buyer = User::factory()->create(['referred_by' => 'DUPLORD1']);
        $order = $this->createPaidOrder($buyer, 5000);

        $this->actingAs($buyer)
            ->postJson('/api/referral/apply', [
                'code'         => 'DUPLORD1',
                'order_id'     => $order->id,
                'order_amount' => 50.00,
            ])
            ->assertSuccessful();

        $this->actingAs($buyer)
            ->postJson('/api/referral/apply', [
                'code'         => 'DUPLORD1',
                'order_id'     => $order->id,
                'order_amount' => 50.00,
            ])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Questo ordine ha già un referral applicato.');

        $this->assertSame(1, ReferralUsage::where('order_id', $order->id)->count());
        $this->assertSame(1, WalletMovement::where('user_id', $proUser->id)->where('source', 'commission')->count());
    }

    /* ================================================================== */
    /*  T11.5.5: Calculate coupon (POST /api/calculate-coupon)              */
    /* ================================================================== */
    public function test_calculate_coupon_with_valid_coupon(): void
    {
        $buyer = User::factory()->create();

        Coupon::factory()->create([
            'code'       => 'SAVE10',
            'percentage' => 10,
            'active'     => true,
        ]);

        $response = $this->actingAs($buyer)
            ->postJson('/api/calculate-coupon', [
                'coupon' => 'SAVE10',
                'total'  => 100.00,
            ]);

        $response->assertSuccessful();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('type', 'coupon');
        $response->assertJsonPath('percentage', 10);
        $response->assertJsonPath('discount_amount', 10);
        $response->assertJsonPath('new_total_raw', 90);
    }

    public function test_calculate_coupon_with_referral_code(): void
    {
        $proUser = $this->createProUser('REFCOUPO');
        $buyer   = User::factory()->create();

        $response = $this->actingAs($buyer)
            ->postJson('/api/calculate-coupon', [
                'coupon' => 'REFCOUPO',
                'total'  => 100.00,
            ]);

        $response->assertSuccessful();
        $response->assertJsonPath('type', 'referral');
        $response->assertJsonPath('percentage', 5);
        $response->assertJsonPath('discount_amount', 5);
        $response->assertJsonPath('new_total_raw', 95);
    }

    public function test_calculate_coupon_invalid_code_returns_404(): void
    {
        $buyer = User::factory()->create();

        $this->actingAs($buyer)
            ->postJson('/api/calculate-coupon', [
                'coupon' => 'NOTEXIST',
                'total'  => 50.00,
            ])
            ->assertStatus(404);
    }

    /* ================================================================== */
    /*  T11.5.6: Expired coupon fails                                      */
    /* ================================================================== */
    public function test_expired_coupon_fails(): void
    {
        $buyer = User::factory()->create();

        // Create an inactive coupon (simulates expired/disabled)
        Coupon::factory()->inactive()->create([
            'code'       => 'EXPIRED1',
            'percentage' => 15,
        ]);

        // CouponController looks for active=true, so inactive coupon is not found.
        // The code will also not match a referral, so it returns 404.
        $this->actingAs($buyer)
            ->postJson('/api/calculate-coupon', [
                'coupon' => 'EXPIRED1',
                'total'  => 50.00,
            ])
            ->assertStatus(404);
    }

    public function test_self_referral_blocked_in_calculate_coupon(): void
    {
        $proUser = $this->createProUser('SELFCOUP');

        $this->actingAs($proUser)
            ->postJson('/api/calculate-coupon', [
                'coupon' => 'SELFCOUP',
                'total'  => 50.00,
            ])
            ->assertStatus(422);
    }

    public function test_apply_referral_rejects_code_not_matching_account_attribution(): void
    {
        $this->createProUser('MATCH001');
        $buyer = User::factory()->create(['referred_by' => 'OTHER001']);
        $order = $this->createPaidOrder($buyer, 2000);

        $this->actingAs($buyer)
            ->postJson('/api/referral/apply', [
                'code' => 'MATCH001',
                'order_id' => $order->id,
                'order_amount' => 20.00,
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Il referral attivo dell\'account non coincide con il codice inviato.');

        $this->assertDatabaseCount('referral_usages', 0);
        $this->assertDatabaseCount('wallet_movements', 0);
    }

    public function test_store_referral_rejects_overwrite_of_existing_code(): void
    {
        $firstPro = $this->createProUser('FIRST001');
        $this->createProUser('SECOND01');
        $buyer = User::factory()->create(['referred_by' => 'FIRST001']);

        $this->actingAs($buyer)
            ->postJson('/api/referral/store', ['code' => 'SECOND01'])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Hai gia un codice referral associato e non puo essere sostituito.');

        $this->assertDatabaseHas('users', [
            'id' => $buyer->id,
            'referred_by' => 'FIRST001',
        ]);

        $this->actingAs($buyer)
            ->postJson('/api/referral/store', ['code' => 'FIRST001'])
            ->assertSuccessful()
            ->assertJsonPath('referred_by', 'FIRST001')
            ->assertJsonPath('pro_name', $firstPro->name);
    }
}
