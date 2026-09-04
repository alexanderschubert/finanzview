<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::factory()->create([
            'is_active' => true,
        ]);
    }

    private function createAccount(User $user, array $attributes = []): Account
    {
        return Account::create(array_merge([
            'user_id' => $user->id,
            'name' => 'Testkonto',
            'institution' => 'Testbank',
            'type' => 'checking',
            'currency' => 'EUR',
            'opening_balance' => 1000.00,
            'is_active' => true,
            'include_in_total' => true,
        ], $attributes));
    }

    private function createCategory(User $user, array $attributes = []): Category
    {
        return Category::create(array_merge([
            'user_id' => $user->id,
            'name' => 'Testkategorie',
            'type' => 'both',
            'is_active' => true,
        ], $attributes));
    }

    private function validPayload(Account $account, ?int $categoryId = null): array
    {
        return [
            'account_id' => $account->id,
            'category_id' => $categoryId,
            'type' => 'expense',
            'amount' => '49.99',
            'transaction_date' => '2026-09-04',
            'description' => 'Testbuchung',
            'merchant' => 'Test Händler',
            'notes' => 'Testnotiz',
            'is_pending' => false,
        ];
    }

    public function test_user_can_create_own_transaction(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $category = $this->createCategory($user, [
            'type' => 'expense',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route('transactions.store'),
                $this->validPayload($account, $category->id)
            );

        $response
            ->assertRedirect(route('transactions.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'description' => 'Testbuchung',
            'amount' => '49.99',
        ]);
    }

    public function test_user_cannot_create_transaction_using_another_users_account(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();

        $ownAccount = $this->createAccount($user);
        $foreignAccount = $this->createAccount($otherUser);

        $payload = $this->validPayload($ownAccount);
        $payload['account_id'] = $foreignAccount->id;

        $response = $this
            ->actingAs($user)
            ->post(route('transactions.store'), $payload);

        $response
            ->assertRedirect()
            ->assertSessionHasErrors('account_id');

        $this->assertDatabaseMissing('transactions', [
            'user_id' => $user->id,
            'account_id' => $foreignAccount->id,
            'description' => 'Testbuchung',
        ]);
    }

    public function test_user_cannot_create_transaction_using_another_users_category(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();

        $account = $this->createAccount($user);

        $foreignCategory = $this->createCategory($otherUser, [
            'type' => 'expense',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route('transactions.store'),
                $this->validPayload($account, $foreignCategory->id)
            );

        $response
            ->assertRedirect()
            ->assertSessionHasErrors('category_id');

        $this->assertDatabaseMissing('transactions', [
            'user_id' => $user->id,
            'category_id' => $foreignCategory->id,
            'description' => 'Testbuchung',
        ]);
    }

    public function test_user_cannot_create_transaction_using_inactive_own_account(): void
    {
        $user = $this->createUser();

        $inactiveAccount = $this->createAccount($user, [
            'is_active' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route('transactions.store'),
                $this->validPayload($inactiveAccount)
            );

        $response
            ->assertRedirect()
            ->assertSessionHasErrors('account_id');

        $this->assertDatabaseMissing('transactions', [
            'user_id' => $user->id,
            'account_id' => $inactiveAccount->id,
        ]);
    }

    public function test_user_cannot_create_transaction_using_inactive_own_category(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);

        $inactiveCategory = $this->createCategory($user, [
            'type' => 'expense',
            'is_active' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route('transactions.store'),
                $this->validPayload($account, $inactiveCategory->id)
            );

        $response
            ->assertRedirect()
            ->assertSessionHasErrors('category_id');

        $this->assertDatabaseMissing('transactions', [
            'user_id' => $user->id,
            'category_id' => $inactiveCategory->id,
        ]);
    }

    public function test_user_cannot_use_category_with_wrong_transaction_type(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);

        $incomeCategory = $this->createCategory($user, [
            'type' => 'income',
        ]);

        $payload = $this->validPayload($account, $incomeCategory->id);
        $payload['type'] = 'expense';

        $response = $this
            ->actingAs($user)
            ->post(route('transactions.store'), $payload);

        $response
            ->assertRedirect()
            ->assertSessionHasErrors('category_id');

        $this->assertDatabaseMissing('transactions', [
            'user_id' => $user->id,
            'category_id' => $incomeCategory->id,
            'description' => 'Testbuchung',
        ]);
    }

    public function test_user_can_edit_own_transaction(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $category = $this->createCategory($user, [
            'type' => 'expense',
        ]);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 20.00,
            'transaction_date' => '2026-09-01',
        'currency' => 'EUR',
            'description' => 'Alte Buchung',
        ]);

        $payload = $this->validPayload($account, $category->id);
        $payload['amount'] = '99.50';
        $payload['description'] = 'Geänderte Buchung';

        $response = $this
            ->actingAs($user)
            ->put(route('transactions.update', $transaction), $payload);

        $response
            ->assertRedirect(route('transactions.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'user_id' => $user->id,
            'amount' => '99.50',
            'description' => 'Geänderte Buchung',
        ]);
    }

    public function test_user_cannot_edit_another_users_transaction(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();

        $otherAccount = $this->createAccount($otherUser);

        $transaction = Transaction::create([
            'user_id' => $otherUser->id,
            'account_id' => $otherAccount->id,
            'type' => 'expense',
            'amount' => 20.00,
            'transaction_date' => '2026-09-01',
        'currency' => 'EUR',
            'description' => 'Fremde Buchung',
        ]);

        $payload = $this->validPayload($otherAccount);
        $payload['description'] = 'Manipulierte Buchung';

        $this
            ->actingAs($user)
            ->put(route('transactions.update', $transaction), $payload)
            ->assertForbidden();

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'user_id' => $otherUser->id,
            'description' => 'Fremde Buchung',
        ]);
    }

    public function test_user_can_delete_own_transaction(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'type' => 'expense',
            'amount' => 20.00,
            'transaction_date' => '2026-09-01',
        'currency' => 'EUR',
            'description' => 'Zu löschen',
        ]);

        $this
            ->actingAs($user)
            ->delete(route('transactions.destroy', $transaction))
            ->assertRedirect(route('transactions.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('transactions', [
            'id' => $transaction->id,
        ]);
    }

    public function test_user_cannot_delete_another_users_transaction(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();

        $otherAccount = $this->createAccount($otherUser);

        $transaction = Transaction::create([
            'user_id' => $otherUser->id,
            'account_id' => $otherAccount->id,
            'type' => 'expense',
            'amount' => 20.00,
            'transaction_date' => '2026-09-01',
        'currency' => 'EUR',
            'description' => 'Fremde Buchung',
        ]);

        $this
            ->actingAs($user)
            ->delete(route('transactions.destroy', $transaction))
            ->assertForbidden();

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'user_id' => $otherUser->id,
            'description' => 'Fremde Buchung',
        ]);
    }
}
