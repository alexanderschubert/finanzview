<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\ApiToken;
use App\Models\BankConnection;
use App\Models\BankConnectionAccount;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Fints\FintsClient;
use App\Services\Fints\FintsResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\Fakes\FakeFintsClient;
use Tests\TestCase;

class NativeApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $other;

    private string $token;

    private Account $account;

    private FakeFintsClient $bank;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config(['services.fints.product_id' => 'TEST123']);

        $this->bank = new FakeFintsClient();
        $this->app->instance(FintsClient::class, $this->bank);

        $this->user = User::factory()->create(['is_active' => true]);
        $this->other = User::factory()->create(['is_active' => true]);

        [, $this->token] = ApiToken::issue($this->user, 'iPad');

        $this->account = $this->makeAccount($this->user, 'Girokonto');
    }

    private function makeAccount(User $user, string $name): Account
    {
        return Account::create([
            'user_id' => $user->id, 'name' => $name, 'type' => 'checking', 'currency' => 'EUR',
            'opening_balance' => 100, 'include_in_total' => true, 'is_active' => true,
        ]);
    }

    private function makeTransaction(User $user, Account $account, array $attributes = []): Transaction
    {
        return Transaction::create($attributes + [
            'user_id' => $user->id, 'account_id' => $account->id, 'type' => 'expense', 'amount' => 20,
            'transaction_date' => '2026-11-10', 'description' => 'Einkauf',
        ]);
    }

    private function makeCategory(User $user, string $name = 'Lebensmittel', string $type = 'expense'): Category
    {
        return Category::create(['user_id' => $user->id, 'name' => $name, 'type' => $type, 'is_active' => true]);
    }

    private function api(?string $token = null)
    {
        return $this->withToken($token ?? $this->token);
    }

    /* ---------- Anmeldung ---------- */

    public function test_api_requires_a_valid_token(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized();
        $this->withToken('falsch')->getJson('/api/v1/me')->assertUnauthorized();

        $this->api()->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('id', $this->user->id)
            ->assertJsonPath('is_admin', false)
            ->assertJsonMissingPath('password');
    }

    public function test_token_is_stored_only_as_hash_and_inactive_users_are_rejected(): void
    {
        $this->assertDatabaseMissing('api_tokens', ['token_hash' => $this->token]);
        $this->assertDatabaseHas('api_tokens', ['token_hash' => hash('sha256', $this->token)]);

        $this->user->forceFill(['is_active' => false])->save();

        $this->api()->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_connect_flow_issues_one_time_code_and_token(): void
    {
        $query = ['state' => 'abc123', 'redirect_uri' => 'finanzview://auth', 'device_name' => 'Alex iPad'];

        $this->get('/app/connect?' . http_build_query($query))->assertRedirect(route('login'));

        $this->actingAs($this->user)
            ->get('/app/connect?' . http_build_query($query))
            ->assertOk()
            ->assertSee('Verbinden');

        $response = $this->actingAs($this->user)->post('/app/connect', $query);

        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('finanzview://auth?', $location);
        parse_str(parse_url($location, PHP_URL_QUERY), $params);
        $this->assertSame('abc123', $params['state']);

        $exchange = $this->postJson('/api/v1/auth/token', ['code' => $params['code'], 'device_name' => 'Alex iPad']);
        $exchange->assertOk()->assertJsonPath('user.id', $this->user->id);

        $newToken = $exchange->json('token');
        $this->assertGreaterThanOrEqual(40, strlen($newToken));
        $this->withToken($newToken)->getJson('/api/v1/me')->assertOk();

        // Der Code ist nur einmal nutzbar.
        $this->postJson('/api/v1/auth/token', ['code' => $params['code']])->assertStatus(422);
    }

    public function test_connect_rejects_foreign_redirect_schemes(): void
    {
        foreach (['https://evil.example/cb', 'javascript:alert(1)', 'finanzview://auth?x=1', 'finanzview.evil://auth'] as $uri) {
            $this->actingAs($this->user)
                ->get('/app/connect?' . http_build_query(['state' => 's', 'redirect_uri' => $uri]))
                ->assertStatus(302)
                ->assertSessionHasErrors('redirect_uri');
        }
    }

    public function test_expired_or_unknown_code_is_rejected(): void
    {
        $this->postJson('/api/v1/auth/token', ['code' => 'gibtsnicht'])->assertStatus(422)->assertJsonValidationErrors('code');

        $this->actingAs($this->user)->post('/app/connect', ['state' => 's', 'redirect_uri' => 'finanzview://auth']);
        Cache::flush();
        $this->postJson('/api/v1/auth/token', ['code' => 'x'])->assertStatus(422);
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        [, $second] = ApiToken::issue($this->user, 'Mac');

        $this->api()->deleteJson('/api/v1/auth/token')->assertNoContent();

        $this->api()->getJson('/api/v1/me')->assertUnauthorized();
        $this->api($second)->getJson('/api/v1/me')->assertOk();
    }

    public function test_connected_apps_are_listed_and_can_be_revoked_in_security_settings(): void
    {
        $this->actingAs($this->user)->get(route('settings.security'))->assertOk()->assertSee('Verbundene Apps')->assertSee('iPad');

        $token = $this->user->apiTokens()->firstOrFail();
        $foreign = ApiToken::issue($this->other, 'Fremdes iPad')[0];

        $this->actingAs($this->user)->delete(route('settings.security.apps.destroy', $foreign))->assertNotFound();
        $this->assertDatabaseHas('api_tokens', ['id' => $foreign->id]);

        $this->actingAs($this->user)->delete(route('settings.security.apps.destroy', $token))->assertRedirect();
        $this->api()->getJson('/api/v1/me')->assertUnauthorized();
    }

    /* ---------- Lesen ---------- */

    public function test_accounts_and_categories_are_scoped_to_the_user(): void
    {
        $this->makeAccount($this->other, 'Fremdkonto');
        $this->makeCategory($this->user);
        $this->makeCategory($this->other, 'Fremd');

        $accounts = $this->api()->getJson('/api/v1/accounts')->assertOk();
        $this->assertCount(1, $accounts->json('data'));
        $this->assertEquals(100.0, $accounts->json('total_balance'));
        $this->assertSame('Girokonto', $accounts->json('data.0.name'));

        $categories = $this->api()->getJson('/api/v1/categories')->assertOk();
        $this->assertSame(['Lebensmittel'], array_column($categories->json(), 'name'));
    }

    public function test_transactions_list_filters_and_isolation(): void
    {
        $food = $this->makeCategory($this->user);
        $this->makeTransaction($this->user, $this->account, ['description' => 'REWE Markt', 'category_id' => $food->id, 'transaction_date' => '2026-11-12']);
        $this->makeTransaction($this->user, $this->account, ['description' => 'Amazon', 'transaction_date' => '2026-10-02']);
        $this->makeTransaction($this->other, $this->makeAccount($this->other, 'X'), ['description' => 'REWE fremd']);

        $this->api()->getJson('/api/v1/transactions')
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.description', 'REWE Markt')
            ->assertJsonPath('data.0.amount', -20)
            ->assertJsonPath('data.0.category.name', 'Lebensmittel');

        $this->api()->getJson('/api/v1/transactions?search=rewe')->assertJsonPath('meta.total', 1);
        $this->api()->getJson('/api/v1/transactions?uncategorized=1')->assertJsonPath('data.0.description', 'Amazon');
        $this->api()->getJson('/api/v1/transactions?from=2026-11-01&to=2026-11-30')->assertJsonPath('meta.total', 1);
        $this->api()->getJson('/api/v1/transactions?category_id=' . $food->id)->assertJsonPath('meta.total', 1);
        $this->api()->getJson('/api/v1/transactions?per_page=1')->assertJsonPath('meta.last_page', 2);
    }

    public function test_other_users_transactions_are_not_found(): void
    {
        $foreign = $this->makeTransaction($this->other, $this->makeAccount($this->other, 'X'));
        $mine = $this->makeTransaction($this->user, $this->account);

        $this->api()->getJson("/api/v1/transactions/{$mine->id}")->assertOk()->assertJsonPath('id', $mine->id);
        $this->api()->getJson("/api/v1/transactions/{$foreign->id}")->assertNotFound();
        $this->api()->patchJson("/api/v1/transactions/{$foreign->id}", ['notes' => 'x'])->assertNotFound();
        $this->assertNull($foreign->fresh()->notes);
    }

    public function test_dashboard_returns_cockpit_and_review_items(): void
    {
        $this->makeTransaction($this->user, $this->account, ['description' => 'Amazon', 'amount' => 50, 'transaction_date' => now()->toDateString()]);
        $this->makeTransaction($this->user, $this->account, ['type' => 'income', 'description' => 'Gehalt', 'amount' => 1000, 'transaction_date' => now()->toDateString()]);

        $response = $this->api()->getJson('/api/v1/dashboard?month=' . now()->format('Y-m'))->assertOk();

        $response->assertJsonPath('month', now()->format('Y-m'))
            ->assertJsonPath('cockpit.has_plan', false)
            ->assertJsonPath('cockpit.spent', 50)
            ->assertJsonPath('cockpit.remaining', 950)
            ->assertJsonPath('review.count', 2)
            ->assertJsonStructure(['greeting_name', 'cockpit' => ['per_day', 'days_left', 'ratio'], 'upcoming', 'review' => ['items' => [['id', 'description', 'amount', 'date']]]]);
    }

    /* ---------- Schreiben ---------- */

    public function test_create_transaction(): void
    {
        $food = $this->makeCategory($this->user);

        $this->api()->postJson('/api/v1/transactions', [
            'account_id' => $this->account->id, 'type' => 'expense', 'amount' => 12.5,
            'date' => '2026-11-20', 'description' => 'Bäcker', 'category_id' => $food->id,
        ])
            ->assertCreated()
            ->assertJsonPath('amount', -12.5)
            ->assertJsonPath('category.id', $food->id)
            ->assertJsonPath('account.id', $this->account->id);

        $this->assertDatabaseHas('transactions', ['user_id' => $this->user->id, 'description' => 'Bäcker']);
    }

    public function test_create_transaction_validates_ownership_and_input(): void
    {
        $foreignAccount = $this->makeAccount($this->other, 'X');
        $foreignCategory = $this->makeCategory($this->other);
        $incomeOnly = $this->makeCategory($this->user, 'Gehalt', 'income');
        $base = ['account_id' => $this->account->id, 'type' => 'expense', 'amount' => 5, 'date' => '2026-11-20', 'description' => 'Test'];

        $this->api()->postJson('/api/v1/transactions', ['account_id' => $foreignAccount->id] + $base)->assertStatus(422)->assertJsonValidationErrors('account_id');
        $this->api()->postJson('/api/v1/transactions', ['category_id' => $foreignCategory->id] + $base)->assertStatus(422)->assertJsonValidationErrors('category_id');
        $this->api()->postJson('/api/v1/transactions', ['category_id' => $incomeOnly->id] + $base)->assertStatus(422)->assertJsonValidationErrors('category_id');
        $this->api()->postJson('/api/v1/transactions', ['amount' => -3] + $base)->assertStatus(422)->assertJsonValidationErrors('amount');
        $this->api()->postJson('/api/v1/transactions', ['type' => 'transfer'] + $base)->assertStatus(422);

        $this->assertSame(0, Transaction::count());
    }

    public function test_update_category_notes_and_tags(): void
    {
        $food = $this->makeCategory($this->user);
        $tag = Tag::create(['user_id' => $this->user->id, 'name' => 'Urlaub']);
        $foreignTag = Tag::create(['user_id' => $this->other->id, 'name' => 'Fremd']);
        $transaction = $this->makeTransaction($this->user, $this->account);

        $this->api()->patchJson("/api/v1/transactions/{$transaction->id}", ['tag_ids' => [$foreignTag->id]])->assertStatus(422);

        $this->api()->patchJson("/api/v1/transactions/{$transaction->id}", [
            'category_id' => $food->id, 'notes' => ' Wocheneinkauf ', 'tag_ids' => [$tag->id],
        ])
            ->assertOk()
            ->assertJsonPath('category.id', $food->id)
            ->assertJsonPath('notes', 'Wocheneinkauf')
            ->assertJsonPath('tags.0.name', 'Urlaub');

        // Nicht gesendete Felder bleiben unberührt, null löscht.
        $this->api()->patchJson("/api/v1/transactions/{$transaction->id}", ['notes' => null])
            ->assertOk()->assertJsonPath('notes', null)->assertJsonPath('category.id', $food->id);
    }

    public function test_update_rejects_foreign_or_mismatching_categories(): void
    {
        $transaction = $this->makeTransaction($this->user, $this->account);

        $this->api()->patchJson("/api/v1/transactions/{$transaction->id}", ['category_id' => $this->makeCategory($this->other)->id])
            ->assertStatus(422)->assertJsonValidationErrors('category_id');
        $this->api()->patchJson("/api/v1/transactions/{$transaction->id}", ['category_id' => $this->makeCategory($this->user, 'Lohn', 'income')->id])
            ->assertStatus(422);
    }

    public function test_remember_sets_payee_default_and_categorizes_others(): void
    {
        $food = $this->makeCategory($this->user);
        $first = $this->makeTransaction($this->user, $this->account, ['description' => 'REWE 1', 'merchant' => 'REWE Markt']);
        $second = $this->makeTransaction($this->user, $this->account, ['description' => 'REWE 2', 'merchant' => 'REWE Markt']);

        $this->api()->patchJson("/api/v1/transactions/{$first->id}", ['payee_name' => 'REWE'])->assertOk()->assertJsonPath('payee.name', 'REWE');
        $this->api()->patchJson("/api/v1/transactions/{$second->id}", ['payee_name' => 'REWE']);

        $this->api()->patchJson("/api/v1/transactions/{$first->id}", ['category_id' => $food->id, 'remember' => true])->assertOk();

        $this->assertSame($food->id, $first->fresh()->payee->default_category_id);
        $this->assertSame($food->id, $second->fresh()->category_id);
    }

    /* ---------- Bankabruf ---------- */

    private function readyConnection(?User $user = null): BankConnection
    {
        $user ??= $this->user;
        $account = $user->is($this->user) ? $this->account : $this->makeAccount($user, 'Bank');

        $connection = BankConnection::create([
            'user_id' => $user->id, 'name' => 'Sparkasse', 'bank_code' => '10050000',
            'url' => 'https://fints.example.test/fints30', 'username' => 'max123',
            'tan_mode' => 923, 'tan_mode_name' => 'pushTAN 2.0',
        ]);

        BankConnectionAccount::create(['bank_connection_id' => $connection->id, 'account_id' => $account->id, 'iban' => 'DE12100500000123456789']);

        return $connection;
    }

    private function syncData(BankConnection $connection, array $rows): array
    {
        return [$connection->linkedAccounts()->first()->id => ['transactions' => $rows, 'balance' => null, 'errors' => []]];
    }

    private function row(float $amount, string $name): array
    {
        return ['date' => '2026-11-18', 'amount' => $amount, 'name' => $name, 'description' => 'Test', 'booking_text' => 'LASTSCHRIFT', 'end_to_end_id' => ''];
    }

    public function test_bank_connections_are_listed_per_user(): void
    {
        $mine = $this->readyConnection();
        $this->readyConnection($this->other);

        $response = $this->api()->getJson('/api/v1/bank-connections')->assertOk()->assertJsonPath('fints_enabled', true);

        $this->assertCount(1, $response->json('data'));
        $this->assertSame($mine->id, $response->json('data.0.id'));
        $this->assertSame('Girokonto', $response->json('data.0.accounts.0.name'));
        $this->assertStringNotContainsString('max123', $response->getContent());
    }

    public function test_sync_done_imports_directly(): void
    {
        $connection = $this->readyConnection();
        $this->bank->beginResults[] = FintsResult::done($this->syncData($connection, [$this->row(-9.99, 'Netflix')]));

        $this->api()->postJson("/api/v1/bank-connections/{$connection->id}/sync", ['pin' => 'geheim'])
            ->assertOk()
            ->assertJsonPath('status', 'done')
            ->assertJsonPath('imported', 1)
            ->assertJsonPath('skipped', 0);

        $this->assertDatabaseHas('transactions', ['user_id' => $this->user->id, 'type' => 'expense']);
        $this->assertNotNull($connection->fresh()->last_synced_at);
    }

    public function test_sync_with_decoupled_challenge_pending_then_done(): void
    {
        $connection = $this->readyConnection();
        $this->bank->beginResults[] = FintsResult::needsTan('s1', 'Bitte in der App freigeben.', 'iPhone', true);

        $response = $this->api()->postJson("/api/v1/bank-connections/{$connection->id}/sync", ['pin' => 'geheim'])
            ->assertStatus(202)
            ->assertJsonPath('status', 'challenge')
            ->assertJsonPath('challenge.decoupled', true)
            ->assertJsonPath('challenge.tan_medium', 'iPhone');
        $this->assertStringNotContainsString('geheim', $response->getContent());

        $this->bank->resumeResults[] = FintsResult::needsTan('s2', null, null, true);
        $this->api()->postJson('/api/v1/bank-connections/challenge')->assertStatus(409)->assertJsonPath('status', 'pending');

        $this->bank->resumeResults[] = FintsResult::done($this->syncData($connection, [$this->row(-5, 'Kiosk')]));
        $this->api()->postJson('/api/v1/bank-connections/challenge')->assertOk()->assertJsonPath('status', 'done')->assertJsonPath('imported', 1);

        // Vorgang ist abgeschlossen.
        $this->api()->postJson('/api/v1/bank-connections/challenge')->assertNotFound();
        $this->assertSame('geheim', $this->bank->callsTo('resume')[0][2]);
    }

    public function test_sync_with_tan_entry_and_follow_up_tan(): void
    {
        $connection = $this->readyConnection();
        $this->bank->beginResults[] = FintsResult::needsTan('s1', 'TAN eingeben', null, false);

        $this->api()->postJson("/api/v1/bank-connections/{$connection->id}/sync", ['pin' => 'geheim'])
            ->assertStatus(202)->assertJsonPath('challenge.decoupled', false);

        $this->api()->postJson('/api/v1/bank-connections/challenge')->assertStatus(422)->assertJsonValidationErrors('tan');

        $this->bank->resumeResults[] = FintsResult::needsTan('s2', 'Weitere TAN', null, false);
        $this->api()->postJson('/api/v1/bank-connections/challenge', ['tan' => '111111'])
            ->assertStatus(202)->assertJsonPath('challenge.message', 'Weitere TAN');

        $this->bank->resumeResults[] = FintsResult::done($this->syncData($connection, []));
        $this->api()->postJson('/api/v1/bank-connections/challenge', ['tan' => '222222'])->assertOk()->assertJsonPath('status', 'done');
    }

    public function test_bank_errors_and_cancel_clear_the_pending_session_without_leaking_secrets(): void
    {
        $connection = $this->readyConnection();
        $this->bank->beginResults[] = FintsResult::needsTan('s1', 'TAN eingeben', null, false);
        $this->api()->postJson("/api/v1/bank-connections/{$connection->id}/sync", ['pin' => 'geheim'])->assertStatus(202);

        $this->api()->deleteJson('/api/v1/bank-connections/challenge')->assertOk();
        $this->api()->postJson('/api/v1/bank-connections/challenge', ['tan' => '1'])->assertNotFound();

        $this->bank->beginResults[] = new \App\Services\Fints\FintsException('Die Bank hat den Zugang abgelehnt.');
        $response = $this->api()->postJson("/api/v1/bank-connections/{$connection->id}/sync", ['pin' => 'geheim'])
            ->assertStatus(422)->assertJsonPath('message', 'Die Bank hat den Zugang abgelehnt.');
        $this->assertStringNotContainsString('geheim', $response->getContent());

        $this->bank->beginResults[] = new \RuntimeException('Verbindung mit PIN geheim fehlgeschlagen');
        $response = $this->api()->postJson("/api/v1/bank-connections/{$connection->id}/sync", ['pin' => 'geheim'])->assertStatus(502);
        $this->assertStringNotContainsString('geheim', $response->getContent());
    }

    public function test_sync_of_foreign_connection_and_foreign_pending_session_are_not_reachable(): void
    {
        $foreign = $this->readyConnection($this->other);

        $this->api()->postJson("/api/v1/bank-connections/{$foreign->id}/sync", ['pin' => 'x'])->assertNotFound();
        $this->assertSame([], $this->bank->calls);

        // Ein laufender Vorgang des anderen Benutzers ist für mich nicht sichtbar.
        $this->bank->beginResults[] = FintsResult::needsTan('s1', 'TAN', null, false);
        [, $otherToken] = ApiToken::issue($this->other, 'Anderes iPad');
        $this->api($otherToken)->postJson("/api/v1/bank-connections/{$foreign->id}/sync", ['pin' => 'x'])->assertStatus(202);

        $this->api()->postJson('/api/v1/bank-connections/challenge', ['tan' => '1'])->assertNotFound();
    }

    public function test_sync_requires_pin_and_enabled_fints(): void
    {
        $connection = $this->readyConnection();

        $this->api()->postJson("/api/v1/bank-connections/{$connection->id}/sync", [])->assertStatus(422)->assertJsonValidationErrors('pin');

        config(['services.fints.product_id' => null]);
        $this->api()->postJson("/api/v1/bank-connections/{$connection->id}/sync", ['pin' => 'x'])->assertStatus(422);
    }
}
