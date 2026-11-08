<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public const PASSWORD = 'password';

    public function run(): void
    {
        $users = [
            ['name' => 'Ada Admin', 'email' => 'admin@example.com', 'role' => Role::Admin, 'is_active' => true],
            ['name' => 'Alice Moreau', 'email' => 'alice@example.com', 'role' => Role::ShopOwner, 'is_active' => true],
            ['name' => 'Bob Okafor', 'email' => 'bob@example.com', 'role' => Role::ShopOwner, 'is_active' => true],
            ['name' => 'Carol Jensen', 'email' => 'carol@example.com', 'role' => Role::ShopOwner, 'is_active' => true],
            // Deactivated account: proves inactive owners cannot log in.
            ['name' => 'Dan Inactive', 'email' => 'dan@example.com', 'role' => Role::ShopOwner, 'is_active' => false],
        ];

        foreach ($users as $user) {
            User::forceCreate($user + [
                'password' => self::PASSWORD,
                'email_verified_at' => now(),
            ]);
        }
    }
}
