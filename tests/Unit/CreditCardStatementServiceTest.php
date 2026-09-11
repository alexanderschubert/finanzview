<?php

namespace Tests\Unit;

use App\Models\CreditCard;
use App\Services\CreditCardStatementService;
use Carbon\Carbon;
use InvalidArgumentException;
use Tests\TestCase;

class CreditCardStatementServiceTest extends TestCase
{
    private CreditCardStatementService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(CreditCardStatementService::class);
    }

    private function card(
        ?int $billingDay = 15,
        ?int $paymentDueDay = 5
    ): CreditCard {
        return new CreditCard([
            'billing_day' => $billingDay,
            'payment_due_day' => $paymentDueDay,
        ]);
    }

    public function test_date_after_billing_day_belongs_to_current_billing_cycle(): void
    {
        $result = $this->service->periodFor(
            $this->card(15, 5),
            Carbon::parse('2026-08-20')
        );

        $this->assertSame('2026-08-16', $result['period_start']->toDateString());
        $this->assertSame('2026-09-15', $result['period_end']->toDateString());
        $this->assertSame('2026-10-05', $result['due_date']->toDateString());
    }

    public function test_date_on_billing_day_belongs_to_previous_cycle(): void
    {
        $result = $this->service->periodFor(
            $this->card(15, 5),
            Carbon::parse('2026-08-15')
        );

        $this->assertSame('2026-07-16', $result['period_start']->toDateString());
        $this->assertSame('2026-08-15', $result['period_end']->toDateString());
        $this->assertSame('2026-09-05', $result['due_date']->toDateString());
    }

    public function test_date_before_billing_day_belongs_to_previous_billing_cycle(): void
    {
        $result = $this->service->periodFor(
            $this->card(15, 5),
            Carbon::parse('2026-08-10')
        );

        $this->assertSame('2026-07-16', $result['period_start']->toDateString());
        $this->assertSame('2026-08-15', $result['period_end']->toDateString());
        $this->assertSame('2026-09-05', $result['due_date']->toDateString());
    }

    public function test_year_boundary_is_handled_correctly(): void
    {
        $result = $this->service->periodFor(
            $this->card(15, 5),
            Carbon::parse('2027-01-20')
        );

        $this->assertSame('2027-01-16', $result['period_start']->toDateString());
        $this->assertSame('2027-02-15', $result['period_end']->toDateString());
        $this->assertSame('2027-03-05', $result['due_date']->toDateString());
    }

    public function test_billing_day_31_is_clamped_to_short_month(): void
    {
        $result = $this->service->periodFor(
            $this->card(31, 5),
            Carbon::parse('2026-03-15')
        );

        $this->assertSame('2026-03-01', $result['period_start']->toDateString());
        $this->assertSame('2026-03-31', $result['period_end']->toDateString());
        $this->assertSame('2026-04-05', $result['due_date']->toDateString());
    }

    public function test_missing_billing_day_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->periodFor(
            $this->card(null, 5),
            Carbon::parse('2026-08-20')
        );
    }

    public function test_missing_payment_due_day_is_allowed(): void
    {
        $result = $this->service->periodFor(
            $this->card(15, null),
            Carbon::parse('2026-08-20')
        );

        $this->assertSame('2026-08-16', $result['period_start']->toDateString());
        $this->assertSame('2026-09-15', $result['period_end']->toDateString());
        $this->assertNull($result['due_date']);
    }
}
