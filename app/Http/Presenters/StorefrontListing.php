<?php

namespace App\Http\Presenters;

use App\Models\ShopProduct;
use Illuminate\Support\Str;

/**
 * Public representation of a listing: only shop-specific display values
 * (overrides with catalog fallbacks). Never exposes internal catalog data.
 * Requires the `product` relation to be loaded.
 */
final class StorefrontListing
{
    public static function card(ShopProduct $listing): array
    {
        return [
            'id' => $listing->id,
            'slug' => $listing->product->slug,
            'name' => $listing->display_name,
            'excerpt' => Str::limit((string) $listing->display_description, 110),
            'price' => $listing->display_price,
            'thumbnail_url' => $listing->display_thumbnail_url,
            'in_stock' => $listing->isInStock(),
        ];
    }

    public static function detail(ShopProduct $listing): array
    {
        return self::card($listing) + [
            'description' => $listing->display_description,
            'stock' => $listing->stock,
            'sku' => $listing->product->sku,
        ];
    }
}
