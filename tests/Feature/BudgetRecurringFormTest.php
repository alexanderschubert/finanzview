<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetRecurringFormTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $account;

    private Category $food;

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

        $this->food = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Lebensmittel',
            'type' => 'expense',
            'icon' => '🛒',
            'is_active' => true,
        ]);
    }

    public function test_budget_form_sends_category_ids(): void
    {
        $this->actingAs($this->user)
            ->get(route('budgets.create'))
            ->assertOk()
            ->assertSee('name="category_ids[]"', false)
            ->assertDontSee('name="categories[]"', false);
    }

    public function test_monthly_budget_without_end_date_keeps_categories_and_runs_on(): void
    {
        $this->actingAs($this->user)
            ->post(route('budgets.store'), [
                'name' => 'Essen',
                'amount' => '400',
                'period' => 'monthly',
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => '',
                'category_ids' => [$this->food->id],
                'is_active' => '1',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('budgets.index'));

        $budget = Budget::where('name', 'Essen')->firstOrFail();

        $this->assertNull($budget->end_date);
        $this->assertEquals([$this->food->id], $budget->categories->pluck('id')->all());

        // Im übernächsten Monat gilt das Budget weiterhin und zählt Ausgaben.
        $later = now()->startOfMonth()->addMonths(2);

        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->food->id,
            'type' => 'expense',
            'amount' => 50,
            'transaction_date' => $later->copy()->addDays(3)->toDateString(),
            'description' => 'Einkauf',
        ]);

        $this->actingAs($this->user)
            ->get(route('budgets.index', ['month' => $later->format('Y-m')]))
            ->assertOk()
            ->assertSee('Noch 350,00 € verfügbar');
    }

    public function test_custom_budget_still_requires_end_date(): void
    {
        $this->actingAs($this->user)
            ->post(route('budgets.store'), [
                'name' => 'Urlaub',
                'amount' => '1000',
                'period' => 'custom',
                'start_date' => now()->toDateString(),
                'end_date' => '',
            ])
            ->assertSessionHasErrors('end_date');
    }

    public function test_budget_edit_prefills_selected_categories(): void
    {
        $budget = Budget::create([
            'user_id' => $this->user->id,
            'name' => 'Essen',
            'amount' => 400,
            'period' => 'monthly',
            'start_date' => now()->startOfMonth()->toDateString(),
            'is_active' => true,
        ]);
        $budget->categories()->attach($this->food->id);

        $this->actingAs($this->user)
            ->get(route('budgets.edit', $budget))
            ->assertOk()
            ->assertSee('value="' . $this->food->id . '" class="sr-only" checked', false);
    }

    public function test_recurring_form_renders_and_stores(): void
    {
        $this->actingAs($this->user)
            ->get(route('recurring-transactions.create'))
            ->assertOk()
            ->assertSee('name="frequency"', false)
            ->assertSee('Girokonto');

        $this->actingAs($this->user)
            ->post(route('recurring-transactions.store'), [
                'type' => 'expense',
                'amount' => '12.99',
                'frequency' => 'monthly',
                'description' => 'Streaming',
                'account_id' => $this->account->id,
                'next_date' => now()->addDays(10)->toDateString(),
                'end_date' => '',
                'is_active' => '1',
            ])
            ->assertSessionHasNoErrors();

        $recurring = RecurringTransaction::where('description', 'Streaming')->firstOrFail();

        $this->actingAs($this->user)
            ->get(route('recurring-transactions.edit', $recurring))
            ->assertOk()
            ->assertSee('value="12.99"', false)
            ->assertSee('Änderungen speichern');
    }
}
