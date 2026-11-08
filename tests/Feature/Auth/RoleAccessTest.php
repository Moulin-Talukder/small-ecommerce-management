<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_from_protected_areas(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/owner')->assertRedirect('/login');
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_admin_can_access_admin_area_but_not_owner_area(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/owner')->assertForbidden();
    }

    public function test_shop_owner_can_access_owner_area_but_not_admin_area(): void
    {
        $owner = User::factory()->shopOwner()->create();

        $this->actingAs($owner)->get('/owner')->assertOk();
        $this->actingAs($owner)->get('/admin')->assertForbidden();
    }

    public function test_dashboard_redirects_each_role_to_its_own_area(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/dashboard')->assertRedirect(route('admin.dashboard'));

        $this->actingAs(User::factory()->shopOwner()->create())
            ->get('/dashboard')->assertRedirect(route('owner.dashboard'));
    }

    public function test_login_redirects_to_the_role_area(): void
    {
        $admin = User::factory()->admin()->create();

        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->get('/dashboard')->assertRedirect(route('admin.dashboard'));
    }
}
