<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\PlanMonth;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PlanService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanCheckingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-15');
        $this->user = User::factory()->create(['is_active' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function account(array $attrs = []): Account
    {
        return Account::create(array_merge([
            'user_id' => $this->user->id, 'name' => 'Giro', 'type' => 'checking',
            'currency' => 'EUR', 'opening_balance' => 100, 'is_active' => true,
        ], $attrs));
    }

    private function book(Account $account, string $type, float $amount, string $date): void
    {
        Transaction::create([
            'user_id' => $this->user->id, 'account_id' => $account->id, 'type' => $type,
            'amount' => $amount, 'transaction_date' => $date, 'description' => 'x',
        ]);
    }

    private function plan(string $month, float $income, float $expense): PlanMonth
    {
        $plan = PlanMonth::create(['user_id' => $this->user->id, 'month' => $month . '-01']);
        $in = $plan->groups()->create(['name' => 'Einkommen', 'kind' => 'income', 'sort_order' => 0]);
        $in->items()->create(['name' => 'Gehalt', 'amount' => $income, 'interval' => 'monthly', 'sort_order' => 0]);
        $out = $plan->groups()->create(['name' => 'Fix', 'kind' => 'expense', 'sort_order' => 1]);
        $out->items()->create(['name' => 'Miete', 'amount' => $expense, 'interval' => 'monthly', 'sort_order' => 0]);

        return $plan;
    }

    public function test_start_is_the_balance_at_the_end_of_the_previous_month(): void
    {
        $giro = $this->account();
        $this->book($giro, 'income', 500, '2026-10-31');
        $this->book($giro, 'expense', 50, '2026-10-02');
        $this->book($giro, 'expense', 999, '2026-11-03'); // zählt erst für den Folgemonat

        $this->plan('2026-11', 2000, 1500);

        $c = app(PlanService::class)->checking($this->user, Carbon::parse('2026-11-01'), 500.0);

        $this->assertSame(550.0, $c['start']);
        $this->assertSame(1050.0, $c['end']);
        $this->assertSame(0.0, $c['overdrawn']);
        $this->assertFalse($c['start_estimated']);
    }

    public function test_overdrawn_amount_and_dispo_are_reported(): void
    {
        $giro = $this->account(['opening_balance' => -200, 'credit_limit' => 500]);

        $c = app(PlanService::class)->checking($this->user, Carbon::parse('2026-11-01'), -100.0);

        $this->assertSame(-300.0, $c['end']);
        $this->assertSame(300.0, $c['overdrawn']);
        $this->assertSame(200.0, $c['limit_left']);
        $this->assertSame(0.0, $c['over_limit']);

        $giro->update(['credit_limit' => 250]);
        $c = app(PlanService::class)->checking($this->user, Carbon::parse('2026-11-01'), -100.0);
        $this->assertSame(50.0, $c['over_limit']);
    }

    public function test_future_month_starts_with_the_estimated_end_of_the_planned_previous_month(): void
    {
        $this->account(['opening_balance' => 1000]);
        $this->plan('2026-11', 2000, 2500); // −500
        $this->plan('2026-12', 2000, 1500); // +500

        $c = app(PlanService::class)->checking($this->user, Carbon::parse('2026-12-01'), 500.0);

        $this->assertSame(500.0, $c['start']);
        $this->assertTrue($c['start_estimated']);
        $this->assertSame(1000.0, $c['end']);
    }

    public function test_other_account_types_and_other_users_are_ignored(): void
    {
        $this->account(['type' => 'savings', 'opening_balance' => 9999]);
        $this->assertNull(app(PlanService::class)->checking($this->user, Carbon::parse('2026-11-01'), 0.0));

        $other = User::factory()->create(['is_active' => true]);
        Account::create(['user_id' => $other->id, 'name' => 'X', 'type' => 'checking', 'currency' => 'EUR', 'opening_balance' => 5, 'is_active' => true]);
        $this->assertNull(app(PlanService::class)->checking($this->user, Carbon::parse('2026-11-01'), 0.0));
    }

    public function test_plan_page_shows_forecast_and_overdrawn_hint(): void
    {
        $this->account(['opening_balance' => 100]);
        $this->plan('2026-11', 1000, 1500);

        $this->actingAs($this->user)->get('/plan?month=2026-11')
            ->assertOk()
            ->assertSee('Geschätzt zum Monatsende')
            ->assertSee('überzogen – bitte versuchen auszugleichen');
    }

    public function test_manual_start_balance_replaces_the_automatic_one_and_can_be_reset(): void
    {
        $this->account(['opening_balance' => 100]);
        $plan = $this->plan('2026-11', 1000, 1500);

        $this->actingAs($this->user)
            ->put(route('plan.start-balance', $plan), ['start_balance' => '-1.234,50'])
            ->assertRedirect();

        $c = app(PlanService::class)->checking($this->user, Carbon::parse('2026-11-01'));
        $this->assertSame(-1234.5, $c['start']);
        $this->assertTrue($c['start_manual']);
        $this->assertSame(-1734.5, $c['end']);

        $this->actingAs($this->user)->put(route('plan.start-balance', $plan), ['start_balance' => ''])->assertRedirect();

        $c = app(PlanService::class)->checking($this->user, Carbon::parse('2026-11-01'));
        $this->assertSame(100.0, $c['start']);
        $this->assertFalse($c['start_manual']);
    }

    public function test_invalid_manual_amount_and_foreign_plans_are_rejected(): void
    {
        $this->account();
        $plan = $this->plan('2026-11', 1000, 500);

        $this->actingAs($this->user)
            ->put(route('plan.start-balance', $plan), ['start_balance' => 'abc'])
            ->assertSessionHasErrors('start_balance');

        $other = User::factory()->create(['is_active' => true]);
        $this->actingAs($other)->put(route('plan.start-balance', $plan), ['start_balance' => '5'])->assertNotFound();
        $this->assertNull($plan->fresh()->start_balance);
    }

    public function test_manual_start_balance_is_chained_into_the_next_planned_month_and_works_without_an_account(): void
    {
        $nov = $this->plan('2026-11', 2000, 1500); // +500
        $this->plan('2026-12', 2000, 2500);        // −500
        $nov->update(['start_balance' => 300]);

        $c = app(PlanService::class)->checking($this->user, Carbon::parse('2026-12-01'));

        $this->assertSame(800.0, $c['start']);
        $this->assertTrue($c['start_estimated']);
        $this->assertSame(300.0, $c['end']);
    }

    public function test_api_sets_and_resets_the_manual_start_balance(): void
    {
        $this->account();
        $this->plan('2026-11', 1000, 400);
        [, $token] = \App\Models\ApiToken::issue($this->user, 'iPad');
        $headers = ['Authorization' => 'Bearer ' . $token];

        $this->patchJson('/api/v1/plan/start-balance', ['month' => '2026-11', 'start_balance' => 250.5], $headers)
            ->assertOk()
            ->assertJsonPath('checking.start', 250.5)
            ->assertJsonPath('checking.start_manual', true)
            ->assertJsonPath('checking.end', 850.5);

        $this->patchJson('/api/v1/plan/start-balance', ['month' => '2026-11', 'start_balance' => null], $headers)
            ->assertOk()
            ->assertJsonPath('checking.start', 100)
            ->assertJsonPath('checking.start_manual', false);
    }
}
