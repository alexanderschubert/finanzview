<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\CategoryRule;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CategoryRuleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $account;

    private Category $abos;

    private Category $shopping;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['is_active' => true]);

        $this->account = Account::create([
            'user_id' => $this->user->id, 'name' => 'Girokonto', 'type' => 'checking',
            'currency' => 'EUR', 'opening_balance' => 0, 'include_in_total' => true, 'is_active' => true,
        ]);

        $this->abos = Category::create(['user_id' => $this->user->id, 'name' => 'Abos', 'type' => 'expense', 'icon' => '📺', 'is_active' => true]);
        $this->shopping = Category::create(['user_id' => $this->user->id, 'name' => 'Shopping', 'type' => 'expense', 'icon' => '🛍️', 'is_active' => true]);
    }

    private function rule(string $pattern, Category $category, array $attributes = []): CategoryRule
    {
        return CategoryRule::create([
            'user_id' => $this->user->id, 'category_id' => $category->id,
            'pattern' => $pattern, 'match_field' => 'any', 'is_active' => true,
            ...$attributes,
        ]);
    }

    private function transaction(array $attributes): Transaction
    {
        return Transaction::create([
            'user_id' => $this->user->id, 'account_id' => $this->account->id,
            'type' => 'expense', 'amount' => 10, 'transaction_date' => '2026-09-01', 'description' => 'Test',
            ...$attributes,
        ]);
    }

    public function test_pages_render_and_categories_link_to_rules(): void
    {
        $rule = $this->rule('Netflix', $this->abos);

        $this->actingAs($this->user)->get(route('categories.index'))->assertSee(route('category-rules.index'), false);

        $this->actingAs($this->user)
            ->get(route('category-rules.index', ['pattern' => 'Spotify', 'category_id' => $this->abos->id]))
            ->assertOk()
            ->assertSee('„Netflix“', false)
            ->assertSee('value="Spotify"', false);

        $this->actingAs($this->user)->get(route('category-rules.edit', $rule))->assertOk()->assertSee('Regel bearbeiten');
    }

    public function test_store_applies_rule_to_uncategorized_transactions_only(): void
    {
        $open = $this->transaction(['merchant' => 'NETFLIX.COM', 'description' => 'Abo']);
        $kept = $this->transaction(['merchant' => 'Netflix', 'category_id' => $this->shopping->id]);
        $income = $this->transaction(['merchant' => 'Netflix Erstattung', 'type' => 'income']);

        $this->actingAs($this->user)
            ->post(route('category-rules.store'), [
                'pattern' => 'netflix',
                'category_id' => $this->abos->id,
                'match_field' => 'any',
                'is_active' => '1',
                'apply' => '1',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Regel „netflix“ wurde angelegt. 1 Buchung wurde zugeordnet.');

        $this->assertSame($this->abos->id, $open->fresh()->category_id);
        $this->assertSame($this->shopping->id, $kept->fresh()->category_id);
        $this->assertNull($income->fresh()->category_id);
    }

    public function test_longer_pattern_wins_and_match_field_is_respected(): void
    {
        $this->rule('Amazon', $this->shopping);
        $this->rule('Amazon Prime', $this->abos);
        $this->rule('Miete', $this->abos, ['match_field' => 'merchant']);

        $prime = $this->transaction(['merchant' => 'AMAZON PRIME VIDEO']);
        $order = $this->transaction(['merchant' => 'Amazon EU']);
        $rent = $this->transaction(['merchant' => 'Hausverwaltung', 'description' => 'Miete Oktober']);

        $this->actingAs($this->user)
            ->post(route('category-rules.apply'))
            ->assertSessionHas('success', '2 Buchungen wurden zugeordnet.');

        $this->assertSame($this->abos->id, $prime->fresh()->category_id);
        $this->assertSame($this->shopping->id, $order->fresh()->category_id);
        $this->assertNull($rent->fresh()->category_id);
    }

    public function test_new_transaction_without_category_uses_rule(): void
    {
        $this->rule('Spotify', $this->abos);
        $this->rule('Pausiert', $this->shopping, ['is_active' => false]);

        foreach (['Spotify AB' => $this->abos->id, 'Pausiert GmbH' => null] as $merchant => $expected) {
            $this->actingAs($this->user)
                ->post(route('transactions.store'), [
                    'account_id' => $this->account->id,
                    'type' => 'expense',
                    'amount' => 9.99,
                    'transaction_date' => '2026-09-05',
                    'description' => 'Monatlich',
                    'merchant' => $merchant,
                    'category_id' => '',
                ])
                ->assertSessionHasNoErrors();

            $this->assertSame($expected, Transaction::where('merchant', $merchant)->value('category_id'));
        }

        // Ausdrücklich gewählte Kategorie hat Vorrang.
        $this->actingAs($this->user)->post(route('transactions.store'), [
            'account_id' => $this->account->id, 'type' => 'expense', 'amount' => 5,
            'transaction_date' => '2026-09-06', 'description' => 'Spotify Geschenk', 'merchant' => 'Spotify',
            'category_id' => $this->shopping->id,
        ]);

        $this->assertSame($this->shopping->id, Transaction::where('description', 'Spotify Geschenk')->value('category_id'));
    }

    public function test_csv_import_prefers_rules_and_flags_pending_sparkasse_rows(): void
    {
        Storage::fake('local');

        $both = Category::create(['user_id' => $this->user->id, 'name' => 'Sonstiges', 'type' => 'both', 'icon' => '📦', 'is_active' => true]);
        $this->rule('Netflix', $this->abos);
        $this->rule('Flohmarkt', $both);

        $csv = "\"Auftragskonto\";\"Buchungstag\";\"Valutadatum\";\"Buchungstext\";\"Verwendungszweck\";\"Glaeubiger ID\";\"Mandatsreferenz\";\"Kundenreferenz (End-to-End)\";\"Sammlerreferenz\";\"Lastschrift Ursprungsbetrag\";\"Auslagenersatz Ruecklastschrift\";\"Beguenstigter/Zahlungspflichtiger\";\"Kontonummer/IBAN\";\"BIC (SWIFT-Code)\";\"Betrag\";\"Waehrung\";\"Info\"\n"
            . "\"DE00\";\"01.09.26\";\"01.09.26\";\"FOLGELASTSCHRIFT\";\"Abo September\";\"\";\"\";\"\";\"\";\"\";\"\";\"NETFLIX INTERNATIONAL\";\"NL00\";\"ABC\";\"-13,99\";\"EUR\";\"Umsatz gebucht\"\n"
            . "\"DE00\";\"02.09.26\";\"02.09.26\";\"GUTSCHR. UEBERWEISUNG\";\"Flohmarkt Verkauf\";\"\";\"\";\"\";\"\";\"\";\"\";\"Max Muster\";\"DE11\";\"XYZ\";\"25,00\";\"EUR\";\"Umsatz gebucht\"\n"
            . "\"DE00\";\"28.09.26\";\"28.09.26\";\"KARTENZAHLUNG\";\"Einkauf\";\"\";\"\";\"\";\"\";\"\";\"\";\"B\xE4ckerei\";\"\";\"\";\"-3,50\";\"EUR\";\"Umsatz vorgemerkt\"\n";

        $location = $this->actingAs($this->user)
            ->post(route('transactions.import.upload'), [
                'account_id' => $this->account->id,
                'file' => UploadedFile::fake()->createWithContent('20260929-12345-umsatz.CSV', $csv),
            ])
            ->assertSessionHasNoErrors()
            ->headers->get('Location');

        $this->actingAs($this->user)
            ->get($location)
            ->assertOk()
            ->assertSee('NETFLIX INTERNATIONAL')
            ->assertSee('Bäckerei')
            ->assertSee('Vorgemerkt – noch nicht gebucht')
            ->assertSee('value="' . $this->abos->id . '" selected', false)
            ->assertSee('value="' . $both->id . '" selected', false);

        preg_match('#/transactions/import/([A-Za-z0-9]{32})#', $location, $match);

        $this->actingAs($this->user)->post(route('transactions.import.store', $match[1]), [
            'account_id' => $this->account->id,
            'selected' => '0,1,2',
            'categories' => "1:{$both->id}",
        ]);

        $this->assertSame($this->abos->id, Transaction::where('merchant', 'NETFLIX INTERNATIONAL')->value('category_id'));
        $this->assertSame($both->id, Transaction::where('merchant', 'Max Muster')->value('category_id'));
        $this->assertTrue(Transaction::where('merchant', 'Bäckerei')->firstOrFail()->is_pending);
    }

    public function test_rules_of_other_users_are_not_accessible(): void
    {
        $other = User::factory()->create(['is_active' => true]);
        $rule = $this->rule('Netflix', $this->abos);

        $this->actingAs($other)->get(route('category-rules.edit', $rule))->assertNotFound();
        $this->actingAs($other)->delete(route('category-rules.destroy', $rule))->assertNotFound();

        $this->actingAs($other)
            ->post(route('category-rules.store'), [
                'pattern' => 'Test', 'category_id' => $this->abos->id, 'match_field' => 'any',
            ])
            ->assertSessionHasErrors('category_id');

        $this->assertSame(1, CategoryRule::count());
    }
}
