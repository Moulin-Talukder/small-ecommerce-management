<?php

namespace Tests\Feature\Owner;

use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Editing a catalog product later (by admin or by its creator) must not touch
 * per-shop overrides, while non-overridden fields follow the new catalog value.
 */
class CatalogChangesKeepOverridesTest extends TestCase
{
    use RefreshDatabase;

    private User $creator;

    private Product $product;

    private ShopProduct $overridden;

    private ShopProduct $plain;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->creator = User::factory()->shopOwner()->create();
        $this->product = Product::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original description',
            'base_price' => 10,
            'sku' => 'KEEP-1',
            'created_by' => $this->creator->id,
        ]);

        // Listed by another owner with a custom name + price, description NOT overridden.
        $this->overridden = ShopProduct::factory()->for($this->product)->for(Shop::factory())->create([
            'price' => 7.50,
            'custom_name' => 'Shop Name',
            'custom_description' => null,
        ]);

        // Listed by the creator without any overrides.
        $this->plain = ShopProduct::factory()->for($this->product)
            ->for(Shop::factory()->for($this->creator, 'owner'))->create();
    }

    private function catalogChange(): array
    {
        return [
            'name' => 'New Catalog Name',
            'description' => 'New catalog description',
            'base_price' => '12.00',
            'sku' => 'KEEP-1',
        ];
    }

    private function assertOverridesKeptAndFallbacksUpdated(): void
    {
        $overridden = $this->overridden->fresh()->load('product');
        $plain = $this->plain->fresh()->load('product');

        // Stored overrides untouched.
        $this->assertSame('7.50', $overridden->price);
        $this->assertSame('Shop Name', $overridden->custom_name);

        // Display values: overrides win, the rest follows the new catalog values.
        $this->assertSame('Shop Name', $overridden->display_name);
        $this->assertSame('7.50', $overridden->display_price);
        $this->assertSame('New catalog description', $overridden->display_description);

        $this->assertSame('New Catalog Name', $plain->display_name);
        $this->assertSame('12.00', $plain->display_price);
        $this->assertSame('New catalog description', $plain->display_description);
    }

    public function test_admin_edit_keeps_overrides(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.products.update', $this->product), $this->catalogChange())
            ->assertSessionHasNoErrors();

        $this->assertOverridesKeptAndFallbacksUpdated();
    }

    public function test_creator_edit_keeps_overrides(): void
    {
        $this->actingAs($this->creator)
            ->put(route('owner.products.update', $this->product), $this->catalogChange())
            ->assertSessionHasNoErrors();

        $this->assertOverridesKeptAndFallbacksUpdated();
    }
}
