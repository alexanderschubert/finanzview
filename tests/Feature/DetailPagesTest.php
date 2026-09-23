<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\Loan;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Services\RecurringTransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DetailPagesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['is_active' => true]);

        $this->account = Account::create([
            'user_id' => $this->user->id,
            'name' => 'Girokonto',
            'type' => 'checking',
            'currency' => 'EUR',
            'opening_balance' => 0,
            'include_in_total' => true,
            'is_active' => true,
        ]);
    }

    public function test_budget_show_displays_ring_and_transactions(): void
    {
        $category = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Lebensmittel',
            'type' => 'expense',
            'icon' => '🛒',
            'is_active' => true,
        ]);

        $budget = Budget::create([
            'user_id' => $this->user->id,
            'name' => 'Essen',
            'amount' => 200,
            'period' => 'monthly',
            'start_date' => now()->startOfMonth()->toDateString(),
            'is_active' => true,
        ]);
        $budget->categories()->attach($category->id);

        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 50,
            'transaction_date' => now()->startOfMonth()->toDateString(),
            'description' => 'Wocheneinkauf',
        ]);

        $this->actingAs($this->user)
            ->get(route('budgets.show', ['budget' => $budget, 'month' => now()->format('Y-m')]))
            ->assertOk()
            ->assertSee('role="progressbar"', false)
            ->assertSee('50,00 €')
            ->assertSee('150,00 €')
            ->assertSee('Wocheneinkauf');
    }

    public function test_budget_show_warns_about_missing_categories(): void
    {
        $budget = Budget::create([
            'user_id' => $this->user->id,
            'name' => 'Ohne',
            'amount' => 100,
            'period' => 'monthly',
            'start_date' => now()->startOfMonth()->toDateString(),
            'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->get(route('budgets.show', $budget))
            ->assertOk()
            ->assertSee('keine Kategorien zugeordnet');
    }

    public function test_credit_card_show_displays_wallet_and_statements(): void
    {
        $card = CreditCard::create([
            'user_id' => $this->user->id,
            'name' => 'Reisekarte',
            'last_four' => '4242',
            'credit_limit' => 1000,
            'current_balance' => 250,
            'billing_day' => 15,
            'payment_due_day' => 5,
            'is_active' => true,
        ]);

        $card->statements()->create([
            'period_start' => '2026-08-16',
            'period_end' => '2026-09-15',
            'due_date' => '2026-10-05',
            'amount' => 180.40,
            'status' => 'issued',
        ]);

        $this->actingAs($this->user)
            ->get(route('credit-cards.show', $card))
            ->assertOk()
            ->assertSee('•••• 4242')
            ->assertSee('750,00 €')
            ->assertSee('180,40 €')
            ->assertSee('Abgerechnet');
    }

    public function test_upcoming_dates_keep_the_anchor_day_at_month_end(): void
    {
        $recurring = RecurringTransaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'description' => 'Miete',
            'amount' => 800,
            'type' => 'expense',
            'frequency' => 'monthly',
            'next_date' => '2027-01-31',
            'is_active' => true,
        ]);

        $dates = app(RecurringTransactionService::class)->upcomingDates($recurring->fresh(), 3);

        $this->assertSame(
            ['2027-01-31', '2027-02-28', '2027-03-31'],
            array_map(fn ($date) => $date->toDateString(), $dates)
        );
    }

    public function test_upcoming_dates_respect_end_date_and_pause(): void
    {
        $recurring = RecurringTransaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'description' => 'Abo',
            'amount' => 10,
            'type' => 'expense',
            'frequency' => 'monthly',
            'next_date' => '2027-01-10',
            'end_date' => '2027-02-15',
            'is_active' => true,
        ]);

        $service = app(RecurringTransactionService::class);

        $this->assertCount(2, $service->upcomingDates($recurring->fresh(), 5));

        $recurring->update(['is_active' => false]);

        $this->assertSame([], $service->upcomingDates($recurring->fresh(), 5));
    }

    public function test_recurring_show_lists_upcoming_dates(): void
    {
        $recurring = RecurringTransaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'description' => 'Streaming',
            'amount' => 12.99,
            'type' => 'expense',
            'frequency' => 'monthly',
            'next_date' => now()->addDays(3)->toDateString(),
            'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->get(route('recurring-transactions.show', $recurring))
            ->assertOk()
            ->assertSee('Nächste Termine')
            ->assertSee('in 3 Tagen')
            ->assertSee('155,88 € im Jahr');
    }

    public function test_loan_show_displays_remaining_debt_and_plan(): void
    {
        $this->actingAs($this->user)
            ->post(route('loans.store'), [
                'name' => 'Autokredit',
                'type' => 'loan',
                'principal_amount' => '6000',
                'interest_rate' => '3',
                'installment_amount' => '500',
                'start_date' => now()->addMonth()->startOfMonth()->toDateString(),
                'is_active' => '1',
            ])
            ->assertSessionHasNoErrors();

        $loan = Loan::where('name', 'Autokredit')->firstOrFail();

        $this->actingAs($this->user)
            ->get(route('loans.show', $loan))
            ->assertOk()
            ->assertSee('Restschuld')
            ->assertSee('Nächste Raten')
            ->assertSee('Kompletter Tilgungsplan')
            ->assertSee('Sondertilgung erfassen');
    }
}
