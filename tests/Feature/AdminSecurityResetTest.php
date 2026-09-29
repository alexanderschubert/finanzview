<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passkeys\Passkey;
use Tests\TestCase;

class AdminSecurityResetTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->forceFill(['is_admin' => true])->save();

        $this->member = User::factory()->create(['is_active' => true, 'name' => 'Erika']);
    }

    private function enableSecurity(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => encrypt('SECRET'),
            'two_factor_recovery_codes' => encrypt(json_encode(['a-b'])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        foreach (['iPhone', 'Mac'] as $name) {
            $user->passkeys()->create([
                'name' => $name,
                'credential_id' => $name . '-' . $user->id,
                'credential' => [],
            ]);
        }
    }

    public function test_edit_page_shows_security_status_and_reset_buttons(): void
    {
        $this->enableSecurity($this->member);

        $this->actingAs($this->admin)
            ->get(route('admin.users.edit', $this->member))
            ->assertOk()
            ->assertSee('Anmeldung &amp; Sicherheit', false)
            ->assertSee('2 Passkeys')
            ->assertSee(route('admin.users.two-factor.destroy', $this->member), false)
            ->assertSee(route('admin.users.passkeys.destroy', $this->member), false);
    }

    public function test_admin_can_reset_two_factor_and_passkeys_of_another_user(): void
    {
        $this->enableSecurity($this->member);

        $this->actingAs($this->admin)
            ->delete(route('admin.users.two-factor.destroy', $this->member))
            ->assertSessionHas('success');

        $member = $this->member->fresh();
        $this->assertNull($member->two_factor_secret);
        $this->assertNull($member->two_factor_recovery_codes);
        $this->assertNull($member->two_factor_confirmed_at);
        $this->assertFalse($member->hasEnabledTwoFactorAuthentication());

        $this->actingAs($this->admin)
            ->delete(route('admin.users.passkeys.destroy', $this->member))
            ->assertSessionHas('success', '2 Passkeys von Erika wurden entfernt.');

        $this->assertSame(0, Passkey::count());
    }

    public function test_admin_cannot_reset_own_security_here(): void
    {
        $this->enableSecurity($this->admin);

        $this->actingAs($this->admin)
            ->get(route('admin.users.edit', $this->admin))
            ->assertOk()
            ->assertDontSee(route('admin.users.two-factor.destroy', $this->admin), false)
            ->assertSee(route('settings.security'), false);

        $this->actingAs($this->admin)
            ->delete(route('admin.users.two-factor.destroy', $this->admin))
            ->assertSessionHas('error');

        $this->actingAs($this->admin)
            ->delete(route('admin.users.passkeys.destroy', $this->admin))
            ->assertSessionHas('error');

        $this->assertNotNull($this->admin->fresh()->two_factor_confirmed_at);
        $this->assertSame(2, Passkey::count());
    }

    public function test_non_admins_cannot_reset(): void
    {
        $this->enableSecurity($this->admin);

        $this->actingAs($this->member)
            ->delete(route('admin.users.two-factor.destroy', $this->admin))
            ->assertForbidden();

        $this->actingAs($this->member)
            ->delete(route('admin.users.passkeys.destroy', $this->admin))
            ->assertForbidden();

        $this->assertNotNull($this->admin->fresh()->two_factor_confirmed_at);
    }
}
