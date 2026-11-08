<?php

namespace Tests\Feature\Owner;

use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ListingManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Shop $shop;

    private Product $product;

    private ShopProduct $listing;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->owner = User::factory()->shopOwner()->create();
        $this->shop = Shop::factory()->for($this->owner, 'owner')->create(['name' => 'My Shop']);
        $this->product = Product::factory()->create([
            'name' => 'Catalog Mug',
            'base_price' => 10,
            'thumbnail' => 'products/catalog-mug.jpg',
        ]);
        $this->listing = ShopProduct::factory()->for($this->shop)->for($this->product)->create([
            'price' => null, 'stock' => 3, 'is_active' => true,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'price' => '',
            'stock' => 3,
            'custom_name' => '',
            'custom_description' => '',
            'is_active' => true,
        ], $overrides);
    }

    private function update(array $overrides = [])
    {
        return $this->actingAs($this->owner)
            ->put(route('owner.listings.update', [$this->shop, $this->listing]), $this->payload($overrides));
    }

    public function test_index_lists_shop_listings_with_display_values_and_search(): void
    {
        ShopProduct::factory()->for($this->shop)->create(['custom_name' => 'Teapot Deluxe']);
        ShopProduct::factory()->create(); // another shop's listing

        $this->actingAs($this->owner)->get(route('owner.listings.index', $this->shop))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Owner/Listings/Index')
                ->has('listings.data', 2));

        $this->get(route('owner.listings.index', [$this->shop, 'search' => 'catalog mug']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.name', 'Catalog Mug')
                ->where('listings.data.0.price', '10.00')
                ->where('listings.data.0.overrides', []));

        $this->get(route('owner.listings.index', [$this->shop, 'search' => 'teapot']))
            ->assertInertia(fn (Assert $page) => $page->has('listings.data', 1)->where('listings.data.0.name', 'Teapot Deluxe'));
    }

    public function test_index_hides_listings_of_soft_deleted_products(): void
    {
        $this->product->delete();

        $this->actingAs($this->owner)->get(route('owner.listings.index', $this->shop))
            ->assertInertia(fn (Assert $page) => $page->has('listings.data', 0));

        $this->get(route('owner.listings.edit', [$this->shop, $this->listing]))->assertNotFound();
    }

    public function test_edit_page_exposes_catalog_values_for_fallback_hints(): void
    {
        $this->actingAs($this->owner)->get(route('owner.listings.edit', [$this->shop, $this->listing]))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Owner/Listings/Edit')
                ->where('product.name', 'Catalog Mug')
                ->where('product.base_price', '10.00')
                ->where('listing.price', null)
                ->where('listing.custom_thumbnail_url', null));
    }

    public function test_owner_can_set_and_clear_overrides(): void
    {
        $this->update([
            'price' => '8.50',
            'custom_name' => 'House Mug',
            'custom_description' => 'Our favourite',
            'stock' => 20,
            'is_active' => false,
        ])->assertRedirect(route('owner.listings.index', $this->shop))->assertSessionHas('success');

        $listing = $this->listing->fresh()->load('product');
        $this->assertSame('8.50', $listing->price);
        $this->assertSame('House Mug', $listing->display_name);
        $this->assertSame(20, $listing->stock);
        $this->assertFalse($listing->is_active);

        // Clearing the overrides falls back to the catalog.
        $this->update();

        $listing = $this->listing->fresh()->load('product');
        $this->assertNull($listing->price);
        $this->assertNull($listing->custom_name);
        $this->assertSame('Catalog Mug', $listing->display_name);
        $this->assertSame('10.00', $listing->display_price);
    }

    public function test_custom_thumbnail_upload_replace_and_remove_falls_back_to_catalog_image(): void
    {
        $this->update(['custom_thumbnail' => UploadedFile::fake()->image('first.jpg')]);
        $first = $this->listing->fresh()->custom_thumbnail;
        Storage::disk('public')->assertExists($first);

        $this->update(['custom_thumbnail' => UploadedFile::fake()->image('second.jpg')]);
        $second = $this->listing->fresh()->custom_thumbnail;
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);

        $this->update(['remove_custom_thumbnail' => true]);
        $listing = $this->listing->fresh()->load('product');
        $this->assertNull($listing->custom_thumbnail);
        Storage::disk('public')->assertMissing($second);
        $this->assertStringEndsWith('/storage/products/catalog-mug.jpg', $listing->display_thumbnail_url);
    }

    public function test_update_validation(): void
    {
        $this->update(['price' => 'abc', 'stock' => -2])->assertSessionHasErrors(['price', 'stock']);
    }

    public function test_owner_can_toggle_visibility(): void
    {
        $this->actingAs($this->owner)->patch(route('owner.listings.toggle', [$this->shop, $this->listing]));
        $this->assertFalse($this->listing->fresh()->is_active);

        $this->patch(route('owner.listings.toggle', [$this->shop, $this->listing]));
        $this->assertTrue($this->listing->fresh()->is_active);
    }

    public function test_removing_a_listing_keeps_the_catalog_product_and_deletes_the_custom_thumbnail(): void
    {
        $thumb = UploadedFile::fake()->image('t.jpg')->store('shop-products', 'public');
        $this->listing->update(['custom_thumbnail' => $thumb]);

        $this->actingAs($this->owner)->delete(route('owner.listings.destroy', [$this->shop, $this->listing]))
            ->assertSessionHas('success');

        $this->assertModelMissing($this->listing);
        $this->assertNotSoftDeleted($this->product);
        Storage::disk('public')->assertMissing($thumb);
    }

    public function test_other_owners_cannot_manage_the_listings(): void
    {
        $intruder = User::factory()->shopOwner()->create();

        $this->actingAs($intruder);
        $this->get(route('owner.listings.index', $this->shop))->assertForbidden();
        $this->get(route('owner.listings.edit', [$this->shop, $this->listing]))->assertForbidden();
        $this->put(route('owner.listings.update', [$this->shop, $this->listing]), $this->payload(['price' => '0']))->assertForbidden();
        $this->patch(route('owner.listings.toggle', [$this->shop, $this->listing]))->assertForbidden();
        $this->delete(route('owner.listings.destroy', [$this->shop, $this->listing]))->assertForbidden();

        $this->assertModelExists($this->listing);
        $this->assertNull($this->listing->fresh()->price);
    }

    public function test_listing_must_belong_to_the_shop_in_the_url(): void
    {
        $ownSecondShop = Shop::factory()->for($this->owner, 'owner')->create();

        // Listing of shop A addressed through shop B → 404 (scoped binding).
        $this->actingAs($this->owner)
            ->get(route('owner.listings.edit', [$ownSecondShop, $this->listing]))
            ->assertNotFound();

        $this->delete(route('owner.listings.destroy', [$ownSecondShop, $this->listing]))->assertNotFound();
        $this->assertModelExists($this->listing);
    }
}
