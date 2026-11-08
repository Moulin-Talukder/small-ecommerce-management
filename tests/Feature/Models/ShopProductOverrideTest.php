<?php

namespace Tests\Feature\Models;

use App\Models\Product;
use App\Models\ShopProduct;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopProductOverrideTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_without_overrides_falls_back_to_catalog_values(): void
    {
        $product = Product::factory()->create([
            'name' => 'Catalog Name',
            'description' => 'Catalog description',
            'base_price' => 20,
            'thumbnail' => 'products/catalog.jpg',
        ]);

        $listing = ShopProduct::factory()->for($product)->create()->load('product');

        $this->assertSame('Catalog Name', $listing->display_name);
        $this->assertSame('Catalog description', $listing->display_description);
        $this->assertSame('20.00', $listing->display_price);
        $this->assertStringEndsWith('/storage/products/catalog.jpg', $listing->display_thumbnail_url);
    }

    public function test_listing_overrides_take_precedence(): void
    {
        $product = Product::factory()->create(['base_price' => 20, 'thumbnail' => 'products/catalog.jpg']);

        $listing = ShopProduct::factory()->for($product)->create([
            'price' => 15.5,
            'custom_name' => 'Shop Name',
            'custom_description' => 'Shop description',
            'custom_thumbnail' => 'shop-products/custom.jpg',
        ])->load('product');

        $this->assertSame('Shop Name', $listing->display_name);
        $this->assertSame('Shop description', $listing->display_description);
        $this->assertSame('15.50', $listing->display_price);
        $this->assertStringEndsWith('/storage/shop-products/custom.jpg', $listing->display_thumbnail_url);
    }

    public function test_empty_string_overrides_fall_back(): void
    {
        $product = Product::factory()->create(['name' => 'Catalog Name']);

        $listing = ShopProduct::factory()->for($product)->create(['custom_name' => ''])->load('product');

        $this->assertSame('Catalog Name', $listing->display_name);
    }

    public function test_same_product_cannot_be_listed_twice_in_one_shop(): void
    {
        $listing = ShopProduct::factory()->create();

        $this->expectException(UniqueConstraintViolationException::class);

        ShopProduct::factory()->create([
            'shop_id' => $listing->shop_id,
            'product_id' => $listing->product_id,
        ]);
    }
}
