<?php

namespace Tests\Feature\Storefront;

use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopProduct;
use Inertia\Testing\AssertableInertia as Assert;

class VisibilityTest extends StorefrontTestCase
{
    public function test_visible_listing_is_reachable(): void
    {
        $this->get(route('storefront.shops.show', $this->shop->slug))->assertOk();
        $this->get($this->productUrl())->assertOk();
    }

    public function test_inactive_shop_returns_404_for_shop_and_product_pages(): void
    {
        $this->shop->update(['is_active' => false]);

        $this->get(route('storefront.shops.show', $this->shop->slug))->assertNotFound();
        $this->get($this->productUrl())->assertNotFound();
    }

    public function test_shop_of_an_inactive_owner_returns_404(): void
    {
        $this->shop->owner->update(['is_active' => false]);

        $this->get(route('storefront.shops.show', $this->shop->slug))->assertNotFound();
        $this->get($this->productUrl())->assertNotFound();
        $this->get('/')->assertInertia(fn (Assert $page) => $page->has('shops.data', 0));
    }

    public function test_hidden_listing_returns_404_and_is_not_on_the_shop_page(): void
    {
        $this->listing->update(['is_active' => false]);

        $this->get($this->productUrl())->assertNotFound();
        $this->get(route('storefront.shops.show', $this->shop->slug))
            ->assertInertia(fn (Assert $page) => $page->has('listings.data', 0));
    }

    public function test_soft_deleted_product_returns_404_and_is_not_listed(): void
    {
        $this->product->delete();

        $this->get($this->productUrl())->assertNotFound();
        $this->get(route('storefront.shops.show', $this->shop->slug))
            ->assertInertia(fn (Assert $page) => $page->has('listings.data', 0));
    }

    public function test_product_not_assigned_to_that_shop_returns_404(): void
    {
        $otherShop = Shop::factory()->create();
        $elsewhere = ShopProduct::factory()->for($otherShop)->create();

        // Exists and is visible in another shop, but not in this one.
        $this->get(route('storefront.products.show', [$otherShop->slug, $elsewhere->product->slug]))->assertOk();
        $this->get($this->productUrl($this->shop, $elsewhere->product))->assertNotFound();
    }

    public function test_unknown_slugs_return_404(): void
    {
        $this->get('/shops/does-not-exist')->assertNotFound();
        $this->get(route('storefront.products.show', [$this->shop->slug, 'nope']))->assertNotFound();
    }

    public function test_unassigned_product_never_appears_in_any_public_response(): void
    {
        $secret = Product::factory()->create([
            'name' => 'Unassigned Secret Gadget',
            'slug' => 'unassigned-secret-gadget',
            'description' => 'Top secret description',
            'sku' => 'SECRET-SKU-1',
        ]);
        // More public data around it, so the pages are not trivially empty.
        ShopProduct::factory()->count(3)->for($this->shop)->create();

        $responses = [
            $this->get('/'),
            $this->get(route('storefront.shops.show', $this->shop->slug)),
            $this->get(route('storefront.shops.show', [$this->shop->slug, 'search' => 'secret'])),
            $this->get($this->productUrl()),
        ];

        foreach ($responses as $response) {
            $response->assertOk()
                ->assertDontSee('Unassigned Secret Gadget')
                ->assertDontSee('unassigned-secret-gadget')
                ->assertDontSee('SECRET-SKU-1')
                ->assertDontSee('Top secret description');
        }

        // And there is no URL that reaches it, in any shop.
        foreach (Shop::all() as $shop) {
            $this->get(route('storefront.products.show', [$shop->slug, $secret->slug]))->assertNotFound();
        }
        $this->get('/products/'.$secret->slug)->assertNotFound();
        $this->get('/products/'.$secret->id)->assertNotFound();
    }
}
