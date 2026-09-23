<?php

namespace Tests\Feature;

use App\Models\CreditCard;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanningPagesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['is_active' => true]);
    }

    public function test_loans_index_shows_remaining_debt_and_loans(): void
    {
        Loan::create([
            'user_id' => $this->user->id,
            'name' => 'Autokredit',
            'creditor_name' => 'Hausbank',
            'principal_amount' => 10000,
            'paid_amount' => 2000,
            'interest_rate' => 5,
            'installment_amount' => 250,
            'paid_installments' => 0,
            'start_date' => '2026-01-01',
            'type' => 'loan',
            'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->get(route('loans.index'))
            ->assertOk()
            ->assertSee('Offene Restschuld')
            ->assertSee('Autokredit')
            ->assertSee('Hausbank')
            ->assertSee('250,00 € / Monat');
    }

    public function test_credit_cards_index_shows_wallet_cards(): void
    {
        CreditCard::create([
            'user_id' => $this->user->id,
            'name' => 'Reisekarte',
            'issuer' => 'Testbank',
            'last_four' => '4242',
            'credit_limit' => 2000,
            'current_balance' => 500,
            'billing_day' => 15,
            'payment_due_day' => 5,
            'color' => '#0b7155',
            'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->get(route('credit-cards.index'))
            ->assertOk()
            ->assertSee('Reisekarte')
            ->assertSee('•••• 4242')
            ->assertSee('1.500,00 € verfügbar')
            ->assertSee('25 % von 2.000 €')
            ->assertSee('#0b7155', false);
    }

    public function test_reports_page_renders_without_emoji_buttons(): void
    {
        $this->actingAs($this->user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Analysen')
            ->assertDontSee('🖨️')
            ->assertSee('Keine Buchungen in diesem Zeitraum');
    }

    public function test_admin_page_shows_registration_switch_and_users(): void
    {
        $this->user->forceFill(['is_admin' => true])->save();

        $other = User::factory()->create(['is_active' => false, 'name' => 'Erika Muster']);

        $this->actingAs($this->user)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('role="switch"', false)
            ->assertSee('Erika Muster')
            ->assertSee('Deaktiviert')
            ->assertSee('(du)')
            ->assertSee('action="' . route('admin.users.destroy', $other) . '"', false)
            ->assertDontSee('action="' . route('admin.users.destroy', $this->user) . '"', false);
    }
}
