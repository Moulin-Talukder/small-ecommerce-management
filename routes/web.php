<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Owner;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', DashboardController::class)
    ->middleware('auth')
    ->name('dashboard');

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', Admin\DashboardController::class)->name('dashboard');

        Route::resource('shops', Admin\ShopController::class)->except('show');
        Route::resource('products', Admin\ProductController::class)->except('show');

        Route::resource('shop-owners', Admin\ShopOwnerController::class)
            ->except('show')
            ->parameters(['shop-owners' => 'shopOwner']);
        Route::patch('shop-owners/{shopOwner}/toggle-active', [Admin\ShopOwnerController::class, 'toggleActive'])
            ->name('shop-owners.toggle-active');
        Route::put('shop-owners/{shopOwner}/password', [Admin\ShopOwnerController::class, 'resetPassword'])
            ->name('shop-owners.password');
    });

Route::middleware(['auth', 'role:shop_owner'])
    ->prefix('owner')
    ->name('owner.')
    ->group(function () {
        Route::get('/', Owner\DashboardController::class)->name('dashboard');
    });

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
