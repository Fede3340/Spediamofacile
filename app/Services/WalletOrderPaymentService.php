<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Models\WalletMovement;
use Illuminate\Support\Facades\DB;

/**
 * Boundary canonico del primo step "pay order with wallet".
 *
 * Possiede:
 * - lock utente + ordine
 * - idempotenza del debit wallet per ordine
 * - verifica saldo disponibile
 * - creazione o riuso del movimento debit
 *
 * Non possiede:
 * - finalizzazione dell'ordine
 * - creazione transaction
 * - dispatch OrderPaid
 *
 * Questi passaggi restano nel secondo step
 * StripeCheckoutController::markOrderCompleted().
 */
class WalletOrderPaymentService
{
    public function __construct(
        private readonly WalletOrderLinkService $walletOrderLink,
    ) {}

    /**
     * @return array{movement?: WalletMovement, new_balance?: float, created?: bool, error?: string}
     */
    public function createOrReuseOrderDebit(User $user, Order $order, float $amount, ?string $description = null): array
    {
        $canonicalOrderReference = $this->walletOrderLink->orderReference($order);
        $walletPaymentIdempotencyKey = $this->walletOrderLink->walletPaymentIdempotencyKey($order);

        return DB::transaction(function () use ($user, $order, $amount, $description, $canonicalOrderReference, $walletPaymentIdempotencyKey) {
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $lockedOrder = Order::query()->lockForUpdate()->find($order->id);

            $existingMovement = WalletMovement::query()
                ->where('user_id', $lockedUser->id)
                ->where('idempotency_key', $walletPaymentIdempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($existingMovement) {
                return [
                    'movement' => $existingMovement,
                    'new_balance' => $lockedUser->walletBalance(),
                    'created' => false,
                ];
            }

            if (! $lockedOrder || ! $lockedOrder->isAwaitingPayment()) {
                return ['error' => 'Ordine non più disponibile per il pagamento.'];
            }

            $expectedAmount = round($lockedOrder->payableTotalCents() / 100, 2);

            if (abs($expectedAmount - round($amount, 2)) > 0.01) {
                return ['error' => "L'importo non corrisponde al totale dell'ordine."];
            }

            if ($expectedAmount <= 0) {
                return ['error' => 'Totale ordine non valido per il pagamento wallet.'];
            }

            $balance = $lockedUser->walletBalance();
            if ($balance < $expectedAmount) {
                return ['error' => 'Saldo insufficiente. Disponibile: '.number_format($balance, 2).' EUR'];
            }

            $movement = WalletMovement::create([
                'user_id' => $user->id,
                'type' => 'debit',
                'amount' => $expectedAmount,
                'currency' => 'EUR',
                'status' => 'confirmed',
                'idempotency_key' => $walletPaymentIdempotencyKey,
                'reference' => $canonicalOrderReference,
                'description' => $description ?: 'Pagamento spedizione',
                'source' => 'wallet',
            ]);

            return [
                'movement' => $movement,
                'new_balance' => $user->walletBalance(),
                'created' => true,
            ];
        });
    }
}
