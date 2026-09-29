<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LogoutOtherDevicesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'is_active' => true,
            'password' => Hash::make('altes-passwort'),
        ]);
    }

    private function session(string $id, int $userId, string $agent, string $ip = '192.168.40.20'): void
    {
        DB::table('sessions')->insert([
            'id' => $id, 'user_id' => $userId, 'ip_address' => $ip, 'user_agent' => $agent,
            'payload' => base64_encode(serialize([])), 'last_activity' => now()->subHour()->timestamp,
        ]);
    }

    public function test_session_of_another_device_ends_after_password_change(): void
    {
        $oldHash = Auth::guard('web')->hashPasswordForCookie($this->user->password);

        // Passendes Passwort: angemeldet.
        $this->actingAs($this->user)
            ->withSession(['password_hash_web' => $oldHash])
            ->get(route('dashboard'))
            ->assertOk();

        // Passwort wurde (auf einem anderen Gerät) geändert → abgemeldet.
        $this->user->forceFill(['password' => Hash::make('neues-passwort')])->save();

        $this->actingAs($this->user)
            ->withSession(['password_hash_web' => $oldHash])
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_password_change_ends_other_sessions_and_keeps_current(): void
    {
        config(['session.driver' => 'database']);

        $this->session('anderes-geraet-1', $this->user->id, 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 Version/18.0 Mobile/15E148 Safari/604.1');

        $token = $this->user->remember_token;

        $this->actingAs($this->user)
            ->put(route('settings.security.password'), [
                'current_password' => 'altes-passwort',
                'password' => 'neues-passwort-123',
                'password_confirmation' => 'neues-passwort-123',
            ])
            ->assertRedirect(route('settings.security'))
            ->assertSessionHas('success', 'Dein Passwort wurde erfolgreich geändert. 1 anderes Gerät wurde abgemeldet.');

        $this->assertFalse(DB::table('sessions')->where('id', 'anderes-geraet-1')->exists());
        $this->assertNotSame($token, $this->user->fresh()->remember_token);
        $this->assertTrue(Hash::check('neues-passwort-123', $this->user->fresh()->password));
    }

    public function test_button_logs_out_other_devices_only_for_this_user(): void
    {
        config(['session.driver' => 'database']);

        $other = User::factory()->create(['is_active' => true]);

        $this->session('mac', $this->user->id, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/130.0 Safari/537.36');
        $this->session('iphone', $this->user->id, 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 Version/18.0 Mobile/15E148 Safari/604.1');
        $this->session('fremd', $other->id, 'Mozilla/5.0 (Windows NT 10.0) Firefox/131.0');

        $this->actingAs($this->user)
            ->get(route('settings.security'))
            ->assertOk()
            ->assertSee('Angemeldete Geräte')
            ->assertSee('Chrome auf Mac')
            ->assertSee('Safari auf iPhone')
            ->assertSee('192.168.40.20')
            ->assertDontSee('Firefox auf Windows');

        $this->actingAs($this->user)
            ->delete(route('settings.security.sessions.destroy'))
            ->assertRedirect(route('settings.security') . '#devices')
            ->assertSessionHas('success', '2 andere Geräte wurden abgemeldet.');

        $this->assertSame(0, DB::table('sessions')->whereIn('id', ['mac', 'iphone'])->count());
        $this->assertTrue(DB::table('sessions')->where('id', 'fremd')->exists());
    }

    public function test_without_database_sessions_the_button_still_invalidates_remember_tokens(): void
    {
        $token = $this->user->remember_token;

        $this->actingAs($this->user)
            ->delete(route('settings.security.sessions.destroy'))
            ->assertSessionHas('success', 'Andere Geräte werden bei ihrer nächsten Anfrage abgemeldet.');

        $this->assertNotSame($token, $this->user->fresh()->remember_token);
    }
}
