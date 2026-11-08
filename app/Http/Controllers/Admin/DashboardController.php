<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopProduct;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'shops' => Shop::count(),
                'activeShops' => Shop::where('is_active', true)->count(),
                'products' => Product::count(),
                'unassignedProducts' => Product::doesntHave('shopProducts')->count(),
                'shopOwners' => User::shopOwners()->count(),
                'activeShopOwners' => User::shopOwners()->where('is_active', true)->count(),
                'listings' => ShopProduct::count(),
                'publicListings' => ShopProduct::publiclyVisible()->count(),
            ],
        ]);
    }
}
