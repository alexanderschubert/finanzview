<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\CreditCard;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CreditCardStatementService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionCreditCardFormTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::factory()->create([
            'is_active' => true,
        ]);
    }

    private function createAccount(User $user, string $name = 'Girokonto'): Account
    {
        return Account::create([
            'user_id' => $user->id,
            'name' => $name,
            'type' => 'checking',
            'currency' => 'EUR',
            'opening_balance' => 0,
            'is_active' => true,
        ]);
    }

    private function createCreditCard(User $user, ?Account $account, array $attributes = []): CreditCard
    {
        return CreditCard::create(array_merge([
            'user_id' => $user->id,
            'account_id' => $account?->id,
            'name' => 'Visa',
            'issuer' => 'Test Bank',
            'last_four' => '4242',
            'credit_limit' => 2000,
            'current_balance' => 0,
            'billing_day' => 15,
            'payment_due_day' => 5,
            'is_active' => true,
        ], $attributes));
    }

    private function payload(Account $account, array $overrides = []): array
    {
        return array_merge([
            'account_id' => $account->id,
            'type' => 'expense',
            'amount' => '42.50',
            'transaction_date' => '2026-08-20',
            'description' => 'Einkauf',
        ], $overrides);
    }

    public function test_create_form_lists_active_credit_cards(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $this->createCreditCard($user, $account, ['name' => 'Aktive Karte']);
        $this->createCreditCard($user, $account, ['name' => 'Alte Karte', 'is_active' => false]);

        $this->actingAs($user)
            ->get(route('transactions.create'))
            ->assertOk()
            ->assertSee('name="credit_card_id"', false)
            ->assertSee('Aktive Karte')
            ->assertDontSee('Alte Karte');
    }

    public function test_store_with_own_credit_card_sets_it(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $card = $this->createCreditCard($user, $account);

        $this->actingAs($user)
            ->post(route('transactions.store'), $this->payload($account, [
                'credit_card_id' => $card->id,
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('transactions.index'));

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'description' => 'Einkauf',
            'credit_card_id' => $card->id,
        ]);
    }

    public function test_store_rejects_credit_card_of_other_user(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);

        $otherUser = $this->createUser();
        $otherCard = $this->createCreditCard($otherUser, $this->createAccount($otherUser));

        $this->actingAs($user)
            ->from(route('transactions.create'))
            ->post(route('transactions.store'), $this->payload($account, [
                'credit_card_id' => $otherCard->id,
            ]))
            ->assertRedirect(route('transactions.create'))
            ->assertSessionHasErrors('credit_card_id');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_store_rejects_inactive_credit_card(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $card = $this->createCreditCard($user, $account, ['is_active' => false]);

        $this->actingAs($user)
            ->post(route('transactions.store'), $this->payload($account, [
                'credit_card_id' => $card->id,
            ]))
            ->assertSessionHasErrors('credit_card_id');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_transfer_ignores_credit_card(): void
    {
        $user = $this->createUser();
        $source = $this->createAccount($user, 'Quelle');
        $target = $this->createAccount($user, 'Ziel');
        $card = $this->createCreditCard($user, $source);

        $this->actingAs($user)
            ->post(route('transactions.store'), $this->payload($source, [
                'type' => 'transfer',
                'transfer_account_id' => $target->id,
                'credit_card_id' => $card->id,
            ]))
            ->assertSessionHasNoErrors();

        $transaction = Transaction::query()->firstOrFail();

        $this->assertSame('transfer', $transaction->type);
        $this->assertNull($transaction->credit_card_id);
    }

    public function test_update_can_clear_credit_card(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $card = $this->createCreditCard($user, $account);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'credit_card_id' => $card->id,
            'type' => 'expense',
            'amount' => 10,
            'transaction_date' => '2026-08-20',
            'description' => 'Alt',
            'is_pending' => false,
        ]);

        $this->actingAs($user)
            ->put(route('transactions.update', $transaction), $this->payload($account, [
                'credit_card_id' => '',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertNull($transaction->fresh()->credit_card_id);
    }

    public function test_update_keeps_linked_inactive_card_and_edit_form_shows_it(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $card = $this->createCreditCard($user, $account, [
            'name' => 'Deaktivierte Karte',
            'is_active' => false,
        ]);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'credit_card_id' => $card->id,
            'type' => 'expense',
            'amount' => 10,
            'transaction_date' => '2026-08-20',
            'description' => 'Alt',
            'is_pending' => false,
        ]);

        $this->actingAs($user)
            ->get(route('transactions.edit', $transaction))
            ->assertOk()
            ->assertSee('Deaktivierte Karte');

        $this->actingAs($user)
            ->put(route('transactions.update', $transaction), $this->payload($account, [
                'credit_card_id' => $card->id,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame($card->id, $transaction->fresh()->credit_card_id);
    }

    public function test_update_rejects_credit_card_of_other_user(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);

        $otherUser = $this->createUser();
        $otherCard = $this->createCreditCard($otherUser, $this->createAccount($otherUser));

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'type' => 'expense',
            'amount' => 10,
            'transaction_date' => '2026-08-20',
            'description' => 'Alt',
            'is_pending' => false,
        ]);

        $this->actingAs($user)
            ->put(route('transactions.update', $transaction), $this->payload($account, [
                'credit_card_id' => $otherCard->id,
            ]))
            ->assertSessionHasErrors('credit_card_id');

        $this->assertNull($transaction->fresh()->credit_card_id);
    }

    public function test_statement_sums_expense_linked_via_form(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $card = $this->createCreditCard($user, $account);

        $this->actingAs($user)
            ->post(route('transactions.store'), $this->payload($account, [
                'amount' => '42.50',
                'transaction_date' => '2026-08-20',
                'credit_card_id' => $card->id,
            ]))
            ->assertSessionHasNoErrors();

        // Buchung ohne Karte darf nicht mitgezählt werden.
        $this->actingAs($user)
            ->post(route('transactions.store'), $this->payload($account, [
                'amount' => '99.00',
                'transaction_date' => '2026-08-20',
            ]))
            ->assertSessionHasNoErrors();

        $statement = app(CreditCardStatementService::class)
            ->generateFor($card, Carbon::parse('2026-08-20'));

        $this->assertSame('42.50', (string) $statement->amount);
    }
}
