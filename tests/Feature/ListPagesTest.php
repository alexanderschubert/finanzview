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

class ListPagesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $giro;

    private Category $food;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['is_active' => true]);

        $this->giro = Account::create([
            'user_id' => $this->user->id,
            'name' => 'Girokonto',
            'type' => 'checking',
            'currency' => 'EUR',
            'opening_balance' => 1000,
            'include_in_total' => true,
            'is_active' => true,
        ]);

        $this->food = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Lebensmittel',
            'type' => 'expense',
            'icon' => '🛒',
            'color' => '#16a57a',
            'is_active' => true,
        ]);
    }

    public function test_settings_index_lists_groups_and_hides_admin_for_users(): void
    {
        $this->actingAs($this->user)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('Erscheinungsbild')
            ->assertSee('Daten &amp; Export', false)
            ->assertDontSee('Bald')
            ->assertDontSee(route('admin.index'), false);
    }

    public function test_accounts_index_shows_balance_and_total(): void
    {
        Account::create([
            'user_id' => $this->user->id,
            'name' => 'Depot',
            'type' => 'investment',
            'currency' => 'EUR',
            'opening_balance' => 5000,
            'include_in_total' => false,
            'is_active' => true,
        ]);

        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->giro->id,
            'type' => 'expense',
            'amount' => 250,
            'transaction_date' => now()->toDateString(),
            'description' => 'Einkauf',
        ]);

        $this->actingAs($this->user)
            ->get(route('accounts.index'))
            ->assertOk()
            ->assertSee('Girokonto')
            ->assertSee('750,00 €')
            ->assertSee('nicht im Gesamtvermögen')
            ->assertSee('1 von 2 Konten eingerechnet');
    }

    public function test_categories_index_groups_by_type(): void
    {
        Category::create([
            'user_id' => $this->user->id,
            'name' => 'Gehalt',
            'type' => 'income',
            'icon' => '💰',
            'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->get(route('categories.index'))
            ->assertOk()
            ->assertSeeInOrder(['Ausgaben', 'Lebensmittel', 'Einnahmen', 'Gehalt']);
    }

    public function test_budgets_index_shows_summary_for_selected_month(): void
    {
        $budget = Budget::create([
            'user_id' => $this->user->id,
            'name' => 'Essen',
            'amount' => 400,
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'period' => 'monthly',
            'is_active' => true,
            'icon' => '🛒',
        ]);

        $budget->categories()->attach($this->food->id);

        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->giro->id,
            'category_id' => $this->food->id,
            'type' => 'expense',
            'amount' => 100,
            'transaction_date' => now()->startOfMonth()->toDateString(),
            'description' => 'Wocheneinkauf',
        ]);

        $this->actingAs($this->user)
            ->get(route('budgets.index', ['month' => now()->format('Y-m')]))
            ->assertOk()
            ->assertSee('Essen')
            ->assertSee('Geplant')
            ->assertSee('von 400,00 €')
            ->assertSee('Noch 300,00 € verfügbar');
    }

    public function test_recurring_index_shows_monthly_fixed_costs(): void
    {
        foreach ([['Miete', 'monthly', 600], ['Versicherung', 'yearly', 120]] as [$description, $frequency, $amount]) {
            RecurringTransaction::create([
                'user_id' => $this->user->id,
                'account_id' => $this->giro->id,
                'description' => $description,
                'amount' => $amount,
                'type' => 'expense',
                'frequency' => $frequency,
                'next_date' => now()->addDays(5)->toDateString(),
                'is_active' => true,
            ]);
        }

        $this->actingAs($this->user)
            ->get(route('recurring-transactions.index'))
            ->assertOk()
            ->assertSee('Miete')
            ->assertSee('Jährlich')
            ->assertSee('610,00 €');
    }
}
