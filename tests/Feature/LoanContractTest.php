<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Kredit mit Vertragsangaben, sichtbarer Tilgungsplan und CSV-Export.
 */
class LoanContractTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['is_active' => true]);
    }

    private function createLoan(array $overrides = []): Loan
    {
        $this->actingAs($this->user)->post('/loans', array_merge([
            'name' => 'Sparkassen Kredit',
            'type' => 'loan',
            'principal_amount' => '29860.30',
            'interest_rate' => '10.7',
            'effective_rate' => '11.24',
            'installment_amount' => '407.60',
            'contract_total' => '48912.47',
            'financed_costs' => '6164.34',
            'total_installments' => '120',
            'start_date' => '2023-01-20',
        ], $overrides))->assertSessionHasNoErrors();

        return Loan::where('user_id', $this->user->id)->latest('id')->firstOrFail();
    }

    public function test_contract_fields_are_saved_and_a_plan_is_created(): void
    {
        $loan = $this->createLoan();

        $this->assertSame('11.240', $loan->effective_rate);
        $this->assertSame('48912.47', $loan->contract_total);
        $this->assertSame('6164.34', $loan->financed_costs);
        $this->assertSame(120, $loan->payments()->where('payment_type', 'regular')->count());
    }

    public function test_contract_fields_are_optional(): void
    {
        $loan = $this->createLoan(['effective_rate' => '', 'contract_total' => '', 'financed_costs' => '']);

        $this->assertNull($loan->effective_rate);
        $this->assertNull($loan->contract_total);
    }

    public function test_effective_rate_must_be_a_sensible_percentage(): void
    {
        $this->actingAs($this->user)->post('/loans', [
            'name' => 'X', 'type' => 'loan', 'principal_amount' => '1000', 'installment_amount' => '100', 'effective_rate' => '250',
        ])->assertSessionHasErrors('effective_rate');
    }

    public function test_contract_fields_can_be_changed(): void
    {
        $loan = $this->createLoan();

        $this->actingAs($this->user)->put("/loans/{$loan->id}", [
            'name' => 'Sparkassen Kredit', 'type' => 'loan', 'principal_amount' => '29860.30', 'interest_rate' => '10.7',
            'installment_amount' => '407.60', 'total_installments' => '120', 'start_date' => '2023-01-20',
            'effective_rate' => '11.30', 'contract_total' => '49000', 'financed_costs' => '',
        ])->assertSessionHasNoErrors();

        $loan->refresh();
        $this->assertSame('11.300', $loan->effective_rate);
        $this->assertSame('49000.00', $loan->contract_total);
        $this->assertNull($loan->financed_costs);
    }

    public function test_page_shows_totals_year_groups_and_contract_comparison(): void
    {
        $loan = $this->createLoan();

        $this->actingAs($this->user)
            ->get("/loans/{$loan->id}")
            ->assertOk()
            ->assertSee('Zinsen insgesamt')
            ->assertSee('Alle Raten zusammen')
            ->assertSee('Laufzeit')
            ->assertSee('120 Monate')
            ->assertSee('Tilgungsplan · 120 Raten')
            ->assertSee('2023')
            ->assertSee('2032')
            ->assertSee('Summe')
            ->assertSee('Effektiver Jahreszins laut Vertrag')
            ->assertSee('11,24 %')
            ->assertSee('Gesamtbetrag laut Vertrag')
            ->assertSee('48.912,47 €')
            ->assertSee(route('loans.plan.export', $loan), false);
    }

    public function test_plan_can_be_exported_as_csv(): void
    {
        $loan = $this->createLoan();

        $response = $this->actingAs($this->user)->get("/loans/{$loan->id}/plan.csv");

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('finanzview-tilgungsplan-sparkassen-kredit.csv', $response->headers->get('Content-Disposition'));

        $csv = $response->streamedContent();
        $lines = array_values(array_filter(explode("\n", trim($csv))));

        $this->assertStringStartsWith("\xEF\xBB\xBFNr.;", $lines[0]);
        $this->assertCount(121, $lines);
        $this->assertMatchesRegularExpression('/^1;20\.01\.2023;407,60;/', $lines[1]);
    }

    public function test_plan_export_is_not_available_for_other_users(): void
    {
        $loan = $this->createLoan();
        $other = User::factory()->create(['is_active' => true]);

        $this->actingAs($other)->get("/loans/{$loan->id}/plan.csv")->assertForbidden();
    }

    public function test_backup_keeps_contract_fields_and_the_plan_split(): void
    {
        $loan = $this->createLoan();
        $content = $this->actingAs($this->user)->post(route('settings.data-export.json'))->assertOk()->getContent();

        $target = User::factory()->create(['is_active' => true]);

        $this->actingAs($target)
            ->post(route('settings.data-export.import'), ['backup' => UploadedFile::fake()->createWithContent('backup.json', $content)])
            ->assertOk();

        $token = session('finanzview_import_token');

        $this->withSession(['finanzview_import_token' => $token])
            ->actingAs($target)
            ->post(route('settings.data-export.import.restore'), ['token' => $token, 'confirm' => '1'])
            ->assertRedirect(route('settings.data-export'));

        $copy = Loan::where('user_id', $target->id)->firstOrFail();

        $this->assertSame('11.240', $copy->effective_rate);
        $this->assertSame('48912.47', $copy->contract_total);
        $this->assertSame('6164.34', $copy->financed_costs);

        $original = $loan->payments()->where('installment_number', 1)->first();
        $restored = $copy->payments()->where('installment_number', 1)->first();

        $this->assertNotNull($restored);
        $this->assertSame($original->interest_amount, $restored->interest_amount);
        $this->assertSame($original->remaining_amount, $restored->remaining_amount);
    }
}
