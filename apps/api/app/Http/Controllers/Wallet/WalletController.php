<?php
namespace App\Http\Controllers\Wallet;

use App\Http\Controllers\Controller;

use App\Models\WalletMovement;
use App\Services\StripePaymentService;
use App\Services\WalletOrderLinkService;
use App\Services\WalletOrderPaymentService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\CardException;

class WalletController extends Controller
{
    public function __construct(
        private readonly StripePaymentService $stripePaymentService,
        private readonly WalletOrderLinkService $walletOrderLink,
        private readonly WalletOrderPaymentService $walletOrderPayment,
    ) {}

    /*
     * Boundary note:
     * - questo controller possiede saldo, movimenti, top-up e debit wallet;
     * - NON completa da solo un ordine pagato con wallet;
     * - l'ordine viene finalizzato dal secondo step
     *   `POST /api/stripe/mark-order-completed` in StripeCheckoutController.
     * - il contratto canonico `order-{id}` / `wallet-{id}` vive in WalletOrderLinkService.
     *
     * Per capire il flusso reale: docs/FEATURE_BOUNDARIES.md -> Wallet / Payment.
     */

    // Mostra il saldo attuale del portafoglio dell'utente
    // Per i Partner Pro mostra anche il saldo delle commissioni
    public function balance(): JsonResponse
    {
        $user = auth()->user();

        return response()->json(
            [
                'balance' => $user->walletBalance(),
                'commission_balance' => $user->isPro() ? $user->commissionBalance() : null,
                'currency' => 'EUR',
            ],
            200,
            [],
            JSON_PRESERVE_ZERO_FRACTION
        );
    }

    // Mostra la lista di tutti i movimenti del portafoglio dell'utente
    // (ricariche, pagamenti, commissioni, prelievi, ecc.)
    // Ordinati dal più recente al più vecchio
    public function movements(): JsonResponse
    {
        $movements = WalletMovement::query()
            ->where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->paginate(30);

        return response()->json($movements);
    }

    // Ricarica il portafoglio usando una carta di credito salvata
    // Crea un pagamento su Stripe e, se va a buon fine, aggiunge i soldi al portafoglio
    public function topUp(\App\Http\Requests\WalletTopUpRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = $request->user();
        $amountCents = (int) round($data['amount'] * 100);
        $idempotencyKey = $this->stripePaymentService->resolveWalletTopUpIdempotencyKey(
            $user,
            $amountCents,
            (string) $data['payment_method_id'],
            $data['idempotency_key'] ?? null
        );

        if (! $this->stripePaymentService->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe non configurato. Vai nelle impostazioni per inserire le chiavi API.',
            ], 503);
        }

        try {
            $paymentIntent = $this->stripePaymentService->createWalletTopUpPayment(
                $user,
                $amountCents,
                (string) $data['payment_method_id'],
                $idempotencyKey
            );

            if (($paymentIntent['status'] ?? null) === 'succeeded') {
                $result = DB::transaction(function () use ($user, $data, $paymentIntent, $idempotencyKey) {
                    $lockedUser = \App\Models\User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

                    $existingMovement = WalletMovement::query()
                        ->where('user_id', $lockedUser->id)
                        ->where('idempotency_key', $idempotencyKey)
                        ->lockForUpdate()
                        ->first();

                    if ($existingMovement) {
                        return [
                            'movement' => $existingMovement,
                            'created' => false,
                            'new_balance' => $lockedUser->walletBalance(),
                        ];
                    }

                    $movement = WalletMovement::create([
                        'user_id' => $lockedUser->id,
                        'type' => 'credit',
                        'amount' => $data['amount'],
                        'currency' => 'EUR',
                        'status' => 'confirmed',
                        'idempotency_key' => $idempotencyKey,
                        'description' => 'Ricarica portafoglio via carta',
                        'source' => 'stripe',
                        'reference' => $paymentIntent['payment_intent_id'],
                    ]);

                    return [
                        'movement' => $movement,
                        'created' => true,
                        'new_balance' => $lockedUser->walletBalance(),
                    ];
                });

                return response()->json([
                    'success' => true,
                    'data' => $result['movement'],
                    'new_balance' => $result['new_balance'],
                ], $result['created'] ? 201 : 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Pagamento non riuscito. Stato: '.($paymentIntent['status'] ?? 'unknown'),
            ], 402);
        } catch (QueryException $e) {
            $existingMovement = WalletMovement::query()
                ->where('user_id', $user->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existingMovement) {
                return response()->json([
                    'success' => true,
                    'data' => $existingMovement,
                    'new_balance' => $user->walletBalance(),
                ], 200);
            }

            Log::error('Wallet top-up persistence error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Errore durante il salvataggio della ricarica. Riprova.',
            ], 500);
        } catch (CardException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pagamento rifiutato: '.$e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('Wallet top-up error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Errore durante il pagamento. Riprova.',
            ], 500);
        }
    }

    // Paga una spedizione usando il saldo del portafoglio.
    // Questo endpoint crea SOLO il movimento debit verificato.
    // La completion dell'ordine vive nel secondo step
    // StripeCheckoutController::markOrderCompleted().
    public function payWithWallet(\App\Http\Requests\WalletPayRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = $request->user();

        $orderReference = $this->walletOrderLink->normalizeOrderReference($data['reference']);
        $orderId = $orderReference
            ? $this->walletOrderLink->extractOrderId($orderReference)
            : null;

        if (! $orderId) {
            return response()->json(['message' => 'Riferimento ordine non valido.'], 422);
        }

        $order = \App\Models\Order::find($orderId);
        if (! $order) {
            return response()->json(['message' => 'Ordine non trovato.'], 404);
        }

        if ((int) $order->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Non autorizzato: questo ordine non appartiene al tuo account.'], 403);
        }

        $orderAmountEur = round($order->payableTotalCents() / 100, 2);
        $requestedAmount = round((float) $data['amount'], 2);
        if (abs($orderAmountEur - $requestedAmount) > 0.01) {
            Log::warning('Wallet pay amount mismatch', [
                'user_id' => $user->id,
                'order_id' => $order->id,
                'order_amount_eur' => $orderAmountEur,
                'requested_amount' => $requestedAmount,
                'gross_subtotal_cents' => $order->grossSubtotalCents(),
                'payable_total_cents' => $order->payableTotalCents(),
            ]);

            return response()->json([
                'message' => "L'importo non corrisponde al totale dell'ordine.",
            ], 422);
        }

        if (! $order->isAwaitingPayment()) {
            return response()->json([
                'message' => 'Questo ordine non è in attesa di pagamento.',
            ], 422);
        }

        $result = $this->walletOrderPayment->createOrReuseOrderDebit(
            $user,
            $order,
            $orderAmountEur,
            $data['description'] ?? 'Pagamento spedizione'
        );

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $result['movement'],
            'new_balance' => $result['new_balance'],
        ], ($result['created'] ?? false) ? 201 : 200);
    }
}
