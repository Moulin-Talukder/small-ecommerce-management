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

class OwnProductsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $otherOwner;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->owner = User::factory()->shopOwner()->create();
        $this->otherOwner = User::factory()->shopOwner()->create();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Handmade Soap',
            'base_price' => '6.50',
            'sku' => '',
            'description' => 'Olive oil soap.',
        ], $overrides);
    }

    public function test_owner_created_product_joins_the_shared_catalog(): void
    {
        $this->actingAs($this->owner)->post(route('owner.products.store'), $this->payload([
            'thumbnail' => UploadedFile::fake()->image('soap.jpg'),
        ]))->assertRedirect(route('owner.products.index'));

        $product = Product::firstOrFail();
        $this->assertSame($this->owner->id, $product->created_by);
        Storage::disk('public')->assertExists($product->thumbnail);

        // Visible to other owners in the catalog browser.
        $this->actingAs($this->otherOwner)->get(route('owner.catalog.index'))
            ->assertInertia(fn (Assert $page) => $page->where('products.data.0.id', $product->id)->where('products.data.0.is_mine', false));
    }

    public function test_owner_can_create_and_list_in_one_of_their_shops(): void
    {
        $shop = Shop::factory()->for($this->owner, 'owner')->create();

        $this->actingAs($this->owner)->post(route('owner.products.store'), $this->payload([
            'list_in_shop_id' => $shop->id,
            'stock' => 12,
        ]))->assertSessionHasNoErrors();

        $listing = ShopProduct::firstOrFail();
        $this->assertSame($shop->id, $listing->shop_id);
        $this->assertSame(12, $listing->stock);
        $this->assertNull($listing->price); // follows the catalog price
    }

    public function test_owner_cannot_list_a_new_product_in_someone_elses_shop(): void
    {
        $foreign = Shop::factory()->for($this->otherOwner, 'owner')->create();

        $this->actingAs($this->owner)->post(route('owner.products.store'), $this->payload([
            'list_in_shop_id' => $foreign->id,
            'stock' => 1,
        ]))->assertSessionHasErrors('list_in_shop_id');

        $this->assertSame(0, Product::count());
    }

    public function test_my_products_lists_only_own_products(): void
    {
        $mine = Product::factory()->create(['created_by' => $this->owner->id]);
        Product::factory()->create(['created_by' => $this->otherOwner->id]);

        $this->actingAs($this->owner)->get(route('owner.products.index'))
            ->assertInertia(fn (Assert $page) => $page->has('products.data', 1)->where('products.data.0.id', $mine->id));
    }

    public function test_owner_can_edit_own_product(): void
    {
        $product = Product::factory()->create(['created_by' => $this->owner->id]);

        $this->actingAs($this->owner)->get(route('owner.products.edit', $product))->assertOk();
        $this->put(route('owner.products.update', $product), $this->payload(['name' => 'Renamed Soap']))
            ->assertRedirect(route('owner.products.index'));

        $this->assertSame('Renamed Soap', $product->fresh()->name);
    }

    public function test_owner_cannot_edit_or_delete_products_created_by_others(): void
    {
        $byAdmin = Product::factory()->create(['created_by' => User::factory()->admin()]);
        $byOther = Product::factory()->create(['created_by' => $this->otherOwner->id]);

        $this->actingAs($this->owner);
        foreach ([$byAdmin, $byOther] as $product) {
            $this->get(route('owner.products.edit', $product))->assertForbidden();
            $this->put(route('owner.products.update', $product), $this->payload())->assertForbidden();
            $this->delete(route('owner.products.destroy', $product))->assertForbidden();
            $this->assertNotSoftDeleted($product);
        }
    }

    public function test_owner_can_delete_own_product_listed_only_in_their_own_shops(): void
    {
        $product = Product::factory()->create(['created_by' => $this->owner->id]);
        ShopProduct::factory()->for($product)->for(Shop::factory()->for($this->owner, 'owner'))->create();

        $this->actingAs($this->owner)->delete(route('owner.products.destroy', $product))
            ->assertRedirect(route('owner.products.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted($product);
    }

    public function test_owner_cannot_delete_own_product_listed_in_another_owners_shop(): void
    {
        $product = Product::factory()->create(['created_by' => $this->owner->id]);
        ShopProduct::factory()->for($product)->for(Shop::factory()->for($this->otherOwner, 'owner'))->create();

        $this->actingAs($this->owner)
            ->from(route('owner.products.index'))
            ->delete(route('owner.products.destroy', $product))
            ->assertRedirect(route('owner.products.index'))
            ->assertSessionHas('error', 'This product is listed in other shops, so it cannot be deleted.');

        $this->assertNotSoftDeleted($product);
    }

    public function test_admin_can_still_delete_a_product_listed_in_other_owners_shops(): void
    {
        $product = Product::factory()->create(['created_by' => $this->owner->id]);
        ShopProduct::factory()->for($product)->for(Shop::factory()->for($this->otherOwner, 'owner'))->create();

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.products.destroy', $product))
            ->assertSessionHas('success');

        $this->assertSoftDeleted($product);
    }

    public function test_new_product_can_reuse_the_name_of_a_deleted_one(): void
    {
        $old = Product::factory()->create(['name' => 'Handmade Soap', 'created_by' => $this->owner->id]);
        $this->actingAs($this->owner)->delete(route('owner.products.destroy', $old));

        $this->post(route('owner.products.store'), $this->payload())->assertSessionHasNoErrors();

        $this->assertSame('handmade-soap-2', Product::latest('id')->first()->slug);
    }
}
