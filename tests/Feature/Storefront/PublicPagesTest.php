<?php

namespace Tests\Feature\Storefront;

use App\Models\Shop;
use App\Models\ShopProduct;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

class PublicPagesTest extends StorefrontTestCase
{
    public function test_home_lists_only_public_shops_with_visible_product_counts(): void
    {
        ShopProduct::factory()->for($this->shop)->inactive()->create(); // hidden: not counted
        Shop::factory()->inactive()->create(['name' => 'Closed Shop']);
        Shop::factory()->for(User::factory()->shopOwner()->inactive(), 'owner')->create(['name' => 'Owner Gone']);

        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Storefront/Shops/Index')
            ->has('shops.data', 1)
            ->where('shops.data.0.slug', $this->shop->slug)
            ->where('shops.data.0.products_count', 1)
            ->missing('shops.data.0.owner_id')
            ->missing('shops.data.0.is_active'));
    }

    public function test_home_has_an_empty_state_when_no_shops_are_public(): void
    {
        $this->shop->update(['is_active' => false]);

        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page->has('shops.data', 0)->where('shops.total', 0));
    }

    public function test_shop_page_shows_only_its_visible_listings_with_display_values(): void
    {
        ShopProduct::factory()->for($this->shop)->create(['custom_name' => 'Zebra Teapot', 'price' => 30]);
        ShopProduct::factory()->for($this->shop)->inactive()->create(['custom_name' => 'Hidden Thing']);
        ShopProduct::factory()->create(['custom_name' => 'Other Shop Item']);

        $this->get(route('storefront.shops.show', $this->shop->slug))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Storefront/Shops/Show')
                ->where('shop.name', 'Corner Store')
                ->has('listings.data', 2)
                ->where('listings.data.0.name', 'Ceramic Mug') // ordered by display name
                ->where('listings.data.0.price', '10.00')
                ->where('listings.data.1.name', 'Zebra Teapot')
                ->where('listings.data.1.price', '30.00'));
    }

    public function test_shop_page_search_matches_display_and_catalog_names(): void
    {
        ShopProduct::factory()->for($this->shop)->create(['custom_name' => 'Zebra Teapot']);

        $names = fn (string $q) => collect($this->get(route('storefront.shops.show', [$this->shop->slug, 'search' => $q]))
            ->viewData('page')['props']['listings']['data'])->pluck('name')->all();

        $this->assertSame(['Zebra Teapot'], $names('teapot'));
        $this->assertSame(['Ceramic Mug'], $names('MUG'));
        $this->assertSame(['Ceramic Mug'], $names('catalog description'));
        $this->assertSame([], $names('nothing-matches'));
    }

    public function test_shop_page_paginates(): void
    {
        ShopProduct::factory()->count(13)->for($this->shop)->create();

        $this->get(route('storefront.shops.show', $this->shop->slug))
            ->assertInertia(fn (Assert $page) => $page->has('listings.data', 12)->where('listings.total', 14)->where('listings.last_page', 2));
    }

    public function test_product_page_shows_shop_specific_values_stock_and_other_products(): void
    {
        $this->listing->update(['price' => 8.5, 'custom_name' => 'House Mug', 'stock' => 0]);
        ShopProduct::factory()->count(5)->for($this->shop)->create();

        $this->get($this->productUrl())->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Storefront/Products/Show')
            ->where('shop.slug', $this->shop->slug)
            ->where('product.name', 'House Mug')
            ->where('product.price', '8.50')
            ->where('product.description', 'Catalog description of the mug') // fallback
            ->where('product.in_stock', false)
            ->has('others', 4)
            ->missing('product.base_price'));
    }

    public function test_product_page_marks_in_stock(): void
    {
        $this->get($this->productUrl())->assertInertia(fn (Assert $page) => $page->where('product.in_stock', true)->where('product.stock', 5));
    }

    public function test_storefront_works_for_logged_in_users_too(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/')->assertOk();
    }
}
