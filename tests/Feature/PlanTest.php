<?php

namespace Tests\Feature;

use App\Models\PlanGroup;
use App\Models\PlanItem;
use App\Models\PlanMonth;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PlanService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PlanTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['is_active' => true]);
    }

    private function plan(string $month = '2026-05', ?User $user = null): PlanMonth
    {
        return PlanMonth::create(['user_id' => ($user ?? $this->user)->id, 'month' => $month . '-01']);
    }

    private function group(PlanMonth $plan, string $name, string $kind = 'expense'): PlanGroup
    {
        return $plan->groups()->create(['name' => $name, 'kind' => $kind, 'sort_order' => $plan->groups()->count()]);
    }

    private function item(PlanGroup $group, string $name, float $amount, ?string $endsOn = null, string $interval = 'monthly', ?string $startsOn = null): PlanItem
    {
        return $group->items()->create([
            'name' => $name, 'amount' => $amount, 'ends_on' => $endsOn,
            'interval' => $interval, 'starts_on' => $startsOn, 'sort_order' => $group->items()->count(),
        ]);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/plan')->assertRedirect('/login');
    }

    public function test_empty_month_offers_to_create_a_plan(): void
    {
        $this->actingAs($this->user)
            ->get('/plan?month=2026-05')
            ->assertOk()
            ->assertSee('Noch kein Plan für')
            ->assertSee('Leeren Plan anlegen');
    }

    public function test_navigation_links_to_the_plan(): void
    {
        $this->actingAs($this->user)
            ->get('/dashboard')
            ->assertSee(route('plan.index'), false);
    }

    public function test_empty_plan_is_created_with_an_income_group(): void
    {
        $this->actingAs($this->user)
            ->post('/plan', ['month' => '2026-05'])
            ->assertRedirect('/plan?month=2026-05');

        $plan = PlanMonth::first();

        $this->assertSame('2026-05', $plan->month->format('Y-m'));
        $this->assertSame(['Einnahmen'], $plan->groups->pluck('name')->all());
        $this->assertTrue($plan->groups->first()->isIncome());
    }

    public function test_plan_is_copied_from_previous_month_without_ended_items(): void
    {
        $may = $this->plan('2026-05');
        $rates = $this->group($may, 'Raten', 'rate');
        $this->item($rates, 'Couch', 74.15, '2026-05-26');
        $this->item($rates, 'Switch', 47.07, '2026-12-09');
        $this->item($this->group($may, 'Wohnung'), 'Miete', 664.45);

        $this->actingAs($this->user)
            ->post('/plan', ['month' => '2026-06', 'source' => 'previous'])
            ->assertRedirect('/plan?month=2026-06');

        $june = PlanMonth::whereDate('month', '2026-06-01')->firstOrFail()->load('groups.items');

        $this->assertSame(['Raten', 'Wohnung'], $june->groups->pluck('name')->all());
        $this->assertSame('rate', $june->groups->first()->kind);
        $this->assertSame(['Switch'], $june->groups->first()->items->pluck('name')->all());
        $this->assertSame('664.45', $june->groups->last()->items->first()->amount);

        // Der Mai bleibt unverändert.
        $this->assertSame(2, $may->fresh()->groups->first()->items->count());
    }

    public function test_item_in_its_last_month_is_kept_when_copying_into_that_month(): void
    {
        $april = $this->plan('2026-04');
        $this->item($this->group($april, 'Raten', 'rate'), 'Otto', 34.57, '2026-05-12');

        $this->actingAs($this->user)->post('/plan', ['month' => '2026-05', 'source' => 'previous']);

        $this->assertSame(['Otto'], PlanItem::pluck('name')->unique()->values()->all());
        $this->assertSame(2, PlanItem::count());
    }

    public function test_totals_split_income_expense_rates_and_loans(): void
    {
        $plan = $this->plan();
        $this->item($this->group($plan, 'Einnahmen', 'income'), 'Gehalt', 2400);
        $this->item($this->group($plan, 'Kredite', 'loan'), 'Sparkasse', 407);
        $this->item($this->group($plan, 'Raten', 'rate'), 'Couch', 74.15);
        $this->item($this->group($plan, 'Wohnung'), 'Miete', 664.45);

        $totals = app(PlanService::class)->totals($plan->fresh());

        $this->assertSame(2400.0, $totals['income']);
        $this->assertSame(1145.6, $totals['expense']);
        $this->assertSame(1254.4, $totals['balance']);
        $this->assertSame(74.15, $totals['rates']);
        $this->assertSame(407.0, $totals['loans']);
        $this->assertCount(3, $totals['groups']);
    }

    public function test_plan_page_shows_totals_and_months_left(): void
    {
        $plan = $this->plan('2026-05');
        $this->item($this->group($plan, 'Einnahmen', 'income'), 'Gehalt', 2900);
        $this->item($this->group($plan, 'Raten', 'rate'), 'Couch', 74.15, '2026-06-26');
        $this->item($this->group($plan, 'Raten OTTO', 'rate'), 'Geschirrspüler', 34.57, '2026-05-12');
        $this->item($this->group($plan, 'Kredite', 'loan'), 'Sparkasse', 407, '2026-11-17');

        $this->actingAs($this->user)
            ->get('/plan?month=2026-05')
            ->assertOk()
            ->assertSee('2.900,00 €')
            ->assertSee('Gehalt')
            ->assertSee('noch 1 Mon.')
            ->assertSee('letzte Rate')
            ->assertSee('noch 6 Mon.')
            ->assertSee('Wohin das Geld geht');
    }

    public function test_amount_can_be_changed_in_german_format(): void
    {
        $item = $this->item($this->group($this->plan(), 'Wohnung'), 'Miete', 100);

        $this->actingAs($this->user)
            ->put("/plan/items/{$item->id}", ['amount' => '1.234,56'])
            ->assertRedirect('/plan?month=2026-05#group-' . $item->plan_group_id);

        $this->assertSame('1234.56', $item->fresh()->amount);
        $this->assertSame('Miete', $item->fresh()->name);
    }

    public function test_invalid_amount_is_rejected(): void
    {
        $item = $this->item($this->group($this->plan(), 'Wohnung'), 'Miete', 100);

        $this->actingAs($this->user)
            ->from('/plan?month=2026-05')
            ->put("/plan/items/{$item->id}", ['amount' => 'abc'])
            ->assertSessionHasErrors('amount');

        $this->assertSame('100.00', $item->fresh()->amount);
    }

    public function test_item_details_can_be_edited_and_removed(): void
    {
        $item = $this->item($this->group($this->plan(), 'Raten', 'rate'), 'Couch', 74.15);

        $this->actingAs($this->user)
            ->put("/plan/items/{$item->id}", ['name' => 'Sofa', 'amount' => '80', 'note' => 'PayPal', 'ends_on' => '2027-01-31'])
            ->assertRedirect();

        $item->refresh();
        $this->assertSame('Sofa', $item->name);
        $this->assertSame('PayPal', $item->note);
        $this->assertSame('2027-01-31', $item->ends_on->toDateString());

        $this->actingAs($this->user)->delete("/plan/items/{$item->id}")->assertRedirect();
        $this->assertNull(PlanItem::find($item->id));
    }

    public function test_group_and_item_can_be_added(): void
    {
        $plan = $this->plan();

        $this->actingAs($this->user)
            ->post("/plan/{$plan->id}/groups", ['name' => 'Versicherung', 'kind' => 'expense'])
            ->assertRedirect();

        $group = PlanGroup::where('name', 'Versicherung')->firstOrFail();

        $this->actingAs($this->user)
            ->post("/plan/groups/{$group->id}/items", ['name' => 'Haftpflicht', 'amount' => '29,30'])
            ->assertRedirect();

        $this->assertSame('29.30', $group->items()->first()->amount);

        // Ohne Betrag wird 0 geplant.
        $this->actingAs($this->user)->post("/plan/groups/{$group->id}/items", ['name' => 'Noch offen']);
        $this->assertSame('0.00', $group->items()->where('name', 'Noch offen')->first()->amount);
    }

    public function test_group_kind_is_validated(): void
    {
        $plan = $this->plan();

        $this->actingAs($this->user)
            ->post("/plan/{$plan->id}/groups", ['name' => 'X', 'kind' => 'bogus'])
            ->assertSessionHasErrors('kind');
    }

    public function test_other_users_data_is_not_accessible(): void
    {
        $other = User::factory()->create(['is_active' => true]);
        $plan = $this->plan('2026-05', $other);
        $group = $this->group($plan, 'Geheim');
        $item = $this->item($group, 'Posten', 10);

        $this->actingAs($this->user)->get("/plan/items/{$item->id}/edit")->assertNotFound();
        $this->actingAs($this->user)->put("/plan/items/{$item->id}", ['amount' => '1'])->assertNotFound();
        $this->actingAs($this->user)->delete("/plan/items/{$item->id}")->assertNotFound();
        $this->actingAs($this->user)->post("/plan/groups/{$group->id}/items", ['name' => 'X'])->assertNotFound();
        $this->actingAs($this->user)->put("/plan/groups/{$group->id}", ['name' => 'X', 'kind' => 'expense'])->assertNotFound();
        $this->actingAs($this->user)->post("/plan/{$plan->id}/groups", ['name' => 'X', 'kind' => 'expense'])->assertNotFound();
        $this->actingAs($this->user)->delete("/plan/{$plan->id}")->assertNotFound();

        $this->actingAs($this->user)->get('/plan?month=2026-05')->assertDontSee('Geheim');
        $this->assertSame('10.00', $item->fresh()->amount);
    }

    public function test_deleting_a_plan_removes_groups_and_items_but_no_transactions(): void
    {
        $plan = $this->plan();
        $this->item($this->group($plan, 'Wohnung'), 'Miete', 664.45);

        $this->actingAs($this->user)->delete("/plan/{$plan->id}")->assertRedirect();

        $this->assertSame(0, PlanMonth::count());
        $this->assertSame(0, PlanGroup::count());
        $this->assertSame(0, PlanItem::count());
        $this->assertSame(0, Transaction::count());
    }

    public function test_csv_import_creates_groups_and_items(): void
    {
        $csv = "Gruppe;Typ;Beschreibung;Betrag;Bemerkung;Letzte Rate\n"
            . "Kredite;Kredit;Sparkassen Kredit;407,00;;17.01.2033\n"
            . "Raten PayPal;Rate;Couch;74,15;;26.06.2026\n"
            . "Raten PayPal;Rate;Switch;47,07;monatlich;09.12.2026\n"
            . "Wohnung;Ausgabe;Miete;1.664,45;;\n"
            . "Einnahmen;Einnahme;Gehalt;2400,00;;\n"
            . ";;Ohne Gruppe;5,00;;\n";

        $this->actingAs($this->user)
            ->post('/plan/import', [
                'month' => '2026-05',
                'file' => UploadedFile::fake()->createWithContent('plan.csv', $csv),
            ])
            ->assertRedirect('/plan?month=2026-05')
            ->assertSessionHas('success');

        $plan = PlanMonth::firstOrFail()->load('groups.items');
        $groups = $plan->groups->keyBy('name');

        $this->assertSame('loan', $groups['Kredite']->kind);
        $this->assertSame('rate', $groups['Raten PayPal']->kind);
        $this->assertSame('income', $groups['Einnahmen']->kind);
        $this->assertSame('expense', $groups['Wohnung']->kind);
        $this->assertSame(2, $groups['Raten PayPal']->items->count());
        $this->assertSame('1664.45', $groups['Wohnung']->items->first()->amount);
        $this->assertSame('2033-01-17', $groups['Kredite']->items->first()->ends_on->toDateString());
        $this->assertSame('monatlich', $groups['Raten PayPal']->items->last()->note);

        // Die Standardgruppe „Einnahmen“ wird wiederverwendet, nicht doppelt angelegt.
        $this->assertSame(1, $plan->groups->where('name', 'Einnahmen')->count());
        $this->assertSame(0, Transaction::count());
    }

    public function test_csv_import_needs_the_required_columns(): void
    {
        $this->actingAs($this->user)
            ->from('/plan/import')
            ->post('/plan/import', [
                'month' => '2026-05',
                'file' => UploadedFile::fake()->createWithContent('plan.csv', "Name;Wert\nMiete;5\n"),
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_amount_parsing(): void
    {
        $this->assertSame(1234.56, PlanService::parseAmount('1.234,56'));
        $this->assertSame(12.5, PlanService::parseAmount('12,5'));
        $this->assertSame(12.5, PlanService::parseAmount('12.50'));
        $this->assertSame(1234.0, PlanService::parseAmount('1.234'));
        $this->assertSame(407.0, PlanService::parseAmount('407 €'));
        $this->assertNull(PlanService::parseAmount('-5'));
        $this->assertNull(PlanService::parseAmount('abc'));
        $this->assertNull(PlanService::parseAmount(''));
    }

    public function test_months_left(): void
    {
        $may = Carbon::create(2026, 5, 1);

        $this->assertNull(PlanService::monthsLeft(null, $may));
        $this->assertSame(0, PlanService::monthsLeft(Carbon::create(2026, 5, 12), $may));
        $this->assertSame(7, PlanService::monthsLeft(Carbon::create(2026, 12, 9), $may));
        $this->assertSame(-2, PlanService::monthsLeft(Carbon::create(2026, 3, 1), $may));
    }

    public function test_user_deletion_removes_plans(): void
    {
        $plan = $this->plan();
        $this->item($this->group($plan, 'Wohnung'), 'Miete', 1);

        $this->user->planMonths()->delete();

        $this->assertSame(0, PlanGroup::count());
        $this->assertSame(0, PlanItem::count());
    }

    public function test_yearly_item_counts_only_in_its_due_month(): void
    {
        $item = $this->item($this->group($this->plan(), 'Auto'), 'KFZ-Steuer', 98, null, 'yearly', '2026-05-08');

        $this->assertTrue($item->isDueIn(Carbon::create(2026, 5, 1)));
        $this->assertSame('off', $item->stateIn(Carbon::create(2026, 6, 1)));
        $this->assertSame('off', $item->stateIn(Carbon::create(2027, 4, 1)));
        $this->assertTrue($item->isDueIn(Carbon::create(2027, 5, 1)));
        $this->assertSame('upcoming', $item->stateIn(Carbon::create(2026, 4, 1)));
        $this->assertSame('2027-05', $item->nextDueAfter(Carbon::create(2026, 6, 1))->format('Y-m'));
    }

    public function test_quarterly_and_half_yearly_rhythm(): void
    {
        $group = $this->group($this->plan(), 'GEZ');
        $quarterly = $this->item($group, 'Rundfunk', 55.08, null, 'quarterly', '2026-01-15');
        $half = $this->item($group, 'Gebühr', 20, null, 'semiannual', '2026-03-01');

        $due = fn (PlanItem $item) => collect(range(1, 12))->filter(fn ($m) => $item->isDueIn(Carbon::create(2026, $m, 1)))->values()->all();

        $this->assertSame([1, 4, 7, 10], $due($quarterly));
        $this->assertSame([3, 9], $due($half));
    }

    public function test_loan_counts_only_between_start_and_end(): void
    {
        $plan = $this->plan('2026-05');
        $loan = $this->item($this->group($plan, 'Kredite', 'loan'), 'Sparkasse', 407, '2026-07-31', 'monthly', '2026-03-20');

        $this->assertSame('upcoming', $loan->stateIn(Carbon::create(2026, 2, 1)));
        $this->assertSame('due', $loan->stateIn(Carbon::create(2026, 3, 1)));
        $this->assertSame('due', $loan->stateIn(Carbon::create(2026, 7, 1)));
        $this->assertSame('ended', $loan->stateIn(Carbon::create(2026, 8, 1)));

        $service = app(PlanService::class);
        $this->assertSame(407.0, $service->totals($plan->fresh())['loans']);

        $this->plan('2026-08', $this->user);
        $august = PlanMonth::whereDate('month', '2026-08-01')->first();
        $this->item($this->group($august, 'Kredite', 'loan'), 'Sparkasse', 407, '2026-07-31', 'monthly', '2026-03-20');
        $this->assertSame(0.0, $service->totals($august->fresh())['loans']);
    }

    public function test_totals_skip_items_that_are_not_due(): void
    {
        $plan = $this->plan('2026-06');
        $group = $this->group($plan, 'Auto');
        $this->item($group, 'Wartung', 35);
        $this->item($group, 'KFZ-Steuer', 98, null, 'yearly', '2026-05-08');

        $this->assertSame(35.0, app(PlanService::class)->totals($plan->fresh())['expense']);

        $this->actingAs($this->user)
            ->get('/plan?month=2026-06')
            ->assertOk()
            ->assertSee('nächste Zahlung');
    }

    public function test_non_monthly_items_need_a_start_date(): void
    {
        $item = $this->item($this->group($this->plan(), 'Auto'), 'KFZ-Steuer', 98);

        $this->actingAs($this->user)
            ->from('/plan/items/' . $item->id . '/edit')
            ->put("/plan/items/{$item->id}", ['name' => 'KFZ-Steuer', 'amount' => '98', 'interval' => 'yearly'])
            ->assertSessionHasErrors('starts_on');

        $this->actingAs($this->user)
            ->put("/plan/items/{$item->id}", ['name' => 'KFZ-Steuer', 'amount' => '98', 'interval' => 'yearly', 'starts_on' => '2026-05-08'])
            ->assertSessionHasNoErrors();

        $item->refresh();
        $this->assertSame('yearly', $item->interval);
        $this->assertSame('2026-05-08', $item->starts_on->toDateString());
    }

    public function test_end_must_not_be_before_start(): void
    {
        $item = $this->item($this->group($this->plan(), 'Kredite', 'loan'), 'Kredit', 100);

        $this->actingAs($this->user)
            ->put("/plan/items/{$item->id}", ['name' => 'Kredit', 'amount' => '100', 'starts_on' => '2026-05-01', 'ends_on' => '2026-01-01'])
            ->assertSessionHasErrors('ends_on');

        $this->actingAs($this->user)
            ->put("/plan/items/{$item->id}", ['name' => 'Kredit', 'amount' => '100', 'ends_on' => '2030-01-01'])
            ->assertSessionHasNoErrors();
    }

    public function test_rhythm_and_dates_are_copied_to_the_next_month(): void
    {
        $this->item($this->group($this->plan('2026-05'), 'Auto'), 'KFZ-Steuer', 98, null, 'yearly', '2026-05-08');

        $this->actingAs($this->user)->post('/plan', ['month' => '2026-06', 'source' => 'previous']);

        $copy = PlanItem::whereHas('group.planMonth', fn ($q) => $q->whereDate('month', '2026-06-01'))->firstOrFail();

        $this->assertSame('yearly', $copy->interval);
        $this->assertSame('2026-05-08', $copy->starts_on->toDateString());
    }

    public function test_csv_import_reads_rhythm_and_start(): void
    {
        $csv = "Gruppe;Typ;Beschreibung;Betrag;Bemerkung;Rhythmus;Beginn;Letzte Rate\n"
            . "Auto;Ausgabe;KFZ-Steuer;98,00;;Jährlich;08.05.2026;\n"
            . "Auto;Ausgabe;Ohne Beginn;12,00;;Jährlich;;\n"
            . "Kredite;Kredit;Sparkasse;407,00;;Monatlich;20.01.2023;17.01.2033\n";

        $this->actingAs($this->user)->post('/plan/import', [
            'month' => '2026-05',
            'file' => UploadedFile::fake()->createWithContent('plan.csv', $csv),
        ])->assertRedirect();

        $items = PlanItem::all()->keyBy('name');

        $this->assertSame('yearly', $items['KFZ-Steuer']->interval);
        $this->assertSame('2026-05-08', $items['KFZ-Steuer']->starts_on->toDateString());
        $this->assertSame('monthly', $items['Ohne Beginn']->interval);
        $this->assertSame('2023-01-20', $items['Sparkasse']->starts_on->toDateString());
        $this->assertSame('2033-01-17', $items['Sparkasse']->ends_on->toDateString());
    }

    public function test_plan_can_be_exported_as_csv_and_imported_again(): void
    {
        $plan = $this->plan('2026-11');
        $this->item($this->group($plan, 'Einnahmen', 'income'), 'Gehalt', 2400);
        $this->item($this->group($plan, 'Kredite', 'loan'), 'Sparkasse', 407, '2033-01-17', 'monthly', '2023-01-20');
        $this->item($this->group($plan, 'Auto'), 'KFZ-Steuer', 98.5, null, 'yearly', '2026-05-08');
        $this->item($this->group($plan, 'Abos'), '=HYPERLINK("x")', 5);

        $response = $this->actingAs($this->user)->get('/plan/export?month=2026-11');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('finanzview-monatsplan-2026-11.csv', $response->headers->get('Content-Disposition'));

        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBFGruppe;Typ;Beschreibung;Betrag;Bemerkung;Rhythmus;Tag;Beginn;\"Letzte Rate\"", $csv);
        $this->assertStringContainsString('Kredite;Kredit;Sparkasse;407,00;;Monatlich;;20.01.2023;17.01.2033', $csv);
        $this->assertStringContainsString('Auto;Ausgabe;KFZ-Steuer;98,50;;Jährlich;;08.05.2026;', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);

        // Die Datei lässt sich in einen anderen Benutzer wieder einlesen.
        $other = User::factory()->create(['is_active' => true]);
        $this->actingAs($other)->post('/plan/import', [
            'month' => '2026-11',
            'file' => UploadedFile::fake()->createWithContent('plan.csv', $csv),
        ])->assertRedirect();

        $items = PlanItem::whereHas('group.planMonth', fn ($q) => $q->where('user_id', $other->id))->get()->keyBy('name');

        $this->assertSame('yearly', $items['KFZ-Steuer']->interval);
        $this->assertSame('2026-05-08', $items['KFZ-Steuer']->starts_on->toDateString());
        $this->assertSame('98.50', $items['KFZ-Steuer']->amount);
        $this->assertSame('2023-01-20', $items['Sparkasse']->starts_on->toDateString());
        $this->assertSame('2033-01-17', $items['Sparkasse']->ends_on->toDateString());
        $this->assertSame('loan', $items['Sparkasse']->group->kind);
        $this->assertSame('income', $items['Gehalt']->group->kind);
    }

    public function test_export_without_a_plan_redirects_back(): void
    {
        $this->actingAs($this->user)
            ->get('/plan/export?month=2026-11')
            ->assertRedirect('/plan?month=2026-11')
            ->assertSessionHas('error');
    }

    public function test_export_only_contains_own_data(): void
    {
        $other = User::factory()->create(['is_active' => true]);
        $this->item($this->group($this->plan('2026-11', $other), 'Geheim'), 'Fremder Posten', 1);

        $this->actingAs($this->user)->get('/plan/export?month=2026-11')->assertRedirect();
    }

    public function test_plan_page_shows_the_export_button_only_with_a_plan(): void
    {
        $this->actingAs($this->user)->get('/plan?month=2026-11')->assertDontSee('CSV exportieren');

        $this->plan('2026-11');

        $this->actingAs($this->user)->get('/plan?month=2026-11')->assertSee('CSV exportieren');
    }
}
