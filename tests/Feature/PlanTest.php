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

    private function item(PlanGroup $group, string $name, float $amount, ?string $endsOn = null): PlanItem
    {
        return $group->items()->create(['name' => $name, 'amount' => $amount, 'ends_on' => $endsOn, 'sort_order' => $group->items()->count()]);
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
            ->assertSee('Monatsplan');
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
}
