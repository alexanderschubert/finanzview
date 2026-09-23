<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionIndexTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $giro;

    private Account $savings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['is_active' => true]);

        $this->giro = $this->account('Giro');
        $this->savings = $this->account('Sparbuch');
    }

    private function account(string $name): Account
    {
        return Account::create([
            'user_id' => $this->user->id,
            'name' => $name,
            'type' => 'checking',
            'currency' => 'EUR',
            'opening_balance' => 0,
            'include_in_total' => true,
            'is_active' => true,
        ]);
    }

    private function transaction(array $attributes): Transaction
    {
        return Transaction::create(array_merge([
            'user_id' => $this->user->id,
            'account_id' => $this->giro->id,
            'type' => 'expense',
            'amount' => 10,
            'transaction_date' => now()->toDateString(),
            'description' => 'Buchung',
            'is_pending' => false,
        ], $attributes));
    }

    public function test_totals_include_all_filtered_transactions_but_not_transfers(): void
    {
        $this->transaction(['type' => 'income', 'amount' => 2500, 'description' => 'Gehalt']);
        $this->transaction(['type' => 'expense', 'amount' => 700.5, 'description' => 'Miete']);
        $this->transaction(['type' => 'expense', 'amount' => 49.5, 'description' => 'Strom']);
        $this->transaction([
            'type' => 'transfer',
            'amount' => 500,
            'description' => 'Sparen',
            'transfer_account_id' => $this->savings->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('transactions.index'))
            ->assertOk()
            ->assertViewHas('totalIncome', 2500.0)
            ->assertViewHas('totalExpense', 750.0)
            ->assertSee('+2.500,00 €')
            ->assertSee('−750,00 €')
            ->assertSee('Heute');
    }

    public function test_category_filter_is_applied(): void
    {
        $food = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Lebensmittel',
            'type' => 'expense',
            'icon' => '🛒',
            'is_active' => true,
        ]);

        $this->transaction(['description' => 'Wocheneinkauf', 'category_id' => $food->id]);
        $this->transaction(['description' => 'Tankfüllung']);

        $this->actingAs($this->user)
            ->get(route('transactions.index', ['category_id' => $food->id]))
            ->assertOk()
            ->assertSee('Wocheneinkauf')
            ->assertDontSee('Tankfüllung');
    }

    public function test_account_filter_includes_incoming_transfers(): void
    {
        $this->transaction([
            'type' => 'transfer',
            'amount' => 500,
            'description' => 'Sparrate',
            'transfer_account_id' => $this->savings->id,
        ]);
        $this->transaction(['description' => 'Kaffee']);

        $this->actingAs($this->user)
            ->get(route('transactions.index', ['account_id' => $this->savings->id]))
            ->assertOk()
            ->assertSee('Sparrate')
            ->assertSee('Giro → Sparbuch')
            ->assertDontSee('Kaffee');
    }

    public function test_other_users_transactions_are_not_listed_or_counted(): void
    {
        $other = User::factory()->create(['is_active' => true]);

        Transaction::create([
            'user_id' => $other->id,
            'account_id' => $this->giro->id,
            'type' => 'income',
            'amount' => 9999,
            'transaction_date' => now()->toDateString(),
            'description' => 'Fremde Buchung',
            'is_pending' => false,
        ]);

        $this->actingAs($this->user)
            ->get(route('transactions.index'))
            ->assertOk()
            ->assertViewHas('totalIncome', 0.0)
            ->assertDontSee('Fremde Buchung');
    }

    public function test_dashboard_renders_with_data(): void
    {
        $this->transaction(['type' => 'income', 'amount' => 1200, 'description' => 'Gehalt']);
        $this->transaction(['type' => 'expense', 'amount' => 80, 'description' => 'Tanken']);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Gesamtvermögen')
            ->assertSee('aria-label="Nächster Monat"', false);
    }
}
