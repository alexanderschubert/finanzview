<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirstAdminTest extends TestCase
{
    use RefreshDatabase;

    private function register(string $email): void
    {
        $this->post('/register', [
            'name' => 'Test',
            'email' => $email,
            'password' => 'geheim12345',
            'password_confirmation' => 'geheim12345',
        ])->assertSessionHasNoErrors();

        auth()->logout();
    }

    public function test_first_registered_user_becomes_admin_later_ones_do_not(): void
    {
        $this->register('erste@example.com');
        $this->register('zweite@example.com');

        $this->assertTrue(User::where('email', 'erste@example.com')->value('is_admin'));
        $this->assertFalse(User::where('email', 'zweite@example.com')->value('is_admin'));
    }

    public function test_admin_command_promotes_and_activates_user(): void
    {
        $user = User::factory()->create(['email' => 'max@example.com', 'is_active' => false]);

        $this->artisan('finanzview:admin', ['email' => 'Max@Example.com'])
            ->expectsOutputToContain('ist jetzt Administrator')
            ->assertSuccessful();

        $user->refresh();
        $this->assertTrue($user->is_admin);
        $this->assertTrue($user->is_active);

        $this->artisan('finanzview:admin', ['email' => 'niemand@example.com'])->assertFailed();
    }
}
