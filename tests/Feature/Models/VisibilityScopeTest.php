<?php

namespace Tests\Feature\Models;

use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisibilityScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_active_shops_of_active_owners_are_public(): void
    {
        $visible = Shop::factory()->create();
        Shop::factory()->inactive()->create();
        Shop::factory()->for(User::factory()->shopOwner()->inactive(), 'owner')->create();

        $this->assertEquals([$visible->id], Shop::publiclyVisible()->pluck('id')->all());
    }

    public function test_only_active_listings_of_live_products_in_public_shops_are_visible(): void
    {
        $shop = Shop::factory()->create();

        $visible = ShopProduct::factory()->for($shop)->create();
        ShopProduct::factory()->for($shop)->inactive()->create();

        $trashed = ShopProduct::factory()->for($shop)->create();
        $trashed->product->delete();

        ShopProduct::factory()->for(Shop::factory()->inactive())->create();

        $this->assertEquals([$visible->id], ShopProduct::publiclyVisible()->pluck('id')->all());
    }

    public function test_unassigned_products_have_no_public_listing(): void
    {
        $unassigned = Product::factory()->create();
        ShopProduct::factory()->create();

        $this->assertFalse(
            ShopProduct::publiclyVisible()->where('product_id', $unassigned->id)->exists()
        );
    }

    public function test_listed_in_other_owners_shops_detection(): void
    {
        $owner = User::factory()->shopOwner()->create();
        $other = User::factory()->shopOwner()->create();
        $product = Product::factory()->create(['created_by' => $owner->id]);

        ShopProduct::factory()->for($product)->for(Shop::factory()->for($owner, 'owner'))->create();
        $this->assertFalse($product->isListedInShopsNotOwnedBy($owner));

        ShopProduct::factory()->for($product)->for(Shop::factory()->for($other, 'owner'))->create();
        $this->assertTrue($product->isListedInShopsNotOwnedBy($owner));
    }
}
