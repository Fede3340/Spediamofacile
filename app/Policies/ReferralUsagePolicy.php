<?php

namespace App\Policies;

use App\Models\ReferralUsage;
use App\Models\User;

/**
 * Authorizza accesso ai record di utilizzo referral (ReferralUsage).
 * Regola base: il Partner Pro vede gli utilizzi del proprio codice (proUser),
 * l'acquirente vede gli utilizzi dove e' buyer, admin bypass.
 * I record sono SEMPRE creati server-side da ReferralController::apply().
 */
class ReferralUsagePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ReferralUsage $usage): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Pro vede i propri guadagni; buyer vede il proprio sconto.
        return $usage->pro_user_id === $user->id
            || $usage->buyer_id === $user->id;
    }

    public function create(User $user): bool
    {
        return false; // generato server-side al momento del checkout con codice referral
    }

    public function update(User $user, ReferralUsage $usage): bool
    {
        return false; // immutabile (audit commissioni)
    }

    public function delete(User $user, ReferralUsage $usage): bool
    {
        return false; // mai cancellabile (audit commissioni)
    }
}
