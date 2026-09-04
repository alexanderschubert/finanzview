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
}
