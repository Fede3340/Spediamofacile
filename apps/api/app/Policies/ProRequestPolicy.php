<?php

namespace App\Policies;

use App\Models\ProRequest;
use App\Models\User;

/**
 * Authorizza gestione richieste "Diventa Partner Pro" (upgrade utente standard → Pro).
 * Owner può vedere/cancellare la propria pendente, admin gestisce approvazione.
 */
class ProRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, ProRequest $request): bool
    {
        return $user->isAdmin() || $request->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return ! $user->isPro(); // solo se NON sei già Pro
    }

    public function approve(User $user, ProRequest $request): bool
    {
        return $user->isAdmin();
    }

    public function reject(User $user, ProRequest $request): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, ProRequest $request): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        // Owner può cancellare solo se ancora pending
        return $request->user_id === $user->id && $request->status === ProRequest::STATUS_PENDING;
    }
}
