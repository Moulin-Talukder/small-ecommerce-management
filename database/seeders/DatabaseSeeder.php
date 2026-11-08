<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    /** Directories on the public disk that hold uploaded/seeded images. */
    private const IMAGE_DIRECTORIES = ['products', 'shops', 'shop-products'];

    public function run(): void
    {
        if (User::where('email', 'admin@example.com')->exists()) {
            $this->command->warn('Demo data is already seeded. Run "php artisan migrate:fresh --seed" to reset it.');

            return;
        }

        // Fresh database: remove image files left over from previous seeds/uploads.
        foreach (self::IMAGE_DIRECTORIES as $directory) {
            Storage::disk('public')->deleteDirectory($directory);
        }

        $this->call([
            UserSeeder::class,
            ProductSeeder::class,
            ShopSeeder::class,
            ShopProductSeeder::class,
        ]);

        if (! file_exists(public_path('storage'))) {
            $this->command->warn('Images will not display until you run "php artisan storage:link".');
        }
    }
}
