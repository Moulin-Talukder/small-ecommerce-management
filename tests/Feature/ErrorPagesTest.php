<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_404_and_403_render_the_styled_error_page(): void
    {
        $this->get('/shops/does-not-exist')->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page->component('Error')->where('status', 404));

        $this->get('/totally/unknown/route')->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page->component('Error')->where('status', 404));

        $this->actingAs(User::factory()->shopOwner()->create())
            ->get('/admin')->assertForbidden()
            ->assertInertia(fn (Assert $page) => $page->component('Error')->where('status', 403));
    }

    public function test_json_requests_keep_json_errors(): void
    {
        $this->getJson('/shops/does-not-exist')->assertNotFound()->assertJsonStructure(['message']);
    }
}
