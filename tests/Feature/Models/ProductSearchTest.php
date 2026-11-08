<?php

namespace Tests\Feature\Models;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_matches_name_sku_and_description_case_insensitively(): void
    {
        $mug = Product::factory()->create(['name' => 'Ceramic Coffee Mug', 'sku' => 'MUG-1', 'description' => 'Stoneware']);
        $press = Product::factory()->create(['name' => 'French Press', 'sku' => 'FRP-2', 'description' => 'Glass coffee maker']);
        Product::factory()->create(['name' => 'Wool Beanie', 'sku' => 'BNE-3', 'description' => 'Warm hat']);

        $this->assertEqualsCanonicalizing([$mug->id, $press->id], Product::search('coffee')->pluck('id')->all());
        $this->assertEquals([$press->id], Product::search('frp-2')->pluck('id')->all());
        $this->assertEquals([$mug->id], Product::search('STONEWARE')->pluck('id')->all());
        $this->assertCount(3, Product::search('  ')->get());
    }
}
