<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * A product in the shared catalog. Shops sell it through ShopProduct listings.
 */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, HasUniqueSlug, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'base_price',
        'thumbnail',
        'sku',
        'created_by',
    ];

    protected $appends = ['thumbnail_url'];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function shopProducts(): HasMany
    {
        return $this->hasMany(ShopProduct::class);
    }

    public function shops(): BelongsToMany
    {
        return $this->belongsToMany(Shop::class, 'shop_products')
            ->using(ShopProduct::class)
            ->withPivot(['id', 'price', 'custom_name', 'custom_description', 'custom_thumbnail', 'stock', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Portable (MySQL + SQLite) search on name, SKU and description.
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        // No backslash escaping: MySQL and SQLite treat LIKE escapes differently.
        $like = '%'.$term.'%';

        $query->where(function (Builder $q) use ($like) {
            $q->where('products.name', 'like', $like)
                ->orWhere('products.sku', 'like', $like)
                ->orWhere('products.description', 'like', $like);
        });
    }

    public function isCreatedBy(User $user): bool
    {
        return $this->created_by === $user->id;
    }

    /**
     * True when the product is listed in at least one shop that does NOT belong
     * to the given owner. Used to block owners from deleting a product others sell.
     */
    public function isListedInShopsNotOwnedBy(User $owner): bool
    {
        return $this->shopProducts()
            ->whereHas('shop', fn (Builder $q) => $q->where('owner_id', '!=', $owner->id))
            ->exists();
    }

    protected function thumbnailUrl(): Attribute
    {
        return Attribute::get(fn () => $this->thumbnail ? Storage::disk('public')->url($this->thumbnail) : null);
    }
}
