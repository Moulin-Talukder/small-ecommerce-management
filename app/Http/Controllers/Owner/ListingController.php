<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreListingRequest;
use App\Http\Requests\Owner\UpdateListingRequest;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopProduct;
use App\Services\ListingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A shop owner's product listings in one of their shops.
 * Routes use scoped bindings: {shopProduct} must belong to {shop}.
 */
class ListingController extends Controller
{
    public function __construct(private readonly ListingService $listings) {}

    public function index(Request $request, Shop $shop): Response
    {
        $this->authorize('manageListings', $shop);

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'status' => in_array($request->query('status'), ['active', 'inactive'], true) ? $request->query('status') : '',
        ];

        $listings = $shop->shopProducts()
            ->whereHas('product') // hide listings of soft-deleted catalog products
            ->with('product:id,name,sku,description,base_price,thumbnail')
            ->when($filters['search'] !== '', fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('custom_name', 'like', "%{$filters['search']}%")
                ->orWhereHas('product', fn (Builder $q) => $q->search($filters['search']))))
            ->when($filters['status'] !== '', fn (Builder $q) => $q->where('shop_products.is_active', $filters['status'] === 'active'))
            ->latest('shop_products.id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (ShopProduct $listing) => [
                'id' => $listing->id,
                'name' => $listing->display_name,
                'catalog_name' => $listing->product->name,
                'sku' => $listing->product->sku,
                'price' => $listing->display_price,
                'base_price' => $listing->product->base_price,
                'stock' => $listing->stock,
                'is_active' => $listing->is_active,
                'thumbnail_url' => $listing->display_thumbnail_url,
                'overrides' => array_keys(array_filter([
                    'price' => $listing->price !== null,
                    'name' => filled($listing->custom_name),
                    'description' => filled($listing->custom_description),
                    'thumbnail' => filled($listing->custom_thumbnail),
                ])),
            ]);

        return Inertia::render('Owner/Listings/Index', [
            'shop' => $shop->only(['id', 'name', 'slug', 'is_active']),
            'shops' => $request->user()->shops()->orderBy('name')->get(['id', 'name']),
            'listings' => $listings,
            'filters' => $filters,
        ]);
    }

    public function store(StoreListingRequest $request, Shop $shop): RedirectResponse
    {
        $product = Product::findOrFail($request->validated('product_id'));

        $listing = $this->listings->add(
            $shop,
            $product,
            $request->safe()->except(['product_id', 'custom_thumbnail']),
            $request->file('custom_thumbnail'),
        );

        $name = $listing->custom_name ?: $product->name;

        return back()->with('success', "\"{$name}\" added to {$shop->name}.");
    }

    public function edit(Shop $shop, ShopProduct $shopProduct): Response
    {
        $this->authorize('update', $shopProduct);

        $product = $shopProduct->product ?? abort(404); // catalog product was deleted

        return Inertia::render('Owner/Listings/Edit', [
            'shop' => $shop->only(['id', 'name', 'slug', 'is_active']),
            'listing' => [
                'id' => $shopProduct->id,
                'price' => $shopProduct->price,
                'custom_name' => $shopProduct->custom_name,
                'custom_description' => $shopProduct->custom_description,
                'custom_thumbnail_url' => $shopProduct->custom_thumbnail_url,
                'stock' => $shopProduct->stock,
                'is_active' => $shopProduct->is_active,
            ],
            'product' => $product->only(['id', 'name', 'slug', 'sku', 'description', 'base_price', 'thumbnail_url']),
        ]);
    }

    public function update(UpdateListingRequest $request, Shop $shop, ShopProduct $shopProduct): RedirectResponse
    {
        $this->listings->update(
            $shopProduct,
            $request->safe()->except(['custom_thumbnail', 'remove_custom_thumbnail']),
            $request->file('custom_thumbnail'),
            $request->boolean('remove_custom_thumbnail'),
        );

        return redirect()->route('owner.listings.index', $shop)
            ->with('success', "\"{$shopProduct->load('product')->display_name}\" updated.");
    }

    public function toggle(Shop $shop, ShopProduct $shopProduct): RedirectResponse
    {
        $this->authorize('update', $shopProduct);

        $shopProduct->update(['is_active' => ! $shopProduct->is_active]);

        $state = $shopProduct->is_active ? 'is now visible' : 'is now hidden';

        return back()->with('success', "\"{$shopProduct->load('product')->display_name}\" {$state} in {$shop->name}.");
    }

    public function destroy(Shop $shop, ShopProduct $shopProduct): RedirectResponse
    {
        $this->authorize('delete', $shopProduct);

        $name = $shopProduct->load('product')->display_name ?? 'Product';

        $this->listings->remove($shopProduct);

        return back()->with('success', "\"{$name}\" removed from {$shop->name}. It is still in the shared catalog.");
    }
}
