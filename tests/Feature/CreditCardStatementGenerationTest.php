<?php

namespace Tests\Feature;

use App\Models\CreditCard;
use App\Models\CreditCardStatement;
use App\Models\User;
use App\Services\CreditCardStatementService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditCardStatementGenerationTest extends TestCase
{
    use RefreshDatabase;

    private CreditCardStatementService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(CreditCardStatementService::class);
    }

    private function createUser(): User
    {
        return User::factory()->create([
            'is_active' => true,
        ]);
    }

    private function createCreditCard(
        User $user,
        array $attributes = []
    ): CreditCard {
        return CreditCard::create(array_merge([
            'user_id' => $user->id,
            'name' => 'Test Kreditkarte',
            'issuer' => 'Test Bank',
            'last_four' => '1234',
            'credit_limit' => 2000,
            'current_balance' => 500,
            'billing_day' => 15,
            'payment_due_day' => 5,
            'is_active' => true,
        ], $attributes));
    }

    public function test_it_creates_statement_for_current_billing_cycle(): void
    {
        $user = $this->createUser();

        $card = $this->createCreditCard($user);

        $statement = $this->service->generateFor(
            $card,
            Carbon::parse('2026-08-20')
        );

        $this->assertInstanceOf(
            CreditCardStatement::class,
            $statement
        );

        $this->assertSame(
            $card->id,
            $statement->credit_card_id
        );

        $this->assertSame(
            '2026-08-16',
            $statement->period_start->toDateString()
        );

        $this->assertSame(
            '2026-09-15',
            $statement->period_end->toDateString()
        );

        $this->assertSame(
            '2026-10-05',
            $statement->due_date->toDateString()
        );

        $this->assertSame(
            '0.00',
            $statement->amount
        );

        $this->assertSame(
            'open',
            $statement->status
        );

        $this->assertDatabaseHas('credit_card_statements', [
            'id' => $statement->id,
            'credit_card_id' => $card->id,
            'amount' => 0,
            'status' => 'open',
        ]);

        $this->assertSame(
            '2026-08-16',
            $statement->period_start->toDateString()
        );

        $this->assertSame(
            '2026-09-15',
            $statement->period_end->toDateString()
        );
    }

    public function test_it_does_not_create_duplicate_statement(): void
    {
        $user = $this->createUser();

        $card = $this->createCreditCard($user);

        $first = $this->service->generateFor(
            $card,
            Carbon::parse('2026-08-20')
        );

        $second = $this->service->generateFor(
            $card,
            Carbon::parse('2026-08-25')
        );

        $this->assertSame(
            $first->id,
            $second->id
        );

        $this->assertDatabaseCount(
            'credit_card_statements',
            1
        );
    }

    public function test_different_billing_cycles_create_different_statements(): void
    {
        $user = $this->createUser();

        $card = $this->createCreditCard($user);

        $august = $this->service->generateFor(
            $card,
            Carbon::parse('2026-08-20')
        );

        $september = $this->service->generateFor(
            $card,
            Carbon::parse('2026-09-20')
        );

        $this->assertNotSame(
            $august->id,
            $september->id
        );

        $this->assertSame(
            '2026-08-16',
            $august->period_start->toDateString()
        );

        $this->assertSame(
            '2026-09-15',
            $august->period_end->toDateString()
        );

        $this->assertSame(
            '2026-09-16',
            $september->period_start->toDateString()
        );

        $this->assertSame(
            '2026-10-15',
            $september->period_end->toDateString()
        );

        $this->assertDatabaseCount(
            'credit_card_statements',
            2
        );
    }

    public function test_two_different_cards_can_have_same_billing_period(): void
    {
        $user = $this->createUser();

        $firstCard = $this->createCreditCard($user, [
            'name' => 'Visa',
        ]);

        $secondCard = $this->createCreditCard($user, [
            'name' => 'Mastercard',
        ]);

        $first = $this->service->generateFor(
            $firstCard,
            Carbon::parse('2026-08-20')
        );

        $second = $this->service->generateFor(
            $secondCard,
            Carbon::parse('2026-08-20')
        );

        $this->assertNotSame(
            $first->id,
            $second->id
        );

        $this->assertSame(
            '2026-08-16',
            $first->period_start->toDateString()
        );

        $this->assertSame(
            '2026-08-16',
            $second->period_start->toDateString()
        );

        $this->assertDatabaseCount(
            'credit_card_statements',
            2
        );
    }

    public function test_missing_billing_day_prevents_statement_generation(): void
    {
        $user = $this->createUser();

        $card = $this->createCreditCard($user, [
            'billing_day' => null,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->generateFor(
            $card,
            Carbon::parse('2026-08-20')
        );
    }
}
