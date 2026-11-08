<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopProduct;
use Database\Seeders\Concerns\CopiesSeedImages;
use Illuminate\Database\Seeder;

/**
 * Assigns catalog products to shops.
 *
 * Deliberately NOT assigned anywhere (must stay invisible publicly):
 *   Bluetooth Speaker, Linen Cushion Cover, Leather Notebook.
 * Other visibility checks built into the demo data:
 *   - Canvas Tote Bag is an inactive listing in Urban Threads.
 *   - Vintage Desk Lamp is listed in Home & Hearth but soft-deleted.
 *   - Gadget Garage is an inactive shop.
 */
class ShopProductSeeder extends Seeder
{
    use CopiesSeedImages;

    public function run(): void
    {
        $listings = [
            'Urban Threads' => [
                'APP-TSH-001' => [
                    'price' => 24.99,
                    'custom_name' => 'Urban Logo Tee',
                    'custom_description' => 'Our classic cotton tee with the Urban Threads logo screen-printed on the chest.',
                    'custom_thumbnail' => 'shop-products/urban-logo-tee.jpg',
                    'stock' => 40,
                ],
                'APP-JNS-002' => ['stock' => 15], // no overrides: falls back to the catalog values
                'APP-BNE-003' => ['price' => 12.00, 'stock' => 25],
                'APP-TOT-004' => ['price' => 13.00, 'stock' => 10, 'is_active' => false],
                'ALC-SCF-001' => ['price' => 32.00, 'stock' => 6],
            ],
            'Home & Hearth' => [
                'HOM-CDL-001' => ['stock' => 30],
                'COF-MUG-001' => ['price' => 11.00, 'stock' => 18],
                'HOM-LMP-003' => ['price' => 39.00, 'stock' => 3],
            ],
            'Bean & Brew' => [
                'COF-MUG-001' => [
                    'price' => 12.50,
                    'custom_name' => 'Bean & Brew Signature Mug',
                    'custom_description' => 'Our ceramic mug glazed in house colours. Comes with a free filter coffee at the counter.',
                    'custom_thumbnail' => 'shop-products/bean-and-brew-signature-mug.jpg',
                    'stock' => 22,
                ],
                'COF-BEA-002' => ['price' => 22.00, 'stock' => 50],
                'COF-FRP-003' => ['stock' => 8],
                'BOB-CBR-001' => ['stock' => 0], // visible but out of stock
                'APP-TOT-004' => ['price' => 10.00, 'custom_name' => 'Bean & Brew Tote', 'stock' => 12],
            ],
            'Gadget Garage' => [
                'TEC-EAR-001' => ['price' => 75.00, 'stock' => 9],
                'TEC-CBL-002' => ['stock' => 40],
                'CAR-PHS-001' => ['stock' => 14],
            ],
        ];

        $shops = Shop::pluck('id', 'name');
        $products = Product::pluck('id', 'sku');

        foreach ($listings as $shopName => $items) {
            foreach ($items as $sku => $attributes) {
                if (isset($attributes['custom_thumbnail'])) {
                    $attributes['custom_thumbnail'] = $this->copySeedImage($attributes['custom_thumbnail']);
                }

                ShopProduct::create($attributes + [
                    'shop_id' => $shops[$shopName],
                    'product_id' => $products[$sku],
                    'is_active' => true,
                ]);
            }
        }

        // Discontinued catalog product: listing stays in the DB but is hidden.
        Product::where('sku', 'HOM-LMP-003')->firstOrFail()->delete();
    }
}
