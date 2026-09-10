<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\FinancialProvider;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'is_active' => true,
        ], $attributes));
    }

    private function createAccount(
        User $user,
        array $attributes = []
    ): Account {
        return Account::create(array_merge([
            'user_id' => $user->id,
            'name' => 'Testkonto',
            'institution' => 'Testbank',
            'type' => 'checking',
            'currency' => 'EUR',
            'opening_balance' => 0,
            'is_active' => true,
            'include_in_total' => true,
        ], $attributes));
    }

    private function createProvider(
        array $attributes = []
    ): FinancialProvider {
        return FinancialProvider::create(array_merge([
            'name' => 'Test Bank',
            'slug' => 'test-bank-' . uniqid(),
            'type' => 'bank',
            'is_active' => true,
        ], $attributes));
    }

    private function createLoan(
        User $user,
        array $attributes = []
    ): Loan {
        return Loan::create(array_merge([
            'user_id' => $user->id,
            'account_id' => null,
            'name' => 'Testkredit',
            'creditor_name' => 'Testbank',
            'provider_id' => null,
            'principal_amount' => 10000.00,
            'paid_amount' => 2000.00,
            'interest_rate' => 5.000,
            'installment_amount' => 250.00,
            'total_installments' => null,
            'paid_installments' => 0,
            'start_date' => '2026-01-01',
            'end_date' => null,
            'type' => 'loan',
            'is_active' => true,
            'notes' => null,
        ], $attributes));
    }

    private function validLoanData(
        array $overrides = []
    ): array {
        return array_merge([
            'name' => 'Neuer Kredit',
            'creditor_name' => 'Testbank',
            'principal_amount' => '10000.00',
            'paid_amount' => '0.00',
            'interest_rate' => '5.000',
            'installment_amount' => '250.00',
            'total_installments' => null,
            'paid_installments' => '0',
            'start_date' => '2026-09-01',
            'end_date' => null,
            'type' => 'loan',
            'is_active' => '1',
            'notes' => null,
        ], $overrides);
    }

    private function extraPaymentData(
        array $overrides = []
    ): array {
        return array_merge([
            'amount' => '500.00',
            'paid_date' => '2026-09-04',
            'notes' => 'Test Sondertilgung',
        ], $overrides);
    }

    public function test_user_can_create_own_loan(): void
    {
        $user = $this->createUser();

        $response = $this
            ->actingAs($user)
            ->post(
                route('loans.store'),
                $this->validLoanData()
            );

        $response->assertRedirect();

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('loans', [
            'user_id' => $user->id,
            'name' => 'Neuer Kredit',
            'principal_amount' => 10000.00,
        ]);
    }

    public function test_creating_loan_generates_amortization_payment_plan(): void
    {
        $user = $this->createUser();

        $response = $this
            ->actingAs($user)
            ->post(
                route('loans.store'),
                $this->validLoanData([
                    'principal_amount' => '20000.00',
                    'paid_amount' => '0.00',
                    'interest_rate' => '5.000',
                    'installment_amount' => '460.59',
                    'total_installments' => '48',
                    'paid_installments' => '0',
                    'start_date' => '2026-09-01',
                ])
            );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $loan = Loan::where('user_id', $user->id)
            ->where('name', 'Neuer Kredit')
            ->first();

        $this->assertNotNull($loan);

        $this->assertDatabaseCount('loan_payments', 48);

        $this->assertDatabaseHas('loan_payments', [
            'loan_id' => $loan->id,
            'installment_number' => 1,
            'payment_type' => 'regular',
            'status' => 'planned',
            'amount' => 460.59,
            'interest_amount' => 83.33,
            'principal_amount' => 377.26,
            'remaining_amount' => 19622.74,
        ]);

        $this->assertDatabaseHas('loan_payments', [
            'loan_id' => $loan->id,
            'installment_number' => 48,
            'payment_type' => 'regular',
            'status' => 'planned',
        ]);
    }

    public function test_generated_amortization_plan_contains_decreasing_interest(): void
    {
        $user = $this->createUser();

        $this
            ->actingAs($user)
            ->post(
                route('loans.store'),
                $this->validLoanData([
                    'principal_amount' => '20000.00',
                    'paid_amount' => '0.00',
                    'interest_rate' => '5.000',
                    'installment_amount' => '460.59',
                    'total_installments' => '48',
                    'paid_installments' => '0',
                    'start_date' => '2026-09-01',
                ])
            )
            ->assertRedirect();

        $loan = Loan::where('user_id', $user->id)
            ->where('name', 'Neuer Kredit')
            ->firstOrFail();

        $firstPayment = LoanPayment::where('loan_id', $loan->id)
            ->where('installment_number', 1)
            ->firstOrFail();

        $secondPayment = LoanPayment::where('loan_id', $loan->id)
            ->where('installment_number', 2)
            ->firstOrFail();

        $lastPayment = LoanPayment::where('loan_id', $loan->id)
            ->orderByDesc('installment_number')
            ->firstOrFail();

        $this->assertEquals(83.33, (float) $firstPayment->interest_amount);
        $this->assertEquals(81.76, (float) $secondPayment->interest_amount);

        $this->assertGreaterThan(
            (float) $secondPayment->interest_amount,
            (float) $firstPayment->interest_amount
        );

        $this->assertEquals(0.00, (float) $lastPayment->remaining_amount);
    }

    public function test_user_cannot_create_loan_using_another_users_account(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();

        $foreignAccount = $this->createAccount($otherUser);

        $response = $this
            ->actingAs($user)
            ->post(
                route('loans.store'),
                $this->validLoanData([
                    'account_id' => $foreignAccount->id,
                ])
            );

        $response->assertNotFound();

        $this->assertDatabaseMissing('loans', [
            'user_id' => $user->id,
            'name' => 'Neuer Kredit',
        ]);
    }

    public function test_user_can_view_own_loan(): void
    {
        $user = $this->createUser();
        $loan = $this->createLoan($user);

        $response = $this
            ->actingAs($user)
            ->get(route('loans.show', $loan));

        $response->assertOk();
        $response->assertViewIs('loans.show');
        $response->assertViewHas(
            'loan',
            fn ($viewLoan) => $viewLoan->id === $loan->id
        );
    }

    public function test_user_cannot_view_another_users_loan(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();

        $loan = $this->createLoan($otherUser);

        $response = $this
            ->actingAs($user)
            ->get(route('loans.show', $loan));

        $response->assertForbidden();
    }

    public function test_user_can_edit_own_loan(): void
    {
        $user = $this->createUser();
        $loan = $this->createLoan($user);

        $response = $this
            ->actingAs($user)
            ->put(
                route('loans.update', $loan),
                $this->validLoanData([
                    'name' => 'Geänderter Kredit',
                    'principal_amount' => '12000.00',
                ])
            );

        $response->assertRedirect(
            route('loans.show', $loan)
        );

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'user_id' => $user->id,
            'name' => 'Geänderter Kredit',
            'principal_amount' => 12000.00,
        ]);
    }

    public function test_user_cannot_edit_another_users_loan(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();

        $loan = $this->createLoan($otherUser);

        $response = $this
            ->actingAs($user)
            ->put(
                route('loans.update', $loan),
                $this->validLoanData([
                    'name' => 'Manipulierter Kredit',
                    'principal_amount' => '99999.00',
                ])
            );

        $response->assertForbidden();

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'user_id' => $otherUser->id,
            'name' => 'Testkredit',
            'principal_amount' => 10000.00,
        ]);
    }

    public function test_user_cannot_assign_another_users_account_when_updating_loan(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();

        $ownAccount = $this->createAccount($user, [
            'name' => 'Eigenes Konto',
        ]);

        $foreignAccount = $this->createAccount($otherUser, [
            'name' => 'Fremdes Konto',
        ]);

        $loan = $this->createLoan($user, [
            'account_id' => $ownAccount->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(
                route('loans.update', $loan),
                $this->validLoanData([
                    'account_id' => $foreignAccount->id,
                ])
            );

        $response->assertNotFound();

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'user_id' => $user->id,
            'account_id' => $ownAccount->id,
        ]);
    }

    public function test_user_can_assign_global_provider_to_own_loan(): void
    {
        $user = $this->createUser();
        $provider = $this->createProvider();

        $response = $this
            ->actingAs($user)
            ->post(
                route('loans.store'),
                $this->validLoanData([
                    'provider_id' => $provider->id,
                ])
            );

        $response->assertRedirect();

        $loan = Loan::where('user_id', $user->id)
            ->where('name', 'Neuer Kredit')
            ->first();

        $this->assertNotNull($loan);

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'provider_id' => $provider->id,
        ]);
    }

    public function test_user_can_delete_own_loan(): void
    {
        $user = $this->createUser();
        $loan = $this->createLoan($user);

        $response = $this
            ->actingAs($user)
            ->delete(route('loans.destroy', $loan));

        $response->assertRedirect(
            route('loans.index')
        );

        $response->assertSessionHas('success');

        $this->assertSoftDeleted('loans', [
            'id' => $loan->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_user_cannot_delete_another_users_loan(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();

        $loan = $this->createLoan($otherUser);

        $response = $this
            ->actingAs($user)
            ->delete(route('loans.destroy', $loan));

        $response->assertForbidden();

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'user_id' => $otherUser->id,
            'name' => 'Testkredit',
        ]);
    }

    public function test_user_can_create_extra_payment_for_own_loan(): void
    {
        $user = $this->createUser();

        $loan = $this->createLoan($user, [
            'principal_amount' => 10000.00,
            'paid_amount' => 2000.00,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route('loans.extra-payment', $loan),
                $this->extraPaymentData([
                    'amount' => '500.00',
                ])
            );

        $response->assertRedirect(
            route('loans.show', $loan)
        );

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('loan_payments', [
            'loan_id' => $loan->id,
            'amount' => 500.00,
            'payment_type' => 'extra',
            'status' => 'paid',
        ]);

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'paid_amount' => 2500.00,
        ]);
    }

    public function test_user_cannot_create_extra_payment_for_another_users_loan(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();

        $loan = $this->createLoan($otherUser);

        $response = $this
            ->actingAs($user)
            ->post(
                route('loans.extra-payment', $loan),
                $this->extraPaymentData()
            );

        $response->assertForbidden();

        $this->assertDatabaseMissing('loan_payments', [
            'loan_id' => $loan->id,
            'payment_type' => 'extra',
        ]);

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'user_id' => $otherUser->id,
            'paid_amount' => 2000.00,
        ]);
    }

    public function test_extra_payment_cannot_exceed_remaining_loan_amount(): void
    {
        $user = $this->createUser();

        $loan = $this->createLoan($user, [
            'principal_amount' => 10000.00,
            'paid_amount' => 9000.00,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route('loans.extra-payment', $loan),
                $this->extraPaymentData([
                    'amount' => '1001.00',
                ])
            );

        $response->assertSessionHasErrors('amount');

        $this->assertDatabaseMissing('loan_payments', [
            'loan_id' => $loan->id,
            'payment_type' => 'extra',
        ]);

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'paid_amount' => 9000.00,
        ]);
    }

    public function test_complete_extra_payment_cancels_future_planned_payments(): void
    {
        $user = $this->createUser();

        $loan = $this->createLoan($user, [
            'principal_amount' => 10000.00,
            'paid_amount' => 9000.00,
            'is_active' => true,
        ]);

        /*
         * Bereits bezahlte Rate.
         */
        LoanPayment::create([
            'loan_id' => $loan->id,
            'transaction_id' => null,
            'installment_number' => 1,
            'due_date' => '2026-08-01',
            'amount' => 250.00,
            'payment_type' => 'regular',
            'paid_date' => '2026-08-01',
            'status' => 'paid',
        ]);

        /*
         * Zukünftige geplante Raten.
         */
        LoanPayment::create([
            'loan_id' => $loan->id,
            'transaction_id' => null,
            'installment_number' => 2,
            'due_date' => '2026-09-01',
            'amount' => 250.00,
            'payment_type' => 'regular',
            'paid_date' => null,
            'status' => 'planned',
        ]);

        LoanPayment::create([
            'loan_id' => $loan->id,
            'transaction_id' => null,
            'installment_number' => 3,
            'due_date' => '2026-10-01',
            'amount' => 250.00,
            'payment_type' => 'regular',
            'paid_date' => null,
            'status' => 'planned',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route('loans.extra-payment', $loan),
                $this->extraPaymentData([
                    'amount' => '1000.00',
                    'notes' => 'Vollständige Ablösung',
                ])
            );

        $response->assertRedirect(
            route('loans.show', $loan)
        );

        $response->assertSessionHas('success');

        /*
         * Kredit ist vollständig getilgt.
         */
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'paid_amount' => 10000.00,
            'is_active' => false,
        ]);

        /*
         * Sondertilgung wurde als bezahlt gespeichert.
         */
        $this->assertDatabaseHas('loan_payments', [
            'loan_id' => $loan->id,
            'payment_type' => 'extra',
            'amount' => 1000.00,
            'status' => 'paid',
            'notes' => 'Vollständige Ablösung',
        ]);

        /*
         * Bereits bezahlte Rate bleibt bezahlt.
         */
        $this->assertDatabaseHas('loan_payments', [
            'loan_id' => $loan->id,
            'installment_number' => 1,
            'status' => 'paid',
        ]);

        /*
         * Alle noch geplanten Raten werden storniert.
         */
        $this->assertDatabaseHas('loan_payments', [
            'loan_id' => $loan->id,
            'installment_number' => 2,
            'status' => 'cancelled',
        ]);

        $this->assertDatabaseHas('loan_payments', [
            'loan_id' => $loan->id,
            'installment_number' => 3,
            'status' => 'cancelled',
        ]);
    }

    public function test_exact_full_extra_payment_deactivates_loan(): void
    {
        $user = $this->createUser();

        $loan = $this->createLoan($user, [
            'principal_amount' => 10000.00,
            'paid_amount' => 9000.00,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route('loans.extra-payment', $loan),
                $this->extraPaymentData([
                    'amount' => '1000.00',
                ])
            );

        $response->assertRedirect(
            route('loans.show', $loan)
        );

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'paid_amount' => 10000.00,
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('loan_payments', [
            'loan_id' => $loan->id,
            'amount' => 1000.00,
            'payment_type' => 'extra',
            'status' => 'paid',
        ]);
    }

    public function test_partial_extra_payment_keeps_future_planned_payments(): void
    {
        $user = $this->createUser();

        $loan = $this->createLoan($user, [
            'principal_amount' => 10000.00,
            'paid_amount' => 9000.00,
            'is_active' => true,
        ]);

        /*
         * Zukünftige geplante Raten.
         */
        LoanPayment::create([
            'loan_id' => $loan->id,
            'transaction_id' => null,
            'installment_number' => 1,
            'due_date' => '2026-10-01',
            'amount' => 250.00,
            'payment_type' => 'regular',
            'paid_date' => null,
            'status' => 'planned',
        ]);

        LoanPayment::create([
            'loan_id' => $loan->id,
            'transaction_id' => null,
            'installment_number' => 2,
            'due_date' => '2026-11-01',
            'amount' => 250.00,
            'payment_type' => 'regular',
            'paid_date' => null,
            'status' => 'planned',
        ]);

        /*
         * Nur 500 € von 1.000 € Restschuld werden getilgt.
         */
        $response = $this
            ->actingAs($user)
            ->post(
                route('loans.extra-payment', $loan),
                $this->extraPaymentData([
                    'amount' => '500.00',
                ])
            );

        $response->assertRedirect(
            route('loans.show', $loan)
        );

        /*
         * Kredit bleibt aktiv.
         */
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'paid_amount' => 9500.00,
            'is_active' => true,
        ]);

        /*
         * Die zukünftigen regulären Raten wurden nach
         * der Sondertilgung neu berechnet.
         *
         * Die alte geplante Rate 1 und 2 werden dabei
         * nicht unverändert übernommen.
         */
        $plannedPayments = LoanPayment::query()
            ->where('loan_id', $loan->id)
            ->where('payment_type', 'regular')
            ->where('status', 'planned')
            ->orderBy('installment_number')
            ->get();

        $this->assertNotEmpty($plannedPayments);

        /*
         * Die neue Monatsrate bleibt unverändert bei 250 €.
         */
        $this->assertEquals(
            250.00,
            (float) $plannedPayments->first()->amount
        );

        /*
         * Die neue erste Rate enthält bereits eine
         * berechnete Zins- und Tilgungskomponente.
         */
        $this->assertGreaterThan(
            0,
            (float) $plannedPayments->first()->principal_amount
        );

        $this->assertGreaterThanOrEqual(
            0,
            (float) $plannedPayments->first()->interest_amount
        );

        /*
         * Sondertilgung wurde korrekt gespeichert.
         */
        $this->assertDatabaseHas('loan_payments', [
            'loan_id' => $loan->id,
            'payment_type' => 'extra',
            'amount' => 500.00,
            'status' => 'paid',
        ]);
    }

    public function test_partial_extra_payment_keeps_loan_active(): void
    {
        $user = $this->createUser();

        $loan = $this->createLoan($user, [
            'principal_amount' => 10000.00,
            'paid_amount' => 9000.00,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route('loans.extra-payment', $loan),
                $this->extraPaymentData([
                    'amount' => '500.00',
                ])
            );

        $response->assertRedirect(
            route('loans.show', $loan)
        );

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'paid_amount' => 9500.00,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('loan_payments', [
            'loan_id' => $loan->id,
            'amount' => 500.00,
            'payment_type' => 'extra',
            'status' => 'paid',
        ]);
    }

    public function test_extra_payment_above_remaining_amount_is_rolled_back(): void
    {
        $user = $this->createUser();

        $loan = $this->createLoan($user, [
            'principal_amount' => 10000.00,
            'paid_amount' => 9000.00,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route('loans.extra-payment', $loan),
                $this->extraPaymentData([
                    'amount' => '1001.00',
                ])
            );

        $response->assertRedirect();
        $response->assertSessionHasErrors('amount');

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'paid_amount' => 9000.00,
            'is_active' => true,
        ]);

        $this->assertDatabaseMissing('loan_payments', [
            'loan_id' => $loan->id,
            'amount' => 1001.00,
            'payment_type' => 'extra',
        ]);
    }

}
