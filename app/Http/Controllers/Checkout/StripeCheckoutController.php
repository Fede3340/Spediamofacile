<?php

// CRITICAL: vedi CLAUDE.md "Eccezioni documentate" — non splittare senza E2E gating Stripe.
// Toccare solo con DB snapshot pre/post + carta test 4242 4242 4242 4242 09/30 123 + rollback se diff.
// PaymentIntent + 3DS confirm vivono in StripeIntentController + StripeConfirmController.

namespace App\Http\Controllers\Checkout;

use App\Events\OrderPaid;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\PayWithExternalProviderRequest;
use App\Mail\OrderAwaitingBankTransferMail;
use App\Models\Order;
use App\Models\Package;
use App\Models\Setting;
use App\Models\Transaction;
use App\Services\CartService;
use App\Services\CheckoutSubmissionContextService;
use App\Services\OrderCreationService;
use App\Services\WalletOrderLinkService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Boundary del checkout per:
 * - createOrder         (cart  → ordini pagabili)
 * - markOrderCompleted  (bonifico/wallet, secondo step canonico)
 *
 * Note flusso wallet: il pagamento NON si chiude qui da solo. Sequence:
 *   1. POST /api/wallet/pay              → debit verificato in WalletController
 *   2. POST /api/stripe/mark-order-completed → completa l'ordine usando quel movimento
 */
class StripeCheckoutController extends Controller
{
    use StripeCheckoutHelpers;

    public function __construct(
        private readonly OrderCreationService $orderCreation,
        private readonly CheckoutSubmissionContextService $submissionContext,
        private readonly CartService $cartService,
        private readonly WalletOrderLinkService $walletOrderLink,
    ) {}

    public function markOrderCompleted(PayWithExternalProviderRequest $request)
    {
        // Idempotenza: lo stesso ext_id deve riconciliare sempre lo stesso ordine
        // e la stessa transazione. Se il primo tentativo ha gia' scritto ordine +
        // transazione ma si e' fermato prima di dispatchare OrderPaid, un retry
        // deve far ripartire i side-effect post-pagamento una sola volta.
        $order = Order::findOrFail($request->order_id);
        if ($unauthorized = $this->ensureOrderOwnership($order)) {
            return $unauthorized;
        }

        $paymentType = $request->payment_type;
        $externalId = $this->resolveExternalId($request, $paymentType, $order);

        $dispatchOrderPaid = false;
        $transaction = null;
        $errorResponse = null;

        DB::transaction(function () use ($order, $paymentType, $externalId, $request, &$dispatchOrderPaid, &$transaction, &$errorResponse) {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($contextError = $this->syncSubmissionContextOnOrder($lockedOrder, $this->submissionContextFromRequest($request))) {
                $errorResponse = $contextError;

                return;
            }

            if ($paymentType === 'wallet' && ! $this->walletOrderLink->resolveVerifiedWalletMovement($lockedOrder, $externalId)) {
                $errorResponse = response()->json(['error' => 'Pagamento wallet non verificato per questo ordine.'], 422);

                return;
            }

            $existingTransaction = $this->loadExistingTransaction($lockedOrder, $paymentType, $externalId);

            if ($existingTransaction && (
                ($paymentType === 'bonifico' && $existingTransaction->status === 'pending')
                || ($paymentType !== 'bonifico' && $existingTransaction->status === 'succeeded')
            )) {
                $shouldDispatchExistingPaidOrder = $this->shouldDispatchExistingPaidOrder($lockedOrder, $paymentType);

                if ($paymentType !== 'bonifico' && $lockedOrder->isAwaitingPayment()) {
                    $lockedOrder->status = Order::COMPLETED;
                }

                $lockedOrder->payment_method = $paymentType;
                $lockedOrder->save();
                if ($paymentType === 'bonifico') {
                    $existingTransaction->total = $lockedOrder->payableTotalCents();
                    $existingTransaction->save();
                }
                $transaction = $existingTransaction;
                $dispatchOrderPaid = $shouldDispatchExistingPaidOrder;

                return;
            }

            if (! $lockedOrder->isAwaitingPayment()) {
                $errorResponse = response()->json(['error' => 'Ordine non più pagabile.'], 422);

                return;
            }

            // F05 — Bonifico: ordine resta in stato dedicato "awaiting_bank_transfer"
            // fino a conferma manuale da parte dell'admin (vedi AdminBankTransferController).
            $lockedOrder->status = $paymentType === 'bonifico'
                ? Order::AWAITING_BANK_TRANSFER
                : Order::COMPLETED;
            $lockedOrder->payment_method = $paymentType;
            $lockedOrder->save();

            $transaction = Transaction::updateOrCreate([
                'order_id' => $lockedOrder->id,
                'ext_id' => $externalId,
            ], [
                'type' => $paymentType,
                'status' => $paymentType === 'bonifico' ? 'pending' : 'succeeded',
                'provider_status' => $paymentType === 'bonifico' ? 'pending' : 'succeeded',
                'total' => $lockedOrder->payableTotalCents(),
            ]);

            $dispatchOrderPaid = $paymentType !== 'bonifico';
        });

        if ($errorResponse) {
            return $errorResponse;
        }

        if ($transaction && $paymentType !== 'bonifico') {
            $freshOrder = $order->fresh();

            if ($dispatchOrderPaid) {
                event(new OrderPaid($freshOrder, $transaction));
                $freshOrder = $freshOrder->fresh();
            }

            $this->clearCartPackagesForOrder($freshOrder);
        }

        if ($transaction && $paymentType === 'bonifico') {
            $this->finalizeBonifico($order->fresh());
        }

        return response()->json(['success' => true]);
    }

