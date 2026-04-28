<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WalletMovement;

/**
 * Authorizza accesso ai movimenti del portafoglio virtuale.
 * Regola base: ogni utente vede solo i propri movimenti, admin vede tutto.
 * Nessun utente può creare/modificare movimenti direttamente: avviene
 * server-side via WalletController/ReferralController/Admin con idempotency_key.
 */
class WalletMovementPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, WalletMovement $movement): bool
    {
        return $user->isAdmin() || $movement->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return false; // movimenti generati solo da flussi server (top-up, prelievo, commissione)
    }

    public function update(User $user, WalletMovement $movement): bool
    {
        return false; // movimenti immutabili (audit trail)
    }

    public function delete(User $user, WalletMovement $movement): bool
    {
        return false; // soldi: niente delete fisico, mai
    }

    public function requestWithdrawal(User $user): bool
    {
        return $user->isPro() || $user->isAdmin();
    }
}
