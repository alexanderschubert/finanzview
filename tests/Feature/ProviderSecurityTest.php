<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\FinancialProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);
    }

    private function user(): User
    {
        return User::factory()->create([
            'is_admin' => false,
            'is_active' => true,
        ]);
    }

    private function provider(array $attributes = []): FinancialProvider
    {
        return FinancialProvider::create(array_merge([
            'name' => 'Test Bank',
            'slug' => 'test-bank',
            'type' => 'bank',
            'is_active' => true,
        ], $attributes));
    }

    public function test_admin_can_create_provider(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(
            route('admin.providers.store'),
            [
                'name' => 'Neue Testbank',
                'type' => 'bank',
                'is_active' => true,
            ]
        );

        $response->assertRedirect(
            route('admin.providers.index')
        );

        $this->assertDatabaseHas('financial_providers', [
            'name' => 'Neue Testbank',
            'slug' => 'neue-testbank',
            'type' => 'bank',
            'is_active' => true,
        ]);
    }

    public function test_non_admin_cannot_create_provider(): void
    {
        $user = $this->user();

        $response = $this->actingAs($user)->post(
            route('admin.providers.store'),
            [
                'name' => 'Nicht erlaubter Anbieter',
                'type' => 'bank',
                'is_active' => true,
            ]
        );

        $response->assertForbidden();

        $this->assertDatabaseMissing('financial_providers', [
            'name' => 'Nicht erlaubter Anbieter',
        ]);
    }

    public function test_admin_can_update_provider(): void
    {
        $admin = $this->admin();
        $provider = $this->provider();

        $response = $this->actingAs($admin)->patch(
            route('admin.providers.update', $provider),
            [
                'name' => 'Geänderte Testbank',
                'slug' => 'geaenderte-testbank',
                'type' => 'payment',
                'is_active' => true,
            ]
        );

        $response->assertRedirect(
            route('admin.providers.index')
        );

        $this->assertDatabaseHas('financial_providers', [
            'id' => $provider->id,
            'name' => 'Geänderte Testbank',
            'slug' => 'geaenderte-testbank',
            'type' => 'payment',
            'is_active' => true,
        ]);
    }

    public function test_non_admin_cannot_update_provider(): void
    {
        $user = $this->user();
        $provider = $this->provider();

        $response = $this->actingAs($user)->patch(
            route('admin.providers.update', $provider),
            [
                'name' => 'Unzulässige Änderung',
                'slug' => 'unzulassige-aenderung',
                'type' => 'bank',
                'is_active' => true,
            ]
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('financial_providers', [
            'id' => $provider->id,
            'name' => 'Test Bank',
            'slug' => 'test-bank',
        ]);
    }

    public function test_admin_can_toggle_provider_active_state(): void
    {
        $admin = $this->admin();
        $provider = $this->provider([
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->patch(
            route('admin.providers.toggle-active', $provider)
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('financial_providers', [
            'id' => $provider->id,
            'is_active' => false,
        ]);
    }

    public function test_non_admin_cannot_toggle_provider_active_state(): void
    {
        $user = $this->user();
        $provider = $this->provider([
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->patch(
            route('admin.providers.toggle-active', $provider)
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('financial_providers', [
            'id' => $provider->id,
            'is_active' => true,
        ]);
    }

    public function test_used_provider_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $provider = $this->provider();

        $account = Account::create([
            'user_id' => $admin->id,
            'name' => 'Testkonto',
            'institution' => 'Test Bank',
            'provider_id' => $provider->id,
            'type' => 'checking',
            'currency' => 'EUR',
            'opening_balance' => 0,
            'is_active' => true,
            'include_in_total' => true,
        ]);

        $response = $this->actingAs($admin)->delete(
            route('admin.providers.destroy', $provider)
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('financial_providers', [
            'id' => $provider->id,
        ]);

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'provider_id' => $provider->id,
        ]);
    }

    public function test_unused_provider_can_be_deleted(): void
    {
        $admin = $this->admin();
        $provider = $this->provider();

        $response = $this->actingAs($admin)->delete(
            route('admin.providers.destroy', $provider)
        );

        $response->assertRedirect();

        $this->assertDatabaseMissing('financial_providers', [
            'id' => $provider->id,
        ]);
    }
}
