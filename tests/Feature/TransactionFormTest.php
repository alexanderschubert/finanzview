<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionFormTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $account;

    private Category $category;

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

        $this->category = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Lebensmittel',
            'type' => 'expense',
            'icon' => '🛒',
            'is_active' => true,
        ]);
    }

    public function test_create_form_renders_type_switch_and_fields(): void
    {
        $this->actingAs($this->user)
            ->get(route('transactions.create'))
            ->assertOk()
            ->assertSee('role="radiogroup"', false)
            ->assertSee('value="transfer"', false)
            ->assertSee('Girokonto')
            ->assertSee('data-type="expense"', false)
            ->assertSee('Buchung speichern')
            ->assertDontSee('delete-transaction-form', false);
    }

    public function test_store_with_pending_switch_off(): void
    {
        $this->actingAs($this->user)
            ->post(route('transactions.store'), [
                'type' => 'expense',
                'amount' => '42.50',
                'description' => 'Wocheneinkauf',
                'transaction_date' => '2026-09-20',
                'account_id' => $this->account->id,
                'category_id' => $this->category->id,
                'is_pending' => '0',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('transactions.index'));

        $this->assertDatabaseHas('transactions', [
            'description' => 'Wocheneinkauf',
            'is_pending' => false,
        ]);
    }

    public function test_edit_form_is_prefilled_and_offers_delete(): void
    {
        $transaction = Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'type' => 'expense',
            'amount' => 19.99,
            'transaction_date' => '2026-09-15',
            'description' => 'Kino',
            'merchant' => 'Cinemaxx',
            'is_pending' => true,
        ]);

        $this->actingAs($this->user)
            ->get(route('transactions.edit', $transaction))
            ->assertOk()
            ->assertSee('value="19.99"', false)
            ->assertSee('value="Kino"', false)
            ->assertSee('value="Cinemaxx"', false)
            ->assertSee('value="2026-09-15"', false)
            ->assertSee('Änderungen speichern')
            ->assertSee('action="' . route('transactions.destroy', $transaction) . '"', false);
    }
}
