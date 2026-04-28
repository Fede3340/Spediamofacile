<?php

/**
 * ReferralRewardController -- Applicazione economica del referral su ordini gia' eleggibili e guadagni Partner Pro.
 *
 * Estratto da ReferralController: gestisce apply (reward reale su ordine) e earnings (statistiche guadagni).
 * Queste funzioni riguardano la parte economica del sistema referral.
 */

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;

use App\Events\ReferralApplied;
use App\Models\Order;
use App\Models\ReferralUsage;
use App\Services\ReferralAccountingService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReferralRewardController extends Controller
{
    /*
     * Boundary note:
     * - CouponController fa la preview/validazione del codice nel checkout;
     * - questo controller registra l'utilizzo referral reale su un ordine eleggibile;
     * - qui nascono sia ReferralUsage sia il WalletMovement di commissione per il Partner Pro.
     */

    /**
     * Applica il codice referral a un ordine specifico.
     * Registra l'utilizzo del codice, calcola lo sconto e accredita la commissione al Partner Pro.
     */
    public function apply(\App\Http\Requests\ApplyReferralRewardRequest $request, ReferralAccountingService $referralAccountingService): JsonResponse
    {
        $data = $request->validated();

        $buyer = auth()->user();
        $proUser = $referralAccountingService->resolveReferralPartner($data['code']);

        if (! $proUser) {
            return response()->json(['message' => 'Codice referral non valido.'], 404);
        }

        if ($proUser->id === $buyer->id) {
            return response()->json(['message' => 'Non puoi usare il tuo stesso codice.'], 422);
        }

        if (! $buyer->referred_by || strtoupper($buyer->referred_by) !== strtoupper($data['code'])) {
            return response()->json([
                'message' => 'Il referral attivo dell\'account non coincide con il codice inviato.',
            ], 422);
        }

        $order = Order::query()
            ->whereKey((int) $data['order_id'])
            ->where('user_id', $buyer->id)
            ->first();

        if (! $order) {
            return response()->json(['message' => 'Ordine non trovato.'], 404);
        }

        if (! $this->isReferralEligibleOrder($order)) {
            return response()->json(['message' => 'Ordine non ancora pagato.'], 422);
        }

        try {
            $result = DB::transaction(function () use ($buyer, $proUser, $order, $referralAccountingService) {
                $lockedOrder = Order::query()
                    ->whereKey($order->id)
                    ->where('user_id', $buyer->id)
                    ->lockForUpdate()
                    ->first();

                if (! $lockedOrder) {
                    return response()->json(['message' => 'Ordine non trovato.'], 404);
                }

                if (! $this->isReferralEligibleOrder($lockedOrder)) {
                    return response()->json(['message' => 'Ordine non ancora pagato.'], 422);
                }

                if (! $this->matchesPaidReferralContext($lockedOrder, (string) $proUser->referral_code)) {
                    return response()->json([
                        'message' => 'Il referral non risulta applicato allo snapshot pagato dell\'ordine.',
                    ], 422);
                }

                $existingUsage = ReferralUsage::query()
                    ->where('order_id', $lockedOrder->id)
                    ->first();

                if ($existingUsage) {
                    return response()->json(['message' => 'Questo ordine ha già un referral applicato.'], 409);
                }

                $usage = $referralAccountingService->createConfirmedReferralUsageAndCommissionCredit(
                    $buyer,
                    $proUser,
                    $lockedOrder
                );

                return [
                    'success' => true,
                    'discount_amount' => round((float) $usage->discount_amount, 2),
                    'usage' => $usage,
                ];
            });

            if ($result instanceof JsonResponse) {
                return $result;
            }

            event(new ReferralApplied($result['usage']->id));

            return response()->json($result);
        } catch (QueryException $e) {
            if ($this->isReferralUniqueConstraintViolation($e)) {
                return response()->json(['message' => 'Questo ordine ha già un referral applicato.'], 409);
            }

            throw $e;
        }
    }

    /**
     * Mostra i guadagni del Partner Pro: lista di tutti gli utilizzi del suo codice referral.
     */
    public function earnings(): JsonResponse
    {
        $user = auth()->user();

        if (! $user->isPro()) {
            return response()->json(['message' => 'Solo account Pro.'], 403);
        }

        $usages = $user->referralUsagesAsPro()
            ->with('buyer:id,name,email')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $usages,
            'total_earnings' => round($usages->where('status', 'confirmed')->sum('commission_amount'), 2),
            'total_usages' => $usages->count(),
            'commission_balance' => $user->commissionBalance(),
        ]);
    }

    private function isReferralEligibleOrder(Order $order): bool
    {
        return in_array($order->rawStatus(), [
            Order::COMPLETED,
            Order::PROCESSING,
            Order::IN_TRANSIT,
            Order::DELIVERED,
            Order::IN_GIACENZA,
            'paid',
        ], true);
    }

    private function matchesPaidReferralContext(Order $order, string $code): bool
    {
        $context = $order->discountContext();

        return is_array($context)
            && ($context['type'] ?? null) === 'referral'
            && strtoupper((string) ($context['code'] ?? '')) === strtoupper($code);
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
