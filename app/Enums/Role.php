<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case ShopOwner = 'shop_owner';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::ShopOwner => 'Shop owner',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** Route name of the home page for this role after login. */
    public function homeRoute(): string
    {
        return match ($this) {
            self::Admin => 'admin.dashboard',
            self::ShopOwner => 'owner.dashboard',
        };
    }
}
