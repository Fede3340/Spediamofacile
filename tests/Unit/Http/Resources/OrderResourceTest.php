<?php

namespace Tests\Unit\Http\Resources;

use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OrderResourceTest extends TestCase
{
    public function test_order_resource_exposes_discounted_payable_total_without_changing_gross_subtotal(): void
    {
        $order = new Order([
            'id' => 123,
            'status' => Order::PENDING,
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
        $order->exists = true;
        $order->created_at = Carbon::parse('2026-04-24 10:00:00', 'UTC');
        $order->setRelation('packages', new Collection);
        $order->setRelation('transactions', new Collection);

        $resource = (new OrderResource($order))->toArray(new Request);

        $this->assertSame(1190, $resource['subtotal_cents']);
        $this->assertSame(1190, $resource['gross_subtotal_cents']);
        $this->assertSame(60, $resource['discount_amount_cents']);
        $this->assertSame(1130, $resource['payable_total_cents']);
        $this->assertSame('SAVE5', $resource['discount_context']['code']);
    }
}
