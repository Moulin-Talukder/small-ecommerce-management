<?php

namespace Database\Seeders;

use App\Models\Shop;
use App\Models\User;
use Database\Seeders\Concerns\CopiesSeedImages;
use Illuminate\Database\Seeder;

class ShopSeeder extends Seeder
{
    use CopiesSeedImages;

    public function run(): void
    {
        $shops = [
            [
                'owner' => 'alice@example.com',
                'name' => 'Urban Threads',
                'description' => 'Everyday streetwear and accessories, designed in the city for the city.',
                'logo' => 'shops/urban-threads.png',
                'is_active' => true,
            ],
            [
                // Alice owns a second shop: owners can run several shops.
                'owner' => 'alice@example.com',
                'name' => 'Home & Hearth',
                'description' => 'Cosy home goods: candles, mugs and little things that make a house a home.',
                'logo' => 'shops/home-and-hearth.png',
                'is_active' => true,
            ],
            [
                'owner' => 'bob@example.com',
                'name' => 'Bean & Brew',
                'description' => 'Specialty coffee, brewing gear and merch from our neighbourhood roastery.',
                'logo' => 'shops/bean-and-brew.png',
                'is_active' => true,
            ],
            [
                // Inactive shop: must not appear publicly, its URLs must 404.
                'owner' => 'carol@example.com',
                'name' => 'Gadget Garage',
                'description' => 'Small gadgets and accessories. Currently closed for restocking.',
                'logo' => 'shops/gadget-garage.png',
                'is_active' => false,
            ],
        ];

        foreach ($shops as $data) {
            Shop::create([
                'owner_id' => User::where('email', $data['owner'])->value('id'),
                'name' => $data['name'],
                'description' => $data['description'],
                'logo' => $this->copySeedImage($data['logo']),
                'is_active' => $data['is_active'],
            ]);
        }
    }
}
