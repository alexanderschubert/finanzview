<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\CreditCard;
use App\Models\CreditCardStatement;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CreditCardStatementService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditCardStatementAmountTest extends TestCase
{
    use RefreshDatabase;

    private CreditCardStatementService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(CreditCardStatementService::class);
    }

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
        CreditCard $card,
        array $attributes = []
    ): Transaction {
        return Transaction::create(array_merge([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'credit_card_id' => $card->id,
            'type' => 'expense',
            'amount' => 50.00,
            'transaction_date' => '2026-08-20',
            'description' => 'Test Kreditkartenzahlung',
            'merchant' => 'Test Händler',
            'is_pending' => false,
            'is_recurring' => false,
        ], $attributes));
    }

    private function createStatement(
        CreditCard $card
    ): CreditCardStatement {
        return CreditCardStatement::create([
            'credit_card_id' => $card->id,
            'period_start' => '2026-08-16',
            'period_end' => '2026-09-15',
            'due_date' => '2026-10-05',
            'amount' => 0,
            'status' => 'open',
        ]);
    }

    public function test_expenses_are_added_to_statement_amount(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $card = $this->createCreditCard($user, $account);
        $statement = $this->createStatement($card);

        $this->createTransaction($user, $account, $card, [
            'amount' => 49.99,
        ]);

        $this->createTransaction($user, $account, $card, [
            'amount' => 100.01,
            'transaction_date' => '2026-09-10',
        ]);

        $amount = $this->service->calculateAmount($statement);

        $this->assertSame('150.00', $amount);
    }

    public function test_income_reduces_statement_amount(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $card = $this->createCreditCard($user, $account);
        $statement = $this->createStatement($card);

        $this->createTransaction($user, $account, $card, [
            'amount' => 100.00,
            'type' => 'expense',
        ]);

        $this->createTransaction($user, $account, $card, [
            'amount' => 25.50,
            'type' => 'income',
        ]);

        $amount = $this->service->calculateAmount($statement);

        $this->assertSame('74.50', $amount);
    }

    public function test_transfers_are_ignored(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $card = $this->createCreditCard($user, $account);
        $statement = $this->createStatement($card);

        $this->createTransaction($user, $account, $card, [
            'amount' => 100.00,
            'type' => 'expense',
        ]);

        $this->createTransaction($user, $account, $card, [
            'amount' => 500.00,
            'type' => 'transfer',
        ]);

        $amount = $this->service->calculateAmount($statement);

        $this->assertSame('100.00', $amount);
    }

    public function test_transactions_outside_statement_period_are_ignored(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $card = $this->createCreditCard($user, $account);
        $statement = $this->createStatement($card);

        $this->createTransaction($user, $account, $card, [
            'amount' => 100.00,
            'transaction_date' => '2026-08-16',
        ]);

        $this->createTransaction($user, $account, $card, [
            'amount' => 200.00,
            'transaction_date' => '2026-09-15',
        ]);

        $this->createTransaction($user, $account, $card, [
            'amount' => 500.00,
            'transaction_date' => '2026-08-15',
        ]);

        $this->createTransaction($user, $account, $card, [
            'amount' => 600.00,
            'transaction_date' => '2026-09-16',
        ]);

        $amount = $this->service->calculateAmount($statement);

        $this->assertSame('300.00', $amount);
    }

    public function test_other_credit_card_transactions_are_ignored(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);

        $card = $this->createCreditCard($user, $account, [
            'name' => 'Kreditkarte 1',
        ]);

        $otherCard = $this->createCreditCard($user, $account, [
            'name' => 'Kreditkarte 2',
            'last_four' => '5678',
        ]);

        $statement = $this->createStatement($card);

        $this->createTransaction($user, $account, $card, [
            'amount' => 100.00,
        ]);

        $this->createTransaction($user, $account, $otherCard, [
            'amount' => 500.00,
        ]);

        $amount = $this->service->calculateAmount($statement);

        $this->assertSame('100.00', $amount);
    }

    public function test_pending_transactions_are_ignored(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $card = $this->createCreditCard($user, $account);
        $statement = $this->createStatement($card);

        $this->createTransaction($user, $account, $card, [
            'amount' => 100.00,
            'is_pending' => false,
        ]);

        $this->createTransaction($user, $account, $card, [
            'amount' => 500.00,
            'is_pending' => true,
        ]);

        $amount = $this->service->calculateAmount($statement);

        $this->assertSame('100.00', $amount);
    }

    public function test_soft_deleted_transactions_are_ignored(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $card = $this->createCreditCard($user, $account);
        $statement = $this->createStatement($card);

        $active = $this->createTransaction($user, $account, $card, [
            'amount' => 100.00,
        ]);

        $deleted = $this->createTransaction($user, $account, $card, [
            'amount' => 500.00,
        ]);

        $deleted->delete();

        $amount = $this->service->calculateAmount($statement);

        $this->assertSame('100.00', $amount);
    }

    public function test_empty_statement_has_zero_amount(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $card = $this->createCreditCard($user, $account);
        $statement = $this->createStatement($card);

        $amount = $this->service->calculateAmount($statement);

        $this->assertSame('0.00', $amount);
    }

    public function test_calculation_includes_both_period_boundaries(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $card = $this->createCreditCard($user, $account);
        $statement = $this->createStatement($card);

        $this->createTransaction($user, $account, $card, [
            'amount' => 25.00,
            'transaction_date' => '2026-08-16',
        ]);

        $this->createTransaction($user, $account, $card, [
            'amount' => 75.00,
            'transaction_date' => '2026-09-15',
        ]);

        $amount = $this->service->calculateAmount($statement);

        $this->assertSame('100.00', $amount);
    }

    public function test_generated_statement_gets_calculated_amount(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $card = $this->createCreditCard($user, $account);

        $this->createTransaction($user, $account, $card, [
            'amount' => 125.50,
            'transaction_date' => '2026-08-20',
        ]);

        $this->createTransaction($user, $account, $card, [
            'amount' => 25.50,
            'type' => 'income',
            'transaction_date' => '2026-09-10',
        ]);

        $statement = $this->service->generateFor(
            $card,
            Carbon::parse('2026-08-20')
        );

        $this->assertSame('100.00', $statement->amount);
        $this->assertSame('100.00', $statement->fresh()->amount);
        $this->assertSame('open', $statement->status);
    }

    public function test_existing_open_statement_amount_is_updated(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $card = $this->createCreditCard($user, $account);

        $statement = $this->createStatement($card);

        $this->assertSame('0.00', $statement->amount);

        $this->createTransaction($user, $account, $card, [
            'amount' => 200.00,
            'transaction_date' => '2026-08-20',
        ]);

        $updated = $this->service->generateFor(
            $card,
            Carbon::parse('2026-08-20')
        );

        $this->assertSame($statement->id, $updated->id);
        $this->assertSame('200.00', $updated->amount);
        $this->assertSame('200.00', $updated->fresh()->amount);
    }

    public function test_paid_statement_amount_is_not_automatically_changed(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $card = $this->createCreditCard($user, $account);

        $statement = $this->createStatement($card);

        $statement->update([
            'amount' => 150.00,
            'status' => 'paid',
        ]);

        $this->createTransaction($user, $account, $card, [
            'amount' => 300.00,
            'transaction_date' => '2026-08-20',
        ]);

        $result = $this->service->generateFor(
            $card,
            Carbon::parse('2026-08-20')
        );

        $this->assertSame($statement->id, $result->id);
        $this->assertSame('150.00', $result->amount);
        $this->assertSame('paid', $result->status);
    }


    public function test_generate_for_is_idempotent_for_same_period(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $card = $this->createCreditCard($user, $account);

        $this->createTransaction($user, $account, $card, [
            'amount' => 125.00,
            'transaction_date' => '2026-08-20',
        ]);

        $first = $this->service->generateFor(
            $card,
            Carbon::parse('2026-08-20')
        );

        $second = $this->service->generateFor(
            $card,
            Carbon::parse('2026-08-25')
        );

        $this->assertSame($first->id, $second->id);
        $this->assertSame('125.00', $second->amount);

        $this->assertSame(
            1,
            CreditCardStatement::query()
                ->where('credit_card_id', $card->id)
                ->count()
        );
    }

}
