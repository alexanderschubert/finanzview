<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Services\RecurringTransactionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanAndRecurringDateFixesTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function createUser(): User
    {
        return User::factory()->create(['is_active' => true]);
    }

    private function createAccount(User $user, array $attributes = []): Account
    {
        return Account::create(array_merge([
            'user_id' => $user->id,
            'name' => 'Girokonto',
            'institution' => 'Testbank',
            'type' => 'checking',
            'currency' => 'EUR',
            'opening_balance' => 0,
            'is_active' => true,
            'include_in_total' => true,
        ], $attributes));
    }

    private function createRecurring(User $user, Account $account, array $overrides = []): RecurringTransaction
    {
        return RecurringTransaction::create(array_merge([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => null,
            'description' => 'Miete',
            'amount' => 850.00,
            'type' => 'expense',
            'frequency' => 'monthly',
            'next_date' => '2026-01-31',
            'end_date' => null,
            'is_active' => true,
        ], $overrides));
    }

    private function loanData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Kredit',
            'creditor_name' => 'Testbank',
            'principal_amount' => '3000.00',
            'paid_amount' => '0.00',
            'interest_rate' => '5.000',
            'installment_amount' => '1000.00',
            'total_installments' => '4',
            'paid_installments' => '0',
            'start_date' => '2026-01-31',
            'end_date' => null,
            'type' => 'loan',
            'is_active' => '1',
            'notes' => null,
        ], $overrides);
    }

    private function dueDates(Loan $loan): array
    {
        return $loan->payments()
            ->where('payment_type', 'regular')
            ->orderBy('installment_number')
            ->get()
            ->map(fn (LoanPayment $p) => Carbon::parse($p->due_date)->format('Y-m-d'))
            ->all();
    }

    public function test_loan_starting_on_31st_keeps_month_end_due_dates(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->post(route('loans.store'), $this->loanData())
            ->assertRedirect();

        $loan = Loan::where('user_id', $user->id)->firstOrFail();

        $this->assertSame(
            ['2026-01-31', '2026-02-28', '2026-03-31'],
            array_slice($this->dueDates($loan), 0, 3)
        );
    }

    public function test_recurring_monthly_from_31st_does_not_drift(): void
    {
        Carbon::setTestNow('2026-04-01');

        $user = $this->createUser();
        $account = $this->createAccount($user);
        $recurring = $this->createRecurring($user, $account);

        $this->assertSame(31, $recurring->fresh()->anchor_day);

        $created = app(RecurringTransactionService::class)->process($recurring);

        $this->assertSame(3, $created);

        $dates = Transaction::where('recurring_transaction_id', $recurring->id)
            ->orderBy('transaction_date')
            ->get()
            ->map(fn ($t) => $t->transaction_date->format('Y-m-d'))
            ->all();

        $this->assertSame(['2026-01-31', '2026-02-28', '2026-03-31'], $dates);
        $this->assertSame('2026-04-30', $recurring->fresh()->next_date->format('Y-m-d'));
        $this->assertSame(31, $recurring->fresh()->anchor_day);
    }

    public function test_recurring_yearly_on_leap_day_returns_to_29th(): void
    {
        Carbon::setTestNow('2032-03-01');

        $user = $this->createUser();
        $account = $this->createAccount($user);
        $recurring = $this->createRecurring($user, $account, [
            'frequency' => 'yearly',
            'next_date' => '2028-02-29',
        ]);

        app(RecurringTransactionService::class)->process($recurring);

        $dates = Transaction::where('recurring_transaction_id', $recurring->id)
            ->orderBy('transaction_date')
            ->get()
            ->map(fn ($t) => $t->transaction_date->format('Y-m-d'))
            ->all();

        $this->assertSame(
            ['2028-02-29', '2029-02-28', '2030-02-28', '2031-02-28', '2032-02-29'],
            $dates
        );
    }

    public function test_recurring_on_inactive_account_creates_nothing(): void
    {
        Carbon::setTestNow('2026-04-01');

        $user = $this->createUser();
        $account = $this->createAccount($user, ['is_active' => false]);
        $recurring = $this->createRecurring($user, $account);

        $this->assertSame(0, app(RecurringTransactionService::class)->process($recurring));
        $this->assertDatabaseCount('transactions', 0);
        $this->assertSame('2026-01-31', $recurring->fresh()->next_date->format('Y-m-d'));
    }

    public function test_recurring_on_deleted_account_creates_nothing(): void
    {
        Carbon::setTestNow('2026-04-01');

        $user = $this->createUser();
        $account = $this->createAccount($user);
        $recurring = $this->createRecurring($user, $account);
        $account->delete();

        $this->assertSame(0, app(RecurringTransactionService::class)->process($recurring));
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_loan_update_with_new_rate_regenerates_only_unpaid_payments(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->post(route('loans.store'), $this->loanData([
                'principal_amount' => '10000.00',
                'installment_amount' => '500.00',
                'total_installments' => '24',
                'interest_rate' => '5.000',
            ]))
            ->assertRedirect();

        $loan = Loan::where('user_id', $user->id)->firstOrFail();

        // Erste Rate als bezahlt markieren.
        $paid = $loan->payments()->where('installment_number', 1)->firstOrFail();
        $paid->update(['status' => 'paid', 'paid_date' => $paid->due_date]);
        $loan->update(['paid_amount' => $paid->principal_amount, 'paid_installments' => 1]);

        $paidSnapshot = $paid->fresh()->only(['amount', 'interest_amount', 'principal_amount', 'due_date']);
        $oldSecond = $loan->payments()->where('status', 'planned')->orderBy('due_date')->firstOrFail();

        $this->actingAs($user)
            ->put(route('loans.update', $loan), $this->loanData([
                'principal_amount' => '10000.00',
                'paid_amount' => (string) $loan->fresh()->paid_amount,
                'installment_amount' => '500.00',
                'total_installments' => '24',
                'paid_installments' => '1',
                'interest_rate' => '8.000',
            ]))
            ->assertRedirect();

        // Bezahlte Rate unverändert.
        $this->assertEquals($paidSnapshot, $paid->fresh()->only(['amount', 'interest_amount', 'principal_amount', 'due_date']));
        $this->assertSame('paid', $paid->fresh()->status);

        // Nächste geplante Rate neu berechnet (höhere Zinsen), gleicher Termin.
        $newSecond = $loan->payments()->where('status', 'planned')->orderBy('due_date')->firstOrFail();
        $this->assertSame(
            Carbon::parse($oldSecond->due_date)->format('Y-m-d'),
            Carbon::parse($newSecond->due_date)->format('Y-m-d')
        );
        $this->assertGreaterThan((float) $oldSecond->interest_amount, (float) $newSecond->interest_amount);

        // Monatsende-Termine bleiben korrekt.
        $this->assertSame(
            ['2026-01-31', '2026-02-28', '2026-03-31', '2026-04-30'],
            array_slice($this->dueDates($loan->fresh()), 0, 4)
        );
    }
}
