<?php

namespace App\Http\Controllers\Checkout;

use App\Http\Controllers\Controller;

use App\Models\Order;
use App\Services\RefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class RefundController extends Controller
{
    const CANCELLATION_FEE_CENTS = RefundService::CANCELLATION_FEE_CENTS;

    public function __construct(
        private readonly RefundService $refundService,
    ) {}

    public function checkRefundEligibility(Order $order): JsonResponse
    {
        Gate::authorize('view', $order);

        return response()->json($this->refundService->calculateEligibility($order));
    }

    /**
     * Request cancellation of an order with optional refund.
     *
     * Delegates entirely to RefundService::processCancellation() which handles
     * locking, eligibility re-check, BRT cancellation, refund routing and
     * order status update inside a single DB transaction.
     */
    public function requestCancellation(\App\Http\Requests\CancelOrderRequest $request, Order $order): JsonResponse
    {
        Gate::authorize('cancel', $order);

        // Quick pre-check outside the transaction for a fast 422 response.
        $preCheck = $this->refundService->calculateEligibility($order);
        if (! $preCheck['eligible']) {
            return response()->json(['error' => $preCheck['reason']], 422);
        }

        try {
            $result = $this->refundService->processCancellation($order, $request->reason);

            $refundEur     = number_format($result['refund_amount_cents'] / 100, 2, ',', '.');
            $commissionEur = number_format($result['commission_cents'] / 100, 2, ',', '.');

            return response()->json([
                'success'       => true,
                'message'       => $result['refund_amount_cents'] > 0
                    ? "Ordine annullato. Rimborso di {$refundEur} EUR processato (commissione: {$commissionEur} EUR)."
                    : 'Ordine annullato con successo.',
                'refund_amount' => $refundEur,
                'commission'    => $commissionEur,
                'refund_method' => $result['refund_method'],
                'brt_cancelled' => $result['brt_cancelled'],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Order cancellation failed', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Errore durante l\'annullamento dell\'ordine. Riprova o contatta l\'assistenza.',
            ], 500);
        }
    }
}
