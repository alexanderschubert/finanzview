<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CodeCheckFixesTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_works_regardless_of_email_case(): void
    {
        User::factory()->create([
            'email' => 'max@example.com',
            'password' => Hash::make('geheim123'),
            'is_active' => true,
        ]);

        $this->post('/login', ['email' => '  Max@Example.COM ', 'password' => 'geheim123'])
            ->assertRedirect(config('fortify.home'));

        $this->assertAuthenticated();
    }

    public function test_profile_email_is_stored_lowercase_and_unique_case_insensitively(): void
    {
        User::factory()->create(['email' => 'erika@example.com']);
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->put(route('settings.profile.update'), ['name' => 'Max', 'email' => 'ERIKA@example.com'])
            ->assertSessionHasErrors('email');

        $this->actingAs($user)
            ->put(route('settings.profile.update'), ['name' => 'Max', 'email' => 'Max.Neu@Example.com'])
            ->assertSessionHasNoErrors();

        $this->assertSame('max.neu@example.com', $user->fresh()->email);
    }

    public function test_forwarded_host_header_is_ignored(): void
    {
        $this->get('/login', ['X-Forwarded-Host' => 'evil.example'])
            ->assertOk()
            ->assertDontSee('evil.example');
    }

    public function test_csv_export_neutralizes_formulas_from_bank_texts(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $account = Account::create([
            'user_id' => $user->id, 'name' => 'Girokonto', 'type' => 'checking',
            'currency' => 'EUR', 'opening_balance' => 0, 'include_in_total' => true, 'is_active' => true,
        ]);

        Transaction::create([
            'user_id' => $user->id, 'account_id' => $account->id, 'type' => 'income', 'amount' => 0.01,
            'transaction_date' => '2026-09-01', 'description' => '=HYPERLINK("http://evil.example","Klick")',
            'merchant' => '@Angreifer',
        ]);

        $csv = $this->actingAs($user)
            ->post(route('settings.data-export.transactions'))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString("\"'=HYPERLINK(\"\"http://evil.example\"\",\"\"Klick\"\")\"", $csv);
        $this->assertStringContainsString("'@Angreifer", $csv);
        $this->assertStringContainsString(';0.01;', $csv);
    }
}
