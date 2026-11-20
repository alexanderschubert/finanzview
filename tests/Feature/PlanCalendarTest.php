<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\PlanItem;
use App\Models\PlanMonth;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Kalender im Monatsplan: Zahlungstag je Posten, Kreditraten, Export/Import.
 */
class PlanCalendarTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['is_active' => true]);
    }

    private function plan(string $month = '2026-11', ?User $user = null): PlanMonth
    {
        return PlanMonth::create(['user_id' => ($user ?? $this->user)->id, 'month' => $month . '-01']);
    }

    private function item(PlanMonth $plan, string $group, string $kind, string $name, float $amount, array $extra = []): PlanItem
    {
        $groupModel = $plan->groups()->where('name', $group)->first()
            ?? $plan->groups()->create(['name' => $group, 'kind' => $kind, 'sort_order' => $plan->groups()->count()]);

        return $groupModel->items()->create(array_merge(['name' => $name, 'amount' => $amount, 'sort_order' => 0], $extra));
    }

    public function test_calendar_places_items_on_their_day_and_lists_the_rest(): void
    {
        $plan = $this->plan();
        $this->item($plan, 'Wohnung', 'expense', 'Miete', 664.45, ['due_day' => 1]);
        $this->item($plan, 'Einnahmen', 'income', 'Gehalt', 2400, ['due_day' => 28]);
        $this->item($plan, 'Wohnung', 'expense', 'Strom', 115);

        $calendar = app(\App\Services\PlanService::class)->calendar($this->user, \Carbon\Carbon::create(2026, 11, 1));

        $this->assertSame(['Miete'], array_column($calendar['days'][1], 'title'));
        $this->assertSame(['Gehalt'], array_column($calendar['days'][28], 'title'));
        $this->assertSame(['Strom'], array_column($calendar['unscheduled'], 'title'));
        $this->assertSame(2400.0, $calendar['totals']['income']);
        $this->assertSame(779.45, $calendar['totals']['expense']);

        $this->actingAs($this->user)
            ->get('/plan/calendar?month=2026-11')
            ->assertOk()
            ->assertSee('Miete')
            ->assertSee('Gehalt')
            ->assertSee('Ohne festen Tag')
            ->assertSee('Strom');
    }

    public function test_day_31_uses_the_last_day_of_short_months(): void
    {
        $plan = $this->plan('2026-11');
        $this->item($plan, 'Wohnung', 'expense', 'Ende', 10, ['due_day' => 31]);

        $calendar = app(\App\Services\PlanService::class)->calendar($this->user, \Carbon\Carbon::create(2026, 11, 1));

        $this->assertSame(['Ende'], array_column($calendar['days'][30], 'title'));
    }

    public function test_items_that_are_not_due_are_left_out(): void
    {
        $plan = $this->plan('2026-11');
        $this->item($plan, 'Auto', 'expense', 'KFZ-Steuer', 98, ['due_day' => 8, 'interval' => 'yearly', 'starts_on' => '2026-05-08']);
        $this->item($plan, 'Auto', 'expense', 'Alt', 10, ['due_day' => 8, 'ends_on' => '2026-06-30']);

        $calendar = app(\App\Services\PlanService::class)->calendar($this->user, \Carbon\Carbon::create(2026, 11, 1));

        $this->assertSame([], $calendar['days']);
        $this->assertSame([], $calendar['unscheduled']);
    }

    public function test_loan_installments_appear_in_their_month(): void
    {
        $this->actingAs($this->user)->post('/loans', [
            'name' => 'Sparkasse Kredit', 'type' => 'loan', 'principal_amount' => '1200', 'interest_rate' => '0',
            'installment_amount' => '100', 'total_installments' => '12', 'start_date' => '2026-11-15',
        ])->assertSessionHasNoErrors();

        $loan = Loan::where('user_id', $this->user->id)->firstOrFail();
        $first = $loan->payments()->where('payment_type', 'regular')->orderBy('due_date')->first();

        $calendar = app(\App\Services\PlanService::class)->calendar($this->user, $first->due_date->copy()->startOfMonth());

        $this->assertSame(['Sparkasse Kredit'], array_column($calendar['days'][(int) $first->due_date->format('j')], 'title'));
        $this->assertSame('loan', $calendar['days'][(int) $first->due_date->format('j')][0]['kind']);
    }

    public function test_loan_is_not_listed_twice_when_the_plan_has_it(): void
    {
        $this->actingAs($this->user)->post('/loans', [
            'name' => 'Sparkasse Kredit', 'type' => 'loan', 'principal_amount' => '1200', 'interest_rate' => '0',
            'installment_amount' => '100', 'total_installments' => '12', 'start_date' => '2026-11-15',
        ]);
        $loan = Loan::where('user_id', $this->user->id)->firstOrFail();
        $first = $loan->payments()->where('payment_type', 'regular')->orderBy('due_date')->first();
        $month = $first->due_date->copy()->startOfMonth();

        $plan = $this->plan($month->format('Y-m'));
        $this->item($plan, 'Kredite', 'loan', 'Sparkasse Kredit', 100, ['due_day' => 15]);

        $calendar = app(\App\Services\PlanService::class)->calendar($this->user, $month);

        $this->assertCount(1, collect($calendar['days'])->flatten(1));
    }

    public function test_other_users_data_is_not_shown(): void
    {
        $other = User::factory()->create(['is_active' => true]);
        $this->item($this->plan('2026-11', $other), 'Geheim', 'expense', 'Fremder Posten', 5, ['due_day' => 3]);

        $this->actingAs($this->user)
            ->get('/plan/calendar?month=2026-11')
            ->assertOk()
            ->assertDontSee('Fremder Posten')
            ->assertSee('Keine Zahlungen in diesem Monat');
    }

    public function test_planning_tabs_include_the_calendar(): void
    {
        foreach (['/plan', '/plan/calendar'] as $url) {
            $this->actingAs($this->user)->get($url)->assertOk()->assertSee('Kalender')->assertSee('Budgets');
        }
    }

    public function test_due_day_can_be_saved_and_is_validated(): void
    {
        $item = $this->item($this->plan(), 'Wohnung', 'expense', 'Miete', 664.45);

        $this->actingAs($this->user)
            ->put("/plan/items/{$item->id}", ['name' => 'Miete', 'amount' => '664,45', 'due_day' => '3'])
            ->assertSessionHasNoErrors();

        $this->assertSame(3, $item->fresh()->due_day);

        $this->actingAs($this->user)
            ->put("/plan/items/{$item->id}", ['name' => 'Miete', 'amount' => '664,45', 'due_day' => '32'])
            ->assertSessionHasErrors('due_day');

        $this->actingAs($this->user)
            ->get("/plan/items/{$item->id}/edit")
            ->assertOk()
            ->assertSee('Zahlungstag im Monat');
    }

    public function test_csv_export_and_import_keep_the_due_day(): void
    {
        $plan = $this->plan();
        $this->item($plan, 'Wohnung', 'expense', 'Miete', 664.45, ['due_day' => 3]);

        $csv = $this->actingAs($this->user)->get('/plan/export?month=2026-11')->streamedContent();

        $this->assertStringContainsString('Rhythmus;Tag;Beginn', $csv);
        $this->assertStringContainsString('Wohnung;Ausgabe;Miete;664,45;;Monatlich;3;', $csv);

        $other = User::factory()->create(['is_active' => true]);
        $this->actingAs($other)->post('/plan/import', [
            'month' => '2026-11',
            'file' => UploadedFile::fake()->createWithContent('plan.csv', $csv),
        ])->assertRedirect();

        $copy = PlanItem::whereHas('group.planMonth', fn ($q) => $q->where('user_id', $other->id))->firstOrFail();
        $this->assertSame(3, $copy->due_day);
    }
}
