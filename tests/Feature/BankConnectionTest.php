<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\BankConnection;
use App\Models\BankConnectionAccount;
use App\Models\Category;
use App\Models\CategoryRule;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Fints\FintsClient;
use App\Services\Fints\FintsException;
use App\Services\Fints\FintsResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Fakes\FakeFintsClient;
use Tests\TestCase;

class BankConnectionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $account;

    private FakeFintsClient $bank;

    private const IBAN = 'DE12100500000123456789';

    private const SAVINGS_IBAN = 'DE34100500000987654321';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config(['services.fints.product_id' => 'TEST123']);

        $this->bank = new FakeFintsClient();
        $this->app->instance(FintsClient::class, $this->bank);

        $this->user = User::factory()->create(['is_active' => true]);

        $this->account = Account::create([
            'user_id' => $this->user->id, 'name' => 'Girokonto', 'type' => 'checking',
            'currency' => 'EUR', 'opening_balance' => 0, 'include_in_total' => true, 'is_active' => true,
        ]);
    }

    private function readyConnection(array $attributes = []): BankConnection
    {
        $connection = BankConnection::create([
            'user_id' => $this->user->id,
            'name' => 'Berliner Sparkasse',
            'bank_code' => '10050000',
            'url' => 'https://fints.example.test/fints30',
            'username' => 'max123',
            'tan_mode' => 923,
            'tan_mode_name' => 'pushTAN 2.0',
            ...$attributes,
        ]);

        BankConnectionAccount::create([
            'bank_connection_id' => $connection->id,
            'account_id' => $this->account->id,
            'iban' => self::IBAN,
        ]);

        return $connection;
    }

    private function link(BankConnection $connection, string $iban = self::IBAN): BankConnectionAccount
    {
        return BankConnectionAccount::where('bank_connection_id', $connection->id)->where('iban', $iban)->firstOrFail();
    }

    private function result(array $transactions, ?float $balance = null, array $errors = []): array
    {
        return [
            'transactions' => $transactions,
            'balance' => $balance === null ? null : ['amount' => $balance, 'date' => '2026-09-28'],
            'errors' => $errors,
        ];
    }

    private function row(string $date, float $amount, string $name, string $description = ''): array
    {
        return ['date' => $date, 'amount' => $amount, 'name' => $name, 'description' => $description, 'booking_text' => 'LASTSCHRIFT', 'end_to_end_id' => ''];
    }

    public function test_without_product_id_the_feature_waits_for_registration(): void
    {
        config(['services.fints.product_id' => null]);

        $this->actingAs($this->user)->get(route('settings.index'))->assertSee('wartet auf Produktregistrierung');

        $this->actingAs($this->user)
            ->get(route('bank-connections.index'))
            ->assertOk()
            ->assertSee('Wartet auf Produktregistrierung')
            ->assertSee('FINTS_PRODUCT_ID')
            ->assertDontSee(route('bank-connections.create'), false);

        $this->actingAs($this->user)->get(route('bank-connections.create'))->assertRedirect(route('bank-connections.index'));
    }

    public function test_full_setup_with_push_tan_and_account_selection(): void
    {
        $this->actingAs($this->user)
            ->post(route('bank-connections.store'), [
                'name' => 'Berliner Sparkasse',
                'bank_code' => '10050000',
                'url' => 'https://fints.example.test/fints30',
                'username' => 'max123',
            ])
            ->assertSessionHasNoErrors();

        $connection = BankConnection::firstOrFail();

        // Anmeldename verschlüsselt, keine PIN in der Datenbank.
        $this->assertNotSame('max123', DB::table('bank_connections')->value('username'));
        $this->assertSame('max123', $connection->username);

        $this->bank->modes = [
            ['id' => 921, 'name' => 'TAN2go', 'decoupled' => false, 'needs_medium' => false],
            ['id' => 923, 'name' => 'pushTAN 2.0', 'decoupled' => true, 'needs_medium' => true],
        ];

        $this->actingAs($this->user)
            ->post(route('bank-connections.tan-modes', $connection), ['pin' => 'geheim'])
            ->assertRedirect(route('bank-connections.setup', $connection));

        $this->assertSame('geheim', $this->bank->callsTo('tanModes')[0][2]);

        $this->actingAs($this->user)
            ->get(route('bank-connections.setup', $connection))
            ->assertSee('pushTAN 2.0')
            ->assertSee('Freigabe in der App (empfohlen)');

        $this->bank->media = [['name' => 'iPhone', 'phone' => null], ['name' => 'iPad', 'phone' => null]];

        $this->actingAs($this->user)
            ->post(route('bank-connections.tan-mode', $connection), ['tan_mode' => 923])
            ->assertRedirect(route('bank-connections.setup', $connection));

        $this->actingAs($this->user)->get(route('bank-connections.setup', $connection))->assertSee('Gerät für die Freigabe');

        $this->bank->beginResults[] = FintsResult::needsTan('state-1', 'Bitte in der App freigeben.', 'iPhone', true);

        $this->actingAs($this->user)
            ->post(route('bank-connections.tan-mode', $connection), ['tan_mode' => 923, 'tan_medium' => 'iPhone'])
            ->assertRedirect(route('bank-connections.challenge'));

        $connection->refresh();
        $this->assertSame(923, $connection->tan_mode);
        $this->assertSame('iPhone', $connection->tan_medium);

        $this->actingAs($this->user)
            ->get(route('bank-connections.challenge'))
            ->assertOk()
            ->assertSee('In der App freigeben')
            ->assertSee('Bitte in der App freigeben.');

        // Erste Nachfrage: noch nicht freigegeben, zweite: fertig.
        $this->bank->resumeResults[] = FintsResult::needsTan('state-2', null, null, true);
        $this->bank->resumeResults[] = FintsResult::done([
            ['iban' => self::IBAN, 'bic' => 'BELADEBEXXX', 'account_number' => '123456789', 'sub_account' => null, 'blz' => '10050000'],
            ['iban' => self::SAVINGS_IBAN, 'bic' => 'BELADEBEXXX', 'account_number' => '987654321', 'sub_account' => null, 'blz' => '10050000'],
            ['iban' => null, 'bic' => null, 'account_number' => '999', 'sub_account' => null, 'blz' => '10050000'],
        ]);

        $this->actingAs($this->user)->postJson(route('bank-connections.confirm'))->assertJson(['status' => 'pending']);

        $this->actingAs($this->user)
            ->postJson(route('bank-connections.confirm'))
            ->assertJson(['status' => 'done', 'redirect' => route('bank-connections.accounts', $connection)]);

        $resumes = $this->bank->callsTo('resume');
        $this->assertSame('state-1', $resumes[0][3]);
        $this->assertSame('state-2', $resumes[1][3]);
        $this->assertNull($resumes[1][4]);

        $this->actingAs($this->user)
            ->get(route('bank-connections.accounts', $connection))
            ->assertOk()
            ->assertSee('DE12 1005 0000 0123 4567 89')
            ->assertSee('DE34 1005 0000 0987 6543 21')
            ->assertSee('+ Neues Sparkonto (z. B. Sparbuch)');

        // Dasselbe FinanzView-Konto zweimal ist nicht erlaubt.
        $this->actingAs($this->user)
            ->post(route('bank-connections.accounts.store', $connection), ['link' => [self::IBAN => $this->account->id, self::SAVINGS_IBAN => $this->account->id]])
            ->assertSessionHasErrors('link');

        $this->actingAs($this->user)
            ->post(route('bank-connections.accounts.store', $connection), ['link' => [self::IBAN => $this->account->id, self::SAVINGS_IBAN => 'new:savings']])
            ->assertRedirect(route('bank-connections.index'));

        $connection->refresh();
        $this->assertTrue($connection->isReady());
        $this->assertSame(2, $connection->linkedAccounts()->count());
        $this->assertSame('BELADEBEXXX', $this->link($connection)->bic);

        $savings = Account::where('iban', self::SAVINGS_IBAN)->firstOrFail();
        $this->assertSame('savings', $savings->type);
        $this->assertSame('Sparkonto 4321', $savings->name);
        $this->assertTrue($this->link($connection, self::SAVINGS_IBAN)->adopt_balance);

        // Vorgang (mit PIN) ist abgeschlossen und gelöscht.
        $this->assertSame([], Storage::disk('local')->files('fints'));

        $this->actingAs($this->user)->get(route('bank-connections.index'))->assertSee('Umsätze abrufen');
    }

    public function test_sync_imports_new_rows_skips_known_and_manual_duplicates(): void
    {
        $connection = $this->readyConnection();

        $abos = Category::create(['user_id' => $this->user->id, 'name' => 'Abos', 'type' => 'expense', 'icon' => '📺', 'is_active' => true]);
        CategoryRule::create(['user_id' => $this->user->id, 'category_id' => $abos->id, 'pattern' => 'Netflix', 'match_field' => 'any', 'is_active' => true]);

        // Von Hand erfasst → beim Abruf als vorhanden erkennen.
        Transaction::create([
            'user_id' => $this->user->id, 'account_id' => $this->account->id, 'type' => 'expense',
            'amount' => 80, 'transaction_date' => '2026-09-03', 'description' => 'Strom',
        ]);

        $rows = [
            $this->row('2026-09-01', -13.99, 'NETFLIX INTERNATIONAL', 'Abo September'),
            $this->row('2026-09-02', 2500, 'Arbeitgeber GmbH', 'Gehalt'),
            $this->row('2026-09-03', -80, 'Stadtwerke', 'Strom'),
        ];

        $linkId = $this->link($connection)->id;

        $this->bank->beginResults[] = FintsResult::done([$linkId => $this->result($rows, 2406.01)]);

        $this->actingAs($this->user)
            ->post(route('bank-connections.sync.start', $connection), ['pin' => 'geheim', 'period' => 'auto'])
            ->assertRedirect(route('bank-connections.index'))
            ->assertSessionHas('success', 'Girokonto: 2 neue Buchungen (1 schon erfasst, übersprungen)');

        $params = $this->bank->callsTo('begin')[0][4];
        $this->assertSame('sync', $this->bank->callsTo('begin')[0][3]);
        $this->assertSame($linkId, $params['accounts'][0]['id']);
        $this->assertSame(self::IBAN, $params['accounts'][0]['iban']);
        $this->assertSame('10050000', $params['accounts'][0]['blz']);
        $this->assertSame(now()->subDays(90)->toDateString(), $params['from']);

        $link = $this->link($connection);
        $this->assertSame('2406.01', $link->bank_balance);
        $this->assertSame('2026-09-28', $link->balance_date->toDateString());

        $netflix = Transaction::where('merchant', 'NETFLIX INTERNATIONAL')->firstOrFail();
        $this->assertSame($abos->id, $netflix->category_id);
        $this->assertSame('13.99', $netflix->amount);
        $this->assertStringStartsWith('fints:', $netflix->external_id);
        $this->assertSame('income', Transaction::where('merchant', 'Arbeitgeber GmbH')->value('type'));

        $connection->refresh();
        $this->assertNotNull($connection->last_synced_at);

        // Zweiter Abruf mit denselben Umsätzen: nichts doppelt, Zeitraum ab letztem Abruf − 14 Tage.
        $this->bank->beginResults[] = FintsResult::done([$linkId => $this->result($rows)]);

        $this->actingAs($this->user)
            ->post(route('bank-connections.sync.start', $connection), ['pin' => 'geheim', 'period' => 'auto'])
            ->assertSessionHas('success', fn ($message) => str_starts_with($message, 'Girokonto: keine neuen Umsätze'));

        $this->assertSame(3, Transaction::count());
        $this->assertSame($connection->last_synced_at->copy()->subDays(14)->toDateString(), $this->bank->callsTo('begin')[1][4]['from']);
    }

    public function test_sync_with_tan_entry(): void
    {
        $connection = $this->readyConnection(['tan_mode' => 921, 'tan_mode_name' => 'TAN2go']);

        $this->bank->beginResults[] = FintsResult::needsTan('state-x', 'TAN für Umsatzabruf eingeben', null, false);
        $this->bank->resumeResults[] = FintsResult::done([$this->link($connection)->id => $this->result([$this->row('2026-09-10', -5, 'Bäckerei')])]);

        $this->actingAs($this->user)
            ->post(route('bank-connections.sync.start', $connection), ['pin' => 'geheim', 'period' => '30'])
            ->assertRedirect(route('bank-connections.challenge'));

        $this->actingAs($this->user)
            ->get(route('bank-connections.challenge'))
            ->assertSee('TAN eingeben')
            ->assertSee('name="tan"', false);

        $this->actingAs($this->user)
            ->post(route('bank-connections.confirm'), [])
            ->assertSessionHasErrors('tan');

        $this->actingAs($this->user)
            ->post(route('bank-connections.confirm'), ['tan' => '123456'])
            ->assertRedirect(route('bank-connections.index'));

        $this->assertSame('123456', $this->bank->callsTo('resume')[0][4]);
        $this->assertSame('geheim', $this->bank->callsTo('resume')[0][2]);
        $this->assertSame(1, Transaction::count());
        $this->assertSame(now()->subDays(30)->toDateString(), $this->bank->callsTo('begin')[0][4]['from']);
    }

    public function test_bank_errors_are_shown_and_pending_is_cleared(): void
    {
        $connection = $this->readyConnection();

        $this->bank->beginResults[] = new FintsException('Die Bank hat den Vorgang abgelehnt: PIN falsch');

        $this->actingAs($this->user)
            ->from(route('bank-connections.sync', $connection))
            ->post(route('bank-connections.sync.start', $connection), ['pin' => 'falsch', 'period' => 'auto'])
            ->assertRedirect(route('bank-connections.sync', $connection))
            ->assertSessionHas('error', 'Die Bank hat den Vorgang abgelehnt: PIN falsch');

        $this->bank->beginResults[] = FintsResult::needsTan('s', null, null, true);
        $this->bank->resumeResults[] = new FintsException('Die Freigabe ist abgelaufen. Bitte den Abruf neu starten.');

        $this->actingAs($this->user)->post(route('bank-connections.sync.start', $connection), ['pin' => 'geheim', 'period' => 'auto']);

        $this->actingAs($this->user)
            ->postJson(route('bank-connections.confirm'))
            ->assertJson(['status' => 'done', 'redirect' => route('bank-connections.index')]);

        $this->assertSame([], Storage::disk('local')->files('fints'));
        $this->actingAs($this->user)->get(route('bank-connections.challenge'))->assertRedirect(route('bank-connections.index'));
    }

    public function test_connections_of_other_users_are_protected(): void
    {
        $connection = $this->readyConnection();
        $other = User::factory()->create(['is_active' => true]);

        $this->actingAs($other)->get(route('bank-connections.edit', $connection))->assertNotFound();
        $this->actingAs($other)->get(route('bank-connections.sync', $connection))->assertNotFound();
        $this->actingAs($other)->post(route('bank-connections.sync.start', $connection), ['pin' => 'x', 'period' => 'auto'])->assertNotFound();
        $this->actingAs($other)->delete(route('bank-connections.destroy', $connection))->assertNotFound();

        $this->assertSame([], $this->bank->calls);
    }

    public function test_changing_access_data_resets_setup(): void
    {
        $connection = $this->readyConnection();

        $this->actingAs($this->user)
            ->put(route('bank-connections.update', $connection), [
                'name' => 'Neu', 'bank_code' => '10050000', 'url' => 'https://fints.example.test/fints30',
                'username' => 'max123',
            ])
            ->assertRedirect(route('bank-connections.index'));

        $this->assertTrue($connection->fresh()->isReady());

        $this->actingAs($this->user)
            ->put(route('bank-connections.update', $connection), [
                'name' => 'Neu', 'bank_code' => '10050000', 'url' => 'https://fints.example.test/fints30',
                'username' => 'anderer',
            ])
            ->assertRedirect(route('bank-connections.setup', $connection));

        $this->assertFalse($connection->fresh()->isReady());
        $this->assertNull($connection->fresh()->tan_mode);
        $this->assertSame(0, BankConnectionAccount::count());
    }

    public function test_new_savings_account_adopts_bank_balance_and_errors_are_kept_per_account(): void
    {
        $connection = $this->readyConnection();

        $savings = Account::create([
            'user_id' => $this->user->id, 'name' => 'Sparkonto 4321', 'type' => 'savings',
            'currency' => 'EUR', 'opening_balance' => 0, 'include_in_total' => true, 'is_active' => true,
        ]);

        $savingsLink = BankConnectionAccount::create([
            'bank_connection_id' => $connection->id, 'account_id' => $savings->id,
            'iban' => self::SAVINGS_IBAN, 'adopt_balance' => true,
        ]);

        $this->bank->beginResults[] = FintsResult::done([
            $this->link($connection)->id => $this->result([$this->row('2026-09-20', -20, 'Kiosk')], 500),
            $savingsLink->id => $this->result([$this->row('2026-09-15', 1.23, '', 'Zinsen')], 5001.23),
        ]);

        $this->actingAs($this->user)
            ->post(route('bank-connections.sync.start', $connection), ['pin' => 'geheim', 'period' => 'auto'])
            ->assertSessionHas('success', 'Girokonto: 1 neue Buchung · Sparkonto 4321: 1 neue Buchung');

        $this->assertCount(2, $this->bank->callsTo('begin')[0][4]['accounts']);

        // Sparkonto: Startsaldo so gesetzt, dass es der Bank entspricht (5.000 + Zinsen).
        $this->assertEqualsWithDelta(5001.23, $savings->fresh()->current_balance, 0.001);
        $this->assertSame('5000.00', $savings->fresh()->opening_balance);
        $this->assertFalse($savingsLink->fresh()->adopt_balance);

        // Girokonto wird nicht automatisch angepasst, sondern zeigt die Abweichung.
        $this->assertEqualsWithDelta(-20, $this->account->fresh()->current_balance, 0.001);

        $this->actingAs($this->user)
            ->get(route('bank-connections.index'))
            ->assertSee('Abweichung +520,00 €')
            ->assertSee('Bank 28.09.: 500,00 €');

        // Nächster Abruf: Sparbuch ohne Umsatzabruf → Fehler je Konto merken.
        $this->bank->beginResults[] = FintsResult::done([
            $savingsLink->id => $this->result([], 5001.23, ['Umsätze: von der Bank für dieses Konto nicht angeboten']),
        ]);

        $this->actingAs($this->user)->post(route('bank-connections.sync.start', $connection), ['pin' => 'geheim', 'period' => 'auto']);

        $this->assertSame('Umsätze: von der Bank für dieses Konto nicht angeboten', $savingsLink->fresh()->last_error);
    }

    public function test_reconcile_adjusts_opening_balance_to_bank_balance(): void
    {
        $connection = $this->readyConnection();
        $link = $this->link($connection);
        $link->update(['bank_balance' => 1234.56, 'balance_date' => '2026-09-28']);

        $this->actingAs($this->user)
            ->post(route('bank-connections.reconcile', $link))
            ->assertSessionHas('success', 'Startsaldo von „Girokonto“ wurde um 1.234,56 € angepasst.');

        $this->assertEqualsWithDelta(1234.56, $this->account->fresh()->current_balance, 0.001);

        $other = User::factory()->create(['is_active' => true]);
        $this->actingAs($other)->post(route('bank-connections.reconcile', $link))->assertNotFound();
    }
}
