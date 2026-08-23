<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $accounts = $user->accounts()
            ->where('is_active', true)
            ->get();

        $totalBalance = $accounts
            ->where('include_in_total', true)
            ->sum(function ($account) {
            return $account->current_balance;
            });

        $recentTransactions = $user->transactions()
            ->with(['account', 'category'])
            ->latest('transaction_date')
            ->latest('id')
            ->limit(10)
            ->get();

        $budgets = $user->budgets()
            ->where('is_active', true)
            ->get();

        $loans = $user->loans()
            ->where('is_active', true)
            ->get();

        return view('dashboard', [
            'user' => $user,
            'accounts' => $accounts,
            'totalBalance' => $totalBalance,
            'recentTransactions' => $recentTransactions,
            'budgets' => $budgets,
            'loans' => $loans,
        ]);
    }
}