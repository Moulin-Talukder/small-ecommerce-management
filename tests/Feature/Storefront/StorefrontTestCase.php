<?php

namespace Tests\Feature\Storefront;

use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class StorefrontTestCase extends TestCase
{
    use RefreshDatabase;

    protected Shop $shop;

    protected Product $product;

    protected ShopProduct $listing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shop = Shop::factory()->create(['name' => 'Corner Store', 'description' => 'Friendly shop']);
        $this->product = Product::factory()->create([
            'name' => 'Ceramic Mug',
            'description' => 'Catalog description of the mug',
            'base_price' => 10,
            'thumbnail' => 'products/mug.jpg',
        ]);
        $this->listing = ShopProduct::factory()->for($this->shop)->for($this->product)->create(['stock' => 5]);
    }

    protected function productUrl(?Shop $shop = null, ?Product $product = null): string
    {
        return route('storefront.products.show', [($shop ?? $this->shop)->slug, ($product ?? $this->product)->slug]);
    }
}
