<?php

namespace Tests\Feature\Models;

use App\Models\Product;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UniqueSlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_slug_is_generated_from_name(): void
    {
        $product = Product::factory()->create(['name' => 'Classic Cotton T-Shirt']);

        $this->assertSame('classic-cotton-t-shirt', $product->slug);
    }

    public function test_duplicate_names_get_suffixed_slugs(): void
    {
        $a = Product::factory()->create(['name' => 'Coffee Mug']);
        $b = Product::factory()->create(['name' => 'Coffee Mug']);
        $c = Product::factory()->create(['name' => 'Coffee Mug']);

        $this->assertSame(['coffee-mug', 'coffee-mug-2', 'coffee-mug-3'], [$a->slug, $b->slug, $c->slug]);
    }

    public function test_new_product_with_same_name_as_soft_deleted_one_gets_unique_slug(): void
    {
        $old = Product::factory()->create(['name' => 'Leather Wallet']);
        $old->delete();

        $new = Product::factory()->create(['name' => 'Leather Wallet']);

        $this->assertSoftDeleted($old);
        $this->assertSame('leather-wallet', $old->slug);
        $this->assertSame('leather-wallet-2', $new->slug);
    }

    public function test_renaming_regenerates_slug_but_keeps_it_when_unchanged(): void
    {
        $product = Product::factory()->create(['name' => 'Desk Lamp']);

        $product->update(['description' => 'Updated']);
        $this->assertSame('desk-lamp', $product->fresh()->slug);

        $product->update(['name' => 'Desk Lamp']);
        $this->assertSame('desk-lamp', $product->fresh()->slug);

        $product->update(['name' => 'Floor Lamp']);
        $this->assertSame('floor-lamp', $product->fresh()->slug);
    }

    public function test_shops_get_unique_slugs(): void
    {
        $a = Shop::factory()->create(['name' => 'Corner Store']);
        $b = Shop::factory()->create(['name' => 'Corner Store']);

        $this->assertSame('corner-store', $a->slug);
        $this->assertSame('corner-store-2', $b->slug);
    }

    public function test_name_without_latin_characters_still_gets_a_slug(): void
    {
        $product = Product::factory()->create(['name' => '!!!']);

        $this->assertSame('product', $product->slug);
    }
}
