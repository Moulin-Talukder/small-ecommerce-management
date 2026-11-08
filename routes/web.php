<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Owner;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Storefront;
use Illuminate\Support\Facades\Route;

// Public storefront (guests). Slugs are resolved through the visibility scopes.
Route::get('/', [Storefront\ShopController::class, 'index'])->name('storefront.shops.index');
Route::get('/shops/{shopSlug}', [Storefront\ShopController::class, 'show'])->name('storefront.shops.show');
Route::get('/shops/{shopSlug}/products/{productSlug}', [Storefront\ProductController::class, 'show'])->name('storefront.products.show');

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

        Route::get('catalog', Owner\CatalogController::class)->name('catalog.index');
        Route::resource('products', Owner\ProductController::class)->except('show');

        // Scoped bindings: a {shopProduct} must belong to the {shop} in the URL (else 404).
        Route::scopeBindings()->controller(Owner\ListingController::class)->group(function () {
            Route::get('shops/{shop}/listings', 'index')->name('listings.index');
            Route::post('shops/{shop}/listings', 'store')->name('listings.store');
            Route::get('shops/{shop}/listings/{shopProduct}/edit', 'edit')->name('listings.edit');
            Route::put('shops/{shop}/listings/{shopProduct}', 'update')->name('listings.update');
            Route::patch('shops/{shop}/listings/{shopProduct}/toggle', 'toggle')->name('listings.toggle');
            Route::delete('shops/{shop}/listings/{shopProduct}', 'destroy')->name('listings.destroy');
        });
    });

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
