<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ReportService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Account $account;
    private Account $savings;
    private Category $food;
    private Category $salary;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-03-15 12:00:00');
        \Illuminate\Support\Carbon::setTestNow('2026-03-15 12:00:00');

        $this->user = User::factory()->create(['is_active' => true]);

        $this->account = $this->account('Giro');
        $this->savings = $this->account('Spar');

        $this->food = Category::create(['user_id' => $this->user->id, 'name' => 'Lebensmittel', 'type' => 'expense', 'icon' => '🛒', 'is_active' => true]);
        $this->salary = Category::create(['user_id' => $this->user->id, 'name' => 'Gehalt', 'type' => 'income', 'icon' => '💰', 'is_active' => true]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        \Illuminate\Support\Carbon::setTestNow();

        parent::tearDown();
    }

    private function account(string $name, ?User $user = null): Account
    {
        return Account::create([
            'user_id' => ($user ?? $this->user)->id, 'name' => $name, 'type' => 'checking',
            'currency' => 'EUR', 'opening_balance' => 0, 'include_in_total' => true, 'is_active' => true,
        ]);
    }

    private function booking(string $type, float $amount, string $date, array $extra = []): Transaction
    {
        return Transaction::create(array_merge([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'type' => $type,
            'amount' => $amount,
            'transaction_date' => $date,
            'description' => 'Test',
        ], $extra));
    }

    public function test_guests_are_redirected(): void
    {
        $this->get('/reports')->assertRedirect('/login');
    }

    public function test_report_totals_categories_and_comparison(): void
    {
        // März (aktueller Zeitraum "Dieser Monat")
        $this->booking('income', 3000, '2026-03-01', ['category_id' => $this->salary->id]);
        $this->booking('expense', 100.10, '2026-03-05', ['category_id' => $this->food->id, 'merchant' => 'REWE']);
        $this->booking('expense', 0.20, '2026-03-31', ['category_id' => $this->food->id, 'merchant' => 'rewe']);
        $this->booking('expense', 50, '2026-03-10', ['description' => 'Kino']);
        // Umbuchung zählt nicht
        $this->booking('transfer', 500, '2026-03-02', ['transfer_account_id' => $this->savings->id]);
        // Februar (Vorperiode)
        $this->booking('expense', 200, '2026-02-28', ['category_id' => $this->food->id]);
        // Außerhalb
        $this->booking('expense', 999, '2026-04-01');

        $response = $this->actingAs($this->user)->get('/reports?period=this_month');

        $response->assertOk();
        $response->assertSee('Analysen');

        $report = $response->viewData('report');

        $this->assertSame(3000.0, $report['totals']['income']);
        $this->assertSame(150.3, $report['totals']['expense']);
        $this->assertSame(2849.7, $report['totals']['balance']);
        $this->assertSame(200.0, $report['previous_totals']['expense']);
        $this->assertEqualsWithDelta(-24.85, $report['changes']['expense'], 0.051);

        $food = $report['expense_categories']->firstWhere('name', 'Lebensmittel');
        $this->assertSame(100.3, $food['amount']);
        $this->assertSame(2, $food['count']);
        $this->assertSame(-49.9, $food['change']);
        $this->assertNotNull($report['expense_categories']->firstWhere('name', 'Ohne Kategorie'));

        // REWE und rewe werden zusammengefasst
        $this->assertSame('REWE', $report['top_merchants']->first()['name']);
        $this->assertSame(2, $report['top_merchants']->first()['count']);
    }

    public function test_multi_month_report_has_monthly_rows_and_matrix(): void
    {
        $this->booking('expense', 10, '2026-01-31', ['category_id' => $this->food->id]);
        $this->booking('expense', 20, '2026-03-01', ['category_id' => $this->food->id]);

        $report = $this->actingAs($this->user)
            ->get('/reports?period=last_3_months')
            ->assertOk()
            ->viewData('report');

        $this->assertSame(['2026-01', '2026-02', '2026-03'], $report['monthly']->pluck('key')->all());
        $this->assertSame([10.0, 0.0, 20.0], $report['monthly']->pluck('expense')->all());
        $this->assertSame(
            ['2026-01' => 10.0, '2026-02' => 0.0, '2026-03' => 20.0],
            $report['category_matrix']['rows']->first()['months']
        );
    }

    public function test_account_filter_and_foreign_accounts(): void
    {
        $this->booking('expense', 10, '2026-03-02');
        $this->booking('expense', 40, '2026-03-02', ['account_id' => $this->savings->id]);

        $report = $this->actingAs($this->user)
            ->get('/reports?period=this_month&account_id=' . $this->savings->id)
            ->assertOk()
            ->viewData('report');

        $this->assertSame(40.0, $report['totals']['expense']);

        $other = User::factory()->create(['is_active' => true]);
        $foreign = $this->account('Fremd', $other);

        $this->actingAs($this->user)
            ->get('/reports?account_id=' . $foreign->id)
            ->assertSessionHasErrors('account_id');
    }

    public function test_other_users_data_is_not_included(): void
    {
        $other = User::factory()->create(['is_active' => true]);
        $foreign = $this->account('Fremd', $other);

        Transaction::create([
            'user_id' => $other->id, 'account_id' => $foreign->id, 'type' => 'expense',
            'amount' => 77, 'transaction_date' => '2026-03-03', 'description' => 'X',
        ]);

        $report = $this->actingAs($this->user)
            ->get('/reports?period=this_month')
            ->assertOk()
            ->viewData('report');

        $this->assertSame(0, $report['transaction_count']);
    }

    public function test_custom_period_and_csv_export(): void
    {
        $this->booking('expense', 12.5, '2026-02-10', ['category_id' => $this->food->id]);

        $response = $this->actingAs($this->user)
            ->get('/reports/export?period=custom&from=2026-02-01&to=2026-02-28');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        $csv = $response->streamedContent();

        $this->assertStringContainsString('01.02.2026 – 28.02.2026', $csv);
        $this->assertStringContainsString('Lebensmittel;12,50', $csv);
    }

    public function test_previous_period_uses_whole_months(): void
    {
        $service = new ReportService();

        [$start, $end] = $service->resolvePeriod('last_month', null, null, CarbonImmutable::parse('2026-03-31'));
        $this->assertSame('2026-02-01', $start->toDateString());
        $this->assertSame('2026-02-28', $end->toDateString());

        [$prevStart, $prevEnd] = $service->previousPeriod($start, $end);
        $this->assertSame('2026-01-01', $prevStart->toDateString());
        $this->assertSame('2026-01-31', $prevEnd->toDateString());

        [$s, $e] = $service->resolvePeriod('custom', '2026-03-10', '2026-03-19');
        [$ps, $pe] = $service->previousPeriod($s, $e);
        $this->assertSame('2026-02-28', $ps->toDateString());
        $this->assertSame('2026-03-09', $pe->toDateString());
    }
}
