<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\CreditCard;
use App\Models\FinancialProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditCardSecurityTest extends TestCase
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
            'slug' => 'test-provider-' . uniqid(),
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

    private function createCreditCard(User $user, array $attributes = []): CreditCard
    {
        return CreditCard::create(array_merge([
            'user_id' => $user->id,
            'name' => 'Test Kreditkarte',
            'issuer' => 'Test Bank',
            'last_four' => '1234',
            'credit_limit' => 2000,
            'current_balance' => 500,
            'billing_day' => 15,
            'payment_due_day' => 5,
            'color' => '#334155',
            'is_active' => true,
        ], $attributes));
    }

    public function test_user_can_view_credit_card_index(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)
            ->get(route('credit-cards.index'));

        $response->assertOk();
        $response->assertViewIs('credit-cards.index');
    }

    public function test_user_can_view_create_credit_card_form(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)
            ->get(route('credit-cards.create'));

        $response->assertOk();
        $response->assertViewIs('credit-cards.create');
    }

    public function test_user_can_create_own_credit_card(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->post(route('credit-cards.store'), [
            'name' => 'PayPal Mastercard',
            'issuer' => 'Mastercard',
            'last_four' => '1234',
            'credit_limit' => '2000.00',
            'current_balance' => '700.00',
            'billing_day' => 15,
            'payment_due_day' => 5,
            'color' => '#334155',
            'is_active' => '1',
        ]);

        $response
            ->assertRedirect(route('credit-cards.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('credit_cards', [
            'user_id' => $user->id,
            'name' => 'PayPal Mastercard',
            'last_four' => '1234',
            'credit_limit' => '2000.00',
            'current_balance' => '700.00',
        ]);
    }

    public function test_user_cannot_create_credit_card_with_another_users_account(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();

        $account = $this->createAccount($otherUser);

        $response = $this->actingAs($user)->post(route('credit-cards.store'), [
            'name' => 'Fremde Karte',
            'account_id' => $account->id,
            'current_balance' => '0',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('credit_cards', [
            'user_id' => $user->id,
            'name' => 'Fremde Karte',
        ]);
    }

    public function test_user_cannot_create_credit_card_with_inactive_provider(): void
    {
        $user = $this->createUser();

        $provider = $this->createProvider([
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)->post(route('credit-cards.store'), [
            'name' => 'Karte',
            'provider_id' => $provider->id,
            'current_balance' => '0',
        ]);

        $response->assertForbidden();
    }

    public function test_user_can_create_credit_card_with_active_global_provider(): void
    {
        $user = $this->createUser();

        $provider = $this->createProvider([
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('credit-cards.store'), [
            'name' => 'Karte mit Anbieter',
            'provider_id' => $provider->id,
            'current_balance' => '0',
        ]);

        $response->assertRedirect(route('credit-cards.index'));

        $this->assertDatabaseHas('credit_cards', [
            'user_id' => $user->id,
            'provider_id' => $provider->id,
            'name' => 'Karte mit Anbieter',
        ]);
    }

    public function test_user_cannot_create_credit_card_with_invalid_last_four(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->post(route('credit-cards.store'), [
            'name' => 'Karte',
            'last_four' => '12345',
            'current_balance' => '0',
        ]);

        $response->assertSessionHasErrors('last_four');

        $this->assertDatabaseMissing('credit_cards', [
            'user_id' => $user->id,
            'name' => 'Karte',
        ]);
    }

    public function test_user_cannot_create_credit_card_with_negative_balance(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->post(route('credit-cards.store'), [
            'name' => 'Karte',
            'current_balance' => '-1',
        ]);

        $response->assertSessionHasErrors('current_balance');

        $this->assertDatabaseMissing('credit_cards', [
            'user_id' => $user->id,
            'name' => 'Karte',
        ]);
    }


    public function test_user_can_view_own_credit_card(): void
    {
        $user = $this->createUser();
        $creditCard = $this->createCreditCard($user);

        $response = $this->actingAs($user)
            ->get(route('credit-cards.show', $creditCard));

        $response->assertOk();
        $response->assertViewIs('credit-cards.show');
        $response->assertSee($creditCard->name);
    }

    public function test_user_cannot_view_another_users_credit_card(): void
    {
        $owner = $this->createUser();
        $attacker = $this->createUser();

        $creditCard = $this->createCreditCard($owner);

        $response = $this->actingAs($attacker)
            ->get(route('credit-cards.show', $creditCard));

        $response->assertForbidden();
    }

    public function test_user_can_edit_own_credit_card(): void
    {
        $user = $this->createUser();
        $creditCard = $this->createCreditCard($user);

        $response = $this->actingAs($user)
            ->get(route('credit-cards.edit', $creditCard));

        $response->assertOk();
        $response->assertViewIs('credit-cards.edit');
    }

    public function test_user_cannot_edit_another_users_credit_card(): void
    {
        $owner = $this->createUser();
        $attacker = $this->createUser();

        $creditCard = $this->createCreditCard($owner);

        $response = $this->actingAs($attacker)
            ->get(route('credit-cards.edit', $creditCard));

        $response->assertForbidden();
    }

    public function test_user_can_update_own_credit_card(): void
    {
        $user = $this->createUser();
        $creditCard = $this->createCreditCard($user);

        $response = $this->actingAs($user)->put(
            route('credit-cards.update', $creditCard),
            [
                'name' => 'Geänderte Kreditkarte',
                'issuer' => 'Neue Bank',
                'last_four' => '9876',
                'credit_limit' => '3000',
                'current_balance' => '900',
                'billing_day' => 20,
                'payment_due_day' => 10,
                'color' => '#123456',
                'is_active' => '1',
            ]
        );

        $response
            ->assertRedirect(route('credit-cards.show', $creditCard))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('credit_cards', [
            'id' => $creditCard->id,
            'user_id' => $user->id,
            'name' => 'Geänderte Kreditkarte',
            'last_four' => '9876',
            'credit_limit' => '3000.00',
            'current_balance' => '900.00',
        ]);
    }

    public function test_user_cannot_update_another_users_credit_card(): void
    {
        $owner = $this->createUser();
        $attacker = $this->createUser();

        $creditCard = $this->createCreditCard($owner);

        $response = $this->actingAs($attacker)->put(
            route('credit-cards.update', $creditCard),
            [
                'name' => 'Hacked Card',
                'current_balance' => '999999',
            ]
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('credit_cards', [
            'id' => $creditCard->id,
            'user_id' => $owner->id,
            'name' => 'Test Kreditkarte',
            'current_balance' => '500.00',
        ]);
    }

    public function test_user_cannot_assign_another_users_account_when_updating_credit_card(): void
    {
        $owner = $this->createUser();
        $attacker = $this->createUser();

        $creditCard = $this->createCreditCard($attacker);
        $foreignAccount = $this->createAccount($owner);

        $response = $this->actingAs($attacker)->put(
            route('credit-cards.update', $creditCard),
            [
                'name' => 'Meine Karte',
                'account_id' => $foreignAccount->id,
                'current_balance' => '500',
            ]
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('credit_cards', [
            'id' => $creditCard->id,
            'user_id' => $attacker->id,
            'account_id' => null,
        ]);
    }

    public function test_user_cannot_delete_another_users_credit_card(): void
    {
        $owner = $this->createUser();
        $attacker = $this->createUser();

        $creditCard = $this->createCreditCard($owner);

        $response = $this->actingAs($attacker)
            ->delete(route('credit-cards.destroy', $creditCard));

        $response->assertForbidden();

        $this->assertDatabaseHas('credit_cards', [
            'id' => $creditCard->id,
            'user_id' => $owner->id,
            'deleted_at' => null,
        ]);
    }

    public function test_user_can_archive_own_credit_card(): void
    {
        $user = $this->createUser();
        $creditCard = $this->createCreditCard($user);

        $response = $this->actingAs($user)
            ->delete(route('credit-cards.destroy', $creditCard));

        $response
            ->assertRedirect(route('credit-cards.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('credit_cards', [
            'id' => $creditCard->id,
        ]);
    }

    public function test_user_can_only_see_own_credit_cards(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();

        $this->createCreditCard($user, [
            'name' => 'Meine Karte',
        ]);

        $this->createCreditCard($otherUser, [
            'name' => 'Fremde Karte',
        ]);

        $response = $this->actingAs($user)
            ->get(route('credit-cards.index'));

        $response
            ->assertOk()
            ->assertSee('Meine Karte')
            ->assertDontSee('Fremde Karte');
    }
}
