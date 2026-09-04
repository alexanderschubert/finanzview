<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_deactivate_themselves(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->patch(
            route('admin.users.toggle-active', $admin)
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'is_admin' => true,
            'is_active' => true,
        ]);
    }

    public function test_last_admin_cannot_be_removed(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->patch(
            route('admin.users.toggle-admin', $admin)
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'is_admin' => true,
            'is_active' => true,
        ]);
    }

    public function test_last_active_admin_cannot_be_deactivated(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->patch(
            route('admin.users.toggle-active', $admin)
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'is_admin' => true,
            'is_active' => true,
        ]);
    }


    public function test_inactive_user_cannot_log_in(): void
    {
        $password = 'TestPassword123!';

        $user = User::factory()->create([
            'is_admin' => false,
            'is_active' => false,
            'password' => $password,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => $password,
        ]);

        $response->assertSessionHasErrors('email');

        $this->assertGuest();
    }


    public function test_inactive_user_is_logged_out_on_next_request(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

}
