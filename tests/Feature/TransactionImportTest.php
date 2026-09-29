<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TransactionImportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $account;

    private const CSV = "\"Buchungstag\";\"Beguenstigter/Zahlungspflichtiger\";\"Verwendungszweck\";\"Betrag\"\n"
        . "\"01.09.2026\";\"REWE Markt 1234\";\"Einkauf\";\"-45,90\"\n"
        . "\"02.09.2026\";\"Arbeitgeber GmbH\";\"Gehalt September\";\"2.500,00\"\n"
        . "\"03.09.2026\";\"Stadtwerke\";\"Strom\";\"-80,00\"\n"
        . "\"kaputt\";\"???\";\"\";\"\"\n";

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->user = User::factory()->create(['is_active' => true]);

        $this->account = Account::create([
            'user_id' => $this->user->id, 'name' => 'Girokonto', 'type' => 'checking',
            'currency' => 'EUR', 'opening_balance' => 0, 'include_in_total' => true, 'is_active' => true,
        ]);
    }

    private function upload(string $content = self::CSV): string
    {
        $response = $this->actingAs($this->user)
            ->post(route('transactions.import.upload'), [
                'account_id' => $this->account->id,
                'file' => UploadedFile::fake()->createWithContent('umsaetze.csv', $content),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        preg_match('#/transactions/import/([A-Za-z0-9]{32})#', $response->headers->get('Location'), $match);

        return $match[1];
    }

    private function previewUrl(string $token): string
    {
        return route('transactions.import.preview', ['token' => $token, 'account_id' => $this->account->id]);
    }

    public function test_upload_page_renders_and_index_links_to_it(): void
    {
        $this->actingAs($this->user)->get(route('transactions.import.create'))->assertOk()->assertSee('CSV-Datei auswählen');
        $this->actingAs($this->user)->get(route('transactions.index'))->assertSee(route('transactions.import.create'), false);
    }

    public function test_preview_shows_rows_with_suggested_category(): void
    {
        $food = Category::create(['user_id' => $this->user->id, 'name' => 'Lebensmittel', 'type' => 'expense', 'icon' => '🛒', 'is_active' => true]);

        Transaction::create([
            'user_id' => $this->user->id, 'account_id' => $this->account->id, 'category_id' => $food->id,
            'type' => 'expense', 'amount' => 12.30, 'transaction_date' => '2026-08-01',
            'description' => 'Einkauf', 'merchant' => 'Rewe Markt 999',
        ]);

        $token = $this->upload();

        $this->actingAs($this->user)
            ->get($this->previewUrl($token))
            ->assertOk()
            ->assertSee('REWE Markt 1234')
            ->assertSee('+2.500,00 €')
            ->assertSee('Datum fehlt oder unbekanntes Format')
            ->assertSee('value="' . $food->id . '" selected', false);
    }

    public function test_selected_rows_are_imported_and_file_is_removed(): void
    {
        $token = $this->upload();

        $this->actingAs($this->user)
            ->post(route('transactions.import.store', $token), [
                'account_id' => $this->account->id,
                'selected' => '0,1,3',
            ])
            ->assertRedirect(route('transactions.index', ['account_id' => $this->account->id]))
            ->assertSessionHas('success', '2 Buchungen wurden importiert.');

        $this->assertSame(2, Transaction::count());

        $salary = Transaction::where('type', 'income')->firstOrFail();
        $this->assertSame('2500.00', $salary->amount);
        $this->assertSame('Arbeitgeber GmbH', $salary->merchant);
        $this->assertSame('Gehalt September', $salary->description);
        $this->assertSame('2026-09-02', $salary->transaction_date->toDateString());

        $this->assertEqualsWithDelta(2454.10, $this->account->fresh()->current_balance, 0.001);

        Storage::disk('local')->assertMissing('imports/' . $this->user->id . '/' . $token . '.csv');
    }

    public function test_reimport_skips_already_imported_rows(): void
    {
        $this->actingAs($this->user)->post(route('transactions.import.store', $this->upload()), [
            'account_id' => $this->account->id,
            'selected' => '0,1,2',
        ]);

        $this->assertSame(3, Transaction::count());

        $token = $this->upload();

        $this->actingAs($this->user)
            ->get($this->previewUrl($token))
            ->assertOk()
            ->assertSee('Bereits importiert');

        // Auch bei manipulierter Auswahl entstehen keine Dubletten.
        $this->actingAs($this->user)
            ->post(route('transactions.import.store', $token), [
                'account_id' => $this->account->id,
                'selected' => '0,1,2',
            ])
            ->assertSessionHas('error');

        $this->assertSame(3, Transaction::count());
    }

    public function test_manually_entered_transaction_is_flagged_as_possible_duplicate(): void
    {
        Transaction::create([
            'user_id' => $this->user->id, 'account_id' => $this->account->id,
            'type' => 'expense', 'amount' => 80, 'transaction_date' => '2026-09-03', 'description' => 'Strom',
        ]);

        $this->actingAs($this->user)
            ->get($this->previewUrl($this->upload()))
            ->assertOk()
            ->assertSee('Gleiche Buchung vorhanden');
    }

    public function test_category_choice_is_validated_against_owner_and_type(): void
    {
        $income = Category::create(['user_id' => $this->user->id, 'name' => 'Gehalt', 'type' => 'income', 'icon' => '💶', 'is_active' => true]);
        $expense = Category::create(['user_id' => $this->user->id, 'name' => 'Energie', 'type' => 'expense', 'icon' => '⚡', 'is_active' => true]);
        $foreign = Category::create(['user_id' => User::factory()->create()->id, 'name' => 'Fremd', 'type' => 'expense', 'icon' => '❓', 'is_active' => true]);

        $this->actingAs($this->user)->post(route('transactions.import.store', $this->upload()), [
            'account_id' => $this->account->id,
            'selected' => '0,1,2',
            'categories' => "0:{$foreign->id};1:{$income->id};2:{$income->id}",
        ]);

        $this->assertNull(Transaction::where('merchant', 'REWE Markt 1234')->value('category_id'));
        $this->assertSame($income->id, Transaction::where('merchant', 'Arbeitgeber GmbH')->value('category_id'));
        $this->assertNull(Transaction::where('merchant', 'Stadtwerke')->value('category_id'));

        $this->assertNotNull($expense);
    }

    public function test_manual_column_mapping_with_debit_and_credit(): void
    {
        $csv = "Datum;Text;Ausgang;Eingang\n05.09.2026;Miete;750,00;\n06.09.2026;Erstattung;;19,99\n";

        $token = $this->upload($csv);

        $this->actingAs($this->user)
            ->get($this->previewUrl($token))
            ->assertOk()
            ->assertSee('−750,00 €')
            ->assertSee('+19,99 €');

        // Zuordnung manuell ändern: „Eingang“ nicht verwenden.
        $this->actingAs($this->user)
            ->get($this->previewUrl($token) . '&map[date]=0&map[description]=1&map[debit]=2&map[credit]=&map[amount]=&map[merchant]=')
            ->assertOk()
            ->assertSee('Betrag fehlt');
    }

    public function test_foreign_account_and_missing_file_are_rejected(): void
    {
        $other = User::factory()->create(['is_active' => true]);
        $foreignAccount = Account::create([
            'user_id' => $other->id, 'name' => 'Fremd', 'type' => 'checking',
            'currency' => 'EUR', 'opening_balance' => 0, 'include_in_total' => true, 'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->post(route('transactions.import.upload'), [
                'account_id' => $foreignAccount->id,
                'file' => UploadedFile::fake()->createWithContent('umsaetze.csv', self::CSV),
            ])
            ->assertSessionHasErrors('account_id');

        $token = $this->upload();

        // Andere Benutzer sehen die Datei nicht.
        $this->actingAs($other)
            ->get(route('transactions.import.preview', ['token' => $token, 'account_id' => $foreignAccount->id]))
            ->assertRedirect(route('transactions.import.create'));

        $this->actingAs($this->user)
            ->get(route('transactions.import.preview', ['token' => str_repeat('a', 32), 'account_id' => $this->account->id]))
            ->assertRedirect(route('transactions.import.create'));
    }

    public function test_non_csv_file_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->post(route('transactions.import.upload'), [
                'account_id' => $this->account->id,
                'file' => UploadedFile::fake()->create('foto.jpg', 10, 'image/jpeg'),
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_amex_export_is_detected_and_sign_can_be_inverted(): void
    {
        $csv = "Datum,Beschreibung,Karteninhaber,Konto #,Betrag\n"
            . "01/09/2026,REWE BERLIN,MAX MUSTER,-11005,\"45,90\"\n"
            . "03/09/2026,ZAHLUNG ERHALTEN. BESTEN DANK.,MAX MUSTER,-11005,\"-200,00\"\n";

        $token = $this->upload($csv);

        // Automatisch erkannt: Ausgaben positiv → umgekehrt.
        $this->actingAs($this->user)
            ->get($this->previewUrl($token))
            ->assertOk()
            ->assertSee('Vorzeichen umgekehrt')
            ->assertSee('−45,90 €')
            ->assertSee('+200,00 €');

        // Manuell abgeschaltet.
        $this->actingAs($this->user)
            ->get($this->previewUrl($token) . '&invert=0')
            ->assertOk()
            ->assertSee('+45,90 €');

        $this->actingAs($this->user)
            ->post(route('transactions.import.store', $token), [
                'account_id' => $this->account->id,
                'invert' => '1',
                'selected' => '0,1',
            ])
            ->assertSessionHas('success', '2 Buchungen wurden importiert.');

        $this->assertSame('expense', Transaction::where('description', 'REWE BERLIN')->value('type'));
        $this->assertSame('income', Transaction::where('description', 'ZAHLUNG ERHALTEN. BESTEN DANK.')->value('type'));
    }

    public function test_sparkasse_export_is_not_inverted(): void
    {
        $this->actingAs($this->user)
            ->get($this->previewUrl($this->upload()))
            ->assertOk()
            ->assertDontSee('Vorzeichen umgekehrt')
            ->assertSee('+2.500,00 €');
    }
}
