<?php

namespace Tests\Unit\Services;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test del dominio coupon (model + scope + metodi business).
 * Non esiste un CouponService separato: la logica vive nel modello Coupon.
 */
class CouponUsageTest extends TestCase
{
    use RefreshDatabase;

    public function test_coupon_attivo_non_scaduto_passa_validazione(): void
    {
        $user = User::factory()->create();
        $coupon = Coupon::factory()->create(['percentage' => 10]);

        [$valid, $reason] = $coupon->validateForUser($user->id);

        $this->assertTrue($valid);
        $this->assertNull($reason);
    }

    public function test_coupon_inattivo_fallisce_validazione(): void
    {
        $user = User::factory()->create();
        $coupon = Coupon::factory()->inactive()->create();

        [$valid, $reason] = $coupon->validateForUser($user->id);

        $this->assertFalse($valid);
        $this->assertSame('Coupon non attivo.', $reason);
    }

    public function test_coupon_scaduto_fallisce_validazione(): void
    {
        $user = User::factory()->create();
        $coupon = Coupon::factory()->expired()->create();

        [$valid, $reason] = $coupon->validateForUser($user->id);

        $this->assertFalse($valid);
        $this->assertSame('Coupon scaduto.', $reason);
    }

    public function test_coupon_esaurito_fallisce_validazione(): void
    {
        $user = User::factory()->create();
        $coupon = Coupon::factory()->create([
            'max_uses' => 1,
            'uses_count' => 1,
        ]);

        [$valid, $reason] = $coupon->validateForUser($user->id);

        $this->assertFalse($valid);
        $this->assertSame('Coupon esaurito.', $reason);
    }

    public function test_record_usage_incrementa_contatore_globale(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $coupon = Coupon::factory()->create(['uses_count' => 0]);

        $result = $coupon->recordUsage($user->id, $order->id);

        $this->assertTrue($result);
        $this->assertSame(1, $coupon->fresh()->uses_count);
    }

    public function test_record_usage_rispetta_max_uses_per_user(): void
    {
        $user = User::factory()->create();
        $order1 = Order::factory()->create(['user_id' => $user->id]);
        $order2 = Order::factory()->create(['user_id' => $user->id]);

        $coupon = Coupon::factory()->create([
            'max_uses_per_user' => 1,
            'uses_count' => 0,
        ]);

        $first = $coupon->recordUsage($user->id, $order1->id);
        $second = $coupon->recordUsage($user->id, $order2->id);

        $this->assertTrue($first);
        $this->assertFalse($second, 'Il secondo utilizzo dallo stesso utente deve essere rifiutato');
        $this->assertSame(1, $coupon->fresh()->uses_count);
    }

    public function test_scope_usable_filtra_coupon_non_attivi_scaduti_o_esauriti(): void
    {
        Coupon::factory()->create(['code' => 'VALID1']);
        Coupon::factory()->inactive()->create(['code' => 'INACTIVE1']);
        Coupon::factory()->expired()->create(['code' => 'EXPIRED1']);
        Coupon::factory()->create(['code' => 'EXHAUSTED1', 'max_uses' => 1, 'uses_count' => 1]);

        $usable = Coupon::usable()->pluck('code')->all();

        $this->assertContains('VALID1', $usable);
        $this->assertNotContains('INACTIVE1', $usable);
        $this->assertNotContains('EXPIRED1', $usable);
        $this->assertNotContains('EXHAUSTED1', $usable);
    }
}
