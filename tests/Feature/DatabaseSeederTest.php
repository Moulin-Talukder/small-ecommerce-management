<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopProduct;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_seeds_without_network_and_copies_images(): void
    {
        Storage::fake('public');

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, User::where('role', 'admin')->count());
        $this->assertGreaterThanOrEqual(3, User::shopOwners()->where('is_active', true)->count());
        $this->assertGreaterThanOrEqual(15, Product::count());

        Product::all()->each(fn (Product $p) => Storage::disk('public')->assertExists($p->thumbnail));
        Shop::all()->each(fn (Shop $s) => Storage::disk('public')->assertExists($s->logo));
    }

    public function test_demo_data_contains_the_visibility_edge_cases(): void
    {
        Storage::fake('public');

        $this->seed(DatabaseSeeder::class);

        // Unassigned catalog products exist and have no public listing.
        $unassigned = Product::doesntHave('shopProducts')->pluck('id');
        $this->assertNotEmpty($unassigned);
        $this->assertFalse(ShopProduct::publiclyVisible()->whereIn('product_id', $unassigned)->exists());

        // Custom prices, an inactive listing, an inactive shop and a trashed product.
        $this->assertTrue(ShopProduct::whereNotNull('price')->exists());
        $this->assertTrue(ShopProduct::where('is_active', false)->exists());
        $this->assertTrue(Shop::where('is_active', false)->exists());
        $this->assertTrue(Product::onlyTrashed()->exists());
    }

    public function test_seeding_twice_does_not_fail(): void
    {
        Storage::fake('public');

        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, User::where('email', 'admin@example.com')->count());
    }
}
