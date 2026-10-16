<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\DashboardSetting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardBentoTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $giro;

    private Account $amex;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['is_active' => true, 'name' => 'Alexander Schubert']);

        $this->giro = Account::create([
            'user_id' => $this->user->id, 'name' => 'Girokonto', 'institution' => 'Sparkasse', 'type' => 'checking',
            'currency' => 'EUR', 'opening_balance' => 1000, 'include_in_total' => true, 'is_active' => true,
        ]);

        $this->amex = Account::create([
            'user_id' => $this->user->id, 'name' => 'AMEX', 'type' => 'credit_card',
            'currency' => 'EUR', 'opening_balance' => 0, 'include_in_total' => false, 'is_active' => true,
        ]);
    }

    private function book(Account $account, string $type, float $amount, string $date, ?Category $category = null): Transaction
    {
        return Transaction::create([
            'user_id' => $this->user->id, 'account_id' => $account->id, 'category_id' => $category?->id,
            'type' => $type, 'amount' => $amount, 'transaction_date' => $date, 'description' => 'Test',
        ]);
    }

    public function test_hero_tile_shows_total_with_growth_curve_and_change(): void
    {
        $this->book($this->giro, 'income', 500, now()->subMonths(3)->toDateString());
        $this->book($this->giro, 'expense', 200, now()->subDay()->toDateString());

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('1.300,00 €')
            ->assertSee('aria-label="Verlauf des Gesamtvermögens"', false)
            ->assertSee('Monaten');
    }

    public function test_budgets_are_shown_as_rings_with_state(): void
    {
        $food = Category::create(['user_id' => $this->user->id, 'name' => 'Lebensmittel', 'type' => 'expense', 'icon' => '🛒', 'is_active' => true]);

        $budget = Budget::create([
            'user_id' => $this->user->id, 'name' => 'Essen', 'amount' => 400, 'period' => 'monthly',
            'start_date' => now()->startOfMonth()->subMonth()->toDateString(), 'is_active' => true,
        ]);
        $budget->categories()->attach($food->id);

        $this->book($this->giro, 'expense', 330, now()->toDateString(), $food);

        $response = $this->actingAs($this->user)->get(route('dashboard'))->assertOk();

        $response->assertSee('aria-label="83 Prozent verbraucht"', false)->assertSee('text-amber-500')->assertSee('von 400 €');
    }

    public function test_accounts_are_wallet_cards(): void
    {
        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Kontostand')
            ->assertSee('Girokonto')
            ->assertSee('Sparkasse')
            ->assertSee('AMEX');
    }

    public function test_savings_rate_gauge_handles_negative_and_positive_values(): void
    {
        $this->book($this->giro, 'income', 1000, now()->toDateString());
        $this->book($this->giro, 'expense', 250, now()->toDateString());

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertSee('aria-label="Sparquote 75,0 Prozent"', false)
            ->assertSee('stroke-dasharray="75.0 100"', false);

        Transaction::query()->delete();
        $this->book($this->giro, 'income', 100, now()->toDateString());
        $this->book($this->giro, 'expense', 300, now()->toDateString());

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertSee('-200,0 %', false)
            ->assertSee('Mehr ausgegeben als eingenommen')
            ->assertDontSee('stroke-dasharray="0.0 100"', false);
    }

    public function test_category_bar_shows_segments_and_total(): void
    {
        $food = Category::create(['user_id' => $this->user->id, 'name' => 'Lebensmittel', 'type' => 'expense', 'icon' => '🛒', 'is_active' => true]);
        $rent = Category::create(['user_id' => $this->user->id, 'name' => 'Wohnen', 'type' => 'expense', 'icon' => '🏠', 'is_active' => true]);

        $this->book($this->giro, 'expense', 300, now()->toDateString(), $food);
        $this->book($this->giro, 'expense', 700, now()->toDateString(), $rent);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertSee('aria-label="Verteilung der Ausgaben"', false)
            ->assertSee('Wohnen')
            ->assertSee('70 %')
            ->assertSee('1.000 €');
    }

    public function test_weekly_tile_marks_the_most_expensive_day(): void
    {
        $this->book($this->giro, 'expense', 40, now()->toDateString());
        $this->book($this->giro, 'expense', 210, now()->subDays(2)->toDateString());
        $this->book($this->giro, 'expense', 99, now()->subDays(20)->toDateString());   // außerhalb der Woche

        $response = $this->actingAs($this->user)->get(route('dashboard'))->assertOk();

        $this->assertEquals([0.0, 0.0, 0.0, 0.0, 210.0, 0.0, 40.0], $response->viewData('weekDays')->pluck('amount')->all());

        $response->assertSee('Diese Woche')->assertSee('250 €')->assertSee('Teuerster Tag:')->assertSee('210 €')->assertSee('bg-orange-500');
    }

    public function test_review_tile_lists_open_tasks_and_filters_uncategorized(): void
    {
        $category = Category::create(['user_id' => $this->user->id, 'name' => 'Abos', 'type' => 'expense', 'icon' => '📺', 'is_active' => true]);

        // Umbuchungs-Paar (Abbuchung + Gutschrift) und eine Buchung ohne Kategorie.
        $this->book($this->giro, 'expense', 350, now()->subDays(3)->toDateString(), $category);
        $this->book($this->amex, 'income', 350, now()->subDays(2)->toDateString(), $category);
        $uncategorized = $this->book($this->giro, 'expense', 12, now()->toDateString());
        $uncategorized->update(['description' => 'Ohne Kategorie buchen']);

        $response = $this->actingAs($this->user)->get(route('dashboard'))->assertOk();

        $this->assertSame(1, $response->viewData('reviewTransfers'));
        $this->assertSame(1, $response->viewData('reviewUncategorized'));

        $response->assertSee('Zu prüfen')
            ->assertSee('1 mögliche Umbuchung')
            ->assertSee('1 ohne Kategorie')
            ->assertSee(route('transactions.transfers'), false)
            ->assertSee('uncategorized=1', false);

        $this->actingAs($this->user)
            ->get(route('transactions.index', ['uncategorized' => 1]))
            ->assertSee('Ohne Kategorie buchen')
            ->assertSee('1 Buchung gefunden');
    }

    public function test_review_tile_says_all_done_when_nothing_is_open(): void
    {
        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertSee('Alles erledigt');
    }

    public function test_new_widgets_are_available_and_can_be_hidden(): void
    {
        $this->assertArrayHasKey('review', DashboardSetting::availableWidgets());
        $this->assertArrayHasKey('weekly', DashboardSetting::availableWidgets());

        $this->actingAs($this->user)->get(route('settings.dashboard'))->assertOk()->assertSee('Zu prüfen')->assertSee('Diese Woche');

        $this->actingAs($this->user)
            ->put(route('settings.dashboard.update'), [
                'display_mode' => 'standard',
                'widgets' => ['summary', 'income'],
                'widget_order' => implode(',', DashboardSetting::defaultWidgetOrder()),
            ])
            ->assertSessionHasNoErrors();

        // Der Test-Benutzer hält die alten Einstellungen im Speicher – frisch laden wie ein neuer Aufruf.
        $this->actingAs($this->user->fresh())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Alles erledigt')
            ->assertDontSee('Diese Woche');
    }

    public function test_grid_gives_the_hero_two_rows(): void
    {
        $content = $this->actingAs($this->user)->get(route('dashboard'))->getContent();

        $this->assertStringContainsString('lg:col-span-6 lg:row-span-2', $content);
        $this->assertStringContainsString('[grid-auto-flow:dense]', $content);
    }
}
