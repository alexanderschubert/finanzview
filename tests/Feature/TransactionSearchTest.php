<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionSearchTest extends TestCase
{
    use RefreshDatabase;

    private function createTransaction(
        User $user,
        Account $account,
        string $description,
        ?string $merchant = null
    ): Transaction {
        return Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'type' => 'expense',
            'amount' => 10,
            'transaction_date' => now()->toDateString(),
            'description' => $description,
            'merchant' => $merchant,
            'is_pending' => false,
        ]);
    }

    public function test_search_matches_description_and_merchant_case_insensitive(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Giro',
            'type' => 'checking',
            'currency' => 'EUR',
            'opening_balance' => 0,
            'include_in_total' => true,
            'is_active' => true,
        ]);

        $this->createTransaction($user, $account, 'Wocheneinkauf', 'REWE Markt');
        $this->createTransaction($user, $account, 'Rewe-Bonus zurück');
        $this->createTransaction($user, $account, 'Tankstelle', 'Aral');

        $response = $this
            ->actingAs($user)
            ->get(route('transactions.index', ['search' => 'rewe']));

        $response->assertOk();
        $response->assertSee('Wocheneinkauf');
        $response->assertSee('Rewe-Bonus zurück');
        $response->assertDontSee('Tankstelle');
    }
}
