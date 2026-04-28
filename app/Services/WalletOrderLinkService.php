<?php

namespace App\Services;

use App\Models\Order;
use App\Models\WalletMovement;

/**
 * Source of truth per il contratto wallet <-> ordine nel checkout.
 *
 * Questo service esiste per evitare che controller, test e frontend ricostruiscano
 * in modo diverso le due stringhe canoniche usate dal pagamento wallet:
 *
 * - `reference = order-{id}`   per il movimento debit salvato in wallet_movements
 * - `ext_id = wallet-{id}`     per il secondo step `mark-order-completed`
 *
 * Garanzie offerte da questo boundary:
 * - normalizza i vecchi riferimenti numerici grezzi all'ordine (`42` -> `order-42`)
 * - accetta come movimento valido solo un debit:
 *   - dello stesso utente dell'ordine
 *   - `confirmed`
 *   - `source = wallet`
 *   - con importo uguale al totale ordine
 *   - con `reference = order-{id}`
 *
 * Nota importante:
 * - queste regole valgono solo per il pagamento ordine tramite wallet
 * - i movimenti wallet di altri flussi (es. refund/bonus) possono avere reference diverse
 */
class WalletOrderLinkService
{
    public function orderReference(Order|int $order): string
    {
        return 'order-'.$this->resolveOrderId($order);
    }

    public function walletExternalId(WalletMovement|int $movement): string
    {
        $movementId = $movement instanceof WalletMovement ? (int) $movement->id : (int) $movement;

        return 'wallet-'.$movementId;
    }

    public function walletPaymentIdempotencyKey(Order $order): string
    {
        return 'wallet_order_'.$order->id.'_'.$order->payableTotalCents();
    }

    public function extractOrderId(string $reference): ?int
    {
        $normalized = trim($reference);

        if (preg_match('/^order-(\d+)$/', $normalized, $matches)) {
            return (int) ($matches[1] ?? 0) ?: null;
        }

        if (ctype_digit($normalized)) {
            return (int) $normalized ?: null;
        }

        return null;
    }

    public function normalizeOrderReference(string $reference): ?string
    {
        $orderId = $this->extractOrderId($reference);

        return $orderId ? $this->orderReference($orderId) : null;
    }

    public function extractWalletMovementId(string $externalId): ?int
    {
        $normalized = trim($externalId);

        if (! preg_match('/^wallet-(\d+)$/', $normalized, $matches)) {
            return null;
        }

        return (int) ($matches[1] ?? 0) ?: null;
    }

    public function resolveVerifiedWalletMovement(Order $order, string $externalId): ?WalletMovement
    {
        $movementId = $this->extractWalletMovementId($externalId);
        if (! $movementId) {
            return null;
        }

        $expectedAmount = number_format($order->payableTotalCents() / 100, 2, '.', '');

        return WalletMovement::query()
            ->whereKey($movementId)
            ->where('user_id', $order->user_id)
            ->where('type', 'debit')
            ->where('status', 'confirmed')
            ->where('source', 'wallet')
            ->where('reference', $this->orderReference($order))
            ->where('amount', $expectedAmount)
            ->first();
    }

    private function resolveOrderId(Order|int $order): int
    {
        return $order instanceof Order ? (int) $order->id : (int) $order;
    }
}
