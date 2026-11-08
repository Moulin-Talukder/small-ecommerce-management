<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreListingRequest;
use App\Models\Product;
use App\Models\Shop;
use App\Services\ListingService;
use Illuminate\Http\RedirectResponse;

class ListingController extends Controller
{
    public function __construct(private readonly ListingService $listings) {}

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
}
