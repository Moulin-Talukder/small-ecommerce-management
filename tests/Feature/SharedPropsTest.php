<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SharedPropsTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_user_is_shared_without_sensitive_fields(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin')->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard')
            ->where('auth.user.id', $admin->id)
            ->where('auth.user.role', 'admin')
            ->missing('auth.user.password')
            ->missing('auth.user.remember_token')
        );
    }

    public function test_flash_messages_are_shared(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->withSession(['success' => 'Saved!', 'error' => 'Oops'])
            ->get('/admin')
            ->assertInertia(fn (Assert $page) => $page
                ->where('flash.success', 'Saved!')
                ->where('flash.error', 'Oops')
            );
    }

    public function test_admin_dashboard_shows_stats(): void
    {
        $admin = User::factory()->admin()->create();
        Shop::factory()->count(2)->create();

        $this->actingAs($admin)->get('/admin')->assertInertia(fn (Assert $page) => $page
            ->where('stats.shops', 2)
            ->where('stats.shopOwners', 2)
        );
    }

    public function test_owner_dashboard_lists_only_own_shops(): void
    {
        $owner = User::factory()->shopOwner()->create();
        $mine = Shop::factory()->for($owner, 'owner')->create();
        Shop::factory()->create();

        $this->actingAs($owner)->get('/owner')->assertInertia(fn (Assert $page) => $page
            ->component('Owner/Dashboard')
            ->has('shops', 1)
            ->where('shops.0.id', $mine->id)
        );
    }
}
