<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ReferralUsage;
use App\Models\User;
use App\Models\WalletMovement;

/**
 * Source of truth backend per matematica referral e scritture economiche collegate.
 *
 * Possiede:
 * - percentuale referral canonica
 * - risoluzione del Partner Pro dal codice
 * - calcolo di sconto cliente, commissione Pro e totale finale
 * - creazione di ReferralUsage + WalletMovement commissione
 *
 * Non possiede:
 * - preview coupon classici
 * - validazione HTTP/auth/ownership
 * - completion dell'ordine o pagamento wallet
 *
 * Nota: le scritture economiche vanno chiamate dentro una transaction gia' aperta
 * dal caller, insieme al lock dell'ordine.
 */
class ReferralAccountingService
{
    private const DEFAULT_REFERRAL_DISCOUNT_PERCENT = 5;

    public function referralDiscountPercent(): int
    {
        return (int) config('services.referral.discount_percent', self::DEFAULT_REFERRAL_DISCOUNT_PERCENT);
    }

    public function resolveReferralPartner(string $code): ?User
    {
        return User::query()
            ->where('referral_code', $this->normalizeReferralCode($code))
            ->where('role', 'Partner Pro')
            ->first();
    }

    /**
     * @return array{
     *   percentage: float,
     *   order_amount: float,
     *   discount_amount: float,
     *   commission_amount: float,
     *   final_total: float
     * }
     */
    public function buildReferralBreakdown(float $orderAmount): array
    {
        $normalizedOrderAmount = round($orderAmount, 2);
        $percentage = (float) $this->referralDiscountPercent();
        $discountAmount = round($normalizedOrderAmount * ($percentage / 100), 2);
        $commissionAmount = round($normalizedOrderAmount * ($percentage / 100), 2);
        $finalAmount = max(0, round($normalizedOrderAmount - $discountAmount, 2));

        return [
            'percentage' => $percentage,
            'order_amount' => $normalizedOrderAmount,
            'discount_amount' => $discountAmount,
            'commission_amount' => $commissionAmount,
            'final_total' => $finalAmount,
        ];
    }

    public function orderAmountFromSubtotal(Order $order): float
    {
        return round(((int) $order->subtotal->amount()) / 100, 2);
    }

    public function createConfirmedReferralUsageAndCommissionCredit(User $buyer, User $proUser, Order $order, ?array $discountContext = null): ReferralUsage
    {
        $breakdown = $this->breakdownForPaidOrder($order, $discountContext);
        $code = $this->normalizeReferralCode((string) $proUser->referral_code);

        $usage = ReferralUsage::create([
            'buyer_id' => $buyer->id,
            'pro_user_id' => $proUser->id,
            'referral_code' => $code,
            'order_id' => $order->id,
            'order_amount' => $breakdown['order_amount'],
            'discount_amount' => $breakdown['discount_amount'],
            'commission_amount' => $breakdown['commission_amount'],
            'status' => 'confirmed',
        ]);

        WalletMovement::create([
            'user_id' => $proUser->id,
            'type' => 'credit',
            'amount' => $breakdown['commission_amount'],
            'currency' => 'EUR',
            'status' => 'confirmed',
            'idempotency_key' => $this->commissionWalletIdempotencyKey($order),
            'description' => $this->commissionWalletDescription($buyer, $order),
            'source' => 'commission',
            'reference' => $this->commissionWalletReference($order),
        ]);

        return $usage;
    }

    public function commissionWalletIdempotencyKey(Order|int $order): string
    {
        return 'commission_referral_order_'.$this->resolveOrderId($order);
    }

    public function commissionWalletReference(Order|int $order): string
    {
        return 'referral_order_'.$this->resolveOrderId($order);
    }

    private function normalizeReferralCode(string $code): string
    {
        return strtoupper(trim($code));
    }

    /**
     * Preferisce lo snapshot pagato: la commissione deve corrispondere allo
     * sconto accettato dall'utente, non alla configurazione attuale.
     */
    private function breakdownForPaidOrder(Order $order, ?array $discountContext): array
    {
        if (is_array($discountContext)
            && is_numeric($discountContext['subtotal_raw'] ?? null)
            && is_numeric($discountContext['discount_amount'] ?? null)
        ) {
            $orderAmount = round((float) $discountContext['subtotal_raw'], 2);
            $discountAmount = round((float) $discountContext['discount_amount'], 2);
            $percentage = is_numeric($discountContext['discount_percent'] ?? null)
                ? round((float) $discountContext['discount_percent'], 2)
                : $this->referralDiscountPercent();

            return [
                'percentage' => $percentage,
                'order_amount' => $orderAmount,
                'discount_amount' => $discountAmount,
                'commission_amount' => $discountAmount,
                'final_total' => is_numeric($discountContext['final_total_raw'] ?? null)
                    ? round((float) $discountContext['final_total_raw'], 2)
                    : max(0, round($orderAmount - $discountAmount, 2)),
            ];
        }

        return $this->buildReferralBreakdown($this->orderAmountFromSubtotal($order));
    }

    private function commissionWalletDescription(User $buyer, Order $order): string
    {
        return 'Commissione referral da '.$buyer->name.' (ordine #'.$order->id.')';
    }

    private function resolveOrderId(Order|int $order): int
    {
        return $order instanceof Order ? (int) $order->id : (int) $order;
    }
}
