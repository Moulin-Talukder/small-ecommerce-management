<?php

namespace App\Policies;

use App\Models\Shop;
use App\Models\User;

class ShopPolicy
{
    /** Shop CRUD is admin-only. */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Shop $shop): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Shop $shop): bool
    {
        return $user->isAdmin();
    }

    /** Only the owning shop owner manages a shop's listings. */
    public function manageListings(User $user, Shop $shop): bool
    {
        return $user->isShopOwner() && $shop->isOwnedBy($user);
    }
}
