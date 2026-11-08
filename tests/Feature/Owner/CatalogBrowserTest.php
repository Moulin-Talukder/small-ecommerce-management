<?php

namespace Tests\Feature\Owner;

use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CatalogBrowserTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Shop $shopA;

    private Shop $shopB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->shopOwner()->create();
        $this->shopA = Shop::factory()->for($this->owner, 'owner')->create(['name' => 'A Shop']);
        $this->shopB = Shop::factory()->for($this->owner, 'owner')->create(['name' => 'B Shop']);
    }

    private function catalog(array $query = []): array
    {
        return $this->actingAs($this->owner)->get(route('owner.catalog.index', $query))
            ->assertOk()
            ->viewData('page')['props'];
    }

    public function test_catalog_shows_products_from_admin_and_all_owners(): void
    {
        $byAdmin = Product::factory()->create(['created_by' => User::factory()->admin()]);
        $byOther = Product::factory()->create(['created_by' => User::factory()->shopOwner()]);
        $mine = Product::factory()->create(['created_by' => $this->owner->id]);
        Product::factory()->create()->delete(); // trashed: never shown

        $ids = collect($this->catalog()['products']['data'])->pluck('id')->sort()->values()->all();
        $this->assertSame([$byAdmin->id, $byOther->id, $mine->id], $ids);

        $this->assertSame([$byAdmin->id], collect($this->catalog(['creator' => 'admin'])['products']['data'])->pluck('id')->all());
        $this->assertSame([$byOther->id], collect($this->catalog(['creator' => 'others'])['products']['data'])->pluck('id')->all());
        $this->assertSame([$mine->id], collect($this->catalog(['creator' => 'mine'])['products']['data'])->pluck('id')->all());
    }

    public function test_in_shop_state_follows_the_selected_shop(): void
    {
        $product = Product::factory()->create();
        $listing = ShopProduct::factory()->for($this->shopA)->for($product)->create();

        $this->actingAs($this->owner)->get(route('owner.catalog.index', ['shop' => $this->shopA->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.shop', $this->shopA->id)
                ->where('products.data.0.listing_id', $listing->id));

        $this->get(route('owner.catalog.index', ['shop' => $this->shopB->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.shop', $this->shopB->id)
                ->where('products.data.0.listing_id', null));
    }

    public function test_availability_filter_is_relative_to_the_selected_shop(): void
    {
        $inA = Product::factory()->create();
        $notInA = Product::factory()->create();
        ShopProduct::factory()->for($this->shopA)->for($inA)->create();

        $available = collect($this->catalog(['shop' => $this->shopA->id, 'availability' => 'available'])['products']['data'])->pluck('id')->all();
        $inShop = collect($this->catalog(['shop' => $this->shopA->id, 'availability' => 'in_shop'])['products']['data'])->pluck('id')->all();

        $this->assertSame([$notInA->id], $available);
        $this->assertSame([$inA->id], $inShop);
    }

    public function test_another_owners_shop_cannot_be_selected(): void
    {
        $foreign = Shop::factory()->create();

        $props = $this->catalog(['shop' => $foreign->id]);

        $this->assertSame($this->shopA->id, $props['filters']['shop']); // falls back to own first shop
        $this->assertNotContains($foreign->id, collect($props['shops'])->pluck('id'));
    }

    public function test_owner_without_shops_can_browse(): void
    {
        $lonely = User::factory()->shopOwner()->create();
        Product::factory()->create();

        $this->actingAs($lonely)->get(route('owner.catalog.index'))
            ->assertInertia(fn (Assert $page) => $page->has('shops', 0)->where('filters.shop', '')->has('products.data', 1));
    }
}
