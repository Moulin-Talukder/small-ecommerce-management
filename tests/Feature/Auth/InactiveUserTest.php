<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InactiveUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->inactive()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => trans('auth.inactive')]);

        $this->assertGuest();
    }

    public function test_wrong_password_for_inactive_user_shows_generic_error(): void
    {
        $user = User::factory()->inactive()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertSessionHasErrors(['email' => trans('auth.failed')]);

        $this->assertGuest();
    }

    public function test_user_deactivated_during_session_is_logged_out(): void
    {
        $user = User::factory()->shopOwner()->create();

        $this->actingAs($user)->get('/owner')->assertOk();

        $user->update(['is_active' => false]);

        $this->get('/owner')->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