    public function createOrder(CreateOrderRequest $request)
    {
        $userId = auth()->id();

        return DB::transaction(function () use ($request, $userId) {
            DB::table('users')->where('id', $userId)->lockForUpdate()->first();

            $submissionContext = $this->submissionContextFromRequest($request, includeDiscountContext: true);

            $requestedPackageIds = $request->has('package_ids') && ! empty($request->package_ids)
                ? (array) $request->package_ids
                : [];
            $candidateSelection = $this->loadCheckoutCandidatePackages($userId, $requestedPackageIds);
            $requestedIds = $candidateSelection['requested_ids'];
            $cartPackageIds = $candidateSelection['cart_package_ids'];
            $packages = $candidateSelection['packages'];

            if ($requestedIds->isNotEmpty() && $cartPackageIds->count() !== $requestedIds->count()) {
                return response()->json(['error' => 'Alcuni pacchi selezionati non sono più nel carrello.'], 422);
            }

            if ($packages->count() !== $cartPackageIds->count()) {
                return response()->json(['error' => 'Alcuni pacchi non sono più disponibili per il checkout.'], 422);
            }

            if ($packages->isEmpty()) {
                return response()->json(['error' => 'Nessun pacco trovato.'], 422);
            }

            $this->cartService->normalizePackagePricing($packages);
            $packages = Package::with(['originAddress', 'destinationAddress', 'service'])
                ->whereIn('id', $packages->pluck('id'))
                ->get();

            if ($request->boolean('single_order_only') && $this->orderCreation->countPackageGroups($packages) > 1) {
                return response()->json([
                    'error' => 'Questo checkout contiene più spedizioni separate. Completa un pagamento per volta.',
                ], 422);
            }

            $submissionContext = $this->submissionContext->enrich(
                $submissionContext,
                $this->submissionContext->snapshotFromPackages($packages, $request->input('billing_data')),
                [
                    'user_id' => $userId,
                    'package_ids' => $packages->pluck('id')->values()->all(),
                    'billing_data' => $request->input('billing_data'),
                ],
            );

            $existingOrders = Order::query()
                ->where('user_id', $userId)
                ->where(function ($query) use ($submissionContext) {
                    $submissionId = trim((string) ($submissionContext['client_submission_id'] ?? ''));

                    $query->where('client_submission_id', $submissionId);

                    if ($submissionId !== '') {
                        $query->orWhere('client_submission_id', 'like', $submissionId.'|%');
                    }
                })
                ->orderBy('id')
                ->get();

            if ($existingOrders->isNotEmpty()) {
                foreach ($existingOrders as $existingOrder) {
                    if ($error = $this->syncSubmissionContextOnOrder($existingOrder, $submissionContext)) {
                        return $error;
                    }
                }

                return response()->json($this->formatOrderResponse($existingOrders));
            }

            $alreadyOrderedPackageIds = $this->findAlreadyOrderedPackageIds($packages);
            if ($alreadyOrderedPackageIds->isNotEmpty()) {
                return response()->json(['error' => 'Alcuni pacchi non sono più disponibili per il checkout.'], 422);
            }

            $orders = $this->orderCreation->createOrdersFromPackages(
                $packages,
                $userId,
                $request->input('billing_data'),
                $submissionContext,
            );

            foreach ($orders as $order) {
                if ($error = $this->syncSubmissionContextOnOrder($order, $submissionContext)) {
                    return $error;
                }
            }

            return response()->json($this->formatOrderResponse($orders));
        });
    }

