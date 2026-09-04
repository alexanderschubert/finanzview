<?php

namespace Tests\Feature;

use App\Models\ApplicationSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_page_is_available_when_registration_is_enabled(): void
    {
        ApplicationSetting::updateOrCreate(
            ['key' => 'registration_enabled'],
            ['value' => 'true']
        );

        $response = $this->get('/register');

        $response->assertOk();
    }

    public function test_registration_page_is_not_available_when_registration_is_disabled(): void
    {
        ApplicationSetting::updateOrCreate(
            ['key' => 'registration_enabled'],
            ['value' => 'false']
        );

        $response = $this->get('/register');

        $response->assertNotFound();
    }

    public function test_registration_post_is_blocked_when_registration_is_disabled(): void
    {
        ApplicationSetting::updateOrCreate(
            ['key' => 'registration_enabled'],
            ['value' => 'false']
        );

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'TestPassword123!',
            'password_confirmation' => 'TestPassword123!',
        ]);

        $response->assertNotFound();

        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_non_admin_users_cannot_change_registration_setting(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->patch(
            route('admin.settings.registration')
        );

        $response->assertForbidden();
    }
}
