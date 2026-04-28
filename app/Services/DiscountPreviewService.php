<?php

namespace App\Services;

use App\Cart\MyMoney;
use App\Models\Coupon;
use App\Models\User;

class DiscountPreviewService
{
    // Preview-only coupon/referral boundary:
    // costruisce la shape UI coerente, senza creare ReferralUsage,
    // WalletMovement o snapshot ordine. La persistenza reale avviene post-pagamento.
    public function __construct(
        private readonly ReferralAccountingService $referralAccounting,
    ) {}

    public function referralDiscountPercent(): int
    {
        return $this->referralAccounting->referralDiscountPercent();
    }

    public function resolveReferralPartner(string $code): ?User
    {
        return $this->referralAccounting->resolveReferralPartner($code);
    }

    public function buildCouponPreview(Coupon $coupon, float $total): array
    {
        return $this->buildPreviewPayload(
            type: 'coupon',
            code: $coupon->code,
            percentageValue: (float) $coupon->percentage,
            total: $total,
        );
    }

    public function buildReferralPreview(User $proUser, float $total, string $code): array
    {
        $breakdown = $this->referralAccounting->buildReferralBreakdown($total);

        return array_merge(
            $this->buildPreviewPayloadFromBreakdown(
                type: 'referral',
                code: $code,
                breakdown: $breakdown,
            ),
            [
                'referral_code' => strtoupper(trim($code)),
                'pro_user_name' => $proUser->name,
            ],
        );
    }

    public function buildReferralDiscountInfo(User $proUser, string $code): array
    {
        return [
            'has_discount' => true,
            'type' => 'referral',
            'referral_code' => strtoupper(trim($code)),
            'discount_percent' => $this->referralDiscountPercent(),
            'pro_name' => $proUser->name,
        ];
    }

    /**
     * @param array{percentage: float, discount_amount: float, final_total: float} $breakdown
     */
    private function buildPreviewPayloadFromBreakdown(string $type, string $code, array $breakdown): array
    {
        $newAmount = new MyMoney((int) round($breakdown['final_total'] * 100));

        return [
            'success' => true,
            'type' => $type,
            'code' => strtoupper(trim($code)),
            'percentage' => $breakdown['percentage'],
            'discount_amount' => $breakdown['discount_amount'],
            'new_total' => $newAmount->formatted(),
            'new_total_raw' => $breakdown['final_total'],
        ];
    }

    private function buildPreviewPayload(string $type, string $code, float $percentageValue, float $total): array
    {
        $discountAmount = round($total * ($percentageValue / 100), 2);
        $finalAmount = max(0, round($total - $discountAmount, 2));
        $newAmount = new MyMoney((int) round($finalAmount * 100));

        return [
            'success' => true,
            'type' => $type,
            'code' => strtoupper(trim($code)),
            'percentage' => $percentageValue,
            'discount_amount' => $discountAmount,
            'new_total' => $newAmount->formatted(),
            'new_total_raw' => $finalAmount,
        ];
    }
}
