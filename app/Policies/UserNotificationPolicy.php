<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserNotification;

/**
 * Authorizza accesso alle notifiche utente (UserNotification).
 * Regola base: ogni utente vede/modifica/cancella SOLO le proprie notifiche.
 * Admin bypass per supporto/diagnostica (es. capire perche' un utente non
 * ha ricevuto una notifica). Le notifiche sono create server-side dai vari
 * trigger (ordine confermato, ritiro programmato, commissione accreditata).
 */
class UserNotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, UserNotification $notification): bool
    {
        return $user->isAdmin() || $notification->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return false; // create server-side dai trigger (mai dall'utente)
    }

    /**
     * Update consentito al destinatario per markAsRead (read_at).
     * Admin puo' aggiornare per supporto.
     */
    public function update(User $user, UserNotification $notification): bool
    {
        return $user->isAdmin() || $notification->user_id === $user->id;
    }

    public function delete(User $user, UserNotification $notification): bool
    {
        return $user->isAdmin() || $notification->user_id === $user->id;
    }
}
