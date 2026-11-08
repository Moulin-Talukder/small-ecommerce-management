<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $shops = $request->user()->shops()
            ->withCount([
                'shopProducts as listings_count',
                'shopProducts as active_listings_count' => fn ($q) => $q->where('is_active', true),
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description', 'logo', 'is_active']);

        return Inertia::render('Owner/Dashboard', [
            'shops' => $shops,
        ]);
    }
}
