<?php

namespace Tests\Feature\Owner;

use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AddToShopTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Shop $shop;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->owner = User::factory()->shopOwner()->create();
        $this->shop = Shop::factory()->for($this->owner, 'owner')->create(['name' => 'My Shop']);
        $this->product = Product::factory()->create(['base_price' => 20, 'created_by' => User::factory()->admin()]);
    }

    private function add(array $overrides = [], ?Shop $shop = null)
    {
        return $this->actingAs($this->owner)
            ->from(route('owner.catalog.index'))
            ->post(route('owner.listings.store', $shop ?? $this->shop), array_merge([
                'product_id' => $this->product->id,
                'price' => '18.00',
                'stock' => 5,
                'is_active' => true,
            ], $overrides));
    }

    public function test_owner_can_add_a_catalog_product_with_overrides(): void
    {
        $this->add([
            'custom_name' => 'Shop Special',
            'custom_description' => 'Only here',
            'custom_thumbnail' => $this->fakeImage('special.jpg'),
        ])->assertRedirect(route('owner.catalog.index'))->assertSessionHas('success', '"Shop Special" added to My Shop.');

        $listing = ShopProduct::firstOrFail();
        $this->assertSame($this->shop->id, $listing->shop_id);
        $this->assertSame('18.00', $listing->price);
        $this->assertSame(5, $listing->stock);
        $this->assertSame('Shop Special', $listing->custom_name);
        Storage::disk('public')->assertExists($listing->custom_thumbnail);
    }

    public function test_empty_price_and_overrides_are_stored_as_null_to_follow_the_catalog(): void
    {
        $this->add(['price' => '', 'custom_name' => '', 'custom_description' => ''])->assertSessionHasNoErrors();

        $listing = ShopProduct::firstOrFail()->load('product');
        $this->assertNull($listing->price);
        $this->assertNull($listing->custom_name);
        $this->assertSame('20.00', $listing->display_price);
    }

    public function test_adding_the_same_product_twice_gives_a_friendly_validation_error(): void
    {
        $this->add()->assertSessionHasNoErrors();

        $this->add()->assertRedirect(route('owner.catalog.index'))
            ->assertSessionHasErrors(['product_id' => 'This product is already in My Shop.']);

        $this->assertSame(1, ShopProduct::count());
    }

    public function test_the_same_product_can_be_added_to_two_different_shops(): void
    {
        $second = Shop::factory()->for($this->owner, 'owner')->create();

        $this->add()->assertSessionHasNoErrors();
        $this->add([], $second)->assertSessionHasNoErrors();

        $this->assertSame(2, ShopProduct::count());
    }

    public function test_listing_validation(): void
    {
        $this->add(['price' => '-5', 'stock' => -1, 'custom_thumbnail' => UploadedFile::fake()->create('x.pdf', 5, 'application/pdf')])
            ->assertSessionHasErrors(['price', 'stock', 'custom_thumbnail']);

        $this->add(['stock' => ''])->assertSessionHasErrors('stock');
        $this->assertSame(0, ShopProduct::count());
    }

    public function test_soft_deleted_products_cannot_be_added(): void
    {
        $this->product->delete();

        $this->add()->assertSessionHasErrors('product_id');
    }

    public function test_owner_cannot_add_products_to_another_owners_shop(): void
    {
        $foreign = Shop::factory()->create();

        $this->add([], $foreign)->assertForbidden();
        $this->assertSame(0, ShopProduct::count());
    }

    public function test_admin_cannot_use_owner_listing_routes(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('owner.listings.store', $this->shop), ['product_id' => $this->product->id, 'stock' => 1, 'is_active' => true])
            ->assertForbidden();
    }
}
