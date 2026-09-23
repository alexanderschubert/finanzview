<?php

namespace Tests\Feature;

use App\Models\ApplicationSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_with_logo_and_form(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Willkommen bei FinanzView')
            ->assertSee('aria-label="FinanzView"', false)
            ->assertSee('data-toggle-password="password"', false)
            ->assertSee(route('password.request'), false);
    }

    public function test_login_page_hides_registration_link_when_disabled(): void
    {
        ApplicationSetting::set('registration_enabled', false);

        $this->get('/login')
            ->assertOk()
            ->assertDontSee('Jetzt registrieren');
    }

    public function test_login_page_shows_registration_link_when_enabled(): void
    {
        ApplicationSetting::set('registration_enabled', true);

        $this->get('/login')
            ->assertOk()
            ->assertSee('Jetzt registrieren');
    }

    public function test_failed_login_shows_german_message(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->from('/login')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'falsches-passwort',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors([
                'email' => 'E-Mail-Adresse oder Passwort sind nicht korrekt.',
            ]);
    }

    public function test_guest_pages_render(): void
    {
        ApplicationSetting::set('registration_enabled', true);

        $this->get('/register')->assertOk()->assertSee('Konto erstellen');
        $this->get('/forgot-password')->assertOk()->assertSee('Passwort vergessen?');
        $this->get('/reset-password/token123?email=test@example.com')->assertOk()->assertSee('Neues Passwort');
    }

    public function test_confirm_password_page_renders(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get('/user/confirm-password')
            ->assertOk()
            ->assertSee('Passwort bestätigen');
    }

    public function test_forbidden_page_uses_new_design(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('admin.index'))
            ->assertForbidden()
            ->assertSee('Kein Zugriff');
    }
}
