<?php

namespace App\Policies;

use App\Models\User;

/**
 * Authorizza gestione utenti.
 * - Ogni utente vede/aggiorna solo se stesso.
 * - Admin vede e gestisce tutti.
 * - Nessuno può cancellare utenti se non admin (e con vincoli business da policy controller).
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $target): bool
    {
        return $user->isAdmin() || $user->id === $target->id;
    }

    public function update(User $user, User $target): bool
    {
        return $user->isAdmin() || $user->id === $target->id;
    }

    public function changeRole(User $user, User $target): bool
    {
        return $user->isAdmin() && $user->id !== $target->id; // admin non si auto-modifica role
    }

    public function delete(User $user, User $target): bool
    {
        return $user->isAdmin() && $user->id !== $target->id; // admin non si auto-cancella
    }
}
