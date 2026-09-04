<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\FinancialProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'is_active' => true,
        ], $attributes));
    }

    private function createProvider(array $attributes = []): FinancialProvider
    {
        return FinancialProvider::create(array_merge([
            'name' => 'Test Bank',
            'slug' => 'test-bank-' . uniqid(),
            'type' => 'bank',
            'is_active' => true,
        ], $attributes));
    }

    private function createAccount(User $user, array $attributes = []): Account
    {
        return Account::create(array_merge([
            'user_id' => $user->id,
            'name' => 'Testkonto',
            'institution' => 'Test Bank',
            'type' => 'checking',
            'currency' => 'EUR',
            'opening_balance' => 1000,
            'credit_limit' => null,
            'iban' => null,
            'account_number' => null,
            'color' => null,
            'icon' => null,
            'notes' => null,
            'include_in_total' => true,
            'is_active' => true,
        ], $attributes));
    }

    private function validAccountData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Neues Girokonto',
            'institution' => 'Test Bank',
            'type' => 'checking',
            'currency' => 'EUR',
            'opening_balance' => 500,
            'credit_limit' => null,
            'iban' => null,
            'account_number' => null,
            'color' => null,
            'icon' => null,
            'notes' => null,
            'include_in_total' => true,
        ], $overrides);
    }

    public function test_user_can_create_own_account(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->post(route('accounts.store'), [
            ...$this->validAccountData(),
        ]);

        $response
            ->assertRedirect(route('accounts.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('accounts', [
            'user_id' => $user->id,
            'name' => 'Neues Girokonto',
            'type' => 'checking',
            'currency' => 'EUR',
        ]);
    }

    public function test_user_can_create_account_with_global_provider(): void
    {
        $user = $this->createUser();
        $provider = $this->createProvider();

        $response = $this->actingAs($user)->post(route('accounts.store'), [
            ...$this->validAccountData([
                'provider_id' => $provider->id,
            ]),
        ]);

        $response->assertRedirect(route('accounts.index'));

        $this->assertDatabaseHas('accounts', [
            'user_id' => $user->id,
            'provider_id' => $provider->id,
        ]);
    }

    public function test_user_cannot_create_account_with_invalid_provider(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->post(route('accounts.store'), [
            ...$this->validAccountData([
                'provider_id' => 999999,
            ]),
        ]);

        $response->assertSessionHasErrors('provider_id');

        $this->assertDatabaseMissing('accounts', [
            'user_id' => $user->id,
            'name' => 'Neues Girokonto',
        ]);
    }

    public function test_user_can_view_own_account(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);

        $response = $this->actingAs($user)
            ->get(route('accounts.edit', $account));

        $response->assertOk();
        $response->assertViewIs('accounts.edit');
    }

    public function test_user_cannot_view_another_users_account(): void
    {
        $owner = $this->createUser();
        $attacker = $this->createUser();

        $account = $this->createAccount($owner);

        $response = $this->actingAs($attacker)
            ->get(route('accounts.edit', $account));

        $response->assertForbidden();
    }

    public function test_user_can_edit_own_account(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);

        $response = $this->actingAs($user)->put(
            route('accounts.update', $account),
            $this->validAccountData([
                'name' => 'Geändertes Konto',
                'opening_balance' => 2500,
                'is_active' => true,
            ])
        );

        $response
            ->assertRedirect(route('accounts.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'user_id' => $user->id,
            'name' => 'Geändertes Konto',
            'opening_balance' => 2500,
        ]);
    }

    public function test_user_cannot_edit_another_users_account(): void
    {
        $owner = $this->createUser();
        $attacker = $this->createUser();

        $account = $this->createAccount($owner);

        $response = $this->actingAs($attacker)->put(
            route('accounts.update', $account),
            $this->validAccountData([
                'name' => 'Hacked Account',
                'opening_balance' => 999999,
            ])
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'user_id' => $owner->id,
            'name' => 'Testkonto',
            'opening_balance' => 1000,
        ]);
    }

    public function test_user_can_delete_own_account(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);

        $response = $this->actingAs($user)
            ->delete(route('accounts.destroy', $account));

        $response
            ->assertRedirect(route('accounts.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('accounts', [
            'id' => $account->id,
        ]);
    }

    public function test_user_cannot_delete_another_users_account(): void
    {
        $owner = $this->createUser();
        $attacker = $this->createUser();

        $account = $this->createAccount($owner);

        $response = $this->actingAs($attacker)
            ->delete(route('accounts.destroy', $account));

        $response->assertForbidden();

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'user_id' => $owner->id,
            'deleted_at' => null,
        ]);
    }

    public function test_user_cannot_change_another_users_account_through_route_model_binding(): void
    {
        $owner = $this->createUser();
        $attacker = $this->createUser();

        $account = $this->createAccount($owner);

        $response = $this->actingAs($attacker)
            ->patch(route('accounts.update', $account), $this->validAccountData([
                'name' => 'Unbefugte Änderung',
            ]));

        $response->assertForbidden();

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'user_id' => $owner->id,
            'name' => 'Testkonto',
        ]);
    }

    public function test_user_can_set_account_inactive_when_updating_own_account(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);

        $response = $this->actingAs($user)->put(
            route('accounts.update', $account),
            $this->validAccountData([
                'name' => 'Inaktives Konto',
                'is_active' => false,
            ])
        );

        $response->assertRedirect(route('accounts.index'));

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'user_id' => $user->id,
            'is_active' => false,
        ]);
    }
}
