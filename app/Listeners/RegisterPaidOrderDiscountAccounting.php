<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Events\ReferralApplied;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\ReferralUsage;
use App\Services\ReferralAccountingService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegisterPaidOrderDiscountAccounting
{
    public function handle(OrderPaid $event): void
    {
        $order = $event->order->fresh(['user']);
        if (! $order) {
            return;
        }

        $context = $order->discountContext();
        if (! is_array($context)) {
            return;
        }

        $type = (string) ($context['type'] ?? '');
        $code = mb_strtoupper(trim((string) ($context['code'] ?? '')), 'UTF-8');

        if ($type === 'coupon') {
            $this->recordCouponUsage($order, $code);

            return;
        }

        if ($type === 'referral') {
            $this->recordReferralUsage($order, $context, $code);
        }
    }

    private function recordCouponUsage(Order $order, string $code): void
    {
        if (! $order->user || $code === '') {
            return;
        }

        $coupon = Coupon::query()->where('code', $code)->first();
        if (! $coupon) {
            Log::warning('Paid order references an unknown coupon code', [
                'order_id' => $order->id,
                'coupon_code' => $code,
            ]);

            return;
        }

        $coupon->recordUsage((int) $order->user_id, (int) $order->id);
    }

    private function recordReferralUsage(Order $order, array $context, string $code): void
    {
        if (! $order->user || $code === '') {
            return;
        }

        /** @var ReferralAccountingService $referralAccounting */
        $referralAccounting = app(ReferralAccountingService::class);
        $proUser = $referralAccounting->resolveReferralPartner($code);

        if (! $proUser || (int) $proUser->id === (int) $order->user_id) {
            Log::warning('Paid order references an invalid referral code', [
                'order_id' => $order->id,
                'referral_code' => $code,
            ]);

            return;
        }

        try {
            $usage = DB::transaction(function () use ($order, $context, $proUser, $referralAccounting) {
                $lockedOrder = Order::query()
                    ->with('user')
                    ->whereKey($order->id)
                    ->lockForUpdate()
                    ->first();

                if (! $lockedOrder || ! $lockedOrder->user) {
                    return null;
                }

                $existingUsage = ReferralUsage::query()
                    ->where('order_id', $lockedOrder->id)
                    ->lockForUpdate()
                    ->first();

                if ($existingUsage) {
                    return $existingUsage;
                }

                return $referralAccounting->createConfirmedReferralUsageAndCommissionCredit(
                    $lockedOrder->user,
                    $proUser,
                    $lockedOrder,
                    $context,
                );
            });

            if ($usage instanceof ReferralUsage && $usage->wasRecentlyCreated) {
                event(new ReferralApplied($usage->id));
            }
        } catch (QueryException $e) {
            if ($this->isReferralUniqueConstraintViolation($e)) {
                return;
            }

            throw $e;
        }
    }

    private function isReferralUniqueConstraintViolation(QueryException $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, 'referral_usages_order_id_unique')
            || str_contains($message, 'wallet_movements_idempotency_key_unique')
            || str_contains($message, 'Duplicate entry')
            || str_contains($message, 'UNIQUE constraint failed');
    }
}
