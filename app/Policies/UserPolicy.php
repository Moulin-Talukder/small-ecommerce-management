<?php

namespace App\Policies;

use App\Models\User;

/**
 * Admin management of shop-owner accounts. Admin accounts are never managed
 * through these screens (no editing/deleting other admins or yourself).
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $target): bool
    {
        return $user->isAdmin() && $target->isShopOwner();
    }

    public function delete(User $user, User $target): bool
    {
        return $user->isAdmin() && $target->isShopOwner();
    }
}
