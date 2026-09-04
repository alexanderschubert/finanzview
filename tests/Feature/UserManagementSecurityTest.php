<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);
    }

    private function user(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'is_admin' => false,
            'is_active' => true,
        ], $attributes));
    }

    public function test_admin_can_deactivate_another_user(): void
    {
        $admin = $this->admin();

        $user = $this->user([
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->patch(
            route('admin.users.toggle-active', $user)
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_reactivate_another_user(): void
    {
        $admin = $this->admin();

        $user = $this->user([
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin)->patch(
            route('admin.users.toggle-active', $user)
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_grant_admin_rights(): void
    {
        $admin = $this->admin();

        $user = $this->user([
            'is_admin' => false,
        ]);

        $response = $this->actingAs($admin)->patch(
            route('admin.users.toggle-admin', $user)
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_admin' => true,
        ]);
    }

    public function test_admin_can_remove_admin_rights_from_another_admin(): void
    {
        $admin = $this->admin();

        $secondAdmin = $this->user([
            'is_admin' => true,
        ]);

        $response = $this->actingAs($admin)->patch(
            route('admin.users.toggle-admin', $secondAdmin)
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $secondAdmin->id,
            'is_admin' => false,
        ]);
    }

    public function test_non_admin_cannot_change_user_active_state(): void
    {
        $user = $this->user();

        $target = $this->user([
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->patch(
            route('admin.users.toggle-active', $target)
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'is_active' => true,
        ]);
    }

    public function test_non_admin_cannot_change_admin_rights(): void
    {
        $user = $this->user();

        $target = $this->user([
            'is_admin' => false,
        ]);

        $response = $this->actingAs($user)->patch(
            route('admin.users.toggle-admin', $target)
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'is_admin' => false,
        ]);
    }
}