    private function resolveExternalId(PayWithExternalProviderRequest $request, string $paymentType, Order $order): string
    {
        if (filled($request->ext_id)) {
            return (string) $request->ext_id;
        }

        $idempotencyKey = $this->extractIdempotencyKey($request);
        if (filled($idempotencyKey)) {
            return $idempotencyKey;
        }

        return "{$paymentType}_order_{$order->id}";
    }

    private function loadExistingTransaction(Order $order, string $paymentType, string $externalId): ?Transaction
    {
        if ($paymentType === 'bonifico') {
            return $order->transactions()
                ->where('type', 'bonifico')
                ->where('status', 'pending')
                ->latest('id')
                ->first();
        }

        return $order->transactions()
            ->where('ext_id', $externalId)
            ->first();
    }

    private function shouldDispatchExistingPaidOrder(Order $order, string $paymentType): bool
    {
        if ($paymentType === 'bonifico') {
            return false;
        }

        if ($order->isAwaitingPayment()) {
            return true;
        }

        // If a succeeded wallet/card transaction already exists but the order is
        // still stuck in the controller-level "completed" state, the first
        // completion attempt most likely committed the order + transaction and
        // failed before dispatching OrderPaid. Retrying must restart the
        // downstream post-payment flow exactly once.
        return $order->rawStatus() === Order::COMPLETED;
    }

    /**
     * F05 — Per bonifico: svuota carrello immediatamente e invia email istruzioni
     * anche se il pagamento non e' ancora "effettivo". L'ordine e' bloccato in
     * awaiting_bank_transfer e non puo' essere ri-pagato con altri metodi.
     */
    private function finalizeBonifico(Order $order): void
    {
        $this->clearCartPackagesForOrder($order);

        try {
            if ($order->user?->email) {
                Mail::to($order->user->email)->queue(new OrderAwaitingBankTransferMail($order));
            }

            $adminEmail = trim((string) Setting::get('admin_notification_email', ''))
                ?: (string) config('mail.from.address');
            if ($adminEmail) {
                $amount = number_format($order->payableTotalCents() / 100, 2, ',', '.');
                Mail::raw(
                    "Nuovo ordine #{$order->id} in attesa di bonifico.\n"
                    ."Importo: {$amount} EUR\n"
                    .'Cliente: '.($order->user?->email ?: '—')."\n"
                    ."Causale attesa: Ordine #{$order->id}\n"
                    .'Gestisci: '.rtrim((string) config('app.frontend_url'), '/').'/account/amministrazione/ordini?filter=awaiting_bank_transfer',
                    function ($message) use ($adminEmail, $order) {
                        $message->to($adminEmail)
                            ->subject('[Admin] Bonifico in attesa - Ordine #'.$order->id);
                    }
                );
            }
        } catch (\Throwable $e) {
            Log::warning('Bank transfer mails failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
