<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\User;
use App\Services\LoanSettlementService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanSettlementTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function loan(array $attrs = []): Loan
    {
        return Loan::create(array_merge([
            'user_id' => User::factory()->create(['is_active' => true])->id,
            'name' => 'Auto', 'principal_amount' => 1000, 'paid_amount' => 0,
            'installment_amount' => 100, 'total_installments' => 10, 'paid_installments' => 0,
            'start_date' => '2026-09-01', 'is_active' => true, 'type' => 'loan',
        ], $attrs));
    }

    private function rate(Loan $loan, int $n, string $due, float $principal = 100, string $status = 'planned'): LoanPayment
    {
        return LoanPayment::create([
            'loan_id' => $loan->id, 'installment_number' => $n, 'due_date' => $due,
            'amount' => $principal, 'interest_amount' => 0, 'principal_amount' => $principal,
            'remaining_amount' => 0, 'payment_type' => 'regular', 'status' => $status,
        ]);
    }

    public function test_due_rates_are_marked_paid_and_reduce_the_remaining_debt(): void
    {
        Carbon::setTestNow('2026-11-15');
        $loan = $this->loan();
        $loan->forceFill(['created_at' => '2026-09-01'])->save();
        $this->rate($loan, 1, '2026-10-01');
        $this->rate($loan, 2, '2026-11-15'); // heute fällig
        $this->rate($loan, 3, '2026-12-01'); // noch nicht

        $result = app(LoanSettlementService::class)->settleDue();

        $this->assertSame(['loans' => 1, 'payments' => 2], $result);
        $loan->refresh();
        $this->assertEquals(200.0, (float) $loan->paid_amount);
        $this->assertSame(2, $loan->paid_installments);
        $this->assertSame('paid', LoanPayment::where('installment_number', 2)->first()->status);
        $this->assertSame('planned', LoanPayment::where('installment_number', 3)->first()->status);
        $this->assertSame('2026-10-01', LoanPayment::where('installment_number', 1)->first()->paid_date->toDateString());
    }

    public function test_running_twice_does_not_count_a_rate_again(): void
    {
        Carbon::setTestNow('2026-11-15');
        $loan = $this->loan();
        $loan->forceFill(['created_at' => '2026-09-01'])->save();
        $this->rate($loan, 1, '2026-11-01');

        app(LoanSettlementService::class)->settleDue();
        $second = app(LoanSettlementService::class)->settleDue();

        $this->assertSame(['loans' => 0, 'payments' => 0], $second);
        $this->assertEquals(100.0, (float) $loan->fresh()->paid_amount);
    }

    public function test_rates_due_before_the_loan_was_entered_do_not_change_the_paid_amount(): void
    {
        Carbon::setTestNow('2026-11-15');
        $loan = $this->loan(['paid_amount' => 300]);
        $loan->forceFill(['created_at' => '2026-11-10'])->save();
        $this->rate($loan, 1, '2026-09-01'); // schon in den 300 enthalten
        $this->rate($loan, 2, '2026-11-12'); // nach dem Anlegen

        app(LoanSettlementService::class)->settleDue();

        $loan->refresh();
        $this->assertEquals(400.0, (float) $loan->paid_amount);
        $this->assertSame(2, $loan->paid_installments);
        $this->assertSame('paid', LoanPayment::where('installment_number', 1)->first()->status);
    }

    public function test_loan_is_deactivated_when_fully_paid_and_inactive_loans_are_skipped(): void
    {
        Carbon::setTestNow('2026-11-15');
        $loan = $this->loan(['principal_amount' => 100]);
        $loan->forceFill(['created_at' => '2026-09-01'])->save();
        $this->rate($loan, 1, '2026-11-01');
        $off = $this->loan(['name' => 'Alt', 'is_active' => false]);
        $this->rate($off, 1, '2026-11-01');

        app(LoanSettlementService::class)->settleDue();

        $this->assertFalse($loan->fresh()->is_active);
        $this->assertSame('planned', LoanPayment::where('loan_id', $off->id)->first()->status);
    }

    public function test_extra_payments_are_left_alone_and_command_runs(): void
    {
        Carbon::setTestNow('2026-11-15');
        $loan = $this->loan();
        LoanPayment::create([
            'loan_id' => $loan->id, 'installment_number' => 1, 'due_date' => '2026-11-01', 'amount' => 50,
            'payment_type' => 'extra', 'status' => 'planned',
        ]);

        $this->artisan('loans:settle-due')->assertSuccessful();

        $this->assertSame('planned', LoanPayment::first()->status);
        $this->assertEquals(0.0, (float) $loan->fresh()->paid_amount);
    }
}
