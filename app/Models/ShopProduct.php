<?php

namespace App\Models;

use Database\Factories\ShopProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\Storage;

/**
 * A catalog product listed in a specific shop, with optional per-shop overrides.
 *
 * Every override falls back to the catalog value when it is NULL/empty. The
 * display_* accessors are the single place that implements this fallback;
 * they require the `product` relation (eager-load it to avoid N+1).
 */
class ShopProduct extends Pivot
{
    /** @use HasFactory<ShopProductFactory> */
    use HasFactory;

    protected $table = 'shop_products';

    public $incrementing = true;

    protected $fillable = [
        'shop_id',
        'product_id',
        'price',
        'custom_name',
        'custom_description',
        'custom_thumbnail',
        'stock',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Listings a guest may see: listing active, catalog product not
     * soft-deleted, and the shop publicly visible.
     */
    public function scopePubliclyVisible(Builder $query): void
    {
        $query->where('shop_products.is_active', true)
            ->whereHas('product') // SoftDeletes scope excludes trashed products
            ->whereHas('shop', fn (Builder $q) => $q->publiclyVisible());
    }

    protected function displayName(): Attribute
    {
        return Attribute::get(fn () => filled($this->custom_name) ? $this->custom_name : $this->product?->name);
    }

    protected function displayDescription(): Attribute
    {
        return Attribute::get(fn () => filled($this->custom_description) ? $this->custom_description : $this->product?->description);
    }

    protected function displayPrice(): Attribute
    {
        return Attribute::get(fn () => $this->price ?? $this->product?->base_price);
    }

    protected function displayThumbnailUrl(): Attribute
    {
        return Attribute::get(fn () => $this->custom_thumbnail
            ? Storage::disk('public')->url($this->custom_thumbnail)
            : $this->product?->thumbnail_url);
    }

    protected function customThumbnailUrl(): Attribute
    {
        return Attribute::get(fn () => $this->custom_thumbnail ? Storage::disk('public')->url($this->custom_thumbnail) : null);
    }

    public function isInStock(): bool
    {
        return $this->stock > 0;
    }
}
