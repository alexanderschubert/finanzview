<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Payee;
use App\Models\PayeeAlias;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PayeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PayeeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $giro;

    private Category $abos;

    private Category $shopping;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['is_active' => true]);

        $this->giro = Account::create([
            'user_id' => $this->user->id, 'name' => 'Girokonto', 'type' => 'checking',
            'currency' => 'EUR', 'opening_balance' => 0, 'include_in_total' => true, 'is_active' => true,
        ]);

        $this->abos = Category::create(['user_id' => $this->user->id, 'name' => 'Abos', 'type' => 'expense', 'icon' => '📺', 'is_active' => true]);
        $this->shopping = Category::create(['user_id' => $this->user->id, 'name' => 'Shopping', 'type' => 'expense', 'icon' => '🛍️', 'is_active' => true]);
    }

    private function spend(string $merchant, float $amount = 10, ?Category $category = null, string $date = '2026-09-10'): Transaction
    {
        return Transaction::create([
            'user_id' => $this->user->id, 'account_id' => $this->giro->id, 'category_id' => $category?->id,
            'type' => 'expense', 'amount' => $amount, 'transaction_date' => $date,
            'description' => 'Zahlung ' . $merchant, 'merchant' => $merchant,
        ]);
    }

    private function payee(string $name): Payee
    {
        return Payee::where('user_id', $this->user->id)->get()->first(fn ($p) => PayeeService::key($p->name) === PayeeService::key($name));
    }

    public function test_payees_are_created_from_existing_merchants_with_stats(): void
    {
        $this->spend('Rewe', 20);
        $this->spend('Rewe', 30);
        $this->spend('REWE', 5);                       // gleiche Schreibweise (Groß-/Kleinschreibung)
        $this->spend('Netflix', 13.99);
        Transaction::create(['user_id' => $this->user->id, 'account_id' => $this->giro->id, 'type' => 'income', 'amount' => 3, 'transaction_date' => '2026-09-11', 'description' => 'Erstattung', 'merchant' => 'Rewe']);

        $this->actingAs($this->user)
            ->get(route('payees.index'))
            ->assertOk()
            ->assertSee('4 Buchungen')
            ->assertSee('−55,00 €')
            ->assertSee('+3,00 €')
            ->assertSee('Netflix');

        $this->assertSame(2, Payee::count());
    }

    public function test_similar_names_are_suggested_and_can_be_merged_in_one_tap(): void
    {
        $this->spend('PAYPAL *PATREONIREL MEM 4159353822', 5.95);
        $this->spend('PAYPAL *PATREONIREL MEM 4159353999', 5.95);
        $this->spend('PAYPAL *PATREONIREL MEM 4159353999', 5.95);
        $this->spend('Netflix');

        $page = $this->actingAs($this->user)->get(route('payees.index', ['view' => 'suggestions']))->assertOk();

        $page->assertSee('MEM 4159353999')->assertSee('2 Buchungen')->assertDontSee('Netflix');

        $ids = Payee::where('name', 'like', 'PAYPAL%')->pluck('id')->all();

        $this->actingAs($this->user)
            ->post(route('payees.merge.store'), ['ids' => $ids, 'name' => 'PayPal Patreon'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', '2 Empfänger wurden zu „PayPal Patreon“ zusammengeführt.');

        $payee = $this->payee('PayPal Patreon');

        // Buchungstext der Bank bleibt unverändert, die Buchungen gehören jetzt zu einem Empfänger.
        $this->assertSame(3, Transaction::where('payee_id', $payee->id)->count());
        $this->assertSame(3, Transaction::where('merchant', 'like', '%PATREONIREL%')->count());
        $this->assertSame(0, Transaction::where('merchant', 'PayPal Patreon')->count());
        $this->assertSame(3, $payee->aliases()->count());

        $this->actingAs($this->user)->get(route('payees.index', ['view' => 'suggestions']))->assertSee('Keine ähnlichen Namen');
    }

    public function test_ignored_suggestions_stay_hidden(): void
    {
        $this->spend('Tankstelle 12');
        $this->spend('Tankstelle 99');

        $this->actingAs($this->user)->get(route('payees.index'));

        $ids = Payee::pluck('id')->all();

        $this->actingAs($this->user)->post(route('payees.ignore'), ['ids' => $ids])->assertSessionHas('success');
        $this->actingAs($this->user)->get(route('payees.index', ['view' => 'suggestions']))->assertSee('Keine ähnlichen Namen');
    }

    public function test_merge_form_selection_and_default_category(): void
    {
        $a = $this->spend('Amazon EU')->merchant;
        $this->spend('Amazon EU');
        $this->spend('AMZN Mktp DE');
        $this->actingAs($this->user)->get(route('payees.index'));

        $ids = Payee::pluck('id')->all();

        $this->actingAs($this->user)
            ->post(route('payees.merge'), ['ids' => [$ids[0]]])
            ->assertRedirect(route('payees.index'));

        $this->actingAs($this->user)
            ->post(route('payees.merge'), ['ids' => $ids])
            ->assertOk()
            ->assertSee('Zusammenführen')
            ->assertSee('value="Amazon EU"', false);

        $this->actingAs($this->user)
            ->post(route('payees.merge.store'), ['ids' => $ids, 'name' => 'Amazon', 'default_category_id' => $this->shopping->id, 'apply' => '1'])
            ->assertSessionHasNoErrors();

        $amazon = $this->payee('Amazon');

        $this->assertSame(3, Transaction::where('payee_id', $amazon->id)->where('category_id', $this->shopping->id)->count());
        $this->assertSame($this->shopping->id, $amazon->default_category_id);
        $this->assertSame(['AMZN Mktp DE'], Transaction::where('merchant', 'AMZN Mktp DE')->pluck('merchant')->all());
    }

    public function test_new_transactions_use_the_canonical_name_and_default_category(): void
    {
        $this->spend('Netflix International B.V.');
        $this->spend('NETFLIX.COM');
        $this->actingAs($this->user)->get(route('payees.index'));

        $this->actingAs($this->user)->post(route('payees.merge.store'), [
            'ids' => Payee::pluck('id')->all(), 'name' => 'Netflix', 'default_category_id' => $this->abos->id,
        ]);

        // Manuelle Eingabe mit alter Schreibweise.
        $this->actingAs($this->user)
            ->post(route('transactions.store'), [
                'account_id' => $this->giro->id, 'type' => 'expense', 'amount' => 13.99,
                'transaction_date' => '2026-10-01', 'description' => 'Oktober', 'merchant' => 'netflix.com', 'category_id' => '',
            ])
            ->assertSessionHasNoErrors();

        $created = Transaction::where('description', 'Oktober')->firstOrFail();
        $this->assertSame('netflix.com', $created->merchant);            // Händlertext unverändert
        $this->assertSame('Netflix', $created->payee->name);            // Empfänger zusätzlich
        $this->assertSame($this->abos->id, $created->category_id);

        // Gewählte Kategorie hat Vorrang.
        $this->actingAs($this->user)->post(route('transactions.store'), [
            'account_id' => $this->giro->id, 'type' => 'expense', 'amount' => 5, 'transaction_date' => '2026-10-02',
            'description' => 'Geschenk', 'merchant' => 'Netflix', 'category_id' => $this->shopping->id,
        ]);

        $this->assertSame($this->shopping->id, Transaction::where('description', 'Geschenk')->value('category_id'));

        // Unbekannter Händler wird als neuer Empfänger angelegt.
        $this->actingAs($this->user)->post(route('transactions.store'), [
            'account_id' => $this->giro->id, 'type' => 'expense', 'amount' => 4, 'transaction_date' => '2026-10-03',
            'description' => 'Brötchen', 'merchant' => 'Bäckerei Müller',
        ]);

        $this->assertNotNull($this->payee('Bäckerei Müller'));
    }

    public function test_csv_import_applies_payee_name_and_category(): void
    {
        Storage::fake('local');

        $this->spend('PayPal (Europe) S.a r.l. et Cie');
        $this->actingAs($this->user)->get(route('payees.index'));
        $this->actingAs($this->user)->post(route('payees.merge.store'), [
            'ids' => [$this->payee('PayPal (Europe) S.a r.l. et Cie')->id, $this->makePayee('PayPal')->id],
            'name' => 'PayPal', 'default_category_id' => $this->shopping->id,
        ]);

        $csv = "\"Buchungstag\";\"Beguenstigter/Zahlungspflichtiger\";\"Verwendungszweck\";\"Betrag\"\n"
            . "\"01.09.2026\";\"PayPal (Europe) S.a r.l. et Cie\";\"Einkauf 1\";\"-12,00\"\n";

        $location = $this->actingAs($this->user)
            ->post(route('transactions.import.upload'), [
                'account_id' => $this->giro->id,
                'file' => UploadedFile::fake()->createWithContent('umsaetze.csv', $csv),
            ])
            ->headers->get('Location');

        preg_match('#/transactions/import/([A-Za-z0-9]{32})#', $location, $match);

        $this->actingAs($this->user)->post(route('transactions.import.store', $match[1]), [
            'account_id' => $this->giro->id, 'selected' => '0',
        ]);

        $imported = Transaction::where('description', 'Einkauf 1')->firstOrFail();
        $this->assertSame('PayPal (Europe) S.a r.l. et Cie', $imported->merchant);   // Originaltext der Bank
        $this->assertSame('PayPal', $imported->payee->name);
        $this->assertSame($this->shopping->id, $imported->category_id);

        // Erneuter Import erkennt die Zeile trotz geändertem Namen als bereits importiert.
        $location = $this->actingAs($this->user)
            ->post(route('transactions.import.upload'), [
                'account_id' => $this->giro->id,
                'file' => UploadedFile::fake()->createWithContent('umsaetze.csv', $csv),
            ])
            ->headers->get('Location');

        $this->actingAs($this->user)->get($location)->assertSee('Bereits importiert');
    }

    private function makePayee(string $name): Payee
    {
        return app(PayeeService::class)->ensure($this->user->id, $name);
    }

    public function test_edit_rename_category_aliases_and_delete(): void
    {
        $this->spend('Rewe Markt 12', 10);
        $this->spend('Rewe Markt 12', 20);
        $uncategorized = $this->spend('Rewe Markt 12', 30);
        $categorized = $this->spend('Rewe Markt 12', 40, $this->shopping);

        $this->actingAs($this->user)->get(route('payees.index'));
        $payee = $this->payee('Rewe Markt 12');

        $this->actingAs($this->user)
            ->get(route('payees.edit', $payee))
            ->assertOk()
            ->assertSee('4 Buchungen')
            ->assertSee('100,00 €');

        $this->actingAs($this->user)
            ->put(route('payees.update', $payee), ['name' => 'Rewe', 'default_category_id' => $this->abos->id, 'apply' => '1'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Empfänger „Rewe“ wurde gespeichert. 3 Buchungen bekamen die Kategorie.');

        $this->assertSame('Rewe Markt 12', $uncategorized->fresh()->merchant);        // Originaltext bleibt
        $this->assertSame('Rewe', $uncategorized->fresh()->payee->name);
        $this->assertSame($this->abos->id, $uncategorized->fresh()->category_id);
        $this->assertSame($this->shopping->id, $categorized->fresh()->category_id);   // bleibt

        // Alte Schreibweise bleibt als Alias – neue Buchungen damit werden zugeordnet.
        $payee->refresh()->load('aliases');
        $this->assertSame(['rewe', 'rewe markt 12'], $payee->aliases->pluck('alias_key')->sort()->values()->all());
        $this->assertSame('Rewe', app(PayeeService::class)->lookup($this->user->id)->find('REWE  Markt 12')['name']);

        $alias = $payee->aliases->firstWhere('alias_key', 'rewe markt 12');

        $this->actingAs($this->user)->delete(route('payees.aliases.destroy', [$payee, $alias]))->assertSessionHas('success');
        $this->assertNull(app(PayeeService::class)->lookup($this->user->id)->find('Rewe Markt 12'));

        // Eigener Name lässt sich nicht entfernen, Empfänger mit Buchungen nicht löschen.
        $own = $payee->aliases()->first();
        $this->actingAs($this->user)->delete(route('payees.aliases.destroy', [$payee, $own]))->assertSessionHas('error');
        $this->actingAs($this->user)->delete(route('payees.destroy', $payee))->assertSessionHas('error');

        // Keine Standardkategorie.
        $this->actingAs($this->user)->put(route('payees.update', $payee), ['name' => 'Rewe', 'default_category_id' => ''])->assertSessionHasNoErrors();
        $this->assertNull($payee->fresh()->default_category_id);

        // Ohne Buchungen lässt er sich löschen.
        $empty = $this->makePayee('Ohne Buchungen');
        $this->actingAs($this->user)->delete(route('payees.destroy', $empty))->assertRedirect(route('payees.index'));
        $this->assertNull(Payee::find($empty->id));
    }

    public function test_name_of_another_payee_is_rejected_and_others_data_is_protected(): void
    {
        $this->spend('Rewe');
        $this->spend('Edeka');
        $this->actingAs($this->user)->get(route('payees.index'));

        $edeka = $this->payee('Edeka');

        $this->actingAs($this->user)
            ->put(route('payees.update', $edeka), ['name' => 'rewe'])
            ->assertSessionHasErrors('name');

        $other = User::factory()->create(['is_active' => true]);

        $this->actingAs($other)->get(route('payees.edit', $edeka))->assertNotFound();
        $this->actingAs($other)->put(route('payees.update', $edeka), ['name' => 'X'])->assertNotFound();
        $this->actingAs($other)->delete(route('payees.destroy', $edeka))->assertNotFound();

        // Fremde IDs werden beim Zusammenführen ignoriert.
        $this->actingAs($other)
            ->post(route('payees.merge.store'), ['ids' => Payee::pluck('id')->all(), 'name' => 'Böse'])
            ->assertRedirect(route('payees.index'));

        $this->assertSame(2, Payee::where('user_id', $this->user->id)->count());
        $this->assertSame(0, Transaction::where('merchant', 'Böse')->count());
    }

    public function test_backup_round_trip_keeps_payees_aliases_and_categories(): void
    {
        $this->spend('Netflix International B.V.');
        $this->spend('NETFLIX.COM');
        $this->actingAs($this->user)->get(route('payees.index'));
        $this->actingAs($this->user)->post(route('payees.merge.store'), [
            'ids' => Payee::pluck('id')->all(), 'name' => 'Netflix', 'default_category_id' => $this->abos->id,
        ]);

        $backup = $this->actingAs($this->user)->post(route('settings.data-export.json'))->assertOk()->getContent();

        $this->assertStringContainsString('"payees"', $backup);

        $target = User::factory()->create(['is_active' => true]);

        $this->actingAs($target)
            ->post(route('settings.data-export.import'), ['backup' => UploadedFile::fake()->createWithContent('backup.json', $backup)])
            ->assertOk()
            ->assertSee('Empfänger');

        $this->withSession(['finanzview_import_token' => session('finanzview_import_token')])
            ->actingAs($target)
            ->post(route('settings.data-export.import.restore'), ['token' => session('finanzview_import_token'), 'confirm' => '1'])
            ->assertSessionHasNoErrors();

        $restored = Payee::where('user_id', $target->id)->where('name', 'Netflix')->firstOrFail();

        $this->assertSame('Abos', $restored->defaultCategory->name);
        $this->assertSame(3, $restored->aliases()->count());

        $this->assertSame('Netflix', app(PayeeService::class)->lookup($target->id)->find('netflix.com')['name']);

        // Buchungen behalten den Originaltext und sind wieder dem Empfänger zugeordnet.
        $this->assertEqualsCanonicalizing(
            ['Netflix International B.V.', 'NETFLIX.COM'],
            Transaction::where('user_id', $target->id)->pluck('merchant')->all()
        );
        $this->assertSame(2, Transaction::where('user_id', $target->id)->where('payee_id', $restored->id)->count());
    }

    public function test_categories_page_links_to_payees_and_form_offers_suggestions(): void
    {
        $this->spend('Rewe');
        $this->actingAs($this->user)->get(route('payees.index'));

        $this->actingAs($this->user)->get(route('categories.index'))->assertSee(route('payees.index'), false);

        $this->actingAs($this->user)
            ->get(route('transactions.create'))
            ->assertSee('<datalist id="payee-names">', false)
            ->assertSee('<option value="Rewe">', false);
    }

    public function test_list_shows_payee_as_title_keeps_bank_text_and_filters_by_payee(): void
    {
        $this->spend('PAYPAL *PATREONIREL MEM 4159353822', 5.95);
        $this->spend('Netflix', 13.99);
        $this->actingAs($this->user)->get(route('payees.index'));

        $paypal = $this->payee('PAYPAL *PATREONIREL MEM 4159353822');
        $this->actingAs($this->user)->put(route('payees.update', $paypal), ['name' => 'Patreon'])->assertSessionHasNoErrors();

        $page = $this->actingAs($this->user)->get(route('transactions.index'))->assertOk();
        $content = $page->getContent();

        $this->assertMatchesRegularExpression('/truncate">\s*Patreon\s*<\/p>/', $content);
        $page->assertSee('PAYPAL *PATREONIREL MEM 4159353822');   // Buchungstext der Bank bleibt sichtbar

        $this->actingAs($this->user)
            ->get(route('transactions.index', ['payee' => $paypal->id]))
            ->assertSee('Patreon')
            ->assertDontSee('Netflix');

        // Suche findet auch den Empfängernamen.
        $this->actingAs($this->user)
            ->get(route('transactions.index', ['search' => 'patreon']))
            ->assertSee('1 Buchung gefunden');
    }

    public function test_payee_can_be_chosen_explicitly_on_a_transaction_and_is_learned(): void
    {
        $transaction = $this->spend('AMZN Mktp DE*2A1B', 25);

        $edit = fn (array $extra) => $this->actingAs($this->user)->put(route('transactions.update', $transaction), [
            'account_id' => $this->giro->id, 'type' => 'expense', 'amount' => 25, 'transaction_date' => '2026-09-10',
            'description' => $transaction->description, 'merchant' => 'AMZN Mktp DE*2A1B', ...$extra,
        ])->assertSessionHasNoErrors();

        $edit(['payee' => 'Amazon']);

        $transaction->refresh();
        $this->assertSame('Amazon', $transaction->payee->name);
        $this->assertSame('AMZN Mktp DE*2A1B', $transaction->merchant);

        // Der Händlertext wurde als Schreibweise gemerkt: neue Buchungen damit gehören zu Amazon.
        $this->assertSame('Amazon', app(PayeeService::class)->lookup($this->user->id)->find('AMZN Mktp DE*2A1B')['name']);

        $this->actingAs($this->user)
            ->get(route('transactions.edit', $transaction))
            ->assertSee('name="payee"', false)
            ->assertSee('value="Amazon"', false);

        // Leeres Feld: Empfänger wird wieder aus dem Händlertext abgeleitet (gelernte Schreibweise).
        $edit(['payee' => '']);
        $this->assertSame('Amazon', $transaction->fresh()->payee->name);
    }

    public function test_sidebar_links_to_payees_and_reports_use_payee_names(): void
    {
        $this->spend('Rewe Markt 12', 40);
        $this->spend('REWE Markt 99', 60);
        $this->actingAs($this->user)->get(route('payees.index'));

        $this->actingAs($this->user)
            ->post(route('payees.merge.store'), ['ids' => Payee::pluck('id')->all(), 'name' => 'Rewe'])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertSee(route('payees.index'), false);

        // Top-Händler fasst beide Schreibweisen unter dem Empfängernamen zusammen.
        $report = app(\App\Services\ReportService::class)->build(
            $this->user,
            \Carbon\CarbonImmutable::parse('2026-09-01'),
            \Carbon\CarbonImmutable::parse('2026-09-30')->endOfDay()
        );

        $top = $report['top_merchants']->first();

        $this->assertSame('Rewe', $top['name']);
        $this->assertSame(2, $top['count']);
        $this->assertEqualsWithDelta(100, $top['amount'], 0.001);
    }
}
