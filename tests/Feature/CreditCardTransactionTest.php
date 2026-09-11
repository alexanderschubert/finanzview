<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\CreditCard;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditCardTransactionTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::factory()->create([
            'is_active' => true,
        ]);
    }

    private function createAccount(User $user): Account
    {
        return Account::create([
            'user_id' => $user->id,
            'name' => 'Test Girokonto',
            'type' => 'checking',
            'balance' => 1000,
            'currency' => 'EUR',
            'is_active' => true,
        ]);
    }

    private function createCreditCard(
        User $user,
        Account $account,
        array $attributes = []
    ): CreditCard {
        return CreditCard::create(array_merge([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'name' => 'Test Kreditkarte',
            'issuer' => 'Test Bank',
            'last_four' => '1234',
            'credit_limit' => 2000,
            'current_balance' => 500,
            'billing_day' => 15,
            'payment_due_day' => 5,
            'is_active' => true,
        ], $attributes));
    }

    private function createTransaction(
        User $user,
        Account $account,
        array $attributes = []
    ): Transaction {
        return Transaction::create(array_merge([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'type' => 'expense',
            'amount' => 50.00,
            'transaction_date' => '2026-08-20',
            'description' => 'Test Kreditkartenzahlung',
            'merchant' => 'Test Händler',
            'is_pending' => false,
            'is_recurring' => false,
        ], $attributes));
    }

    public function test_transaction_can_be_assigned_to_credit_card(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $card = $this->createCreditCard($user, $account);

        $transaction = $this->createTransaction(
            $user,
            $account,
            [
                'credit_card_id' => $card->id,
            ]
        );

        $this->assertSame(
            $card->id,
            $transaction->credit_card_id
        );

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'credit_card_id' => $card->id,
        ]);
    }

    public function test_transaction_credit_card_relationship_works(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $card = $this->createCreditCard($user, $account);

        $transaction = $this->createTransaction(
            $user,
            $account,
            [
                'credit_card_id' => $card->id,
            ]
        );

        $this->assertTrue(
            $transaction->creditCard->is($card)
        );
    }

    public function test_credit_card_transactions_relationship_works(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $card = $this->createCreditCard($user, $account);

        $transaction = $this->createTransaction(
            $user,
            $account,
            [
                'credit_card_id' => $card->id,
            ]
        );

        $this->assertCount(
            1,
            $card->transactions
        );

        $this->assertTrue(
            $card->transactions->first()->is($transaction)
        );
    }

    public function test_transaction_can_exist_without_credit_card(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);

        $transaction = $this->createTransaction(
            $user,
            $account
        );

        $this->assertNull(
            $transaction->credit_card_id
        );

        $this->assertNull(
            $transaction->creditCard
        );
    }

    public function test_deleting_credit_card_unassigns_transactions(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $card = $this->createCreditCard($user, $account);

        $transaction = $this->createTransaction(
            $user,
            $account,
            [
                'credit_card_id' => $card->id,
            ]
        );

        $card->forceDelete();

        $transaction->refresh();

        $this->assertNull(
            $transaction->credit_card_id
        );

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'credit_card_id' => null,
        ]);
    }
}
