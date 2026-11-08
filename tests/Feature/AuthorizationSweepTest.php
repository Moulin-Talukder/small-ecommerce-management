<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Tests\TestCase;

/**
 * Hits EVERY admin.* and owner.* route as the wrong kind of user. New routes
 * are covered automatically because the list comes from the router.
 */
class AuthorizationSweepTest extends TestCase
{
    use RefreshDatabase;

    /** Routes that are intentionally public (no auth middleware). */
    private const PUBLIC_ROUTES = [
        'storefront.shops.index', 'storefront.shops.show', 'storefront.products.show',
        'login', 'password.request', 'password.email', 'password.reset', 'password.store',
        'storage.local', 'storage.local.upload', 'sanctum.csrf-cookie',
    ];

    private User $admin;

    private User $owner;

    private User $otherOwner;

    private Shop $ownerShop;

    private Product $ownerProduct;

    private ShopProduct $ownerListing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->owner = User::factory()->shopOwner()->create();
        $this->otherOwner = User::factory()->shopOwner()->create();
        $this->ownerShop = Shop::factory()->for($this->owner, 'owner')->create();
        $this->ownerProduct = Product::factory()->create(['created_by' => $this->owner->id]);
        $this->ownerListing = ShopProduct::factory()->for($this->ownerShop)->for($this->ownerProduct)->create();
    }

    /** @return array<string, Route> */
    private function routes(string $prefix): array
    {
        $routes = collect(Router::getRoutes()->getRoutes())
            ->filter(fn (Route $r) => str_starts_with((string) $r->getName(), $prefix))
            ->keyBy(fn (Route $r) => $r->getName())
            ->all();

        $this->assertNotEmpty($routes, "No {$prefix} routes found");

        return $routes;
    }

    private function url(Route $route): string
    {
        $params = [
            'shopOwner' => $this->owner->id,
            'shop' => $this->ownerShop->id,
            'product' => $this->ownerProduct->id,
            'shopProduct' => $this->ownerListing->id,
        ];

        return route($route->getName(), array_intersect_key($params, array_flip($route->parameterNames())));
    }

    private function hit(Route $route)
    {
        $method = collect($route->methods())->reject(fn ($m) => $m === 'HEAD')->first();

        return $this->call($method, $this->url($route), [
            'name' => 'Hacked', 'email' => 'hacked@example.com', 'is_active' => 0,
            'password' => 'hacked-password', 'password_confirmation' => 'hacked-password',
            'owner_id' => $this->otherOwner->id, 'base_price' => 1, 'stock' => 1,
            'product_id' => $this->ownerProduct->id,
        ]);
    }

    private function assertNothingChanged(): void
    {
        $this->assertSame(3, User::count());
        $this->assertTrue($this->owner->fresh()->is_active);
        $this->assertNotSame('Hacked', $this->owner->fresh()->name);
        $this->assertTrue($this->ownerShop->fresh()->owner->is($this->owner));
        $this->assertNotSoftDeleted($this->ownerProduct);
        $this->assertNotSame('Hacked', $this->ownerProduct->fresh()->name);
        $this->assertModelExists($this->ownerListing);
        $this->assertTrue($this->ownerListing->fresh()->is_active);
        $this->assertSame(1, ShopProduct::count());
        $this->assertSame(1, Product::count());
        $this->assertSame(1, Shop::count());
    }

    public function test_every_non_public_route_requires_authentication(): void
    {
        foreach (Router::getRoutes()->getRoutes() as $route) {
            $name = (string) $route->getName();
            if ($name === '' || in_array($name, self::PUBLIC_ROUTES, true) || str_starts_with($route->uri(), '_') || $route->uri() === 'up') {
                continue;
            }

            $middleware = $route->gatherMiddleware();
            $this->assertTrue(
                in_array('auth', $middleware, true) || in_array('guest', $middleware, true),
                "Route [{$name}] ({$route->uri()}) has neither auth nor guest middleware",
            );
        }
    }

    public function test_guests_are_redirected_to_login_from_every_admin_and_owner_route(): void
    {
        foreach ($this->routes('admin.') + $this->routes('owner.') as $name => $route) {
            $this->hit($route)->assertRedirect(route('login'));
        }

        $this->assertNothingChanged();
    }

    public function test_shop_owners_get_403_on_every_admin_route(): void
    {
        $this->actingAs($this->owner);

        foreach ($this->routes('admin.') as $name => $route) {
            $this->assertSame(403, $this->hit($route)->status(), "admin route [{$name}] did not return 403 for a shop owner");
        }

        $this->assertNothingChanged();
    }

    public function test_admins_get_403_on_every_owner_route(): void
    {
        $this->actingAs($this->admin);

        foreach ($this->routes('owner.') as $name => $route) {
            $this->assertSame(403, $this->hit($route)->status(), "owner route [{$name}] did not return 403 for an admin");
        }

        $this->assertNothingChanged();
    }

    public function test_another_owner_cannot_touch_shops_listings_or_products_they_do_not_own(): void
    {
        $this->actingAs($this->otherOwner);
        $checked = 0;

        foreach ($this->routes('owner.') as $name => $route) {
            if (! array_intersect($route->parameterNames(), ['shop', 'shopProduct', 'product'])) {
                continue; // dashboard, catalog, "my products" list/create: scoped to the user
            }

            $status = $this->hit($route)->status();
            $this->assertContains($status, [403, 404], "owner route [{$name}] returned {$status} for a different owner");
            $checked++;
        }

        $this->assertGreaterThanOrEqual(9, $checked);
        $this->assertNothingChanged();
    }

    public function test_inactive_user_is_logged_out_on_every_protected_route(): void
    {
        foreach ($this->routes('owner.') as $name => $route) {
            $this->owner->update(['is_active' => false]);
            $this->actingAs($this->owner->fresh());
            $this->hit($route)->assertRedirect(route('login'));
            $this->assertGuest();
            $this->owner->update(['is_active' => true]);
        }

        $this->ownerShop->refresh();
        $this->assertNothingChanged();
    }
}
