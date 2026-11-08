<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

/**
 * Shared catalog. Admins manage every product; shop owners may browse all
 * products but only edit/delete the ones they created.
 *
 * The extra owner delete rule ("not listed in other owners' shops") is a
 * business rule checked in the controller so it can show a clear flash error.
 */
class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isShopOwner();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isShopOwner();
    }

    public function update(User $user, Product $product): bool
    {
        return $user->isAdmin() || ($user->isShopOwner() && $product->isCreatedBy($user));
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->update($user, $product);
    }
}
