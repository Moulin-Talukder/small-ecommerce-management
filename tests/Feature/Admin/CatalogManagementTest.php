<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CatalogManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->admin = User::factory()->admin()->create();
        $this->owner = User::factory()->shopOwner()->create(['name' => 'Olivia Owner']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ceramic Mug',
            'base_price' => '12.50',
            'sku' => 'MUG-001',
            'description' => 'A sturdy mug.',
        ], $overrides);
    }

    public function test_index_shows_creator_and_listing_count(): void
    {
        $product = Product::factory()->create(['created_by' => $this->owner->id]);
        ShopProduct::factory()->count(2)->for($product)->create();

        $this->actingAs($this->admin)->get(route('admin.products.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Products/Index')
                ->has('products.data', 1)
                ->where('products.data.0.shops_count', 2)
                ->where('products.data.0.creator.name', 'Olivia Owner'));
    }

    public function test_index_filters_by_creator_and_listing_status(): void
    {
        $byAdmin = Product::factory()->create(['created_by' => $this->admin->id]);
        $byOwner = Product::factory()->create(['created_by' => $this->owner->id]);
        $byOther = Product::factory()->create(['created_by' => User::factory()->shopOwner()]);
        ShopProduct::factory()->for($byOwner)->create();

        $ids = fn (array $query) => collect($this->get(route('admin.products.index', $query))
            ->viewData('page')['props']['products']['data'])->pluck('id')->sort()->values()->all();

        $this->actingAs($this->admin);
        $this->assertSame([$byAdmin->id], $ids(['creator' => 'admin']));
        $this->assertSame([$byOwner->id, $byOther->id], $ids(['creator' => 'owners']));
        $this->assertSame([$byOwner->id], $ids(['creator' => (string) $this->owner->id]));
        $this->assertSame([$byOwner->id], $ids(['listing' => 'listed']));
        $this->assertSame([$byAdmin->id, $byOther->id], $ids(['listing' => 'unlisted']));
        $this->assertSame([$byOwner->id], $ids(['creator' => 'owners', 'listing' => 'listed']));
    }

    public function test_admin_can_create_a_product_with_a_thumbnail(): void
    {
        $this->actingAs($this->admin)->post(route('admin.products.store'), $this->payload([
            'thumbnail' => $this->fakeImage('mug.jpg'),
            'created_by' => $this->owner->id, // ignored: creator is the logged-in user
        ]))->assertRedirect(route('admin.products.index'))->assertSessionHas('success');

        $product = Product::firstOrFail();
        $this->assertSame('ceramic-mug', $product->slug);
        $this->assertSame('12.50', $product->base_price);
        $this->assertSame($this->admin->id, $product->created_by);
        Storage::disk('public')->assertExists($product->thumbnail);
    }

    public function test_product_validation(): void
    {
        Product::factory()->create(['sku' => 'TAKEN']);

        $this->actingAs($this->admin)->post(route('admin.products.store'), $this->payload([
            'name' => '',
            'base_price' => '-1',
            'sku' => 'TAKEN',
            'thumbnail' => UploadedFile::fake()->create('file.pdf', 10, 'application/pdf'),
        ]))->assertSessionHasErrors(['name', 'base_price', 'sku', 'thumbnail']);

        $this->post(route('admin.products.store'), $this->payload(['base_price' => '1.999']))
            ->assertSessionHasErrors('base_price');
    }

    public function test_sku_of_a_soft_deleted_product_is_still_reserved(): void
    {
        Product::factory()->create(['sku' => 'OLD-1'])->delete();

        $this->actingAs($this->admin)
            ->post(route('admin.products.store'), $this->payload(['sku' => 'OLD-1']))
            ->assertSessionHasErrors('sku');
    }

    public function test_products_without_sku_can_coexist(): void
    {
        $this->actingAs($this->admin);
        $this->post(route('admin.products.store'), $this->payload(['sku' => '']))->assertSessionHasNoErrors();
        $this->post(route('admin.products.store'), $this->payload(['sku' => null]))->assertSessionHasNoErrors();

        $this->assertSame(2, Product::whereNull('sku')->count());
    }

    public function test_admin_can_edit_any_product_and_replace_its_thumbnail(): void
    {
        $old = $this->fakeImage('old.jpg')->store('products', 'public');
        $product = Product::factory()->create(['created_by' => $this->owner->id, 'thumbnail' => $old, 'sku' => 'MUG-001']);

        $this->actingAs($this->admin)->get(route('admin.products.edit', $product))->assertOk();

        $this->put(route('admin.products.update', $product), $this->payload([
            'name' => 'Big Mug',
            'base_price' => '15',
            'thumbnail' => $this->fakeImage('new.jpg'),
        ]))->assertRedirect(route('admin.products.index'));

        $product->refresh();
        $this->assertSame('Big Mug', $product->name);
        $this->assertSame('15.00', $product->base_price);
        $this->assertSame($this->owner->id, $product->created_by); // creator unchanged
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($product->thumbnail);
    }

    public function test_admin_can_remove_a_thumbnail(): void
    {
        $old = $this->fakeImage('old.jpg')->store('products', 'public');
        $product = Product::factory()->create(['thumbnail' => $old]);

        $this->actingAs($this->admin)->put(route('admin.products.update', $product), $this->payload([
            'sku' => $product->sku,
            'remove_thumbnail' => true,
        ]));

        $this->assertNull($product->fresh()->thumbnail);
        Storage::disk('public')->assertMissing($old);
    }

    public function test_admin_can_always_soft_delete_even_when_listed_in_shops(): void
    {
        $product = Product::factory()->create(['created_by' => $this->owner->id]);
        $listing = ShopProduct::factory()->for($product)->create();

        $this->actingAs($this->admin)->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted($product);
        $this->assertModelExists($listing);
        $this->assertFalse(ShopProduct::publiclyVisible()->whereKey($listing->id)->exists());
    }

    public function test_shop_owners_cannot_use_admin_catalog_routes(): void
    {
        $product = Product::factory()->create(['created_by' => $this->owner->id]);

        $this->actingAs($this->owner);
        $this->get(route('admin.products.index'))->assertForbidden();
        $this->delete(route('admin.products.destroy', $product))->assertForbidden();
        $this->assertNotSoftDeleted($product);
    }
}
