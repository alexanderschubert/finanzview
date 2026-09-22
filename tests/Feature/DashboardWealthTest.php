<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardWealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_wealth_chart_matches_total_balance_and_ignores_excluded_accounts(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $included = Account::create([
            'user_id' => $user->id, 'name' => 'Giro', 'type' => 'checking',
            'currency' => 'EUR', 'opening_balance' => 1000,
            'include_in_total' => true, 'is_active' => true,
        ]);

        $excluded = Account::create([
            'user_id' => $user->id, 'name' => 'Depot', 'type' => 'savings',
            'currency' => 'EUR', 'opening_balance' => 0,
            'include_in_total' => false, 'is_active' => true,
        ]);

        foreach ([[$included, 'income', 200], [$included, 'expense', 50], [$excluded, 'income', 5000]] as [$account, $type, $amount]) {
            Transaction::create([
                'user_id' => $user->id, 'account_id' => $account->id,
                'type' => $type, 'amount' => $amount,
                'transaction_date' => now()->startOfMonth()->toDateString(),
                'description' => 'Test',
            ]);
        }

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $this->assertEquals(1150.0, $response->viewData('totalBalance'));
        $this->assertEquals(1150.0, $response->viewData('wealthMonths')->last()['balance']);
        $this->assertEquals(1150.0, $included->fresh()->current_balance);
    }
}
