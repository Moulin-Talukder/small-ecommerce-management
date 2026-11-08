<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Presenters\StorefrontListing;
use App\Models\Shop;
use App\Models\ShopProduct;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function show(string $shopSlug, string $productSlug): Response
    {
        $shop = Shop::publiclyVisible()->where('slug', $shopSlug)->firstOrFail();

        // The product is only reachable through a visible listing in THIS shop.
        $listing = ShopProduct::publiclyVisible()
            ->whereBelongsTo($shop)
            ->whereHas('product', fn (Builder $q) => $q->where('slug', $productSlug))
            ->with('product')
            ->firstOrFail();

        $others = ShopProduct::publiclyVisible()
            ->whereBelongsTo($shop)
            ->whereKeyNot($listing->id)
            ->with('product')
            ->inRandomOrder()
            ->limit(4)
            ->get()
            ->map(fn (ShopProduct $other) => StorefrontListing::card($other));

        return Inertia::render('Storefront/Products/Show', [
            'shop' => [
                'name' => $shop->name,
                'slug' => $shop->slug,
                'logo_url' => $shop->logo_url,
            ],
            'product' => StorefrontListing::detail($listing),
            'others' => $others,
        ]);
    }
}
