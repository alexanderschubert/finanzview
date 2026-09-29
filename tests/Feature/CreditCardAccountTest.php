<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\CreditCard;
use App\Models\CreditCardStatement;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CreditCardStatementService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CreditCardAccountTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $giro;

    private Account $amex;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['is_active' => true]);
        $this->giro = $this->account('Girokonto', 'checking');
        $this->amex = $this->account('AMEX', 'credit_card');
    }

    private function account(string $name, string $type): Account
    {
        return Account::create([
            'user_id' => $this->user->id, 'name' => $name, 'type' => $type,
            'currency' => 'EUR', 'opening_balance' => 0, 'include_in_total' => true, 'is_active' => true,
        ]);
    }

    private function card(array $attributes = []): CreditCard
    {
        return CreditCard::create([
            'user_id' => $this->user->id, 'account_id' => $this->giro->id, 'name' => 'Amex Gold',
            'issuer' => 'American Express', 'credit_limit' => 1000, 'current_balance' => 999,
            'billing_day' => 15, 'payment_due_day' => 5, 'is_active' => true,
            ...$attributes,
        ]);
    }

    private function transaction(Account $account, string $type, float $amount, string $date, array $extra = []): Transaction
    {
        return Transaction::create([
            'user_id' => $this->user->id, 'account_id' => $account->id, 'type' => $type,
            'amount' => $amount, 'transaction_date' => $date, 'description' => 'Test', ...$extra,
        ]);
    }

    public function test_balance_is_computed_from_card_account(): void
    {
        $this->transaction($this->amex, 'expense', 200, '2026-09-10');
        $this->transaction($this->amex, 'expense', 45.30, '2026-09-12');

        $card = $this->card(['card_account_id' => $this->amex->id]);

        $this->assertSame('245.30', $card->current_balance);

        $this->actingAs($this->user)
            ->get(route('credit-cards.index'))
            ->assertOk()
            ->assertSee('245,30 €')
            ->assertSee('754,70 € verfügbar')
            ->assertDontSee('Saldo von Hand eingetragen');

        $this->actingAs($this->user)
            ->get(route('credit-cards.show', $card))
            ->assertSee('AMEX (Saldo wird berechnet)');
    }

    public function test_card_without_account_keeps_manual_value_and_shows_hint(): void
    {
        $card = $this->card();

        $this->assertSame('999.00', $card->current_balance);

        $this->actingAs($this->user)
            ->get(route('credit-cards.index'))
            ->assertSee('999,00 €')
            ->assertSee('Saldo von Hand eingetragen');
    }

    public function test_edit_suggests_matching_account_and_update_links_it(): void
    {
        $card = $this->card();

        $content = $this->actingAs($this->user)->get(route('credit-cards.edit', $card))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/value="' . $this->amex->id . '"\s+data-balance="[^"]*"\s+selected/', $content);

        $this->actingAs($this->user)
            ->put(route('credit-cards.update', $card), [
                'name' => 'Amex Gold', 'account_id' => $this->giro->id, 'card_account_id' => $this->amex->id,
                'billing_day' => 15, 'payment_due_day' => 5, 'is_active' => '1',
            ])
            ->assertSessionHasNoErrors();

        $card->refresh();
        $this->assertSame($this->amex->id, $card->card_account_id);
        // Handwert bleibt gespeichert, wird aber nicht mehr angezeigt.
        $this->assertSame('999.00', $card->getRawOriginal('current_balance') === null ? null : number_format((float) $card->getRawOriginal('current_balance'), 2, '.', ''));
        $this->assertSame('0.00', $card->current_balance);
    }

    public function test_validation_of_card_account(): void
    {
        $other = User::factory()->create();
        $foreign = Account::create([
            'user_id' => $other->id, 'name' => 'Fremd', 'type' => 'credit_card',
            'currency' => 'EUR', 'opening_balance' => 0, 'include_in_total' => true, 'is_active' => true,
        ]);

        // Ohne Kartenkonto ist der Saldo Pflicht.
        $this->actingAs($this->user)
            ->post(route('credit-cards.store'), ['name' => 'Visa'])
            ->assertSessionHasErrors('current_balance');

        // Kartenkonto und Abbuchungskonto müssen verschieden sein.
        $this->actingAs($this->user)
            ->post(route('credit-cards.store'), ['name' => 'Visa', 'account_id' => $this->giro->id, 'card_account_id' => $this->giro->id])
            ->assertSessionHasErrors('card_account_id');

        $this->actingAs($this->user)
            ->post(route('credit-cards.store'), ['name' => 'Visa', 'card_account_id' => $foreign->id])
            ->assertForbidden();

        $this->actingAs($this->user)
            ->post(route('credit-cards.store'), ['name' => 'Visa', 'account_id' => $this->giro->id, 'card_account_id' => $this->amex->id])
            ->assertSessionHasNoErrors();

        $this->assertSame($this->amex->id, CreditCard::where('name', 'Visa')->value('card_account_id'));
    }

    public function test_statement_counts_card_account_transactions_but_not_settlement_transfers(): void
    {
        $card = $this->card(['card_account_id' => $this->amex->id]);

        $this->transaction($this->amex, 'expense', 100, '2026-08-20');
        $this->transaction($this->amex, 'expense', 50, '2026-09-01');
        $this->transaction($this->amex, 'income', 10, '2026-09-02');              // Gutschrift
        $this->transaction($this->giro, 'expense', 30, '2026-09-03', ['credit_card_id' => $card->id]); // ausdrücklich der Karte zugeordnet
        $this->transaction($this->giro, 'transfer', 300, '2026-09-05', ['transfer_account_id' => $this->amex->id]); // bezahlte Abrechnung
        $this->transaction($this->amex, 'expense', 999, '2026-09-20');            // nächster Zeitraum

        $statement = app(CreditCardStatementService::class)->generateFor($card, Carbon::create(2026, 9, 10));

        $this->assertSame('2026-08-16', $statement->period_start->toDateString());
        $this->assertSame('2026-09-15', $statement->period_end->toDateString());
        $this->assertSame('170.00', $statement->amount);
    }

    public function test_linking_recalculates_open_statements(): void
    {
        $card = $this->card();
        $statement = CreditCardStatement::create([
            'credit_card_id' => $card->id, 'period_start' => '2026-08-16', 'period_end' => '2026-09-15',
            'due_date' => '2026-10-05', 'amount' => 0, 'status' => 'open',
        ]);

        $this->transaction($this->amex, 'expense', 80, '2026-09-01');

        $this->actingAs($this->user)->put(route('credit-cards.update', $card), [
            'name' => 'Amex Gold', 'account_id' => $this->giro->id, 'card_account_id' => $this->amex->id,
            'billing_day' => 15, 'payment_due_day' => 5, 'is_active' => '1',
        ]);

        $this->assertSame('80.00', $statement->fresh()->amount);
    }

    public function test_backup_keeps_card_account_and_cards_without_payment_account(): void
    {
        $this->card(['card_account_id' => $this->amex->id]);
        $this->card(['name' => 'Visa ohne Konto', 'account_id' => null]);

        $backup = $this->actingAs($this->user)->post(route('settings.data-export.json'))->assertOk()->getContent();

        $target = User::factory()->create(['is_active' => true]);

        $this->actingAs($target)
            ->post(route('settings.data-export.import'), ['backup' => UploadedFile::fake()->createWithContent('backup.json', $backup)])
            ->assertOk();

        $this->withSession(['finanzview_import_token' => session('finanzview_import_token')])
            ->actingAs($target)
            ->post(route('settings.data-export.import.restore'), ['token' => session('finanzview_import_token'), 'confirm' => '1'])
            ->assertSessionHasNoErrors();

        $restored = CreditCard::where('user_id', $target->id)->where('name', 'Amex Gold')->firstOrFail();
        $this->assertSame('AMEX', $restored->cardAccount->name);
        $this->assertNotNull(CreditCard::where('user_id', $target->id)->where('name', 'Visa ohne Konto')->first());
    }
}
