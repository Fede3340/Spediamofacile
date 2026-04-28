<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WithdrawalRequest;

/**
 * Authorizza richieste di prelievo (Pro user wallet → conto bancario).
 * Solo l'utente Pro proprietario può creare/vedere le proprie richieste,
 * solo l'admin può approvare/rifiutare.
 */
class WithdrawalRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPro() || $user->isAdmin();
    }

    public function view(User $user, WithdrawalRequest $request): bool
    {
        return $user->isAdmin() || $request->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isPro(); // solo Pro può richiedere prelievo
    }

    public function approve(User $user, WithdrawalRequest $request): bool
    {
        return $user->isAdmin();
    }

    public function reject(User $user, WithdrawalRequest $request): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, WithdrawalRequest $request): bool
    {
        // Owner può cancellare la sua richiesta solo se ancora pending
        if ($user->isAdmin()) {
            return true;
        }
        return $request->user_id === $user->id && $request->status === WithdrawalRequest::STATUS_PENDING;
    }

    public function delete(User $user, WithdrawalRequest $request): bool
    {
        return false; // mai delete (audit trail soldi)
    }
}
