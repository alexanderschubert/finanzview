<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Category;
use App\Models\Loan;
use App\Models\PlanItem;
use App\Models\PlanMonth;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NativeApiPlanningTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $other;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['is_active' => true]);
        $this->other = User::factory()->create(['is_active' => true]);
        [, $this->token] = ApiToken::issue($this->user, 'iPad');
    }

    private function api(string $method, string $uri, array $data = [])
    {
        return $this->withHeader('Authorization', 'Bearer ' . $this->token)->json($method, $uri, $data);
    }

    private function plan(User $user, string $month = '2026-05'): PlanMonth
    {
        return PlanMonth::create(['user_id' => $user->id, 'month' => $month . '-01']);
    }

    public function test_plan_is_empty_without_a_plan_and_lists_groups_with_a_plan(): void
    {
        $this->api('GET', '/api/v1/plan?month=2026-05')->assertOk()->assertJson(['exists' => false, 'groups' => []]);

        $plan = $this->plan($this->user);
        $income = $plan->groups()->create(['name' => 'Einnahmen', 'kind' => 'income', 'sort_order' => 0]);
        $home = $plan->groups()->create(['name' => 'Wohnen', 'kind' => 'expense', 'icon' => '🏠', 'color' => '#38bdf8', 'sort_order' => 1]);
        PlanItem::create(['plan_group_id' => $income->id, 'name' => 'Gehalt', 'amount' => 3000, 'interval' => 'monthly', 'sort_order' => 0]);
        PlanItem::create(['plan_group_id' => $home->id, 'name' => 'Miete', 'amount' => 900, 'interval' => 'monthly', 'due_day' => 1, 'sort_order' => 0]);

        $this->api('GET', '/api/v1/plan?month=2026-05')
            ->assertOk()
            ->assertJsonPath('exists', true)
            ->assertJsonPath('totals.income', 3000)
            ->assertJsonPath('totals.expense', 900)
            ->assertJsonPath('totals.planned_ratio', 0.3)
            ->assertJsonFragment(['name' => 'Miete', 'due_day' => 1, 'state' => 'due']);
    }

    public function test_plan_item_amount_can_be_changed_but_not_for_other_users(): void
    {
        $plan = $this->plan($this->user);
        $group = $plan->groups()->create(['name' => 'Wohnen', 'kind' => 'expense', 'sort_order' => 1]);
        $item = PlanItem::create(['plan_group_id' => $group->id, 'name' => 'Miete', 'amount' => 900, 'interval' => 'monthly', 'sort_order' => 0]);

        $this->api('PATCH', '/api/v1/plan/items/' . $item->id, ['amount' => 950.5])->assertOk()->assertJson(['amount' => 950.5]);
        $this->assertSame('950.50', (string) $item->fresh()->amount);
        $this->api('PATCH', '/api/v1/plan/items/' . $item->id, ['amount' => 'abc'])->assertStatus(422);

        $foreignPlan = $this->plan($this->other);
        $foreignGroup = $foreignPlan->groups()->create(['name' => 'Fremd', 'kind' => 'expense', 'sort_order' => 1]);
        $foreign = PlanItem::create(['plan_group_id' => $foreignGroup->id, 'name' => 'Fremd', 'amount' => 10, 'interval' => 'monthly', 'sort_order' => 0]);
        $this->api('PATCH', '/api/v1/plan/items/' . $foreign->id, ['amount' => 1])->assertNotFound();
    }

    public function test_loans_list_and_detail_are_scoped_to_the_user(): void
    {
        $mine = Loan::create([
            'user_id' => $this->user->id, 'name' => 'Auto', 'principal_amount' => 10000, 'paid_amount' => 2500,
            'interest_rate' => 3.9, 'installment_amount' => 300, 'total_installments' => 36, 'paid_installments' => 9,
            'start_date' => '2026-01-01', 'end_date' => '2028-12-01', 'type' => 'loan', 'is_active' => true,
        ]);
        $mine->payments()->create(['installment_number' => 1, 'due_date' => '2026-02-01', 'amount' => 300, 'interest_amount' => 30, 'principal_amount' => 270, 'remaining_amount' => 9730, 'payment_type' => 'regular', 'status' => 'paid']);
        $theirs = Loan::create([
            'user_id' => $this->other->id, 'name' => 'Fremd', 'principal_amount' => 500, 'paid_amount' => 0, 'interest_rate' => 1,
            'installment_amount' => 50, 'total_installments' => 10, 'paid_installments' => 0, 'start_date' => '2026-01-01', 'type' => 'loan', 'is_active' => true,
        ]);

        $this->api('GET', '/api/v1/loans')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Auto')->assertJsonPath('data.0.progress', 25);
        $this->api('GET', '/api/v1/loans/' . $mine->id)->assertOk()->assertJsonPath('totals.count', 1)->assertJsonPath('schedule.0.number', 1)->assertJsonPath('schedule.0.status', 'paid');
        $this->api('GET', '/api/v1/loans/' . $theirs->id)->assertNotFound();
    }

    public function test_month_report_summarizes_expenses_by_category(): void
    {
        $account = \App\Models\Account::create([
            'user_id' => $this->user->id, 'name' => 'Giro', 'type' => 'checking', 'currency' => 'EUR',
            'opening_balance' => 0, 'include_in_total' => true, 'is_active' => true,
        ]);
        $food = Category::create(['user_id' => $this->user->id, 'name' => 'Lebensmittel', 'type' => 'expense', 'icon' => '🛒', 'is_active' => true]);
        Transaction::create(['user_id' => $this->user->id, 'account_id' => $account->id, 'category_id' => $food->id, 'type' => 'expense', 'amount' => 40, 'transaction_date' => '2026-05-10', 'description' => 'Einkauf']);
        Transaction::create(['user_id' => $this->user->id, 'account_id' => $account->id, 'type' => 'income', 'amount' => 1000, 'transaction_date' => '2026-05-01', 'description' => 'Gehalt']);

        $this->api('GET', '/api/v1/reports/month?month=2026-05')
            ->assertOk()
            ->assertJsonPath('has_data', true)
            ->assertJsonPath('totals.income', 1000)
            ->assertJsonPath('totals.expense', 40)
            ->assertJsonPath('categories.0.name', 'Lebensmittel')
            ->assertJsonPath('categories.0.share', 100);
    }

    public function test_new_endpoints_require_a_token(): void
    {
        foreach (['/api/v1/plan', '/api/v1/loans', '/api/v1/reports/month'] as $uri) {
            $this->getJson($uri)->assertUnauthorized();
        }
    }
}
