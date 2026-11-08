<?php

namespace App\Http\Controllers\Owner;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Shared catalog browser for shop owners. The "in shop" state of every
 * product is computed for the shop selected in the shop switcher.
 */
class CatalogController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $this->authorize('viewAny', Product::class);

        $user = $request->user();
        $shops = $user->shops()->orderBy('name')->get(['id', 'name', 'is_active']);
        $shop = $shops->firstWhere('id', $request->integer('shop')) ?? $shops->first();

        $creator = (string) $request->query('creator', '');
        $availability = (string) $request->query('availability', '');

        $filters = [
            'shop' => $shop?->id ?? '',
            'search' => trim((string) $request->query('search', '')),
            'creator' => in_array($creator, ['admin', 'mine', 'others'], true) ? $creator : '',
            'availability' => $shop && in_array($availability, ['available', 'in_shop'], true) ? $availability : '',
        ];

        $inShop = fn ($q) => $q->where('shop_id', $shop?->id);

        $products = Product::query()
            ->with('creator:id,name,role')
            ->search($filters['search'])
            ->when($filters['creator'] === 'admin', fn (Builder $q) => $q->whereHas('creator', fn (Builder $q) => $q->where('role', Role::Admin->value)))
            ->when($filters['creator'] === 'mine', fn (Builder $q) => $q->where('created_by', $user->id))
            ->when($filters['creator'] === 'others', fn (Builder $q) => $q->whereHas('creator', fn (Builder $q) => $q
                ->where('role', Role::ShopOwner->value)
                ->whereKeyNot($user->id)))
            ->when($filters['availability'] === 'available', fn (Builder $q) => $q->whereDoesntHave('shopProducts', $inShop))
            ->when($filters['availability'] === 'in_shop', fn (Builder $q) => $q->whereHas('shopProducts', $inShop))
            ->when($shop, fn (Builder $q) => $q->with(['shopProducts' => fn ($q) => $inShop($q)->select(['id', 'shop_id', 'product_id'])]))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'description' => $product->description,
                'base_price' => $product->base_price,
                'thumbnail_url' => $product->thumbnail_url,
                'creator_name' => $product->creator?->name ?? 'Deleted user',
                'creator_is_admin' => $product->creator?->isAdmin() ?? false,
                'is_mine' => $product->isCreatedBy($user),
                'listing_id' => $shop ? $product->shopProducts->first()?->id : null,
            ]);

        return Inertia::render('Owner/Catalog/Index', [
            'products' => $products,
            'shops' => $shops,
            'filters' => $filters,
        ]);
    }
}
