<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Services\RecurringTransactionService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringTransactionProcessingTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
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
            'name' => 'Girokonto',
            'institution' => 'Testbank',
            'type' => 'checking',
            'currency' => 'EUR',
            'opening_balance' => 1000,
            'credit_limit' => 0,
            'iban' => null,
            'account_number' => null,
            'color' => '#000000',
            'icon' => 'wallet',
            'notes' => null,
            'include_in_total' => true,
            'is_active' => true,
        ]);
    }

    private function createCategory(User $user): Category
    {
        return Category::create([
            'user_id' => $user->id,
            'name' => 'Miete',
            'type' => 'expense',
            'icon' => 'home',
            'color' => '#000000',
            'description' => null,
            'is_active' => true,
        ]);
    }

    private function createRecurring(
        User $user,
        Account $account,
        ?Category $category,
        array $overrides = []
    ): RecurringTransaction {
        return RecurringTransaction::create(array_merge([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category?->id,
            'description' => 'Miete',
            'amount' => 850.00,
            'type' => 'expense',
            'frequency' => 'monthly',
            'next_date' => Carbon::today(),
            'end_date' => null,
            'is_active' => true,
        ], $overrides));
    }

    public function test_due_recurring_transaction_is_created(): void
    {
        Carbon::setTestNow('2026-09-04');

        $user = $this->createUser();
        $account = $this->createAccount($user);
        $category = $this->createCategory($user);

        $recurring = $this->createRecurring($user, $account, $category);

        $created = app(RecurringTransactionService::class)->process($recurring);

        $this->assertSame(1, $created);

        $transaction = Transaction::where(
            'recurring_transaction_id',
            $recurring->id
        )->firstOrFail();

        $this->assertSame($user->id, $transaction->user_id);
        $this->assertSame($account->id, $transaction->account_id);
        $this->assertSame($category->id, $transaction->category_id);
        $this->assertSame('2026-09-04', $transaction->transaction_date->format('Y-m-d'));
        $this->assertSame('850.00', $transaction->amount);
        $this->assertTrue($transaction->is_recurring);
        $this->assertFalse($transaction->is_pending);
    }

    public function test_future_recurring_transaction_is_not_created(): void
    {
        Carbon::setTestNow('2026-09-04');

        $user = $this->createUser();
        $account = $this->createAccount($user);
        $category = $this->createCategory($user);

        $recurring = $this->createRecurring(
            $user,
            $account,
            $category,
            [
                'next_date' => Carbon::tomorrow(),
            ]
        );

        $created = app(RecurringTransactionService::class)->process($recurring);

        $this->assertSame(0, $created);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_inactive_recurring_transaction_is_not_processed(): void
    {
        Carbon::setTestNow('2026-09-04');

        $user = $this->createUser();
        $account = $this->createAccount($user);
        $category = $this->createCategory($user);

        $recurring = $this->createRecurring(
            $user,
            $account,
            $category,
            [
                'is_active' => false,
            ]
        );

        $created = app(RecurringTransactionService::class)->process($recurring);

        $this->assertSame(0, $created);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_multiple_missed_monthly_transactions_are_caught_up(): void
    {
        Carbon::setTestNow('2026-09-04');

        $user = $this->createUser();
        $account = $this->createAccount($user);
        $category = $this->createCategory($user);

        $firstDate = Carbon::create(2026, 6, 4);

        $recurring = $this->createRecurring(
            $user,
            $account,
            $category,
            [
                'next_date' => $firstDate,
            ]
        );

        $created = app(RecurringTransactionService::class)->process($recurring);

        $this->assertSame(4, $created);
        $this->assertDatabaseCount('transactions', 4);

        foreach (range(0, 3) as $month) {
            $date = $firstDate->copy()->addMonthsNoOverflow($month);

            $this->assertTrue(
                Transaction::where('recurring_transaction_id', $recurring->id)
                    ->whereDate('transaction_date', $date)
                    ->exists()
            );
        }

        $recurring->refresh();

        $this->assertSame(
            '2026-10-04',
            $recurring->next_date->format('Y-m-d')
        );
    }

    public function test_existing_execution_is_not_created_twice(): void
    {
        Carbon::setTestNow('2026-09-04');

        $user = $this->createUser();
        $account = $this->createAccount($user);
        $category = $this->createCategory($user);

        $recurring = $this->createRecurring($user, $account, $category);

        Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 850.00,
            'transaction_date' => Carbon::today(),
            'description' => 'Miete',
            'merchant' => null,
            'reference' => null,
            'notes' => null,
            'is_pending' => false,
            'is_recurring' => true,
            'recurring_transaction_id' => $recurring->id,
        ]);

        $created = app(RecurringTransactionService::class)->process($recurring);

        $this->assertSame(0, $created);
        $this->assertDatabaseCount('transactions', 1);

        $recurring->refresh();

        $this->assertSame(
            '2026-10-04',
            $recurring->next_date->format('Y-m-d')
        );
    }

    public function test_end_date_allows_execution_on_end_date(): void
    {
        Carbon::setTestNow('2026-09-04');

        $user = $this->createUser();
        $account = $this->createAccount($user);
        $category = $this->createCategory($user);

        $date = Carbon::today();

        $recurring = $this->createRecurring(
            $user,
            $account,
            $category,
            [
                'next_date' => $date,
                'end_date' => $date,
            ]
        );

        $created = app(RecurringTransactionService::class)->process($recurring);

        $this->assertSame(1, $created);

        $this->assertTrue(
            Transaction::where('recurring_transaction_id', $recurring->id)
                ->whereDate('transaction_date', $date)
                ->exists()
        );

        $recurring->refresh();

        $this->assertFalse($recurring->is_active);
        $this->assertSame('2026-10-04', $recurring->next_date->format('Y-m-d'));
    }

    public function test_recurring_transaction_after_end_date_is_deactivated_without_creation(): void
    {
        Carbon::setTestNow('2026-09-04');

        $user = $this->createUser();
        $account = $this->createAccount($user);
        $category = $this->createCategory($user);

        $recurring = $this->createRecurring(
            $user,
            $account,
            $category,
            [
                'next_date' => Carbon::today(),
                'end_date' => Carbon::yesterday(),
            ]
        );

        $created = app(RecurringTransactionService::class)->process($recurring);

        $this->assertSame(0, $created);
        $this->assertDatabaseCount('transactions', 0);

        $recurring->refresh();

        $this->assertFalse($recurring->is_active);
    }

    public function test_weekly_frequency_calculates_next_date_correctly(): void
    {
        Carbon::setTestNow('2026-09-04');

        $user = $this->createUser();
        $account = $this->createAccount($user);
        $category = $this->createCategory($user);

        $recurring = $this->createRecurring(
            $user,
            $account,
            $category,
            [
                'frequency' => 'weekly',
                'next_date' => Carbon::today(),
            ]
        );

        $created = app(RecurringTransactionService::class)->process($recurring);

        $this->assertSame(1, $created);

        $recurring->refresh();

        $this->assertSame(
            '2026-09-11',
            $recurring->next_date->format('Y-m-d')
        );
    }

    public function test_monthly_frequency_handles_end_of_month_without_overflow(): void
    {
        Carbon::setTestNow('2026-02-01');

        $user = $this->createUser();
        $account = $this->createAccount($user);
        $category = $this->createCategory($user);

        $date = Carbon::create(2026, 1, 31);

        $recurring = $this->createRecurring(
            $user,
            $account,
            $category,
            [
                'frequency' => 'monthly',
                'next_date' => $date,
            ]
        );

        $created = app(RecurringTransactionService::class)->process($recurring);

        $this->assertSame(1, $created);

        $recurring->refresh();

        $this->assertSame(
            '2026-02-28',
            $recurring->next_date->format('Y-m-d')
        );
    }

    public function test_quarterly_frequency_calculates_next_date_correctly(): void
    {
        Carbon::setTestNow('2026-09-04');

        $user = $this->createUser();
        $account = $this->createAccount($user);
        $category = $this->createCategory($user);

        $recurring = $this->createRecurring(
            $user,
            $account,
            $category,
            [
                'frequency' => 'quarterly',
                'next_date' => Carbon::today(),
            ]
        );

        $created = app(RecurringTransactionService::class)->process($recurring);

        $this->assertSame(1, $created);

        $recurring->refresh();

        $this->assertSame(
            '2026-12-04',
            $recurring->next_date->format('Y-m-d')
        );
    }

    public function test_yearly_frequency_calculates_next_date_correctly(): void
    {
        Carbon::setTestNow('2026-09-04');

        $user = $this->createUser();
        $account = $this->createAccount($user);
        $category = $this->createCategory($user);

        $account = $this->createAccount($user);
        $category = $this->createCategory($user);

        $recurring = $this->createRecurring(
            $user,
            $account,
            $category,
            [
                'frequency' => 'yearly',
                'next_date' => Carbon::today(),
            ]
        );

        $created = app(RecurringTransactionService::class)->process($recurring);

        $this->assertSame(1, $created);

        $recurring->refresh();

        $this->assertSame(
            '2027-09-04',
            $recurring->next_date->format('Y-m-d')
        );
    }

    public function test_database_prevents_duplicate_recurring_execution(): void
    {
        $user = User::factory()->create();
        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Testkonto',
            'type' => 'checking',
            'balance' => 1000,
            'currency' => 'EUR',
            'is_active' => true,
        ]);

        $recurring = RecurringTransaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'description' => 'Miete',
            'amount' => 500,
            'type' => 'expense',
            'frequency' => 'monthly',
            'next_date' => '2026-09-01',
            'is_active' => true,
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'type' => 'expense',
            'amount' => 500,
            'transaction_date' => '2026-09-01',
            'description' => 'Miete',
            'is_pending' => false,
            'is_recurring' => true,
            'recurring_transaction_id' => $recurring->id,
        ]);

        $this->expectException(QueryException::class);

        Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'type' => 'expense',
            'amount' => 500,
            'transaction_date' => '2026-09-01',
            'description' => 'Miete doppelt',
            'is_pending' => false,
            'is_recurring' => true,
            'recurring_transaction_id' => $recurring->id,
        ]);
    }

    public function test_soft_deleted_recurring_execution_can_be_recreated(): void
    {
        $user = User::factory()->create();
        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Testkonto',
            'type' => 'checking',
            'balance' => 1000,
            'currency' => 'EUR',
            'is_active' => true,
        ]);

        $recurring = RecurringTransaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'description' => 'Miete',
            'amount' => 500,
            'type' => 'expense',
            'frequency' => 'monthly',
            'next_date' => '2026-09-01',
            'is_active' => true,
        ]);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'type' => 'expense',
            'amount' => 500,
            'transaction_date' => '2026-09-01',
            'description' => 'Miete',
            'is_pending' => false,
            'is_recurring' => true,
            'recurring_transaction_id' => $recurring->id,
        ]);

        $transaction->delete();

        $replacement = Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'type' => 'expense',
            'amount' => 500,
            'transaction_date' => '2026-09-01',
            'description' => 'Miete erneut',
            'is_pending' => false,
            'is_recurring' => true,
            'recurring_transaction_id' => $recurring->id,
        ]);

        $this->assertNotNull($replacement->id);
    }

    public function test_created_transaction_belongs_to_same_user_as_recurring_transaction(): void
    {
        Carbon::setTestNow('2026-09-04');

        $user = $this->createUser();
        $otherUser = $this->createUser();

        $account = $this->createAccount($user);
        $category = $this->createCategory($user);

        $recurring = $this->createRecurring($user, $account, $category);

        $created = app(RecurringTransactionService::class)->process($recurring);

        $this->assertSame(1, $created);

        $transaction = Transaction::where(
            'recurring_transaction_id',
            $recurring->id
        )->firstOrFail();

        $this->assertSame($user->id, $transaction->user_id);
        $this->assertNotSame($otherUser->id, $transaction->user_id);
    }
}
