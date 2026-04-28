<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

/**
 * Authorizza gestione servizi (extra spedizione: assicurazione, contrassegno, ecc.).
 * Lettura pubblica (endpoint /api/public/services), CRUD solo admin.
 */
class ServicePolicy
{
    public function viewAny(User $user): bool
    {
        return true; // lista visibile a tutti gli utenti autenticati
    }

    public function view(User $user, Service $service): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Service $service): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Service $service): bool
    {
        return $user->isAdmin();
    }
}
