<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountCategoryFormTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['is_active' => true]);
    }

    public function test_account_create_form_shows_live_preview(): void
    {
        $this->actingAs($this->user)
            ->get(route('accounts.create'))
            ->assertOk()
            ->assertSee('data-account-preview', false)
            ->assertSee('Neues Konto')
            ->assertSee('name="institution"', false)
            ->assertSee('Konto anlegen');
    }

    public function test_account_can_be_created_without_color_and_with_switches(): void
    {
        $this->actingAs($this->user)
            ->post(route('accounts.store'), [
                'name' => 'Tagesgeld',
                'type' => 'savings',
                'institution' => 'Direktbank',
                'currency' => 'EUR',
                'opening_balance' => '2500.00',
                'color' => '',
                'include_in_total' => '1',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('accounts.index'));

        $this->assertDatabaseHas('accounts', [
            'name' => 'Tagesgeld',
            'institution' => 'Direktbank',
            'color' => null,
            'include_in_total' => true,
        ]);
    }

    public function test_account_edit_form_can_deactivate(): void
    {
        $account = Account::create([
            'user_id' => $this->user->id,
            'name' => 'Altes Konto',
            'type' => 'checking',
            'currency' => 'EUR',
            'opening_balance' => 0,
            'include_in_total' => true,
            'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->get(route('accounts.edit', $account))
            ->assertOk()
            ->assertSee('Aktueller Kontostand')
            ->assertSee('name="is_active"', false)
            ->assertSee('action="' . route('accounts.destroy', $account) . '"', false);

        $this->actingAs($this->user)
            ->put(route('accounts.update', $account), [
                'name' => 'Altes Konto',
                'type' => 'checking',
                'currency' => 'EUR',
                'opening_balance' => '0',
                'include_in_total' => '0',
                'is_active' => '0',
            ])
            ->assertSessionHasNoErrors();

        $this->assertFalse($account->fresh()->is_active);
        $this->assertFalse($account->fresh()->include_in_total);
    }

    public function test_category_form_offers_emoji_suggestions_and_saves(): void
    {
        $this->actingAs($this->user)
            ->get(route('categories.create'))
            ->assertOk()
            ->assertSee('data-emoji="🛒"', false)
            ->assertSee('role="radiogroup"', false);

        $this->actingAs($this->user)
            ->post(route('categories.store'), [
                'name' => 'Haustier',
                'type' => 'expense',
                'icon' => '🐶',
                'color' => '#16a57a',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('categories.index'));

        $category = Category::where('name', 'Haustier')->firstOrFail();

        $this->actingAs($this->user)
            ->get(route('categories.edit', $category))
            ->assertOk()
            ->assertSee('background-color: #16a57a26', false)
            ->assertSee('name="is_active"', false);
    }
}
