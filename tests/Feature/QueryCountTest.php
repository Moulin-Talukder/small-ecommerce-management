<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * N+1 guard: the number of queries of each main page must not grow with the
 * amount of data on the page (eager loading / withCount everywhere).
 */
class QueryCountTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $owner;

    private Shop $shop;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->owner = User::factory()->shopOwner()->create();
        $this->shop = Shop::factory()->for($this->owner, 'owner')->create();
        $this->addData(2);
    }

    /** Adds products (by various creators) listed in the owner's shop and in other shops. */
    private function addData(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $creator = $i % 2 ? $this->admin : User::factory()->shopOwner()->create();
            $product = Product::factory()->create(['created_by' => $creator->id, 'thumbnail' => 'products/x.jpg']);
            ShopProduct::factory()->for($this->shop)->for($product)->create(['custom_name' => $i % 3 ? null : "Custom {$i}"]);
            ShopProduct::factory()->for($product)->for(Shop::factory()->for($creator->isAdmin() ? $this->owner : $creator, 'owner'))->create();
        }
    }

    private function queriesFor(?User $user, string $url): int
    {
        $user ? $this->actingAs($user) : auth()->logout();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get($url)->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    public function test_query_count_does_not_grow_with_data(): void
    {
        $product = Product::first();
        $listing = $this->shop->shopProducts()->first();

        $pages = [
            'storefront home' => [null, fn () => route('storefront.shops.index')],
            'storefront shop' => [null, fn () => route('storefront.shops.show', $this->shop->slug)],
            'storefront product' => [null, fn () => route('storefront.products.show', [$this->shop->slug, $product->slug])],
            'admin dashboard' => [$this->admin, fn () => route('admin.dashboard')],
            'admin shops' => [$this->admin, fn () => route('admin.shops.index')],
            'admin products' => [$this->admin, fn () => route('admin.products.index')],
            'admin product edit' => [$this->admin, fn () => route('admin.products.edit', $product)],
            'admin shop owners' => [$this->admin, fn () => route('admin.shop-owners.index')],
            'owner dashboard' => [$this->owner, fn () => route('owner.dashboard')],
            'owner catalog' => [$this->owner, fn () => route('owner.catalog.index')],
            'owner listings' => [$this->owner, fn () => route('owner.listings.index', $this->shop)],
            'owner listing edit' => [$this->owner, fn () => route('owner.listings.edit', [$this->shop, $listing])],
        ];

        $before = [];
        foreach ($pages as $name => [$user, $url]) {
            $before[$name] = $this->queriesFor($user, $url());
        }

        $this->addData(8); // 5x the data (still within one page of results)

        foreach ($pages as $name => [$user, $url]) {
            $after = $this->queriesFor($user, $url());
            $this->assertSame($before[$name], $after, "{$name}: {$before[$name]} queries before, {$after} after adding data (N+1?)");
            $this->assertLessThanOrEqual(15, $after, "{$name} runs {$after} queries");
        }
    }
}
