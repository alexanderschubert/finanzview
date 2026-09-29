<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyReportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $giro;

    private Category $food;

    private Category $rent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['is_active' => true]);

        $this->giro = Account::create([
            'user_id' => $this->user->id, 'name' => 'Girokonto', 'type' => 'checking',
            'currency' => 'EUR', 'opening_balance' => 0, 'include_in_total' => true, 'is_active' => true,
        ]);

        $savings = Account::create([
            'user_id' => $this->user->id, 'name' => 'Sparbuch', 'type' => 'savings',
            'currency' => 'EUR', 'opening_balance' => 0, 'include_in_total' => true, 'is_active' => true,
        ]);

        $this->food = Category::create(['user_id' => $this->user->id, 'name' => 'Lebensmittel', 'type' => 'expense', 'icon' => '🛒', 'is_active' => true]);
        $this->rent = Category::create(['user_id' => $this->user->id, 'name' => 'Miete', 'type' => 'expense', 'icon' => '🏠', 'is_active' => true]);

        // Drei übliche Monate: 3.000 € Gehalt, 800 € Miete, 300 € Lebensmittel.
        foreach (['2026-03', '2026-04', '2026-05'] as $month) {
            $this->book('income', 3000, "{$month}-01", 'Gehalt', null, 'Arbeitgeber GmbH');
            $this->book('expense', 800, "{$month}-03", 'Miete', $this->rent, 'Vermieter');
            $this->book('expense', 300, "{$month}-10", 'Einkauf', $this->food, 'REWE Markt 12');
        }

        // Berichtsmonat Juni: mehr Lebensmittel, ein neuer Händler, eine Umbuchung.
        $this->book('income', 3000, '2026-06-01', 'Gehalt', null, 'Arbeitgeber GmbH');
        $this->book('expense', 800, '2026-06-03', 'Miete', $this->rent, 'Vermieter');
        $this->book('expense', 420, '2026-06-10', 'Einkauf', $this->food, 'REWE Markt 99');
        $this->book('expense', 13.99, '2026-06-15', 'Abo', null, 'NETFLIX.COM');

        Transaction::create([
            'user_id' => $this->user->id, 'account_id' => $this->giro->id, 'transfer_account_id' => $savings->id,
            'type' => 'transfer', 'amount' => 500, 'transaction_date' => '2026-06-20', 'description' => 'Sparen',
        ]);
    }

    private function book(string $type, float $amount, string $date, string $description, ?Category $category, ?string $merchant): void
    {
        Transaction::create([
            'user_id' => $this->user->id, 'account_id' => $this->giro->id, 'category_id' => $category?->id,
            'type' => $type, 'amount' => $amount, 'transaction_date' => $date,
            'description' => $description, 'merchant' => $merchant,
        ]);
    }

    public function test_report_shows_totals_comparison_and_insights(): void
    {
        $this->actingAs($this->user)
            ->get(route('reports.month', ['month' => '2026-06']))
            ->assertOk()
            ->assertSee('Juni 2026')
            ->assertSee('1.233,99 €')                                   // Ausgaben ohne Umbuchung
            ->assertSee('Vormonat 1.100,00 € · Ø 1.100,00 €')
            ->assertSee('Du hast 133,99 € mehr ausgegeben als in einem üblichen Monat (Ø 1.100,00 €).')
            ->assertSee('Lebensmittel: 420,00 € – 40 % mehr als üblich (Ø 300,00 €).')
            ->assertSee('Sparquote 58,9 %')
            ->assertDontSee('Miete: 800,00 €')                             // unverändert → nicht auffällig
            ->assertSee('Verglichen mit dem Vormonat und deinem üblichen Monat (Ø der letzten 3 Monate).');
    }

    public function test_new_merchants_ignore_digits_and_known_ones(): void
    {
        $response = $this->actingAs($this->user)->get(route('reports.month', ['month' => '2026-06']));

        $response->assertSee('Zum ersten Mal')->assertSee('NETFLIX.COM');

        $content = $response->getContent();
        $section = substr($content, strpos($content, 'Zum ersten Mal'));
        $this->assertStringNotContainsString('REWE Markt 99', substr($section, 0, 2000));
    }

    public function test_budget_status_and_largest_expenses(): void
    {
        $budget = Budget::create([
            'user_id' => $this->user->id, 'name' => 'Essen', 'amount' => 400,
            'period' => 'monthly', 'start_date' => '2026-01-01', 'is_active' => true,
        ]);
        $budget->categories()->attach($this->food->id);

        $this->actingAs($this->user)
            ->get(route('reports.month', ['month' => '2026-06']))
            ->assertSee('Budgets')
            ->assertSee('420,00 € von 400,00 €')
            ->assertSee('Überschritten um 20,00 €')
            ->assertSee('Größte Ausgaben')
            ->assertSee('Vermieter');
    }

    public function test_empty_month_and_first_month_without_history(): void
    {
        $this->actingAs($this->user)
            ->get(route('reports.month', ['month' => '2026-08']))
            ->assertOk()
            ->assertSee('Keine Buchungen in diesem Monat');

        $this->actingAs($this->user)
            ->get(route('reports.month', ['month' => '2026-03']))
            ->assertOk()
            ->assertSee('Für Vergleiche mit einem üblichen Monat fehlen noch ältere Buchungen.');
    }

    public function test_navigation_links_to_report_and_invalid_month_falls_back(): void
    {
        $this->actingAs($this->user)
            ->get(route('reports.month', ['month' => 'kaputt']))
            ->assertOk()
            ->assertSee(now()->translatedFormat('F Y'));

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertSee(route('reports.month'), false);
    }
}
