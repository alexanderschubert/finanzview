<?php

namespace Tests\Feature;

use App\Models\CreditCard;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanCardFormTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['is_active' => true]);
    }

    public function test_credit_card_form_shows_preview_and_stores(): void
    {
        $this->actingAs($this->user)
            ->get(route('credit-cards.create'))
            ->assertOk()
            ->assertSee('data-card-preview', false)
            ->assertSee('Neue Kreditkarte');

        $this->actingAs($this->user)
            ->post(route('credit-cards.store'), [
                'name' => 'Reisekarte',
                'issuer' => 'Testbank',
                'last_four' => '4242',
                'credit_limit' => '3000',
                'current_balance' => '120.50',
                'billing_day' => '15',
                'payment_due_day' => '5',
                'color' => '#1f5fa8',
                'is_active' => '1',
            ])
            ->assertSessionHasNoErrors();

        $card = CreditCard::where('name', 'Reisekarte')->firstOrFail();

        $this->assertSame('#1f5fa8', $card->color);

        $this->actingAs($this->user)
            ->get(route('credit-cards.edit', $card))
            ->assertOk()
            ->assertSee('•••• 4242')
            ->assertSee('value="120.50"', false)
            ->assertSee('Archivieren');
    }

    public function test_loan_form_renders_calculator_and_stores(): void
    {
        $this->actingAs($this->user)
            ->get(route('loans.create'))
            ->assertOk()
            ->assertSee('data-calc-result', false)
            ->assertSee('Ratenkredit');

        $this->actingAs($this->user)
            ->post(route('loans.store'), [
                'name' => 'Autokredit',
                'creditor_name' => 'Hausbank',
                'type' => 'loan',
                'principal_amount' => '12000',
                'interest_rate' => '4.9',
                'installment_amount' => '350',
                'paid_amount' => '0',
                'paid_installments' => '0',
                'start_date' => now()->startOfMonth()->toDateString(),
                'creditor_color' => '',
                'is_active' => '1',
            ])
            ->assertSessionHasNoErrors();

        $loan = Loan::where('name', 'Autokredit')->firstOrFail();

        $this->actingAs($this->user)
            ->get(route('loans.edit', $loan))
            ->assertOk()
            ->assertSee('value="Hausbank"', false)
            ->assertSee('offenen Raten neu berechnet');
    }
}
