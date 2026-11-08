<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\Concerns\CopiesSeedImages;
use Illuminate\Database\Seeder;

/**
 * Shared catalog. Most products are created by the admin; a few by shop
 * owners (they become part of the shared catalog automatically).
 */
class ProductSeeder extends Seeder
{
    use CopiesSeedImages;

    public function run(): void
    {
        $products = [
            // [name, sku, base_price, created_by, image, description]
            ['Classic Cotton T-Shirt', 'APP-TSH-001', 19.99, 'admin@example.com', 'classic-cotton-t-shirt', 'A soft, breathable 100% organic cotton tee with a relaxed fit. Pre-shrunk and built to last.'],
            ['Slim Fit Denim Jeans', 'APP-JNS-002', 49.90, 'admin@example.com', 'slim-fit-denim-jeans', 'Stretch denim with a modern slim cut, five pockets and a mid-rise waist.'],
            ['Wool Beanie', 'APP-BNE-003', 14.50, 'admin@example.com', 'wool-beanie', 'Rib-knit merino wool beanie that keeps you warm without the itch.'],
            ['Canvas Tote Bag', 'APP-TOT-004', 12.00, 'admin@example.com', 'canvas-tote-bag', 'Heavy-duty 12oz cotton canvas tote with reinforced handles.'],
            ['Ceramic Coffee Mug', 'COF-MUG-001', 9.99, 'admin@example.com', 'ceramic-coffee-mug', 'Stoneware mug, 350 ml, dishwasher and microwave safe.'],
            ['Single-Origin Coffee Beans', 'COF-BEA-002', 24.00, 'admin@example.com', 'single-origin-coffee-beans', '1 kg of whole beans from a single Ethiopian farm. Notes of blueberry and dark chocolate.'],
            ['French Press', 'COF-FRP-003', 34.95, 'admin@example.com', 'french-press', 'Borosilicate glass French press with a stainless steel filter. Makes 8 cups.'],
            ['Wireless Earbuds', 'TEC-EAR-001', 79.00, 'admin@example.com', 'wireless-earbuds', 'True wireless earbuds with active noise cancelling and 24h battery with case.'],
            ['USB-C Charging Cable', 'TEC-CBL-002', 11.49, 'admin@example.com', 'usb-c-charging-cable', 'Braided 2 m USB-C to USB-C cable, 100 W fast charging.'],
            ['Bluetooth Speaker', 'TEC-SPK-003', 59.00, 'admin@example.com', 'bluetooth-speaker', 'Waterproof portable speaker with 12 hours of playback. Not sold in any shop yet.'],
            ['Scented Soy Candle', 'HOM-CDL-001', 16.00, 'admin@example.com', 'scented-soy-candle', 'Hand-poured soy wax candle with cedar and vanilla notes. 40 hour burn time.'],
            ['Linen Cushion Cover', 'HOM-CSH-002', 22.00, 'admin@example.com', 'linen-cushion-cover', 'Washed linen cushion cover, 45 x 45 cm, hidden zip. Not sold in any shop yet.'],
            ['Vintage Desk Lamp', 'HOM-LMP-003', 45.00, 'admin@example.com', 'vintage-desk-lamp', 'Brass-finish desk lamp. Discontinued (soft-deleted) to prove trashed products are hidden.'],
            // Created by shop owners: part of the shared catalog too.
            ['Hand-Knitted Scarf', 'ALC-SCF-001', 29.00, 'alice@example.com', 'hand-knitted-scarf', 'Chunky hand-knitted scarf made from recycled wool.'],
            ['Leather Notebook', 'ALC-NTB-002', 27.50, 'alice@example.com', 'leather-notebook', 'A5 refillable notebook with a full-grain leather cover. Not sold in any shop yet.'],
            ['Cold Brew Concentrate', 'BOB-CBR-001', 13.50, 'bob@example.com', 'cold-brew-concentrate', '1 litre of slow-steeped cold brew concentrate. Mix 1:2 with water or milk.'],
            ['Aluminium Phone Stand', 'CAR-PHS-001', 18.00, 'carol@example.com', 'aluminium-phone-stand', 'Adjustable aluminium stand for phones and small tablets.'],
        ];

        $users = User::pluck('id', 'email');

        foreach ($products as [$name, $sku, $price, $creator, $image, $description]) {
            Product::create([
                'name' => $name,
                'sku' => $sku,
                'base_price' => $price,
                'description' => $description,
                'thumbnail' => $this->copySeedImage("products/{$image}.jpg"),
                'created_by' => $users[$creator],
            ]);
        }
    }
}
