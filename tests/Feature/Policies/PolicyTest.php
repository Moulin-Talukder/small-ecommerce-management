<?php

namespace Tests\Feature\Policies;

use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $owner;

    private User $otherOwner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->owner = User::factory()->shopOwner()->create();
        $this->otherOwner = User::factory()->shopOwner()->create();
    }

    public function test_only_admin_manages_shop_owner_accounts_and_never_admins(): void
    {
        $this->assertTrue($this->admin->can('viewAny', User::class));
        $this->assertTrue($this->admin->can('create', User::class));
        $this->assertTrue($this->admin->can('update', $this->owner));
        $this->assertTrue($this->admin->can('delete', $this->owner));
        $this->assertFalse($this->admin->can('update', $this->admin));
        $this->assertFalse($this->admin->can('delete', User::factory()->admin()->create()));

        $this->assertFalse($this->owner->can('viewAny', User::class));
        $this->assertFalse($this->owner->can('update', $this->otherOwner));
    }

    public function test_only_admin_manages_shops(): void
    {
        $shop = Shop::factory()->for($this->owner, 'owner')->create();

        foreach (['update', 'delete'] as $ability) {
            $this->assertTrue($this->admin->can($ability, $shop));
            $this->assertFalse($this->owner->can($ability, $shop));
        }
        $this->assertTrue($this->admin->can('create', Shop::class));
        $this->assertFalse($this->owner->can('create', Shop::class));
    }

    public function test_only_the_shop_owner_manages_its_listings(): void
    {
        $shop = Shop::factory()->for($this->owner, 'owner')->create();
        $listing = ShopProduct::factory()->for($shop)->create();

        $this->assertTrue($this->owner->can('manageListings', $shop));
        $this->assertTrue($this->owner->can('update', $listing));
        $this->assertTrue($this->owner->can('delete', $listing));

        $this->assertFalse($this->otherOwner->can('manageListings', $shop));
        $this->assertFalse($this->otherOwner->can('update', $listing));
        $this->assertFalse($this->admin->can('manageListings', $shop));
    }

    public function test_catalog_products_are_editable_by_admin_and_their_creator_only(): void
    {
        $own = Product::factory()->create(['created_by' => $this->owner->id]);
        $others = Product::factory()->create(['created_by' => $this->otherOwner->id]);
        $adminMade = Product::factory()->create(['created_by' => $this->admin->id]);

        $this->assertTrue($this->owner->can('viewAny', Product::class));
        $this->assertTrue($this->owner->can('create', Product::class));

        $this->assertTrue($this->owner->can('update', $own));
        $this->assertTrue($this->owner->can('delete', $own));
        $this->assertFalse($this->owner->can('update', $others));
        $this->assertFalse($this->owner->can('delete', $adminMade));

        $this->assertTrue($this->admin->can('update', $others));
        $this->assertTrue($this->admin->can('delete', $own));
    }
}
