<?php

namespace App\Policies;

use App\Models\BillingAddress;
use App\Models\User;

/**
 * Authorizza accesso agli indirizzi di fatturazione.
 * Owned by user: ogni cliente gestisce i propri, admin vede tutto.
 * Contiene dati sensibili (P.IVA, codice fiscale, SDI) → niente cross-user access.
 */
class BillingAddressPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, BillingAddress $billingAddress): bool
    {
        return $user->isAdmin() || $billingAddress->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true; // ogni utente autenticato può creare il proprio
    }

    public function update(User $user, BillingAddress $billingAddress): bool
    {
        return $user->isAdmin() || $billingAddress->user_id === $user->id;
    }

    public function delete(User $user, BillingAddress $billingAddress): bool
    {
        return $user->isAdmin() || $billingAddress->user_id === $user->id;
    }
}
