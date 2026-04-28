<?php

namespace App\Policies;

use App\Models\ContactMessage;
use App\Models\User;

/**
 * Authorizza accesso ai messaggi del modulo "Contattaci" (ContactMessage).
 * I messaggi NON hanno user_id (li possono inviare anche utenti non registrati),
 * quindi NON c'e' ownership-check classico: solo gli admin gestiscono questa risorsa.
 *
 * Create e' pubblico (controller usa endpoint senza auth con rate-limit + captcha).
 */
class ContactMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, ContactMessage $message): bool
    {
        return $user->isAdmin();
    }

    public function create(?User $user = null): bool
    {
        return true; // pubblico (modulo contatti accessibile a chiunque)
    }

    public function update(User $user, ContactMessage $message): bool
    {
        return $user->isAdmin(); // markAsRead riservato all'admin
    }

    public function delete(User $user, ContactMessage $message): bool
    {
        return $user->isAdmin();
    }
}
