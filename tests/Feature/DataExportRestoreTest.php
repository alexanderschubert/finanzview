<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DataExportRestoreTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::factory()->create([
            'is_active' => true,
        ]);
    }

    private function createAccount(User $user, array $attributes = []): Account
    {
        return Account::create(array_merge([
            'user_id' => $user->id,
            'name' => 'Girokonto',
            'institution' => 'Testbank',
            'type' => 'checking',
            'currency' => 'EUR',
            'opening_balance' => 1000,
            'include_in_total' => true,
            'is_active' => true,
        ], $attributes));
    }

    private function createTransaction(
        User $user,
        Account $account,
        array $attributes = []
    ): Transaction {
        return Transaction::create(array_merge([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'type' => 'expense',
            'amount' => 3.50,
            'transaction_date' => '2026-09-01',
            'description' => 'Kaffee',
        ], $attributes));
    }

    /**
     * Exportiert die Daten des Benutzers über den echten Endpoint.
     */
    private function exportBackup(User $user): string
    {
        $response = $this
            ->actingAs($user)
            ->post(route('settings.data-export.json'));

        $response->assertOk();

        return $response->getContent();
    }

    /**
     * Lädt ein Backup hoch und führt den Restore durch.
     */
    private function restoreBackupFor(User $user, string $content)
    {
        $file = UploadedFile::fake()->createWithContent(
            'backup.json',
            $content
        );

        $this
            ->actingAs($user)
            ->post(
                route('settings.data-export.import'),
                ['backup' => $file]
            )
            ->assertOk();

        $token = session('finanzview_import_token');

        $this->assertNotNull($token);

        return $this
            ->withSession([
                'finanzview_import_token' => $token,
            ])
            ->actingAs($user)
            ->post(
                route('settings.data-export.import.restore'),
                [
                    'token' => $token,
                    'confirm' => '1',
                ]
            );
    }

    public function test_restore_works_after_deleting_account_with_transactions(): void
    {
        $owner = $this->createUser();

        $active = $this->createAccount($owner, [
            'name' => 'Aktives Konto',
        ]);

        $deleted = $this->createAccount($owner, [
            'name' => 'Gelöschtes Konto',
        ]);

        $this->createTransaction($owner, $active, [
            'description' => 'Aktive Buchung',
        ]);

        $this->createTransaction($owner, $deleted, [
            'description' => 'Buchung auf gelöschtem Konto',
        ]);

        $this
            ->actingAs($owner)
            ->delete(route('accounts.destroy', $deleted))
            ->assertRedirect();

        $this->assertSoftDeleted('accounts', [
            'id' => $deleted->id,
        ]);

        $content = $this->exportBackup($owner);

        $target = $this->createUser();

        $this->restoreBackupFor($target, $content)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('settings.data-export'));

        $this->assertSame(
            2,
            DB::table('transactions')
                ->where('user_id', $target->id)
                ->count()
        );

        $restoredDeleted = DB::table('accounts')
            ->where('user_id', $target->id)
            ->where('name', 'Gelöschtes Konto')
            ->first();

        $this->assertNotNull($restoredDeleted);
        $this->assertNotNull($restoredDeleted->deleted_at);

        $restoredActive = DB::table('accounts')
            ->where('user_id', $target->id)
            ->where('name', 'Aktives Konto')
            ->first();

        $this->assertNotNull($restoredActive);
        $this->assertNull($restoredActive->deleted_at);
    }

    public function test_transfer_round_trip_preserves_target_account_and_balances(): void
    {
        $owner = $this->createUser();

        $from = $this->createAccount($owner, [
            'name' => 'Girokonto',
            'opening_balance' => 1000,
        ]);

        $to = $this->createAccount($owner, [
            'name' => 'Sparkonto',
            'opening_balance' => 0,
        ]);

        $this->createTransaction($owner, $from, [
            'type' => 'transfer',
            'amount' => 250,
            'description' => 'Umbuchung Sparen',
            'transfer_account_id' => $to->id,
        ]);

        $content = $this->exportBackup($owner);

        $target = $this->createUser();

        $this->restoreBackupFor($target, $content)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('settings.data-export'));

        $restoredFrom = Account::query()
            ->where('user_id', $target->id)
            ->where('name', 'Girokonto')
            ->firstOrFail();

        $restoredTo = Account::query()
            ->where('user_id', $target->id)
            ->where('name', 'Sparkonto')
            ->firstOrFail();

        $this->assertDatabaseHas('transactions', [
            'user_id' => $target->id,
            'account_id' => $restoredFrom->id,
            'transfer_account_id' => $restoredTo->id,
            'type' => 'transfer',
        ]);

        $this->assertEqualsWithDelta(
            750.0,
            $restoredFrom->current_balance,
            0.001
        );

        $this->assertEqualsWithDelta(
            250.0,
            $restoredTo->current_balance,
            0.001
        );
    }

    public function test_identical_transactions_are_all_restored_without_duplicates_on_second_restore(): void
    {
        $owner = $this->createUser();

        $account = $this->createAccount($owner);

        $this->createTransaction($owner, $account);
        $this->createTransaction($owner, $account);

        $content = $this->exportBackup($owner);

        $target = $this->createUser();

        $this->restoreBackupFor($target, $content)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('settings.data-export'));

        $this->assertSame(
            2,
            DB::table('transactions')
                ->where('user_id', $target->id)
                ->where('description', 'Kaffee')
                ->count()
        );

        $this->restoreBackupFor($target, $content)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('settings.data-export'));

        $this->assertSame(
            2,
            DB::table('transactions')
                ->where('user_id', $target->id)
                ->where('description', 'Kaffee')
                ->count()
        );

        $this->assertSame(
            1,
            DB::table('accounts')
                ->where('user_id', $target->id)
                ->count()
        );
    }

    public function test_older_backup_without_new_fields_still_restores(): void
    {
        $owner = $this->createUser();

        $account = $this->createAccount($owner);

        $this->createTransaction($owner, $account);

        $payload = json_decode(
            $this->exportBackup($owner),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        foreach ($payload['accounts'] as &$item) {
            unset($item['deleted_at']);
        }
        unset($item);

        foreach ($payload['transactions'] as &$item) {
            unset($item['transfer_account_id'], $item['credit_card_id']);
        }
        unset($item);

        $target = $this->createUser();

        $this->restoreBackupFor(
            $target,
            json_encode($payload, JSON_THROW_ON_ERROR)
        )
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('settings.data-export'));

        $this->assertSame(
            1,
            DB::table('transactions')
                ->where('user_id', $target->id)
                ->count()
        );
    }
}
