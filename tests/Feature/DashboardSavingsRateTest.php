<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\DashboardSetting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardSavingsRateTest extends TestCase
{
    use RefreshDatabase;

    public function test_savings_rate_is_calculated_when_monthly_balance_widget_is_hidden(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        DashboardSetting::create([
            'user_id' => $user->id,
            'widgets' => array_merge(DashboardSetting::defaultWidgets(), ['monthly_balance' => false]),
        ]);

        $account = Account::create([
            'user_id' => $user->id, 'name' => 'Giro', 'type' => 'checking',
            'currency' => 'EUR', 'opening_balance' => 0,
            'include_in_total' => true, 'is_active' => true,
        ]);

        foreach ([['income', 300], ['expense', 75]] as [$type, $amount]) {
            Transaction::create([
                'user_id' => $user->id, 'account_id' => $account->id,
                'type' => $type, 'amount' => $amount,
                'transaction_date' => now()->startOfMonth()->toDateString(),
                'description' => 'Test',
            ]);
        }

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $this->assertEquals(75.0, $response->viewData('savingsRate'));
        $response->assertSee('75,0 %');
    }
}
