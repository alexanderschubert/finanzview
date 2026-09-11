<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTransferTest extends TestCase
{
    use RefreshDatabase;

    private function createAccount(
        User $user,
        string $name,
        float $openingBalance = 0
    ): Account {
        return Account::create([
            'user_id' => $user->id,
            'name' => $name,
            'type' => 'checking',
            'currency' => 'EUR',
            'opening_balance' => $openingBalance,
            'include_in_total' => false,
            'is_active' => true,
        ]);
    }

    private function createTransfer(
        User $user,
        Account $source,
        Account $target,
        float $amount
    ): Transaction {
        return Transaction::create([
            'user_id' => $user->id,
            'account_id' => $source->id,
            'transfer_account_id' => $target->id,
            'type' => 'transfer',
            'amount' => $amount,
            'transaction_date' => now()->toDateString(),
            'description' => 'Transfer Test',
            'is_pending' => false,
        ]);
    }

    public function test_transfer_updates_both_account_balances(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $source = $this->createAccount($user, 'Quelle', 1000);
        $target = $this->createAccount($user, 'Ziel', 100);

        $transaction = $this->createTransfer(
            $user,
            $source,
            $target,
            250
        );

        $this->assertSame(
            750.0,
            $source->fresh()->current_balance
        );

        $this->assertSame(
            350.0,
            $target->fresh()->current_balance
        );

        $this->assertTrue(
            $transaction->transferAccount->is($target)
        );
    }

    public function test_transfer_cannot_use_same_source_and_target_account(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $account = $this->createAccount($user, 'Konto');

        $response = $this
            ->actingAs($user)
            ->post(route('transactions.store'), [
                'account_id' => $account->id,
                'transfer_account_id' => $account->id,
                'type' => 'transfer',
                'amount' => 100,
                'transaction_date' => now()->toDateString(),
                'description' => 'Ungültiger Transfer',
            ]);

        $response->assertSessionHasErrors('transfer_account_id');

        $this->assertDatabaseMissing('transactions', [
            'user_id' => $user->id,
            'type' => 'transfer',
            'description' => 'Ungültiger Transfer',
        ]);
    }

    public function test_transfer_cannot_target_another_users_account(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $otherUser = User::factory()->create(['is_active' => true]);

        $source = $this->createAccount($user, 'Quelle');

        $foreignTarget = $this->createAccount(
            $otherUser,
            'Fremdes Konto'
        );

        $response = $this
            ->actingAs($user)
            ->post(route('transactions.store'), [
                'account_id' => $source->id,
                'transfer_account_id' => $foreignTarget->id,
                'type' => 'transfer',
                'amount' => 100,
                'transaction_date' => now()->toDateString(),
                'description' => 'Fremdkonto Transfer',
            ]);

        $response->assertSessionHasErrors('transfer_account_id');

        $this->assertDatabaseMissing('transactions', [
            'user_id' => $user->id,
            'type' => 'transfer',
            'description' => 'Fremdkonto Transfer',
        ]);
    }

    public function test_transfer_requires_target_account(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $source = $this->createAccount($user, 'Quelle');

        $response = $this
            ->actingAs($user)
            ->post(route('transactions.store'), [
                'account_id' => $source->id,
                'type' => 'transfer',
                'amount' => 100,
                'transaction_date' => now()->toDateString(),
                'description' => 'Transfer ohne Ziel',
            ]);

        $response->assertSessionHasErrors('transfer_account_id');
    }

    public function test_updating_transfer_amount_updates_both_account_balances(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $source = $this->createAccount($user, 'Quelle', 1000);
        $target = $this->createAccount($user, 'Ziel', 100);

        $transaction = $this->createTransfer(
            $user,
            $source,
            $target,
            250
        );

        $response = $this
            ->actingAs($user)
            ->put(route('transactions.update', $transaction), [
                'account_id' => $source->id,
                'transfer_account_id' => $target->id,
                'type' => 'transfer',
                'amount' => 400,
                'transaction_date' => now()->toDateString(),
                'description' => 'Transfer geändert',
            ]);

        $response->assertRedirect(route('transactions.index'));

        $this->assertSame(
            600.0,
            $source->fresh()->current_balance
        );

        $this->assertSame(
            500.0,
            $target->fresh()->current_balance
        );
    }

    public function test_updating_transfer_target_moves_credit_to_new_target_account(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $source = $this->createAccount($user, 'Quelle', 1000);
        $oldTarget = $this->createAccount($user, 'Altes Ziel', 100);
        $newTarget = $this->createAccount($user, 'Neues Ziel', 50);

        $transaction = $this->createTransfer(
            $user,
            $source,
            $oldTarget,
            250
        );

        $response = $this
            ->actingAs($user)
            ->put(route('transactions.update', $transaction), [
                'account_id' => $source->id,
                'transfer_account_id' => $newTarget->id,
                'type' => 'transfer',
                'amount' => 250,
                'transaction_date' => now()->toDateString(),
                'description' => 'Transfer Ziel geändert',
            ]);

        $response->assertRedirect(route('transactions.index'));

        $this->assertSame(
            750.0,
            $source->fresh()->current_balance
        );

        $this->assertSame(
            100.0,
            $oldTarget->fresh()->current_balance
        );

        $this->assertSame(
            300.0,
            $newTarget->fresh()->current_balance
        );

        $this->assertTrue(
            $transaction->fresh()->transferAccount->is($newTarget)
        );
    }

    public function test_changing_transfer_to_expense_removes_transfer_target(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $source = $this->createAccount($user, 'Quelle', 1000);
        $target = $this->createAccount($user, 'Ziel', 100);

        $transaction = $this->createTransfer(
            $user,
            $source,
            $target,
            250
        );

        $response = $this
            ->actingAs($user)
            ->put(route('transactions.update', $transaction), [
                'account_id' => $source->id,
                'type' => 'expense',
                'amount' => 250,
                'transaction_date' => now()->toDateString(),
                'description' => 'Jetzt Ausgabe',
            ]);

        $response->assertRedirect(route('transactions.index'));

        $this->assertSame(
            750.0,
            $source->fresh()->current_balance
        );

        $this->assertSame(
            100.0,
            $target->fresh()->current_balance
        );

        $updatedTransaction = $transaction->fresh();

        $this->assertSame(
            'expense',
            $updatedTransaction->type
        );

        $this->assertNull(
            $updatedTransaction->transfer_account_id
        );
    }

    public function test_deleting_transfer_restores_both_account_balances(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $source = $this->createAccount($user, 'Quelle', 1000);
        $target = $this->createAccount($user, 'Ziel', 100);

        $transaction = $this->createTransfer(
            $user,
            $source,
            $target,
            250
        );

        $this->assertSame(
            750.0,
            $source->fresh()->current_balance
        );

        $this->assertSame(
            350.0,
            $target->fresh()->current_balance
        );

        $response = $this
            ->actingAs($user)
            ->delete(route('transactions.destroy', $transaction));

        $response->assertRedirect(route('transactions.index'));

        $this->assertSame(
            1000.0,
            $source->fresh()->current_balance
        );

        $this->assertSame(
            100.0,
            $target->fresh()->current_balance
        );

        $this->assertSoftDeleted('transactions', [
            'id' => $transaction->id,
        ]);
    }


    public function test_transfer_cannot_be_updated_to_an_inactive_target_account(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $source = $this->createAccount($user, 'Quelle', 1000);
        $target = $this->createAccount($user, 'Ziel', 100);

        $transaction = $this->createTransfer(
            $user,
            $source,
            $target,
            250
        );

        $target->update([
            'is_active' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('transactions.update', $transaction), [
                'account_id' => $source->id,
                'transfer_account_id' => $target->id,
                'type' => 'transfer',
                'amount' => 250,
                'transaction_date' => now()->toDateString(),
                'description' => 'Transfer auf inaktives Konto',
            ]);

        $response->assertSessionHasErrors('transfer_account_id');

        $updatedTransaction = $transaction->fresh();

        $this->assertSame(
            'transfer',
            $updatedTransaction->type
        );

        $this->assertSame(
            $target->id,
            $updatedTransaction->transfer_account_id
        );

        $this->assertSame(
            750.0,
            $source->fresh()->current_balance
        );

        $this->assertSame(
            350.0,
            $target->fresh()->current_balance
        );
    }

    public function test_changing_transfer_to_income_removes_transfer_target(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $source = $this->createAccount($user, 'Quelle', 1000);
        $target = $this->createAccount($user, 'Ziel', 100);

        $transaction = $this->createTransfer(
            $user,
            $source,
            $target,
            250
        );

        $response = $this
            ->actingAs($user)
            ->put(route('transactions.update', $transaction), [
                'account_id' => $source->id,
                'type' => 'income',
                'amount' => 250,
                'transaction_date' => now()->toDateString(),
                'description' => 'Jetzt Einnahme',
            ]);

        $response->assertRedirect(route('transactions.index'));

        $this->assertSame(
            1250.0,
            $source->fresh()->current_balance
        );

        $this->assertSame(
            100.0,
            $target->fresh()->current_balance
        );

        $updatedTransaction = $transaction->fresh();

        $this->assertSame(
            'income',
            $updatedTransaction->type
        );

        $this->assertNull(
            $updatedTransaction->transfer_account_id
        );
    }

}
