<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;
use Tests\TestCase;

class PasskeyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['is_active' => true, 'email' => 'erika@example.com', 'name' => 'Erika']);
    }

    private function passkey(User $user, string $name = 'iPhone'): Passkey
    {
        return $user->passkeys()->create([
            'name' => $name,
            'credential_id' => 'cred-' . $name . '-' . $user->id,
            'credential' => ['aaguid' => '00000000-0000-0000-0000-000000000000'],
        ]);
    }

    private function confirmed(): static
    {
        return $this->withSession(['auth.password_confirmed_at' => time()]);
    }

    public function test_login_page_offers_passkey_and_autofill(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Mit Passkey anmelden')
            ->assertSee('data-options-url="' . route('passkey.login-options') . '"', false)
            ->assertSee('autocomplete="username webauthn"', false)
            ->assertSee('name="csrf-token"', false);
    }

    public function test_login_options_use_current_host_as_relying_party(): void
    {
        $response = $this->getJson(route('passkey.login-options'))->assertOk();

        $this->assertSame('localhost', $response->json('options.rpId'));
        $this->assertNotEmpty($response->json('options.challenge'));
        $this->assertSame('required', $response->json('options.userVerification'));
        $this->assertSame(['http://localhost'], config('passkeys.allowed_origins'));
    }

    public function test_registration_requires_recent_password_confirmation(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('passkey.registration-options'))
            ->assertStatus(423);

        $response = $this->actingAs($this->user)
            ->confirmed()
            ->getJson(route('passkey.registration-options'))
            ->assertOk();

        $this->assertSame('erika@example.com', $response->json('options.user.name'));
        $this->assertSame('Erika', $response->json('options.user.displayName'));
        $this->assertSame('localhost', $response->json('options.rp.id'));
        $this->assertSame('required', $response->json('options.authenticatorSelection.residentKey'));
    }

    public function test_security_page_asks_for_confirmation_before_managing_passkeys(): void
    {
        $this->actingAs($this->user)
            ->get(route('settings.security'))
            ->assertOk()
            ->assertSee('Passkey einrichten')
            ->assertSee(route('settings.security.passkeys'), false)
            ->assertDontSee('data-passkey-register', false);

        $this->actingAs($this->user)
            ->get(route('settings.security.passkeys'))
            ->assertRedirect(route('password.confirm'));

        $this->actingAs($this->user)
            ->confirmed()
            ->get(route('settings.security.passkeys'))
            ->assertRedirect(route('settings.security') . '#passkeys');

        $this->actingAs($this->user)
            ->confirmed()
            ->get(route('settings.security'))
            ->assertSee('data-passkey-register', false)
            ->assertSee('data-options-url="' . route('passkey.registration-options') . '"', false);
    }

    public function test_passkeys_are_listed_and_can_be_deleted(): void
    {
        $passkey = $this->passkey($this->user, 'MacBook');

        $this->actingAs($this->user)
            ->confirmed()
            ->get(route('settings.security'))
            ->assertSee('MacBook')
            ->assertSee('1 Passkey')
            ->assertSee('noch nicht benutzt');

        $this->actingAs($this->user)
            ->confirmed()
            ->from(route('settings.security'))
            ->delete(route('passkey.destroy', $passkey))
            ->assertRedirect(route('settings.security'))
            ->assertSessionHas('status', 'passkey-deleted');

        $this->assertSame(0, Passkey::count());
    }

    public function test_other_users_cannot_delete_a_passkey(): void
    {
        $passkey = $this->passkey($this->user);
        $other = User::factory()->create(['is_active' => true]);

        $this->actingAs($other)
            ->confirmed()
            ->delete(route('passkey.destroy', $passkey))
            ->assertForbidden();

        $this->assertSame(1, Passkey::count());
    }

    public function test_inactive_users_cannot_sign_in_with_a_passkey(): void
    {
        $active = $this->passkey($this->user);
        $inactiveUser = User::factory()->create(['is_active' => false]);
        $inactive = $this->passkey($inactiveUser, 'Alt');

        $request = Request::create('/passkeys/login', 'POST');

        $this->assertTrue(Passkeys::allowsLogin($request, $active));
        $this->assertNotNull($this->user->fresh()->last_login_at);

        $this->assertFalse(Passkeys::allowsLogin($request, $inactive));
    }

    public function test_confirm_password_page_offers_passkey_only_with_passkeys(): void
    {
        $this->actingAs($this->user)->get(route('password.confirm'))->assertOk()->assertDontSee('Mit Passkey bestätigen');

        $this->passkey($this->user);

        $this->actingAs($this->user)->get(route('password.confirm'))->assertOk()->assertSee('Mit Passkey bestätigen');
    }
}
