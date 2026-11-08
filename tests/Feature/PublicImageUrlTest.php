<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Public-disk URLs are root-relative, so images load whatever host/port the
 * app is served on, even when it differs from APP_URL.
 */
class PublicImageUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_image_urls_are_root_relative(): void
    {
        config(['app.url' => 'http://configured-host.test:1234']);

        $product = Product::factory()->create(['thumbnail' => 'products/mug.jpg']);
        $shop = Shop::factory()->create(['logo' => 'shops/logo.png']);
        $listing = ShopProduct::factory()->for($shop)->for($product)->create(['custom_thumbnail' => 'shop-products/custom.jpg']);

        $this->assertSame('/storage/products/mug.jpg', $product->thumbnail_url);
        $this->assertSame('/storage/shops/logo.png', $shop->logo_url);
        $this->assertSame('/storage/shop-products/custom.jpg', $listing->load('product')->display_thumbnail_url);
    }

    public function test_storefront_serves_root_relative_image_urls_on_any_host(): void
    {
        config(['app.url' => 'http://127.0.0.1:8000']);

        $shop = Shop::factory()->create(['logo' => 'shops/logo.png']);
        ShopProduct::factory()->for($shop)->for(Product::factory()->state(['thumbnail' => 'products/mug.jpg']))->create();

        // Request arrives on a completely different host/port.
        $this->get('http://my-shop.test:8080/shops/'.$shop->slug)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('shop.logo_url', '/storage/shops/logo.png')
                ->where('listings.data.0.thumbnail_url', '/storage/products/mug.jpg'))
            ->assertDontSee('127.0.0.1:8000/storage', false);
    }
}
