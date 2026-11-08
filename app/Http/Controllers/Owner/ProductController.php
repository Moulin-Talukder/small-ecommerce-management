<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreOwnProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Product;
use App\Models\Shop;
use App\Services\CatalogProductService;
use App\Services\ListingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Catalog products created by the logged-in shop owner. They are part of
 * the shared catalog; only their creator (or an admin) may edit/delete them.
 */
class ProductController extends Controller
{
    public function __construct(
        private readonly CatalogProductService $products,
        private readonly ListingService $listings,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $filters = ['search' => trim((string) $request->query('search', ''))];

        $products = $user->createdProducts()
            ->search($filters['search'])
            ->withCount([
                'shopProducts as own_shops_count' => fn (Builder $q) => $q->whereHas('shop', fn (Builder $q) => $q->where('owner_id', $user->id)),
                'shopProducts as other_shops_count' => fn (Builder $q) => $q->whereHas('shop', fn (Builder $q) => $q->where('owner_id', '!=', $user->id)),
            ])
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Owner/Products/Index', [
            'products' => $products,
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Product::class);

        return Inertia::render('Owner/Products/Create', [
            'shops' => $request->user()->shops()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreOwnProductRequest $request): RedirectResponse
    {
        $product = DB::transaction(function () use ($request) {
            $product = $this->products->create($request->productData(), $request->file('thumbnail'), $request->user());

            if ($shopId = $request->validated('list_in_shop_id')) {
                // Listed with no overrides: it follows the catalog values.
                $this->listings->add(Shop::findOrFail($shopId), $product, [
                    'price' => null,
                    'stock' => (int) $request->validated('stock', 0),
                    'is_active' => true,
                ]);
            }

            return $product;
        });

        $message = "Product \"{$product->name}\" added to the shared catalog";
        $message .= $request->filled('list_in_shop_id') ? ' and listed in your shop.' : '.';

        return redirect()->route('owner.products.index')->with('success', $message);
    }

    public function edit(Request $request, Product $product): Response
    {
        $this->authorize('update', $product);

        $user = $request->user();

        return Inertia::render('Owner/Products/Edit', [
            'product' => $product,
            'ownShops' => $product->shops()->where('owner_id', $user->id)->orderBy('name')->pluck('shops.name'),
            'otherShopsCount' => $product->shopProducts()->whereHas('shop', fn (Builder $q) => $q->where('owner_id', '!=', $user->id))->count(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->products->update(
            $product,
            $request->productData(),
            $request->file('thumbnail'),
            $request->boolean('remove_thumbnail'),
        );

        return redirect()->route('owner.products.index')
            ->with('success', "Product \"{$product->name}\" updated.");
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        // Other owners sell this product: deleting it would pull it from their shops.
        if ($product->isListedInShopsNotOwnedBy($request->user())) {
            return back()->with('error', 'This product is listed in other shops, so it cannot be deleted.');
        }

        $product->delete();

        return redirect()->route('owner.products.index')
            ->with('success', "Product \"{$product->name}\" deleted.");
    }
}
