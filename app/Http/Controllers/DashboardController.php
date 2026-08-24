<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        /*
         * Ausgewählten Monat bestimmen.
         *
         * Format:
         * 2026-08
         */
        $selectedMonth = $request->input(
            'month',
            now()->format('Y-m')
        );

        try {
            $month = Carbon::createFromFormat(
                'Y-m',
                $selectedMonth
            )->startOfMonth();
        } catch (\Exception $e) {
            $month = now()->startOfMonth();
            $selectedMonth = $month->format('Y-m');
        }

        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();


        /*
         * Aktuelles Jahr
         */
        $startOfYear = $month->copy()->startOfYear();
        $endOfYear = $month->copy()->endOfYear();


        /*
         * Konten
         */
        $accounts = $user->accounts()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();


        /*
         * Kontostände berechnen
         */
        foreach ($accounts as $account) {

            $income = $account->transactions()
                ->where('type', 'income')
                ->sum('amount');

            $expense = $account->transactions()
                ->where('type', 'expense')
                ->sum('amount');

            $account->calculated_balance =
                $account->opening_balance
                + $income
                - $expense;
        }


        /*
         * Gesamtvermögen
         */
        $totalBalance = $accounts
            ->where('include_in_total', true)
            ->sum('calculated_balance');


        /*
         * Einnahmen im ausgewählten Monat
         */
        $monthlyIncome = $user->transactions()
            ->where('type', 'income')
            ->whereBetween('transaction_date', [
                $startOfMonth,
                $endOfMonth,
            ])
            ->sum('amount');


        /*
         * Ausgaben im ausgewählten Monat
         */
        $monthlyExpense = $user->transactions()
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [
                $startOfMonth,
                $endOfMonth,
            ])
            ->sum('amount');


        /*
         * Monatssaldo
         */
        $monthlyBalance =
            $monthlyIncome - $monthlyExpense;


        /*
         * Sparquote
         */
        $savingsRate = $monthlyIncome > 0
            ? ($monthlyBalance / $monthlyIncome) * 100
            : 0;


        /*
         * Jahreswerte
         */
        $yearlyIncome = $user->transactions()
            ->where('type', 'income')
            ->whereBetween('transaction_date', [
                $startOfYear,
                $endOfYear,
            ])
            ->sum('amount');

        $yearlyExpense = $user->transactions()
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [
                $startOfYear,
                $endOfYear,
            ])
            ->sum('amount');


        /*
         * Letzte Buchungen
         */
        $recentTransactions = $user->transactions()
            ->with([
                'account',
                'category',
            ])
            ->latest('transaction_date')
            ->latest('id')
            ->limit(8)
            ->get();


        /*
         * Ausgaben nach Kategorie
         */
        $expensesByCategory = $user->transactions()
            ->with('category')
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [
                $startOfMonth,
                $endOfMonth,
            ])
            ->get()
            ->groupBy('category_id')
            ->map(function ($transactions) {

                return [
                    'category' => $transactions->first()->category,
                    'amount' => $transactions->sum('amount'),
                ];

            })
            ->sortByDesc('amount')
            ->values();


        /*
         * Letzte 6 Monate für das Diagramm
         */
        $chartMonths = collect();

        for ($i = 5; $i >= 0; $i--) {

            $chartMonth = $month->copy()
                ->subMonths($i);

            $income = $user->transactions()
                ->where('type', 'income')
                ->whereBetween('transaction_date', [
                    $chartMonth->copy()->startOfMonth(),
                    $chartMonth->copy()->endOfMonth(),
                ])
                ->sum('amount');

            $expense = $user->transactions()
                ->where('type', 'expense')
                ->whereBetween('transaction_date', [
                    $chartMonth->copy()->startOfMonth(),
                    $chartMonth->copy()->endOfMonth(),
                ])
                ->sum('amount');

            $chartMonths->push([
                'label' => $chartMonth->translatedFormat('M'),
                'full_label' => $chartMonth->translatedFormat('F Y'),
                'income' => (float) $income,
                'expense' => (float) $expense,
                'balance' => (float) ($income - $expense),
            ]);
        }


        return view('dashboard', [

            'accounts' => $accounts,

            'totalBalance' => $totalBalance,

            'monthlyIncome' => $monthlyIncome,

            'monthlyExpense' => $monthlyExpense,

            'monthlyBalance' => $monthlyBalance,

            'savingsRate' => $savingsRate,

            'yearlyIncome' => $yearlyIncome,

            'yearlyExpense' => $yearlyExpense,

            'recentTransactions' => $recentTransactions,

            'expensesByCategory' => $expensesByCategory,

            'chartMonths' => $chartMonths,

            'selectedMonth' => $selectedMonth,

            'currentMonth' => $month->translatedFormat('F Y'),
        ]);
    }
}