<?php

namespace App\Policies;

use App\Models\ShopProduct;
use App\Models\User;

/** A listing is managed by the owner of the shop it belongs to. */
class ShopProductPolicy
{
    public function update(User $user, ShopProduct $listing): bool
    {
        return $user->isShopOwner() && $listing->shop->isOwnedBy($user);
    }

    public function delete(User $user, ShopProduct $listing): bool
    {
        return $this->update($user, $listing);
    }
}
