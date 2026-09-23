<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function userWithTwoFactor(string $secret): User
    {
        $user = User::factory()->create(['is_active' => true]);

        $user->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $user;
    }

    public function test_login_without_two_factor_goes_directly_to_dashboard(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_two_factor_requires_challenge(): void
    {
        $secret = (new Google2FA())->generateSecretKey();
        $user = $this->userWithTwoFactor($secret);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/two-factor-challenge');
        $this->assertGuest();

        $this->get('/two-factor-challenge')
            ->assertOk()
            ->assertSee('Bestätigungscode');
    }

    public function test_valid_two_factor_code_completes_login(): void
    {
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();
        $user = $this->userWithTwoFactor($secret);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response = $this->post('/two-factor-challenge', [
            'code' => $google2fa->getCurrentOtp($secret),
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_two_factor_code_is_rejected(): void
    {
        $secret = (new Google2FA())->generateSecretKey();
        $user = $this->userWithTwoFactor($secret);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response = $this->post('/two-factor-challenge', [
            'code' => '000000',
        ]);

        $response->assertRedirect('/two-factor-challenge');
        $this->assertGuest();
    }

    public function test_recovery_code_completes_login_once(): void
    {
        $secret = (new Google2FA())->generateSecretKey();
        $user = $this->userWithTwoFactor($secret);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->post('/two-factor-challenge', [
            'recovery_code' => 'recovery-code-1',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
        $this->assertNotContains('recovery-code-1', $user->fresh()->recoveryCodes());
    }

    public function test_user_can_enable_and_confirm_two_factor(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post('/user/two-factor-authentication')
            ->assertRedirect();

        $user->refresh();

        $this->assertNotNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);

        $this->get(route('settings.security'))
            ->assertOk()
            ->assertSee('Einrichtung abbrechen');

        $code = (new Google2FA())->getCurrentOtp(decrypt($user->two_factor_secret));

        $this->post('/user/confirmed-two-factor-authentication', [
            'code' => $code,
        ])->assertRedirect();

        $this->assertTrue($user->fresh()->hasEnabledTwoFactorAuthentication());
    }

    public function test_enabling_two_factor_requires_password_confirmation(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->post('/user/two-factor-authentication')
            ->assertRedirect(route('password.confirm'));

        $this->assertNull($user->fresh()->two_factor_secret);
    }

    public function test_security_page_shows_active_state(): void
    {
        $secret = (new Google2FA())->generateSecretKey();
        $user = $this->userWithTwoFactor($secret);

        $this->actingAs($user)
            ->get(route('settings.security'))
            ->assertOk()
            ->assertSee('Neue Wiederherstellungscodes erzeugen')
            ->assertDontSee('recovery-code-1');
    }
}
