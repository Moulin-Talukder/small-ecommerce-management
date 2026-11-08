<?php

namespace Tests\Feature\Admin;

use App\Models\Shop;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ShopManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->admin = User::factory()->admin()->create();
        $this->owner = User::factory()->shopOwner()->create();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Corner Store',
            'description' => 'Friendly neighbourhood shop.',
            'owner_id' => $this->owner->id,
            'is_active' => true,
        ], $overrides);
    }

    public function test_index_lists_shops_with_filters(): void
    {
        Shop::factory()->for($this->owner, 'owner')->create(['name' => 'Alpha Shop']);
        Shop::factory()->inactive()->create(['name' => 'Beta Shop']);

        $this->actingAs($this->admin)->get(route('admin.shops.index'))
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Shops/Index')->has('shops.data', 2));

        $this->get(route('admin.shops.index', ['search' => 'alpha']))
            ->assertInertia(fn (Assert $page) => $page->has('shops.data', 1)->where('shops.data.0.name', 'Alpha Shop'));

        $this->get(route('admin.shops.index', ['status' => 'inactive']))
            ->assertInertia(fn (Assert $page) => $page->has('shops.data', 1)->where('shops.data.0.name', 'Beta Shop'));

        $this->get(route('admin.shops.index', ['owner' => $this->owner->id]))
            ->assertInertia(fn (Assert $page) => $page->has('shops.data', 1)->where('shops.data.0.name', 'Alpha Shop'));
    }

    public function test_admin_can_create_a_shop_with_a_logo(): void
    {
        $this->actingAs($this->admin)->post(route('admin.shops.store'), $this->payload([
            'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
        ]))->assertRedirect(route('admin.shops.index'))->assertSessionHas('success');

        $shop = Shop::firstOrFail();
        $this->assertSame('corner-store', $shop->slug);
        $this->assertTrue($shop->owner->is($this->owner));
        $this->assertNotNull($shop->logo);
        Storage::disk('public')->assertExists($shop->logo);
    }

    public function test_shop_must_be_assigned_to_a_shop_owner(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.shops.store'), $this->payload(['owner_id' => $this->admin->id]))
            ->assertSessionHasErrors('owner_id');

        $this->post(route('admin.shops.store'), $this->payload(['owner_id' => 999]))
            ->assertSessionHasErrors('owner_id');

        $this->assertSame(0, Shop::count());
    }

    public function test_logo_must_be_a_valid_image(): void
    {
        $this->actingAs($this->admin)->post(route('admin.shops.store'), $this->payload([
            'logo' => UploadedFile::fake()->create('logo.pdf', 100, 'application/pdf'),
        ]))->assertSessionHasErrors('logo');

        $this->post(route('admin.shops.store'), $this->payload([
            'logo' => UploadedFile::fake()->image('huge.jpg')->size(5000),
        ]))->assertSessionHasErrors('logo');
    }

    public function test_admin_can_update_a_shop_and_reassign_the_owner(): void
    {
        $shop = Shop::factory()->for($this->owner, 'owner')->create();
        $newOwner = User::factory()->shopOwner()->create();

        $this->actingAs($this->admin)->put(route('admin.shops.update', $shop), $this->payload([
            'name' => 'Renamed Shop',
            'owner_id' => $newOwner->id,
            'is_active' => false,
        ]))->assertRedirect(route('admin.shops.index'));

        $shop->refresh();
        $this->assertSame('Renamed Shop', $shop->name);
        $this->assertSame('renamed-shop', $shop->slug);
        $this->assertTrue($shop->owner->is($newOwner));
        $this->assertFalse($shop->is_active);
    }

    public function test_replacing_the_logo_deletes_the_old_file(): void
    {
        $old = UploadedFile::fake()->image('old.png')->store('shops', 'public');
        $shop = Shop::factory()->for($this->owner, 'owner')->create(['logo' => $old]);

        $this->actingAs($this->admin)->put(route('admin.shops.update', $shop), $this->payload([
            'logo' => UploadedFile::fake()->image('new.png'),
        ]));

        $shop->refresh();
        $this->assertNotSame($old, $shop->logo);
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($shop->logo);
    }

    public function test_updating_without_a_new_logo_keeps_it_and_remove_flag_deletes_it(): void
    {
        $logo = UploadedFile::fake()->image('logo.png')->store('shops', 'public');
        $shop = Shop::factory()->for($this->owner, 'owner')->create(['logo' => $logo]);

        $this->actingAs($this->admin)->put(route('admin.shops.update', $shop), $this->payload());
        $this->assertSame($logo, $shop->fresh()->logo);

        $this->put(route('admin.shops.update', $shop), $this->payload(['remove_logo' => true]));
        $this->assertNull($shop->fresh()->logo);
        Storage::disk('public')->assertMissing($logo);
    }

    public function test_deleting_a_shop_removes_listings_and_image_files(): void
    {
        $logo = UploadedFile::fake()->image('logo.png')->store('shops', 'public');
        $thumb = UploadedFile::fake()->image('thumb.jpg')->store('shop-products', 'public');
        $shop = Shop::factory()->for($this->owner, 'owner')->create(['logo' => $logo]);
        $listing = ShopProduct::factory()->for($shop)->create(['custom_thumbnail' => $thumb]);

        $this->actingAs($this->admin)->delete(route('admin.shops.destroy', $shop))
            ->assertRedirect(route('admin.shops.index'))
            ->assertSessionHas('success');

        $this->assertModelMissing($shop);
        $this->assertModelMissing($listing);
        $this->assertModelExists($listing->product); // catalog untouched
        Storage::disk('public')->assertMissing([$logo, $thumb]);
    }

    public function test_shop_owners_cannot_manage_shops(): void
    {
        $shop = Shop::factory()->for($this->owner, 'owner')->create();

        $this->actingAs($this->owner);
        $this->get(route('admin.shops.index'))->assertForbidden();
        $this->post(route('admin.shops.store'), $this->payload())->assertForbidden();
        $this->put(route('admin.shops.update', $shop), $this->payload(['name' => 'Hacked']))->assertForbidden();
        $this->delete(route('admin.shops.destroy', $shop))->assertForbidden();

        $this->assertSame($shop->name, $shop->fresh()->name);
    }
}
