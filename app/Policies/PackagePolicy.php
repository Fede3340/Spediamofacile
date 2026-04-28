<?php

namespace App\Policies;

use App\Models\Package;
use App\Models\User;

/**
 * Authorizza accesso ai pacchi (Package).
 * Regola base: ogni utente vede/modifica solo i propri pacchi, admin bypass.
 * Un Package appartiene a un utente tramite la colonna user_id (vedi Package.php).
 *
 * NOTA: questa Policy NON è ancora cablata sui controller esistenti
 * (CartController, SavedShipmentController, OrderController). Il refactor
 * dei controller per usarla è separato (vedi task SEC-policies-wire-up).
 */
class PackagePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Package $package): bool
    {
        return $user->isAdmin() || $package->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true; // ogni utente autenticato puo' creare pacchi nel proprio carrello
    }

    public function update(User $user, Package $package): bool
    {
        return $user->isAdmin() || $package->user_id === $user->id;
    }

    public function delete(User $user, Package $package): bool
    {
        return $user->isAdmin() || $package->user_id === $user->id;
    }

    public function restore(User $user, Package $package): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Package $package): bool
    {
        return $user->isAdmin();
    }
}
