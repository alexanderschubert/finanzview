<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringTransactionSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
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

    private function createCategory(User $user, array $attributes = []): Category
    {
        return Category::create(array_merge([
            'user_id' => $user->id,
            'name' => 'Testkategorie',
            'type' => 'expense',
            'icon' => null,
            'color' => null,
            'description' => null,
            'is_active' => true,
        ], $attributes));
    }

    private function createRecurringTransaction(
        User $user,
        Account $account,
        ?Category $category = null,
        array $attributes = []
    ): RecurringTransaction {
        return RecurringTransaction::create(array_merge([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category?->id,
            'description' => 'Monatliche Miete',
            'amount' => 850.00,
            'type' => 'expense',
            'frequency' => 'monthly',
            'next_date' => '2026-10-01',
            'end_date' => null,
            'is_active' => true,
        ], $attributes));
    }

    private function validData(
        Account $account,
        ?Category $category = null,
        array $overrides = []
    ): array {
        return array_merge([
            'account_id' => $account->id,
            'category_id' => $category?->id,
            'description' => 'Monatliche Miete',
            'amount' => 850.00,
            'type' => 'expense',
            'frequency' => 'monthly',
            'next_date' => '2026-10-01',
            'end_date' => null,
            'is_active' => true,
        ], $overrides);
    }

    public function test_user_can_create_own_recurring_transaction(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $category = $this->createCategory($user);

        $response = $this->actingAs($user)->post(
            route('recurring-transactions.store'),
            $this->validData($account, $category)
        );

        $response
            ->assertRedirect(route('recurring-transactions.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('recurring_transactions', [
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'description' => 'Monatliche Miete',
        ]);
    }

    public function test_user_cannot_create_recurring_transaction_using_another_users_account(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();

        $foreignAccount = $this->createAccount($otherUser);
        $category = $this->createCategory($user);

        $response = $this->actingAs($user)->post(
            route('recurring-transactions.store'),
            $this->validData($foreignAccount, $category)
        );

        $response
            ->assertSessionHasErrors('account_id')
            ->assertRedirect();

        $this->assertDatabaseMissing('recurring_transactions', [
            'user_id' => $user->id,
            'account_id' => $foreignAccount->id,
        ]);
    }

    public function test_user_cannot_create_recurring_transaction_using_another_users_category(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();

        $account = $this->createAccount($user);
        $foreignCategory = $this->createCategory($otherUser);

        $response = $this->actingAs($user)->post(
            route('recurring-transactions.store'),
            $this->validData($account, $foreignCategory)
        );

        $response
            ->assertSessionHasErrors('category_id')
            ->assertRedirect();

        $this->assertDatabaseMissing('recurring_transactions', [
            'user_id' => $user->id,
            'category_id' => $foreignCategory->id,
        ]);
    }

    public function test_user_can_create_recurring_transaction_without_category(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);

        $response = $this->actingAs($user)->post(
            route('recurring-transactions.store'),
            $this->validData($account)
        );

        $response
            ->assertRedirect(route('recurring-transactions.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('recurring_transactions', [
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => null,
        ]);
    }

    public function test_user_cannot_create_recurring_transaction_with_invalid_frequency(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);

        $response = $this->actingAs($user)->post(
            route('recurring-transactions.store'),
            $this->validData($account, null, [
                'frequency' => 'daily',
            ])
        );

        $response->assertSessionHasErrors('frequency');

        $this->assertDatabaseMissing('recurring_transactions', [
            'user_id' => $user->id,
            'description' => 'Monatliche Miete',
        ]);
    }

    public function test_user_cannot_create_recurring_transaction_with_invalid_type(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);

        $response = $this->actingAs($user)->post(
            route('recurring-transactions.store'),
            $this->validData($account, null, [
                'type' => 'transfer',
            ])
        );

        $response->assertSessionHasErrors('type');

        $this->assertDatabaseMissing('recurring_transactions', [
            'user_id' => $user->id,
            'description' => 'Monatliche Miete',
        ]);
    }

    public function test_user_cannot_create_recurring_transaction_with_end_date_before_next_date(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);

        $response = $this->actingAs($user)->post(
            route('recurring-transactions.store'),
            $this->validData($account, null, [
                'next_date' => '2026-10-01',
                'end_date' => '2026-09-30',
            ])
        );

        $response->assertSessionHasErrors('end_date');

        $this->assertDatabaseMissing('recurring_transactions', [
            'user_id' => $user->id,
            'description' => 'Monatliche Miete',
        ]);
    }

    public function test_user_can_view_own_recurring_transaction(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $recurring = $this->createRecurringTransaction($user, $account);

        $response = $this->actingAs($user)
            ->get(route('recurring-transactions.show', $recurring));

        $response->assertOk();
        $response->assertViewIs('recurring_transactions.show');
    }

    public function test_user_cannot_view_another_users_recurring_transaction(): void
    {
        $owner = $this->createUser();
        $attacker = $this->createUser();

        $account = $this->createAccount($owner);
        $recurring = $this->createRecurringTransaction($owner, $account);

        $response = $this->actingAs($attacker)
            ->get(route('recurring-transactions.show', $recurring));

        $response->assertForbidden();
    }

    public function test_user_can_edit_own_recurring_transaction(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $category = $this->createCategory($user);
        $recurring = $this->createRecurringTransaction($user, $account);

        $response = $this->actingAs($user)->put(
            route('recurring-transactions.update', $recurring),
            $this->validData($account, $category, [
                'description' => 'Geänderte wiederkehrende Buchung',
                'amount' => 900.00,
                'frequency' => 'quarterly',
            ])
        );

        $response
            ->assertRedirect(route('recurring-transactions.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('recurring_transactions', [
            'id' => $recurring->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'description' => 'Geänderte wiederkehrende Buchung',
            'amount' => 900.00,
            'frequency' => 'quarterly',
        ]);
    }

    public function test_user_cannot_edit_another_users_recurring_transaction(): void
    {
        $owner = $this->createUser();
        $attacker = $this->createUser();

        $account = $this->createAccount($owner);
        $recurring = $this->createRecurringTransaction($owner, $account);

        $attackerAccount = $this->createAccount($attacker);

        $response = $this->actingAs($attacker)->put(
            route('recurring-transactions.update', $recurring),
            $this->validData($attackerAccount, null, [
                'description' => 'Unbefugte Änderung',
                'amount' => 999999,
            ])
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('recurring_transactions', [
            'id' => $recurring->id,
            'user_id' => $owner->id,
            'account_id' => $account->id,
            'description' => 'Monatliche Miete',
            'amount' => 850.00,
        ]);
    }

    public function test_user_cannot_update_recurring_transaction_to_another_users_account(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();

        $account = $this->createAccount($user);
        $foreignAccount = $this->createAccount($otherUser);

        $recurring = $this->createRecurringTransaction($user, $account);

        $response = $this->actingAs($user)->put(
            route('recurring-transactions.update', $recurring),
            $this->validData($foreignAccount)
        );

        $response
            ->assertSessionHasErrors('account_id')
            ->assertRedirect();

        $this->assertDatabaseHas('recurring_transactions', [
            'id' => $recurring->id,
            'user_id' => $user->id,
            'account_id' => $account->id,
        ]);
    }

    public function test_user_cannot_update_recurring_transaction_to_another_users_category(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();

        $account = $this->createAccount($user);
        $category = $this->createCategory($user);
        $foreignCategory = $this->createCategory($otherUser);

        $recurring = $this->createRecurringTransaction(
            $user,
            $account,
            $category
        );

        $response = $this->actingAs($user)->put(
            route('recurring-transactions.update', $recurring),
            $this->validData($account, $foreignCategory)
        );

        $response
            ->assertSessionHasErrors('category_id')
            ->assertRedirect();

        $this->assertDatabaseHas('recurring_transactions', [
            'id' => $recurring->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);
    }

    public function test_user_can_delete_own_recurring_transaction(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $recurring = $this->createRecurringTransaction($user, $account);

        $response = $this->actingAs($user)
            ->delete(route('recurring-transactions.destroy', $recurring));

        $response
            ->assertRedirect(route('recurring-transactions.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('recurring_transactions', [
            'id' => $recurring->id,
        ]);
    }

    public function test_user_cannot_delete_another_users_recurring_transaction(): void
    {
        $owner = $this->createUser();
        $attacker = $this->createUser();

        $account = $this->createAccount($owner);
        $recurring = $this->createRecurringTransaction($owner, $account);

        $response = $this->actingAs($attacker)
            ->delete(route('recurring-transactions.destroy', $recurring));

        $response->assertForbidden();

        $this->assertDatabaseHas('recurring_transactions', [
            'id' => $recurring->id,
            'user_id' => $owner->id,
            'deleted_at' => null,
        ]);
    }
}
