<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Schnellerfassung (Dialog) und Detailspalte der Buchungsliste.
 */
class TransactionQuickTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $account;

    private Category $food;

    private Category $salary;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['is_active' => true]);

        $this->account = Account::create([
            'user_id' => $this->user->id, 'name' => 'Girokonto', 'type' => 'checking', 'currency' => 'EUR',
            'opening_balance' => 0, 'include_in_total' => true, 'is_active' => true,
        ]);

        $this->food = Category::create(['user_id' => $this->user->id, 'name' => 'Lebensmittel', 'type' => 'expense', 'icon' => '🛒', 'is_active' => true]);
        $this->salary = Category::create(['user_id' => $this->user->id, 'name' => 'Gehalt', 'type' => 'income', 'icon' => '💼', 'is_active' => true]);
    }

    private function transaction(array $overrides = [], ?User $user = null): Transaction
    {
        $user ??= $this->user;
        $account = $user->is($this->user) ? $this->account : Account::create([
            'user_id' => $user->id, 'name' => 'Konto', 'type' => 'checking', 'currency' => 'EUR',
            'opening_balance' => 0, 'include_in_total' => true, 'is_active' => true,
        ]);

        return Transaction::create(array_merge([
            'user_id' => $user->id, 'account_id' => $account->id, 'type' => 'expense', 'amount' => 12.50,
            'transaction_date' => '2026-11-03', 'description' => 'REWE Markt',
        ], $overrides));
    }

    public function test_quick_form_offers_accounts_and_categories(): void
    {
        $this->actingAs($this->user)
            ->get(route('transactions.quick'))
            ->assertOk()
            ->assertSee('data-quick-form', false)
            ->assertSee('Lebensmittel')
            ->assertSee('Gehalt')
            ->assertSee('name="account_id" value="' . $this->account->id . '"', false)
            ->assertSee(route('transactions.store'), false);
    }

    public function test_quick_form_needs_a_login(): void
    {
        $this->get(route('transactions.quick'))->assertRedirect('/login');
    }

    public function test_quick_form_posts_to_the_normal_store_route(): void
    {
        $this->actingAs($this->user)
            ->post(route('transactions.store'), [
                'account_id' => $this->account->id, 'type' => 'expense', 'amount' => '23.40',
                'transaction_date' => '2026-11-14', 'description' => 'Tanken', 'category_id' => $this->food->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('23.40', Transaction::where('description', 'Tanken')->firstOrFail()->amount);

        $this->actingAs($this->user)
            ->postJson(route('transactions.store'), ['account_id' => $this->account->id, 'type' => 'expense', 'transaction_date' => '2026-11-14'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['amount', 'description']);
    }

    public function test_pages_offer_the_quick_dialog(): void
    {
        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('id="quick-add"', false)
            ->assertSee('data-quick-add', false)
            ->assertSee(route('transactions.quick'), false);
    }

    public function test_panel_shows_details_and_category_choices(): void
    {
        $transaction = $this->transaction(['category_id' => $this->food->id, 'notes' => 'Wocheneinkauf']);

        $this->actingAs($this->user)
            ->get(route('transactions.panel', $transaction))
            ->assertOk()
            ->assertSee('REWE Markt')
            ->assertSee('12,50 €')
            ->assertSee('Girokonto')
            ->assertSee('Wocheneinkauf')
            ->assertSee('Lebensmittel')
            ->assertDontSee('Gehalt');
    }

    public function test_panel_is_protected(): void
    {
        $other = User::factory()->create(['is_active' => true]);
        $foreign = $this->transaction([], $other);

        $this->actingAs($this->user)->get(route('transactions.panel', $foreign))->assertForbidden();
        $this->actingAs($this->user)->patchJson(route('transactions.quick-update', $foreign), ['notes' => 'x'])->assertForbidden();
    }

    public function test_category_and_note_can_be_changed_quickly(): void
    {
        $transaction = $this->transaction();

        $this->actingAs($this->user)
            ->patchJson(route('transactions.quick-update', $transaction), ['category_id' => $this->food->id, 'notes' => '  Wocheneinkauf  '])
            ->assertOk()
            ->assertJson(['category' => 'Lebensmittel', 'icon' => '🛒']);

        $transaction->refresh();
        $this->assertSame($this->food->id, $transaction->category_id);
        $this->assertSame('Wocheneinkauf', $transaction->notes);

        // Kategorie entfernen, Notiz leeren.
        $this->actingAs($this->user)
            ->patchJson(route('transactions.quick-update', $transaction), ['category_id' => null, 'notes' => ''])
            ->assertOk()
            ->assertJson(['category' => null]);

        $transaction->refresh();
        $this->assertNull($transaction->category_id);
        $this->assertNull($transaction->notes);
    }

    public function test_quick_update_checks_category_type_owner_and_transfers(): void
    {
        $transaction = $this->transaction();

        $this->actingAs($this->user)
            ->patchJson(route('transactions.quick-update', $transaction), ['category_id' => $this->salary->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('category_id');

        $other = User::factory()->create(['is_active' => true]);
        $foreignCategory = Category::create(['user_id' => $other->id, 'name' => 'Fremd', 'type' => 'expense', 'is_active' => true]);

        $this->actingAs($this->user)
            ->patchJson(route('transactions.quick-update', $transaction), ['category_id' => $foreignCategory->id])
            ->assertStatus(422);

        $savings = Account::create([
            'user_id' => $this->user->id, 'name' => 'Sparbuch', 'type' => 'savings', 'currency' => 'EUR',
            'opening_balance' => 0, 'include_in_total' => true, 'is_active' => true,
        ]);
        $transfer = $this->transaction(['type' => 'transfer', 'transfer_account_id' => $savings->id, 'description' => 'Sparen']);

        $this->actingAs($this->user)
            ->patchJson(route('transactions.quick-update', $transfer), ['category_id' => $this->food->id])
            ->assertStatus(422);

        $this->assertNull($transaction->fresh()->category_id);
    }

    public function test_list_has_the_detail_column_hooks(): void
    {
        $transaction = $this->transaction(['category_id' => $this->food->id]);

        $this->actingAs($this->user)
            ->get(route('transactions.index'))
            ->assertOk()
            ->assertSee('id="tx-panel"', false)
            ->assertSee('data-tx-panel="' . route('transactions.panel', $transaction) . '"', false)
            ->assertSee('data-tx-details', false);
    }
}
