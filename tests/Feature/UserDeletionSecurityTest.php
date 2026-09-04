<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserDeletionSecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ein Administrator darf einen anderen normalen Benutzer löschen.
     */
    public function test_admin_can_delete_another_user(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'is_admin' => false,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->delete(route('admin.users.destroy', $user));

        $response
            ->assertSessionHas('success', 'Benutzer wurde gelöscht.');

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    /**
     * Ein Nicht-Administrator darf keinen Benutzer löschen.
     */
    public function test_non_admin_cannot_delete_user(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'is_active' => true,
        ]);

        $target = User::factory()->create([
            'is_admin' => false,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(route('admin.users.destroy', $target));

        $response->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
        ]);
    }

    /**
     * Ein Administrator darf seinen eigenen Account nicht löschen.
     */
    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin));

        $response->assertSessionHas(
            'error',
            'Du kannst deinen eigenen Account nicht löschen.'
        );

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
        ]);
    }

    /**
     * Der letzte Administrator darf nicht gelöscht werden.
     */
    public function test_last_admin_cannot_be_deleted(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $target = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        /*
         * Es gibt zwei Administratoren.
         * Der erste Admin darf den zweiten löschen.
         */
        $response = $this
            ->actingAs($admin)
            ->delete(route('admin.users.destroy', $target));

        $response->assertSessionHas('success', 'Benutzer wurde gelöscht.');

        $this->assertDatabaseMissing('users', [
            'id' => $target->id,
        ]);

        /*
         * Jetzt ist nur noch ein Administrator vorhanden.
         * Dieser darf nicht gelöscht werden.
         */
        $response = $this
            ->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin));

        $response->assertSessionHas(
            'error',
            'Du kannst deinen eigenen Account nicht löschen.'
        );

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
        ]);
    }

    /**
     * Beim Löschen eines Benutzers werden seine abhängigen Daten
     * ebenfalls entfernt.
     */
    public function test_deleting_user_removes_all_dependent_data(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'is_admin' => false,
            'is_active' => true,
        ]);

        /*
         * Account
         */
        $accountId = DB::table('accounts')->insertGetId([
            'user_id' => $user->id,
            'name' => 'Testkonto',
            'institution' => 'Testbank',
            'type' => 'checking',
            'currency' => 'EUR',
            'opening_balance' => 1000,
            'credit_limit' => null,
            'iban' => null,
            'account_number' => null,
            'is_active' => true,
            'include_in_total' => true,
            'color' => null,
            'icon' => null,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        /*
         * Kategorie
         */
        $categoryId = DB::table('categories')->insertGetId([
            'user_id' => $user->id,
            'parent_id' => null,
            'name' => 'Testkategorie',
            'type' => 'expense',
            'icon' => null,
            'color' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        /*
         * Transaktion
         */
        $transactionId = DB::table('transactions')->insertGetId([
            'user_id' => $user->id,
            'account_id' => $accountId,
            'category_id' => $categoryId,
            'transaction_date' => now()->toDateString(),
            'description' => 'Testbuchung',
            'notes' => null,
            'amount' => 25.50,
            'type' => 'expense',
            'currency' => 'EUR',
            'merchant' => 'Test',
            'reference' => null,
            'is_pending' => false,
            'is_recurring' => false,
            'external_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        /*
         * Budget
         */
        $budgetId = DB::table('budgets')->insertGetId([
            'user_id' => $user->id,
            'name' => 'Testbudget',
            'amount' => 500,
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'period' => 'monthly',
            'is_active' => true,
            'color' => null,
            'icon' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        /*
         * Budget-Kategorie-Verknüpfung
         */
        DB::table('budget_category')->insert([
            'budget_id' => $budgetId,
            'category_id' => $categoryId,
        ]);

        /*
         * Kredit
         */
        $loanId = DB::table('loans')->insertGetId([
            'user_id' => $user->id,
            'account_id' => $accountId,
            'name' => 'Testkredit',
            'principal_amount' => 10000,
            'paid_amount' => 1000,
            'interest_rate' => 4.5,
            'installment_amount' => 250,
            'total_installments' => 48,
            'paid_installments' => 4,
            'start_date' => now()->subMonths(4)->toDateString(),
            'end_date' => now()->addYears(3)->toDateString(),
            'type' => 'loan',
            'is_active' => true,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        /*
         * Kreditkarte
         */
        $creditCardId = DB::table('credit_cards')->insertGetId([
            'user_id' => $user->id,
            'account_id' => $accountId,
            'name' => 'Test Kreditkarte',
            'issuer' => 'Testbank',
            'last_four' => '1234',
            'credit_limit' => 5000,
            'current_balance' => 250,
            'billing_day' => 1,
            'payment_due_day' => 15,
            'color' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        /*
         * Wiederkehrende Buchung
         */
        $recurringId = DB::table('recurring_transactions')->insertGetId([
            'user_id' => $user->id,
            'account_id' => $accountId,
            'category_id' => $categoryId,
            'description' => 'Test Abo',
            'amount' => 9.99,
            'type' => 'expense',
            'frequency' => 'monthly',
            'next_date' => now()->addMonth()->toDateString(),
            'end_date' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        /*
         * Einstellung
         */
        $settingId = DB::table('settings')->insertGetId([
            'user_id' => $user->id,
            'currency' => 'EUR',
            'decimal_places' => 2,
            'date_format' => 'd.m.Y',
            'first_day_of_week' => 1,
            'default_account_id' => $accountId,
            'default_category_id' => $categoryId,
            'month_start_day' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        /*
         * Sicherheitscheck vor dem Löschen:
         * Alle Testdaten müssen vorhanden sein.
         */
        $this->assertDatabaseHas('accounts', ['id' => $accountId]);
        $this->assertDatabaseHas('categories', ['id' => $categoryId]);
        $this->assertDatabaseHas('transactions', ['id' => $transactionId]);
        $this->assertDatabaseHas('budgets', ['id' => $budgetId]);
        $this->assertDatabaseHas('budget_category', [
            'budget_id' => $budgetId,
            'category_id' => $categoryId,
        ]);
        $this->assertDatabaseHas('loans', ['id' => $loanId]);
        $this->assertDatabaseHas('credit_cards', ['id' => $creditCardId]);
        $this->assertDatabaseHas('recurring_transactions', [
            'id' => $recurringId,
        ]);
        $this->assertDatabaseHas('settings', ['id' => $settingId]);

        /*
         * Benutzer über den echten Admin-Endpunkt löschen.
         */
        $response = $this
            ->actingAs($admin)
            ->delete(route('admin.users.destroy', $user));

        $response->assertSessionHas(
            'success',
            'Benutzer wurde gelöscht.'
        );

        /*
         * Benutzer muss verschwunden sein.
         */
        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);

        /*
         * Account, Kategorie, Budget, Kredit, Kreditkarte,
         * wiederkehrende Buchung und Einstellung müssen
         * nicht mehr als aktive Datensätze vorhanden sein.
         */
        $this->assertDatabaseMissing('accounts', [
            'id' => $accountId,
        ]);

        $this->assertDatabaseMissing('categories', [
            'id' => $categoryId,
        ]);

        $this->assertDatabaseMissing('budgets', [
            'id' => $budgetId,
        ]);

        $this->assertDatabaseMissing('loans', [
            'id' => $loanId,
        ]);

        $this->assertDatabaseMissing('credit_cards', [
            'id' => $creditCardId,
        ]);

        $this->assertDatabaseMissing('recurring_transactions', [
            'id' => $recurringId,
        ]);

        $this->assertDatabaseMissing('settings', [
            'id' => $settingId,
        ]);

        /*
         * Die Budget-Kategorie-Verknüpfung muss ebenfalls verschwunden sein.
         */
        $this->assertDatabaseMissing('budget_category', [
            'budget_id' => $budgetId,
            'category_id' => $categoryId,
        ]);

        /*
         * Transaktionen werden zunächst per SoftDeletes gelöscht.
         * Anschließend wird der Benutzer gelöscht. Aufgrund der
         * ON DELETE CASCADE-FK darf die Transaktion danach
         * vollständig aus der Datenbank verschwunden sein.
         */
        $this->assertDatabaseMissing('transactions', [
            'id' => $transactionId,
        ]);
    }
}
