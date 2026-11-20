<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\DashboardSetting;
use App\Models\PlanMonth;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PayeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dashboard im Cockpit-Layout: Noch frei bis Monatsende, nächste Zahlungen,
 * Direktzuordnung bei „Zu prüfen“ und die fertigen Anordnungen.
 */
class DashboardCockpitTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['is_active' => true]);

        $this->account = Account::create([
            'user_id' => $this->user->id, 'name' => 'Girokonto', 'type' => 'checking', 'currency' => 'EUR',
            'opening_balance' => 1000, 'include_in_total' => true, 'is_active' => true,
        ]);

        $this->travelTo(now()->startOfMonth()->addDays(18)); // Tag 19
    }

    private function cockpit(): void
    {
        $this->actingAs($this->user)->post(route('settings.dashboard.preset'), ['preset' => 'cockpit'])->assertRedirect(route('dashboard'));
    }

    private function plan(): PlanMonth
    {
        $plan = PlanMonth::create(['user_id' => $this->user->id, 'month' => now()->startOfMonth()->toDateString()]);
        $plan->groups()->create(['name' => 'Einnahmen', 'kind' => 'income', 'sort_order' => 1])->items()->create(['name' => 'Gehalt', 'amount' => 3000, 'due_day' => 28]);
        $housing = $plan->groups()->create(['name' => 'Wohnung', 'kind' => 'expense', 'sort_order' => 2]);
        $housing->items()->create(['name' => 'Miete', 'amount' => 1500, 'due_day' => 1]);
        $housing->items()->create(['name' => 'Strom', 'amount' => 100, 'due_day' => 25]);
        $housing->items()->create(['name' => 'Internet', 'amount' => 50]);

        return $plan;
    }

    private function spend(float $amount, string $merchant = 'Laden', string $type = 'expense'): Transaction
    {
        $transaction = Transaction::create([
            'user_id' => $this->user->id, 'account_id' => $this->account->id, 'type' => $type, 'amount' => $amount,
            'transaction_date' => now()->toDateString(), 'description' => $merchant, 'merchant' => $merchant,
        ]);

        app(PayeeService::class)->assignUnassigned($this->user->id);

        return $transaction->fresh();
    }

    public function test_presets_set_widgets_and_order(): void
    {
        $this->cockpit();

        $setting = $this->user->fresh()->dashboardSetting;

        $this->assertTrue($setting->widgets['cockpit']);
        $this->assertTrue($setting->widgets['upcoming']);
        $this->assertFalse($setting->widgets['summary']);
        $this->assertFalse($setting->widgets['weekly']);
        $this->assertSame(['cockpit', 'upcoming', 'review', 'plan', 'accounts', 'loans', 'categories'], array_slice($setting->widget_order, 0, 7));

        $this->actingAs($this->user)->post(route('settings.dashboard.preset'), ['preset' => 'classic'])->assertRedirect(route('dashboard'));

        $setting = $this->user->fresh()->dashboardSetting;
        $this->assertFalse($setting->widgets['cockpit']);
        $this->assertTrue($setting->widgets['summary']);

        $this->actingAs($this->user)->post(route('settings.dashboard.preset'), ['preset' => 'nope'])->assertSessionHasErrors('preset');
    }

    public function test_layout_hint_is_shown_until_a_choice_is_made(): void
    {
        $this->actingAs($this->user)->get(route('dashboard'))->assertOk()->assertSee('Neu: das Cockpit-Layout');

        $this->cockpit();
        $this->user = $this->user->fresh(); // die Beziehung wurde bei der ersten Anfrage geladen

        $this->actingAs($this->user)->get(route('dashboard'))->assertOk()->assertDontSee('Neu: das Cockpit-Layout');

        // „Beim bisherigen bleiben“ speichert das klassische Layout und blendet den Hinweis aus.
        $other = User::factory()->create(['is_active' => true]);
        $this->actingAs($other)->post(route('settings.dashboard.preset'), ['preset' => 'classic']);
        $this->actingAs($other->fresh())->get(route('dashboard'))->assertDontSee('Neu: das Cockpit-Layout');
    }

    public function test_cockpit_shows_what_is_left_until_month_end(): void
    {
        $this->plan();
        $this->spend(1200);
        $this->cockpit();

        $days = now()->daysInMonth - now()->day;

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Noch frei bis Monatsende')
            ->assertSee('450,00 €') // Plan 1.650 minus 1.200
            ->assertSee('Plan 1.650 €')
            ->assertSee('bisher 1.200 €')
            ->assertSee("{$days} Tage")
            ->assertSee('73 %');
    }

    public function test_cockpit_turns_red_when_over_plan(): void
    {
        $this->plan();
        $this->spend(1800);
        $this->cockpit();

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('−150,00 €')
            ->assertSee('text-red-600', false);
    }

    public function test_cockpit_without_a_plan_falls_back_to_the_monthly_balance(): void
    {
        $this->spend(100);
        $this->spend(1000, 'Arbeitgeber', 'income');
        $this->cockpit();

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Saldo im Monat')
            ->assertSee('900,00 €')
            ->assertSee('Monatsplan');
    }

    public function test_upcoming_lists_the_next_payments_from_today_on(): void
    {
        $this->plan();
        $this->cockpit();

        $html = $this->actingAs($this->user)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('Nächste Zahlungen', $html);
        $this->assertStringContainsString('Strom', $html);      // Tag 25
        $this->assertStringContainsString('Gehalt', $html);     // Tag 28
        $this->assertStringNotContainsString('Internet', $html); // ohne festen Tag
        $this->assertLessThan(strpos($html, 'Gehalt'), strpos($html, 'Strom'));
        $this->assertStringContainsString(route('plan.calendar', ['month' => now()->format('Y-m')]), $html);
    }

    public function test_upcoming_continues_into_the_next_month(): void
    {
        $this->plan();
        $next = PlanMonth::create(['user_id' => $this->user->id, 'month' => now()->addMonthNoOverflow()->startOfMonth()->toDateString()]);
        $next->groups()->create(['name' => 'Wohnung', 'kind' => 'expense', 'sort_order' => 1])->items()->create(['name' => 'Miete Folgemonat', 'amount' => 1500, 'due_day' => 1]);
        $this->cockpit();

        $this->actingAs($this->user)->get(route('dashboard'))->assertOk()->assertSee('Miete Folgemonat');
    }

    public function test_upcoming_has_an_empty_state(): void
    {
        $this->cockpit();

        $this->actingAs($this->user)->get(route('dashboard'))->assertOk()->assertSee('Keine Zahlungen mit festem Tag');
    }

    public function test_review_tile_assigns_inline_and_returns_to_the_dashboard(): void
    {
        $food = Category::create(['user_id' => $this->user->id, 'name' => 'Lebensmittel', 'type' => 'expense', 'icon' => '🛒', 'is_active' => true]);
        $transaction = $this->spend(6.8, 'Bäckerei Schmidt');
        $this->cockpit();

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Bäckerei Schmidt')
            ->assertSee('Lebensmittel')
            ->assertSee('name="back" value="dashboard"', false)
            ->assertSee(route('transactions.review.assign', $transaction), false);

        $this->actingAs($this->user)
            ->post(route('transactions.review.assign', $transaction), ['category_id' => $food->id, 'back' => 'dashboard'])
            ->assertRedirect(route('dashboard'));

        $this->assertSame($food->id, $transaction->fresh()->category_id);
    }

    public function test_classic_layout_keeps_the_small_review_tile(): void
    {
        $this->spend(6.8, 'Bäckerei Schmidt');
        $this->actingAs($this->user)->post(route('settings.dashboard.preset'), ['preset' => 'classic']);
        $this->user = $this->user->fresh();

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Zu prüfen')
            ->assertDontSee('name="back" value="dashboard"', false)
            ->assertDontSee('Noch frei bis Monatsende');
    }

    public function test_settings_page_offers_the_presets(): void
    {
        $this->actingAs($this->user)
            ->get(route('settings.dashboard'))
            ->assertOk()
            ->assertSee('Fertige Anordnung')
            ->assertSee('Cockpit')
            ->assertSee('Klassisch')
            ->assertSee(route('settings.dashboard.preset'), false);
    }

    public function test_registry_lists_the_new_widgets_first(): void
    {
        $keys = array_keys(DashboardSetting::availableWidgets());

        $this->assertSame(['cockpit', 'upcoming'], array_slice($keys, 0, 2));
    }
}
