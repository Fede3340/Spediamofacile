<?php

namespace Tests\Unit\Services;

use App\Models\Coupon;
use App\Models\User;
use App\Services\DiscountPreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscountPreviewServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_coupon_preview_uses_coupon_percentage_and_formats_total(): void
    {
        $service = app(DiscountPreviewService::class);
        $coupon = Coupon::factory()->create([
            'code' => 'SAVE10',
            'percentage' => 10,
            'active' => true,
        ]);

        $preview = $service->buildCouponPreview($coupon, 100.00);

        $this->assertSame('coupon', $preview['type']);
        $this->assertSame('SAVE10', $preview['code']);
        $this->assertSame(10.0, $preview['percentage']);
        $this->assertSame(10.0, $preview['discount_amount']);
        $this->assertSame(90.0, $preview['new_total_raw']);
    }

    public function test_build_referral_preview_uses_single_referral_percentage_source(): void
    {
        $service = app(DiscountPreviewService::class);
        $proUser = User::factory()->partnerPro()->create([
            'referral_code' => 'PROTEST1',
        ]);

        $preview = $service->buildReferralPreview($proUser, 80.00, 'PROTEST1');

        $this->assertSame('referral', $preview['type']);
        $this->assertSame('PROTEST1', $preview['code']);
        $this->assertSame('PROTEST1', $preview['referral_code']);
        $this->assertSame($service->referralDiscountPercent(), (int) $preview['percentage']);
        $this->assertSame(4.0, $preview['discount_amount']);
        $this->assertSame(76.0, $preview['new_total_raw']);
        $this->assertSame($proUser->name, $preview['pro_user_name']);
    }

    public function test_build_referral_discount_info_matches_preview_percentage_source(): void
    {
        $service = app(DiscountPreviewService::class);
        $proUser = User::factory()->partnerPro()->create([
            'referral_code' => 'PROTEST2',
        ]);

        $payload = $service->buildReferralDiscountInfo($proUser, 'PROTEST2');

        $this->assertTrue($payload['has_discount']);
        $this->assertSame('referral', $payload['type']);
        $this->assertSame('PROTEST2', $payload['referral_code']);
        $this->assertSame($service->referralDiscountPercent(), $payload['discount_percent']);
        $this->assertSame($proUser->name, $payload['pro_name']);
    }
}
