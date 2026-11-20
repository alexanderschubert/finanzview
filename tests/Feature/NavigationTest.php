<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sechs Bereiche und Einstellungen in der Seitenleiste, Tabs innerhalb der Bereiche.
 */
class NavigationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['is_active' => true]);
    }

    private function sidebar(string $html): string
    {
        $start = strpos($html, '<aside');

        return substr($html, $start, strpos($html, '</aside>', $start) - $start);
    }

    public function test_sidebar_has_the_areas_and_no_single_page_entries(): void
    {
        $html = $this->actingAs($this->user)->get('/dashboard')->assertOk()->getContent();
        $sidebar = $this->sidebar($html);

        foreach (['Übersicht', 'Konten', 'Buchungen', 'Planung', 'Kredite', 'Auswertung', 'Einstellungen'] as $label) {
            $this->assertStringContainsString($label, $sidebar, $label);
        }

        // Diese Seiten sind jetzt Tabs, keine eigenen Einträge mehr.
        foreach (['Ordnung', 'Kategorien', 'Kreditkarten', 'Budgets', 'Monatsplan', 'Wiederkehrend', 'Empfänger', 'Analysen', 'Monatsbericht'] as $label) {
            $this->assertStringNotContainsString($label, $sidebar, $label);
        }
    }

    public function test_every_area_landing_page_opens(): void
    {
        foreach (['dashboard', 'accounts.index', 'transactions.index', 'plan.index', 'loans.index', 'reports.month', 'settings.index'] as $route) {
            $this->actingAs($this->user)->get(route($route))->assertOk();
        }
    }

    public function test_planning_pages_show_their_tabs(): void
    {
        foreach (['plan.index', 'budgets.index', 'recurring-transactions.index'] as $route) {
            $this->actingAs($this->user)
                ->get(route($route))
                ->assertOk()
                ->assertSee('aria-label="Bereich"', false)
                ->assertSee('Monatsplan')
                ->assertSee('Budgets')
                ->assertSee('Wiederkehrend');
        }
    }

    public function test_active_tab_is_marked(): void
    {
        $html = $this->actingAs($this->user)->get(route('budgets.index'))->getContent();

        $this->assertMatchesRegularExpression('/<a href="[^"]*\/budgets"\s+class="fv-tab"\s+aria-current="page"\s*>Budgets<\/a>/', $html);
    }

    public function test_accounts_area_has_cards_and_bank_tabs(): void
    {
        $this->actingAs($this->user)
            ->get(route('accounts.index'))
            ->assertOk()
            ->assertSee('Kreditkarten')
            ->assertSee('Bankabruf');

        $this->actingAs($this->user)->get(route('credit-cards.index'))->assertOk()->assertSee('Bankabruf');
    }

    public function test_settings_area_groups_general_categories_payees_tags_and_rules(): void
    {
        foreach (['settings.index', 'categories.index', 'payees.index', 'tags.index', 'category-rules.index'] as $route) {
            $this->actingAs($this->user)
                ->get(route($route))
                ->assertOk()
                ->assertSee('Allgemein')
                ->assertSee('Kategorien')
                ->assertSee('Empfänger')
                ->assertSee('Tags')
                ->assertSee('Regeln');
        }
    }

    public function test_settings_subpages_have_no_tab_strip(): void
    {
        $this->actingAs($this->user)
            ->get(route('settings.profile'))
            ->assertOk()
            ->assertDontSee('aria-label="Bereich"', false);
    }

    public function test_forms_and_detail_pages_have_no_tab_strip(): void
    {
        $this->actingAs($this->user)
            ->get(route('transactions.create'))
            ->assertOk()
            ->assertDontSee('aria-label="Bereich"', false);

        $this->actingAs($this->user)
            ->get(route('plan.import'))
            ->assertOk()
            ->assertDontSee('aria-label="Bereich"', false);
    }

    public function test_loans_have_their_own_area_without_tabs(): void
    {
        $this->actingAs($this->user)
            ->get(route('loans.index'))
            ->assertOk()
            ->assertDontSee('aria-label="Bereich"', false);
    }

    public function test_mobile_tab_bar_offers_the_main_areas(): void
    {
        $html = $this->actingAs($this->user)->get('/dashboard')->getContent();
        $bar = substr($html, strpos($html, 'aria-label="Hauptnavigation"'));

        $this->assertStringContainsString('Planung', $bar);
        $this->assertStringContainsString('Kredite', $bar);
        $this->assertStringContainsString('Einstellungen', $bar);
        $this->assertStringNotContainsString('Ordnung', $bar);
    }
}
