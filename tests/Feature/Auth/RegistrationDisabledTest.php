<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RegistrationDisabledTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_route_is_not_defined(): void
    {
        $this->assertFalse(Route::has('register'));
    }

    public function test_registration_page_returns_404(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_registration_cannot_be_submitted(): void
    {
        $this->post('/register', [
            'name' => 'Intruder',
            'email' => 'intruder@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'intruder@example.com']);
    }
}
