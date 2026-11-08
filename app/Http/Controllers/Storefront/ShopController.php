<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Presenters\StorefrontListing;
use App\Models\Shop;
use App\Models\ShopProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public pages. Every query goes through the publiclyVisible() scopes, so an
 * inactive shop/owner, a hidden listing or a soft-deleted product is a 404,
 * and a product that is not assigned to the shop simply has no route.
 */
class ShopController extends Controller
{
    public function index(): Response
    {
        $shops = Shop::publiclyVisible()
            ->withCount(['shopProducts as products_count' => fn (Builder $q) => $q->publiclyVisible()])
            ->orderBy('name')
            ->paginate(12)
            ->through(fn (Shop $shop) => [
                'name' => $shop->name,
                'slug' => $shop->slug,
                'description' => $shop->description,
                'logo_url' => $shop->logo_url,
                'products_count' => $shop->products_count,
            ]);

        return Inertia::render('Storefront/Shops/Index', [
            'shops' => $shops,
        ]);
    }

    public function show(Request $request, string $shopSlug): Response
    {
        $shop = Shop::publiclyVisible()->where('slug', $shopSlug)->firstOrFail();

        $search = trim((string) $request->query('search', ''));

        $listings = ShopProduct::publiclyVisible()
            ->whereBelongsTo($shop)
            ->with('product')
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('custom_name', 'like', "%{$search}%")
                ->orWhere('custom_description', 'like', "%{$search}%")
                ->orWhereHas('product', fn (Builder $q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%"))))
            ->orderByDisplayName()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (ShopProduct $listing) => StorefrontListing::card($listing));

        return Inertia::render('Storefront/Shops/Show', [
            'shop' => [
                'name' => $shop->name,
                'slug' => $shop->slug,
                'description' => $shop->description,
                'logo_url' => $shop->logo_url,
            ],
            'listings' => $listings,
            'filters' => ['search' => $search],
        ]);
    }
}
