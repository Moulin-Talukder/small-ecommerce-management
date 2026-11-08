<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use Database\Factories\ShopFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Shop extends Model
{
    /** @use HasFactory<ShopFactory> */
    use HasFactory, HasUniqueSlug;

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'description',
        'logo',
        'is_active',
    ];

    protected $appends = ['logo_url'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function shopProducts(): HasMany
    {
        return $this->hasMany(ShopProduct::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'shop_products')
            ->using(ShopProduct::class)
            ->withPivot(['id', 'price', 'custom_name', 'custom_description', 'custom_thumbnail', 'stock', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Shops a guest may see: the shop is active AND its owner account is active.
     */
    public function scopePubliclyVisible(Builder $query): void
    {
        $query->where('shops.is_active', true)
            ->whereHas('owner', fn (Builder $q) => $q->where('is_active', true));
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->owner_id === $user->id;
    }

    protected function logoUrl(): Attribute
    {
        return Attribute::get(fn () => $this->logo ? Storage::disk('public')->url($this->logo) : null);
    }
}
