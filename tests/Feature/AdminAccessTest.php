<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_when_accessing_admin(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }

    public function test_non_admin_users_receive_forbidden_when_accessing_admin(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertForbidden();
    }

    public function test_admin_users_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
    }
}
