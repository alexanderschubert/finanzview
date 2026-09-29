<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CsvImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TransferMatchingTest extends TestCase
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
        $this->amex = $this->account('Amex', 'credit_card');
    }

    private function account(string $name, string $type): Account
    {
        return Account::create([
            'user_id' => $this->user->id, 'name' => $name, 'type' => $type,
            'currency' => 'EUR', 'opening_balance' => 0, 'include_in_total' => true, 'is_active' => true,
        ]);
    }

    private function transaction(Account $account, string $type, float $amount, string $date, string $description, array $extra = []): Transaction
    {
        return Transaction::create([
            'user_id' => $this->user->id, 'account_id' => $account->id, 'type' => $type,
            'amount' => $amount, 'transaction_date' => $date, 'description' => $description, ...$extra,
        ]);
    }

    private function amexSettlement(): array
    {
        return [
            $this->transaction($this->giro, 'expense', 350, now()->subDays(10)->toDateString(), 'AMERICAN EXPRESS EUROPE'),
            $this->transaction($this->amex, 'income', 350, now()->subDays(8)->toDateString(), 'ZAHLUNG ERHALTEN. BESTEN DANK.'),
        ];
    }

    public function test_settlement_pair_is_suggested_with_banner(): void
    {
        $this->amexSettlement();

        // Keine Paare: gleiches Konto, anderer Betrag, zu weit auseinander.
        $this->transaction($this->giro, 'expense', 50, now()->subDays(5)->toDateString(), 'Einkauf');
        $this->transaction($this->giro, 'income', 50, now()->subDays(5)->toDateString(), 'Erstattung');
        $this->transaction($this->giro, 'expense', 99, now()->subDays(40)->toDateString(), 'Alt');
        $this->transaction($this->amex, 'income', 99, now()->subDays(20)->toDateString(), 'Alt');

        $this->actingAs($this->user)
            ->get(route('transactions.index'))
            ->assertSee('1 mögliche Umbuchung gefunden');

        $this->actingAs($this->user)
            ->get(route('transactions.transfers'))
            ->assertOk()
            ->assertSee('AMERICAN EXPRESS EUROPE')
            ->assertSee('ZAHLUNG ERHALTEN. BESTEN DANK.')
            ->assertSee('2 Tage Abstand')
            ->assertDontSee('Erstattung');
    }

    public function test_merge_turns_pair_into_transfer_and_keeps_balances(): void
    {
        [$expense, $income] = $this->amexSettlement();
        $this->transaction($this->amex, 'expense', 350, now()->subDays(20)->toDateString(), 'Einkäufe');

        $giroBefore = $this->giro->current_balance;
        $amexBefore = $this->amex->current_balance;

        $this->actingAs($this->user)
            ->post(route('transactions.transfers.merge'), ['expense_id' => $expense->id, 'income_id' => $income->id])
            ->assertSessionHas('success', 'Als Umbuchung zusammengefasst.');

        $expense->refresh();
        $this->assertSame('transfer', $expense->type);
        $this->assertSame($this->amex->id, $expense->transfer_account_id);
        $this->assertSoftDeleted($income);

        $this->assertEqualsWithDelta($giroBefore, $this->giro->fresh()->current_balance, 0.001);
        $this->assertEqualsWithDelta($amexBefore, $this->amex->fresh()->current_balance, 0.001);

        // Ausgaben zählen nur noch einmal (die Einkäufe), keine Einnahme mehr.
        $this->actingAs($this->user)
            ->get(route('transactions.index'))
            ->assertViewHas('totalExpense', 350.0)
            ->assertViewHas('totalIncome', 0.0)
            ->assertViewHas('transferSuggestions', 0);
    }

    public function test_dismissed_pairs_are_not_suggested_again(): void
    {
        [$expense, $income] = $this->amexSettlement();

        $this->actingAs($this->user)
            ->post(route('transactions.transfers.dismiss'), ['expense_id' => $expense->id, 'income_id' => $income->id]);

        $this->actingAs($this->user)->get(route('transactions.transfers'))->assertSee('Keine möglichen Umbuchungen');
        $this->assertSame('expense', $expense->fresh()->type);
    }

    public function test_merge_all_and_foreign_transactions_are_protected(): void
    {
        [$expense, $income] = $this->amexSettlement();
        $this->transaction($this->giro, 'expense', 200, now()->subDays(40)->toDateString(), 'Sparen');
        $savings = $this->account('Sparbuch', 'savings');
        $this->transaction($savings, 'income', 200, now()->subDays(40)->toDateString(), 'Übertrag');

        $other = User::factory()->create(['is_active' => true]);

        $this->actingAs($other)
            ->post(route('transactions.transfers.merge'), ['expense_id' => $expense->id, 'income_id' => $income->id])
            ->assertNotFound();

        $this->actingAs($this->user)
            ->post(route('transactions.transfers.merge-all'))
            ->assertSessionHas('success', '2 Umbuchungen zusammengefasst.');

        $this->assertSame(2, Transaction::where('type', 'transfer')->count());
        $this->assertSame(2, Transaction::count());
    }

    public function test_invalid_pair_is_rejected(): void
    {
        $a = $this->transaction($this->giro, 'expense', 10, now()->toDateString(), 'A');
        $b = $this->transaction($this->amex, 'income', 11, now()->toDateString(), 'B');

        $this->actingAs($this->user)
            ->post(route('transactions.transfers.merge'), ['expense_id' => $a->id, 'income_id' => $b->id])
            ->assertSessionHas('error');

        $this->assertSame('expense', $a->fresh()->type);
    }

    public function test_csv_reimport_does_not_bring_back_merged_income(): void
    {
        Storage::fake('local');

        $csv = "Datum,Beschreibung,Karteninhaber,Konto #,Betrag\n"
            . now()->subDays(8)->format('d/m/Y') . ",ZAHLUNG ERHALTEN. BESTEN DANK.,MAX,-1,\"-350,00\"\n";

        $upload = function () use ($csv) {
            $location = $this->actingAs($this->user)
                ->post(route('transactions.import.upload'), [
                    'account_id' => $this->amex->id,
                    'file' => UploadedFile::fake()->createWithContent('amex.csv', $csv),
                ])
                ->headers->get('Location');

            preg_match('#/transactions/import/([A-Za-z0-9]{32})#', $location, $match);

            return $match[1];
        };

        $this->actingAs($this->user)->post(route('transactions.import.store', $upload()), [
            'account_id' => $this->amex->id, 'invert' => '1', 'selected' => '0',
        ]);

        $income = Transaction::where('account_id', $this->amex->id)->firstOrFail();
        $expense = $this->transaction($this->giro, 'expense', 350, now()->subDays(10)->toDateString(), 'AMERICAN EXPRESS');

        $this->actingAs($this->user)->post(route('transactions.transfers.merge'), ['expense_id' => $expense->id, 'income_id' => $income->id]);

        $token = $upload();

        $this->actingAs($this->user)
            ->get(route('transactions.import.preview', ['token' => $token, 'account_id' => $this->amex->id]))
            ->assertSee('Bereits importiert');
    }
}
