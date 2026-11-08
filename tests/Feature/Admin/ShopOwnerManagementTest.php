<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ShopOwnerManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_index_lists_only_shop_owners_and_supports_search_and_status_filter(): void
    {
        User::factory()->shopOwner()->create(['name' => 'Alice Active']);
        User::factory()->shopOwner()->inactive()->create(['name' => 'Ian Inactive']);

        $this->actingAs($this->admin)->get(route('admin.shop-owners.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShopOwners/Index')
                ->has('owners.data', 2)
                ->missing('owners.data.0.password'));

        $this->get(route('admin.shop-owners.index', ['search' => 'alice']))
            ->assertInertia(fn (Assert $page) => $page->has('owners.data', 1)->where('owners.data.0.name', 'Alice Active'));

        $this->get(route('admin.shop-owners.index', ['status' => 'inactive']))
            ->assertInertia(fn (Assert $page) => $page->has('owners.data', 1)->where('owners.data.0.name', 'Ian Inactive'));
    }

    public function test_admin_can_create_a_shop_owner(): void
    {
        $this->actingAs($this->admin)->post(route('admin.shop-owners.store'), [
            'name' => 'New Owner',
            'email' => 'new@example.com',
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
            'is_active' => true,
            'role' => 'admin', // must be ignored
        ])->assertRedirect(route('admin.shop-owners.index'))->assertSessionHas('success');

        $owner = User::where('email', 'new@example.com')->firstOrFail();
        $this->assertSame(Role::ShopOwner, $owner->role);
        $this->assertTrue($owner->is_active);
        $this->assertTrue(Hash::check('secret-pass', $owner->password));
    }

    public function test_email_is_normalised_to_lowercase(): void
    {
        $this->actingAs($this->admin)->post(route('admin.shop-owners.store'), [
            'name' => 'Mixed Case',
            'email' => '  Mixed.Case@Example.COM ',
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
            'is_active' => true,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'mixed.case@example.com']);
    }

    public function test_create_validates_input(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($this->admin)->post(route('admin.shop-owners.store'), [
            'name' => '',
            'email' => 'taken@example.com',
            'password' => 'short',
            'password_confirmation' => 'different',
            'is_active' => true,
        ])->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_admin_can_update_a_shop_owner(): void
    {
        $owner = User::factory()->shopOwner()->create();

        $this->actingAs($this->admin)->put(route('admin.shop-owners.update', $owner), [
            'name' => 'Renamed',
            'email' => $owner->email, // unchanged email passes the unique rule
            'is_active' => false,
        ])->assertRedirect(route('admin.shop-owners.index'));

        $owner->refresh();
        $this->assertSame('Renamed', $owner->name);
        $this->assertFalse($owner->is_active);
    }

    public function test_admin_can_reset_a_shop_owner_password(): void
    {
        $owner = User::factory()->shopOwner()->create();

        $this->actingAs($this->admin)->put(route('admin.shop-owners.password', $owner), [
            'password' => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])->assertSessionHas('success');

        $this->assertTrue(Hash::check('brand-new-pass', $owner->fresh()->password));
    }

    public function test_admin_can_toggle_active_state(): void
    {
        $owner = User::factory()->shopOwner()->create();

        $this->actingAs($this->admin)->patch(route('admin.shop-owners.toggle-active', $owner));
        $this->assertFalse($owner->fresh()->is_active);

        $this->patch(route('admin.shop-owners.toggle-active', $owner));
        $this->assertTrue($owner->fresh()->is_active);
    }

    public function test_admin_can_delete_owner_without_shops_and_their_products_stay_in_catalog(): void
    {
        $owner = User::factory()->shopOwner()->create();
        $product = Product::factory()->create(['created_by' => $owner->id]);

        $this->actingAs($this->admin)->delete(route('admin.shop-owners.destroy', $owner))
            ->assertRedirect(route('admin.shop-owners.index'))
            ->assertSessionHas('success');

        $this->assertModelMissing($owner);
        $this->assertNull($product->fresh()->created_by);
    }

    public function test_deleting_an_owner_who_still_owns_shops_is_blocked(): void
    {
        $owner = User::factory()->shopOwner()->create();
        Shop::factory()->for($owner, 'owner')->create();

        $this->actingAs($this->admin)
            ->from(route('admin.shop-owners.index'))
            ->delete(route('admin.shop-owners.destroy', $owner))
            ->assertRedirect(route('admin.shop-owners.index'))
            ->assertSessionHas('error');

        $this->assertModelExists($owner);
    }

    public function test_admin_accounts_cannot_be_managed_through_shop_owner_routes(): void
    {
        $otherAdmin = User::factory()->admin()->create();

        $this->actingAs($this->admin);
        $this->get(route('admin.shop-owners.edit', $otherAdmin))->assertForbidden();
        $this->patch(route('admin.shop-owners.toggle-active', $this->admin))->assertForbidden();
        $this->delete(route('admin.shop-owners.destroy', $otherAdmin))->assertForbidden();
        $this->put(route('admin.shop-owners.password', $otherAdmin), [
            'password' => 'whatever-123', 'password_confirmation' => 'whatever-123',
        ])->assertForbidden();
    }

    public function test_shop_owners_cannot_manage_accounts(): void
    {
        $owner = User::factory()->shopOwner()->create();
        $other = User::factory()->shopOwner()->create();

        $this->actingAs($owner);
        $this->get(route('admin.shop-owners.index'))->assertForbidden();
        $this->patch(route('admin.shop-owners.toggle-active', $other))->assertForbidden();
        $this->delete(route('admin.shop-owners.destroy', $other))->assertForbidden();
        $this->assertTrue($other->fresh()->is_active);
    }
}
