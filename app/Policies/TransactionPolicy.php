<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

/**
 * Authorizza accesso alle transazioni di pagamento (Transaction).
 * Regola base: l'utente vede solo le transazioni dei propri ordini, admin bypass.
 * Le transazioni sono SEMPRE create dal server (Stripe webhook / StripeController):
 * nessun utente può crearle/modificarle/eliminarle direttamente.
 * L'ownership è derivata via Order: $transaction->order->user_id === $user->id.
 */
class TransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Transaction $transaction): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Ownership via Order: la transazione appartiene all'ordine,
        // l'ordine appartiene all'utente.
        $order = $transaction->order;

        return $order !== null && $order->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return false; // create solo via Stripe webhook / server-side
    }

    public function update(User $user, Transaction $transaction): bool
    {
        return false; // immutabili per audit/contabilita'
    }

    public function delete(User $user, Transaction $transaction): bool
    {
        return false; // mai cancellabili: audit pagamenti
    }
}
