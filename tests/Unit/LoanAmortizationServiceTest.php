<?php

namespace Tests\Unit;

use App\Models\Loan;
use App\Services\LoanAmortizationService;
use Tests\TestCase;

class LoanAmortizationServiceTest extends TestCase
{
    private LoanAmortizationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(LoanAmortizationService::class);
    }

    private function loan(array $attributes): Loan
    {
        return new Loan($attributes);
    }

    public function test_annuity_loan_calculates_interest_and_principal_separately(): void
    {
        $loan = $this->loan([
            'principal_amount' => 20000.00,
            'paid_amount' => 0.00,
            'interest_rate' => 5.000,
            'installment_amount' => 460.59,
            'total_installments' => 48,
            'paid_installments' => 0,
        ]);

        $plan = $this->service->calculate($loan);

        $this->assertNotEmpty($plan);

        // 20.000 € × 5 % / 12 = 83,33 € Zinsen
        $this->assertSame(83.33, $plan[0]['interest_amount']);

        // 460,59 € - 83,33 € = 377,26 € Tilgung
        $this->assertSame(377.26, $plan[0]['principal_amount']);

        $this->assertSame(460.59, $plan[0]['amount']);
        $this->assertSame(19622.74, $plan[0]['remaining_amount']);
    }

    public function test_interest_decreases_as_remaining_balance_decreases(): void
    {
        $loan = $this->loan([
            'principal_amount' => 20000.00,
            'paid_amount' => 0.00,
            'interest_rate' => 5.000,
            'installment_amount' => 460.59,
            'total_installments' => 48,
        ]);

        $plan = $this->service->calculate($loan);

        $this->assertGreaterThan(
            $plan[1]['interest_amount'],
            $plan[0]['interest_amount']
        );

        $this->assertGreaterThan(
            $plan[2]['interest_amount'],
            $plan[1]['interest_amount']
        );
    }

    public function test_calculation_can_start_from_current_remaining_balance(): void
    {
        $plan = $this->service->calculateFromBalance(
            10000.00,
            5.000,
            460.59,
            null
        );

        $this->assertNotEmpty($plan);

        // 10.000 € × 5 % / 12 = 41,67 € Zinsen
        $this->assertSame(
            41.67,
            $plan[0]['interest_amount']
        );

        // 460,59 € - 41,67 € = 418,92 € Tilgung
        $this->assertSame(
            418.92,
            $plan[0]['principal_amount']
        );

        $this->assertSame(
            9581.08,
            $plan[0]['remaining_amount']
        );
    }

    public function test_constant_installment_shortens_term_after_extra_payment(): void
    {
        $normalPlan = $this->service->calculateFromBalance(
            20000.00,
            5.000,
            460.59,
            null
        );

        $afterExtraPaymentPlan = $this->service->calculateFromBalance(
            19000.00,
            5.000,
            460.59,
            null
        );

        $this->assertNotEmpty($normalPlan);
        $this->assertNotEmpty($afterExtraPaymentPlan);

        /*
         * Variante A:
         * Die Rate bleibt identisch.
         * Durch die niedrigere Restschuld wird der Kredit
         * mit weniger Raten vollständig getilgt.
         */
        $this->assertSame(
            460.59,
            $normalPlan[0]['amount']
        );

        $this->assertSame(
            460.59,
            $afterExtraPaymentPlan[0]['amount']
        );

        $this->assertLessThan(
            count($normalPlan),
            count($afterExtraPaymentPlan)
        );

        $this->assertSame(
            0.00,
            $afterExtraPaymentPlan[array_key_last(
                $afterExtraPaymentPlan
            )]['remaining_amount']
        );
    }

    public function test_zero_interest_loan_is_calculated_without_interest(): void
    {
        $loan = $this->loan([
            'principal_amount' => 1000.00,
            'paid_amount' => 0.00,
            'interest_rate' => 0.000,
            'installment_amount' => 250.00,
            'total_installments' => 4,
        ]);

        $plan = $this->service->calculate($loan);

        $this->assertCount(4, $plan);

        foreach ($plan as $payment) {
            $this->assertSame(0.00, $payment['interest_amount']);
        }

        $this->assertSame(250.00, $plan[0]['principal_amount']);
        $this->assertSame(750.00, $plan[0]['remaining_amount']);
        $this->assertSame(0.00, $plan[3]['remaining_amount']);
    }

    public function test_last_payment_is_reduced_when_normal_installment_would_overpay(): void
    {
        $loan = $this->loan([
            'principal_amount' => 1000.00,
            'paid_amount' => 0.00,
            'interest_rate' => 0.000,
            'installment_amount' => 300.00,
            'total_installments' => 10,
        ]);

        $plan = $this->service->calculate($loan);

        $this->assertCount(4, $plan);

        $this->assertSame(100.00, $plan[3]['amount']);
        $this->assertSame(100.00, $plan[3]['principal_amount']);
        $this->assertSame(0.00, $plan[3]['remaining_amount']);
    }

    public function test_plan_never_creates_negative_remaining_balance(): void
    {
        $loan = $this->loan([
            'principal_amount' => 1234.56,
            'paid_amount' => 0.00,
            'interest_rate' => 3.750,
            'installment_amount' => 5000.00,
            'total_installments' => 10,
        ]);

        $plan = $this->service->calculate($loan);

        foreach ($plan as $payment) {
            $this->assertGreaterThanOrEqual(
                0.00,
                $payment['remaining_amount']
            );
        }

        $last = end($plan);

        $this->assertSame(0.00, $last['remaining_amount']);
    }

    public function test_total_principal_payments_equal_original_principal(): void
    {
        $loan = $this->loan([
            'principal_amount' => 10000.00,
            'paid_amount' => 0.00,
            'interest_rate' => 4.500,
            'installment_amount' => 500.00,
            'total_installments' => 30,
        ]);

        $plan = $this->service->calculate($loan);

        $totalPrincipal = round(
            array_sum(
                array_column($plan, 'principal_amount')
            ),
            2
        );

        $this->assertSame(10000.00, $totalPrincipal);
        $this->assertSame(
            0.00,
            $plan[array_key_last($plan)]['remaining_amount']
        );
    }

    public function test_plan_respects_total_installments(): void
    {
        $loan = $this->loan([
            'principal_amount' => 20000.00,
            'paid_amount' => 0.00,
            'interest_rate' => 5.000,
            'installment_amount' => 100.00,
            'total_installments' => 12,
        ]);

        $plan = $this->service->calculate($loan);

        $this->assertCount(12, $plan);

        $this->assertGreaterThan(
            0.00,
            $plan[array_key_last($plan)]['remaining_amount']
        );
    }

    public function test_empty_plan_is_returned_for_invalid_loan_amount(): void
    {
        $loan = $this->loan([
            'principal_amount' => 0.00,
            'paid_amount' => 0.00,
            'interest_rate' => 5.000,
            'installment_amount' => 500.00,
            'total_installments' => 12,
        ]);

        $plan = $this->service->calculate($loan);

        $this->assertSame([], $plan);
    }
}
