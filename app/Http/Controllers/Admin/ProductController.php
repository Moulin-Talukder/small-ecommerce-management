<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Product;
use App\Models\User;
use App\Services\CatalogProductService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(private readonly CatalogProductService $products) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Product::class);

        $creator = (string) $request->query('creator', '');
        $listing = (string) $request->query('listing', '');

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            // '' | 'admin' | 'owners' | '<user id>'
            'creator' => in_array($creator, ['admin', 'owners'], true) || ctype_digit($creator) ? $creator : '',
            'listing' => in_array($listing, ['listed', 'unlisted'], true) ? $listing : '',
        ];

        $products = Product::query()
            ->with('creator:id,name,role')
            ->withCount('shopProducts as shops_count')
            ->search($filters['search'])
            ->when($filters['creator'] === 'admin', fn (Builder $q) => $q->whereHas('creator', fn (Builder $q) => $q->where('role', Role::Admin->value)))
            ->when($filters['creator'] === 'owners', fn (Builder $q) => $q->whereHas('creator', fn (Builder $q) => $q->where('role', Role::ShopOwner->value)))
            ->when(ctype_digit($filters['creator']), fn (Builder $q) => $q->where('created_by', (int) $filters['creator']))
            ->when($filters['listing'] === 'listed', fn (Builder $q) => $q->has('shopProducts'))
            ->when($filters['listing'] === 'unlisted', fn (Builder $q) => $q->doesntHave('shopProducts'))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'filters' => $filters,
            'creators' => User::shopOwners()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Product::class);

        return Inertia::render('Admin/Products/Create');
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = $this->products->create($request->productData(), $request->file('thumbnail'), $request->user());

        return redirect()->route('admin.products.index')
            ->with('success', "Product \"{$product->name}\" added to the catalog.");
    }

    public function edit(Product $product): Response
    {
        $this->authorize('update', $product);

        $product->load([
            'creator:id,name,role',
            'shopProducts' => fn ($q) => $q->with('shop:id,name,is_active')->orderBy('shop_id'),
        ]);

        return Inertia::render('Admin/Products/Edit', [
            'product' => $product,
            'listings' => $product->shopProducts->map(fn ($listing) => [
                'id' => $listing->id,
                'shop' => $listing->shop->only(['id', 'name', 'is_active']),
                'custom_name' => $listing->custom_name,
                'price' => $listing->price,
                'stock' => $listing->stock,
                'is_active' => $listing->is_active,
            ]),
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

        return redirect()->route('admin.products.index')
            ->with('success', "Product \"{$product->name}\" updated.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        // Soft delete: the product disappears from every shop's public pages.
        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', "Product \"{$product->name}\" deleted from the catalog.");
    }
}
