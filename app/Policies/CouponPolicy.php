<?php

namespace App\Policies;

use App\Models\Coupon;
use App\Models\User;

/**
 * Authorizza gestione coupon: solo admin può CRUD coupon (definisce sconti business).
 * Gli utenti normali interagiscono via /api/coupons/validate (endpoint pubblico, no policy).
 */
class CouponPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Coupon $coupon): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Coupon $coupon): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Coupon $coupon): bool
    {
        return $user->isAdmin();
    }
}
