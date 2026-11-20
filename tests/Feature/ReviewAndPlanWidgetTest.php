<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Payee;
use App\Models\PlanMonth;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PayeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * „Zu prüfen“ (Kategorie mit einem Klick zuordnen) und das Dashboard-Widget „Plan gegen tatsächlich“.
 */
class ReviewAndPlanWidgetTest extends TestCase
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

    private function spend(string $merchant, float $amount, ?User $user = null, string $type = 'expense'): Transaction
    {
        $user ??= $this->user;
        $account = $user->is($this->user) ? $this->account : Account::create([
            'user_id' => $user->id, 'name' => 'Konto', 'type' => 'checking', 'currency' => 'EUR',
            'opening_balance' => 0, 'include_in_total' => true, 'is_active' => true,
        ]);

        $transaction = Transaction::create([
            'user_id' => $user->id, 'account_id' => $account->id, 'type' => $type, 'amount' => $amount,
            'transaction_date' => now()->toDateString(), 'description' => $merchant, 'merchant' => $merchant,
        ]);

        app(PayeeService::class)->assignUnassigned($user->id);

        return $transaction->fresh();
    }

    public function test_review_page_lists_uncategorized_transactions(): void
    {
        $this->spend('Bäckerei Schmidt', 6.80);
        $categorized = $this->spend('REWE', 20);
        $categorized->update(['category_id' => $this->food->id]);

        $this->actingAs($this->user)
            ->get(route('transactions.review'))
            ->assertOk()
            ->assertSee('Zu prüfen')
            ->assertSee('Bäckerei Schmidt')
            ->assertDontSee('REWE')
            ->assertSee('Lebensmittel');
    }

    public function test_review_page_is_empty_when_everything_is_categorized(): void
    {
        $this->actingAs($this->user)
            ->get(route('transactions.review'))
            ->assertOk()
            ->assertSee('Alles erledigt');
    }

    public function test_category_can_be_assigned(): void
    {
        $transaction = $this->spend('Bäckerei Schmidt', 6.80);

        $this->actingAs($this->user)
            ->post(route('transactions.review.assign', $transaction), ['category_id' => $this->food->id])
            ->assertRedirect(route('transactions.review'))
            ->assertSessionHas('success');

        $this->assertSame($this->food->id, $transaction->fresh()->category_id);
    }

    public function test_assign_can_remember_the_category_for_the_payee_and_apply_it_to_others(): void
    {
        $first = $this->spend('Bäckerei Schmidt', 6.80);
        $second = $this->spend('Bäckerei Schmidt', 4.20);

        $this->actingAs($this->user)
            ->post(route('transactions.review.assign', $first), ['category_id' => $this->food->id, 'remember' => '1'])
            ->assertSessionHas('success');

        $payee = Payee::where('user_id', $this->user->id)->firstOrFail();

        $this->assertSame($this->food->id, $payee->default_category_id);
        $this->assertSame($this->food->id, $second->fresh()->category_id);
    }

    public function test_category_type_must_fit_the_transaction(): void
    {
        $transaction = $this->spend('Bäckerei Schmidt', 6.80);

        $this->actingAs($this->user)
            ->from(route('transactions.review'))
            ->post(route('transactions.review.assign', $transaction), ['category_id' => $this->salary->id])
            ->assertSessionHasErrors('category_id');

        $this->assertNull($transaction->fresh()->category_id);
    }

    public function test_other_users_transactions_and_categories_are_protected(): void
    {
        $other = User::factory()->create(['is_active' => true]);
        $foreign = $this->spend('Fremd', 5, $other);
        $mine = $this->spend('Meins', 5);

        $this->actingAs($this->user)
            ->post(route('transactions.review.assign', $foreign), ['category_id' => $this->food->id])
            ->assertForbidden();

        $foreignCategory = Category::create(['user_id' => $other->id, 'name' => 'Fremd', 'type' => 'expense', 'is_active' => true]);

        $this->actingAs($this->user)
            ->from(route('transactions.review'))
            ->post(route('transactions.review.assign', $mine), ['category_id' => $foreignCategory->id])
            ->assertSessionHasErrors('category_id');

        $this->assertNull($mine->fresh()->category_id);
    }

    public function test_tab_shows_the_open_count_and_the_dashboard_links_to_the_review(): void
    {
        $this->spend('A', 1);
        $this->spend('B', 2);

        $this->actingAs($this->user)
            ->get(route('transactions.index'))
            ->assertOk()
            ->assertSee('Zu prüfen')
            ->assertSee('tabular-nums">2</span>', false);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('transactions.review'), false);
    }

    public function test_plan_widget_compares_plan_and_actual_numbers(): void
    {
        $month = now()->startOfMonth();
        $plan = PlanMonth::create(['user_id' => $this->user->id, 'month' => $month->toDateString()]);
        $plan->groups()->create(['name' => 'Einnahmen', 'kind' => 'income', 'sort_order' => 1])->items()->create(['name' => 'Gehalt', 'amount' => 3000]);
        $plan->groups()->create(['name' => 'Wohnung', 'kind' => 'expense', 'sort_order' => 2])->items()->create(['name' => 'Miete', 'amount' => 1000]);

        $this->spend('Vermieter', 400);
        $income = $this->spend('Arbeitgeber', 1500, null, 'income');

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Plan gegen tatsächlich')
            ->assertSee('400 €')
            ->assertSee('von 1.000 €')
            ->assertSee('Noch 600 € im Plan')
            ->assertSee('1.500 €')
            ->assertSee('von 3.000 €')
            ->assertSee('Noch 1.500 € erwartet');
    }

    public function test_plan_widget_offers_to_create_a_plan(): void
    {
        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Noch kein Plan für diesen Monat')
            ->assertSee('Plan anlegen');
    }
}
