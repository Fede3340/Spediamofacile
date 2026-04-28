<?php

namespace Tests\Unit\Models;

use App\Models\Order;
use PHPUnit\Framework\TestCase;

class OrderPayableTotalTest extends TestCase
{
    public function test_payable_total_defaults_to_gross_subtotal_without_discount_context(): void
    {
        $order = new Order([
            'subtotal' => 1190,
            'pricing_snapshot' => ['total_cents' => 1190],
        ]);

        $this->assertNull($order->discountContext());
        $this->assertSame(1190, $order->grossSubtotalCents());
        $this->assertSame(0, $order->discountAmountCents());
        $this->assertSame(1190, $order->payableTotalCents());
    }

    public function test_payable_total_uses_discounted_final_total_from_snapshot(): void
    {
        $order = new Order([
            'subtotal' => 1190,
            'pricing_snapshot' => [
                'discount_context' => [
                    'type' => 'coupon',
                    'code' => 'SAVE5',
                    'discount_amount' => 0.6,
                    'subtotal_raw' => 11.9,
                    'final_total_raw' => 11.3,
                ],
            ],
        ]);

        $this->assertSame(60, $order->discountAmountCents());
        $this->assertSame(1130, $order->payableTotalCents());
        $this->assertSame('1130', $order->payableTotal()->amount());
    }

    public function test_payable_total_falls_back_to_gross_minus_discount_when_final_total_is_missing(): void
    {
        $order = new Order([
            'subtotal' => 1190,
            'pricing_snapshot' => [
                'discount_context' => [
                    'type' => 'referral',
                    'code' => 'FRIEND',
                    'discount_amount' => 2.5,
                ],
            ],
        ]);

        $this->assertSame(250, $order->discountAmountCents());
        $this->assertSame(940, $order->payableTotalCents());
    }

    public function test_payable_total_never_exceeds_gross_subtotal_or_goes_below_zero(): void
    {
        $aboveGross = new Order([
            'subtotal' => 1190,
            'pricing_snapshot' => [
                'discount_context' => [
                    'type' => 'coupon',
                    'code' => 'BAD',
                    'discount_amount' => 0,
                    'final_total_raw' => 15,
                ],
            ],
        ]);

        $fullDiscount = new Order([
            'subtotal' => 1190,
            'pricing_snapshot' => [
                'discount_context' => [
                    'type' => 'coupon',
                    'code' => 'FREE',
                    'discount_amount' => 20,
                    'final_total_raw' => 0,
                ],
            ],
        ]);

        $this->assertSame(1190, $aboveGross->payableTotalCents());
        $this->assertSame(0, $fullDiscount->payableTotalCents());
    }
}
