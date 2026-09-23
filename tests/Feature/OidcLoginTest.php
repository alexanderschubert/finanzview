<?php

namespace Tests\Feature;

use App\Models\ApplicationSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class OidcLoginTest extends TestCase
{
    use RefreshDatabase;

    private const ISSUER = 'https://idp.test/application/o/finanzview/';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.oidc.enabled' => true,
            'services.oidc.issuer' => self::ISSUER,
            'services.oidc.client_id' => 'finanzview',
            'services.oidc.client_secret' => 'secret',
            'services.oidc.button_label' => 'Mit Authentik anmelden',
            'services.oidc.trust_email' => false,
        ]);

        Http::fake([
            self::ISSUER . '.well-known/openid-configuration' => Http::response([
                'issuer' => self::ISSUER,
                'authorization_endpoint' => 'https://idp.test/application/o/authorize/',
                'token_endpoint' => 'https://idp.test/application/o/token/',
                'userinfo_endpoint' => 'https://idp.test/application/o/userinfo/',
            ]),
        ]);
    }

    private function idToken(array $claims): string
    {
        $encode = fn (array $data) => rtrim(strtr(base64_encode(json_encode($data)), '+/', '-_'), '=');

        return $encode(['alg' => 'RS256', 'typ' => 'JWT'])
            . '.' . $encode($claims)
            . '.signature';
    }

    /**
     * Führt den kompletten Ablauf aus: Weiterleitung zum Provider,
     * Token-Austausch und Rückkehr zu FinanzView.
     */
    private function loginWithClaims(array $claims, array $overrides = []): TestResponse
    {
        $this->get(route('oidc.redirect'))->assertRedirect();

        $flow = session('oidc');

        $idTokenClaims = array_merge([
            'iss' => self::ISSUER,
            'aud' => 'finanzview',
            'exp' => time() + 300,
            'iat' => time(),
            'nonce' => $flow['nonce'],
        ], $claims);

        Http::fake([
            'https://idp.test/application/o/token/' => Http::response([
                'access_token' => 'access-token',
                'token_type' => 'Bearer',
                'id_token' => $this->idToken($idTokenClaims),
            ]),
            'https://idp.test/application/o/userinfo/' => Http::response([
                'sub' => $idTokenClaims['sub'],
            ]),
        ]);

        return $this->get(route('oidc.callback', array_merge([
            'code' => 'authorization-code',
            'state' => $flow['state'],
        ], $overrides)));
    }

    public function test_login_page_hides_sso_button_when_disabled(): void
    {
        config(['services.oidc.enabled' => false]);

        $this->get('/login')
            ->assertOk()
            ->assertDontSee('Mit Authentik anmelden');

        $this->get(route('oidc.redirect'))->assertNotFound();
    }

    public function test_login_page_shows_sso_button_when_enabled(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Mit Authentik anmelden');
    }

    public function test_redirect_points_to_provider_with_pkce(): void
    {
        $response = $this->get(route('oidc.redirect'));

        $location = $response->headers->get('Location');

        $this->assertStringStartsWith('https://idp.test/application/o/authorize/?', $location);
        $this->assertStringContainsString('client_id=finanzview', $location);
        $this->assertStringContainsString('code_challenge_method=S256', $location);
        $this->assertStringContainsString('state=' . session('oidc')['state'], $location);
    }

    public function test_new_user_is_created_when_registration_is_enabled(): void
    {
        $response = $this->loginWithClaims([
            'sub' => 'authentik-123',
            'email' => 'neu@example.com',
            'email_verified' => true,
            'name' => 'Neue Person',
        ]);

        $response->assertRedirect('/dashboard');

        $user = User::where('email', 'neu@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('authentik-123', $user->oidc_sub);
        $this->assertSame('Neue Person', $user->name);
        $this->assertGreaterThan(0, $user->categories()->count());
    }

    public function test_new_user_is_not_created_when_registration_is_disabled(): void
    {
        ApplicationSetting::set('registration_enabled', false);

        $response = $this->loginWithClaims([
            'sub' => 'authentik-123',
            'email' => 'neu@example.com',
            'email_verified' => true,
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'neu@example.com']);
    }

    public function test_existing_user_is_linked_by_verified_email(): void
    {
        ApplicationSetting::set('registration_enabled', false);

        $user = User::factory()->create([
            'email' => 'bestand@example.com',
            'is_active' => true,
        ]);

        $this->loginWithClaims([
            'sub' => 'authentik-456',
            'email' => 'bestand@example.com',
            'email_verified' => true,
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
        $this->assertSame('authentik-456', $user->fresh()->oidc_sub);
    }

    public function test_existing_user_is_not_linked_by_unverified_email(): void
    {
        $user = User::factory()->create([
            'email' => 'bestand@example.com',
            'is_active' => true,
        ]);

        $this->loginWithClaims([
            'sub' => 'authentik-456',
            'email' => 'bestand@example.com',
            'email_verified' => false,
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertNull($user->fresh()->oidc_sub);
    }

    public function test_unverified_email_is_linked_when_trusted_by_config(): void
    {
        config(['services.oidc.trust_email' => true]);

        $user = User::factory()->create([
            'email' => 'bestand@example.com',
            'is_active' => true,
        ]);

        $this->loginWithClaims([
            'sub' => 'authentik-456',
            'email' => 'bestand@example.com',
            'email_verified' => false,
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_linked_user_logs_in_by_sub_even_after_email_change(): void
    {
        $user = User::factory()->create([
            'email' => 'alt@example.com',
            'is_active' => true,
        ]);
        $user->forceFill(['oidc_sub' => 'authentik-789'])->save();

        $this->loginWithClaims([
            'sub' => 'authentik-789',
            'email' => 'neu@example.com',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $user->forceFill(['oidc_sub' => 'authentik-789'])->save();

        $this->loginWithClaims([
            'sub' => 'authentik-789',
            'email' => $user->email,
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_invalid_state_is_rejected(): void
    {
        $this->loginWithClaims([
            'sub' => 'authentik-123',
            'email' => 'neu@example.com',
            'email_verified' => true,
        ], ['state' => 'manipuliert'])->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'neu@example.com']);
    }

    public function test_wrong_nonce_is_rejected(): void
    {
        $this->loginWithClaims([
            'sub' => 'authentik-123',
            'email' => 'neu@example.com',
            'email_verified' => true,
            'nonce' => 'falsche-nonce',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_token_for_other_client_is_rejected(): void
    {
        $this->loginWithClaims([
            'sub' => 'authentik-123',
            'email' => 'neu@example.com',
            'email_verified' => true,
            'aud' => 'andere-app',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_expired_token_is_rejected(): void
    {
        $this->loginWithClaims([
            'sub' => 'authentik-123',
            'email' => 'neu@example.com',
            'email_verified' => true,
            'exp' => time() - 3600,
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
