<?php

namespace App\Policies;

use App\Models\InvoiceArchive;
use App\Models\User;

/**
 * Authorizza accesso fatture archiviate (PDF + metadati SDI).
 * Owned by user via order: l'utente vede solo le fatture dei propri ordini, admin tutto.
 */
class InvoiceArchivePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, InvoiceArchive $invoice): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        // Verifica via order: l'archivio fattura referenzia order_id che a sua volta ha user_id.
        return $invoice->order && $invoice->order->user_id === $user->id;
    }

    public function download(User $user, InvoiceArchive $invoice): bool
    {
        return $this->view($user, $invoice);
    }

    public function create(User $user): bool
    {
        return false; // generate solo da flusso server post-pagamento
    }

    public function update(User $user, InvoiceArchive $invoice): bool
    {
        return false; // immutabile (audit fiscale)
    }

    public function delete(User $user, InvoiceArchive $invoice): bool
    {
        return false; // mai delete (compliance fiscale)
    }
}
