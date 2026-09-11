<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\CreditCard;
use App\Models\CreditCardStatement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditCardStatementCommandTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::factory()->create([
            'is_active' => true,
        ]);
    }

    private function createAccount(User $user): Account
    {
        return Account::create([
            'user_id' => $user->id,
            'name' => 'Test Girokonto',
            'type' => 'checking',
            'balance' => 1000,
            'currency' => 'EUR',
            'is_active' => true,
        ]);
    }

    private function createCreditCard(
        User $user,
        Account $account,
        array $attributes = []
    ): CreditCard {
        return CreditCard::create(array_merge([
            'user_id' => $user->id,
            'account_id' => $account->id,
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

    public function test_command_generates_statement_for_active_card(): void
    {
        Carbon::setTestNow('2026-08-20');

        $user = $this->createUser();
        $account = $this->createAccount($user);
        $card = $this->createCreditCard($user, $account);

        $account->transactions()->create([
            'user_id' => $user->id,
            'credit_card_id' => $card->id,
            'type' => 'expense',
            'amount' => 150.00,
            'transaction_date' => '2026-08-20',
            'description' => 'Test Zahlung',
            'is_pending' => false,
            'is_recurring' => false,
        ]);

        $this->artisan('credit-cards:generate-statements')
            ->assertExitCode(0);

        $this->assertDatabaseHas('credit_card_statements', [
            'credit_card_id' => $card->id,
            'period_start' => '2026-08-16 00:00:00',
            'period_end' => '2026-09-15 00:00:00',
            'amount' => 150.00,
            'status' => 'open',
        ]);

        Carbon::setTestNow();
    }

    public function test_command_processes_multiple_active_cards(): void
    {
        Carbon::setTestNow('2026-08-20');

        $user = $this->createUser();
        $account = $this->createAccount($user);

        $cardOne = $this->createCreditCard($user, $account, [
            'name' => 'Kreditkarte 1',
            'last_four' => '1111',
        ]);

        $cardTwo = $this->createCreditCard($user, $account, [
            'name' => 'Kreditkarte 2',
            'last_four' => '2222',
        ]);

        $this->artisan('credit-cards:generate-statements')
            ->assertExitCode(0);

        $this->assertDatabaseHas('credit_card_statements', [
            'credit_card_id' => $cardOne->id,
        ]);

        $this->assertDatabaseHas('credit_card_statements', [
            'credit_card_id' => $cardTwo->id,
        ]);

        $this->assertSame(
            2,
            CreditCardStatement::query()->count()
        );

        Carbon::setTestNow();
    }

    public function test_command_ignores_inactive_cards(): void
    {
        Carbon::setTestNow('2026-08-20');

        $user = $this->createUser();
        $account = $this->createAccount($user);

        $card = $this->createCreditCard($user, $account, [
            'is_active' => false,
        ]);

        $this->artisan('credit-cards:generate-statements')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('credit_card_statements', [
            'credit_card_id' => $card->id,
        ]);

        Carbon::setTestNow();
    }

    public function test_command_ignores_cards_without_billing_day(): void
    {
        Carbon::setTestNow('2026-08-20');

        $user = $this->createUser();
        $account = $this->createAccount($user);

        $card = $this->createCreditCard($user, $account, [
            'billing_day' => null,
        ]);

        $this->artisan('credit-cards:generate-statements')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('credit_card_statements', [
            'credit_card_id' => $card->id,
        ]);

        Carbon::setTestNow();
    }

    public function test_command_does_not_create_duplicate_statements(): void
    {
        Carbon::setTestNow('2026-08-20');

        $user = $this->createUser();
        $account = $this->createAccount($user);
        $card = $this->createCreditCard($user, $account);

        $this->artisan('credit-cards:generate-statements')
            ->assertExitCode(0);

        $this->artisan('credit-cards:generate-statements')
            ->assertExitCode(0);

        $this->assertSame(
            1,
            CreditCardStatement::query()
                ->where('credit_card_id', $card->id)
                ->count()
        );

        Carbon::setTestNow();
    }
}
