<?php

namespace App\Http\Controllers;

use App\Services\BudgetService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(
        Request $request,
        BudgetService $budgetService
    ) {
        $user = $request->user();

        /*
         * =========================================================
         * AUSGEWÄHLTEN MONAT
         * =========================================================
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

            $selectedMonth =
                $month->format('Y-m');
        }


        $startOfMonth =
            $month->copy()->startOfMonth();

        $endOfMonth =
            $month->copy()->endOfMonth();


        /*
         * =========================================================
         * JAHR
         * =========================================================
         */

        $startOfYear =
            $month->copy()->startOfYear();

        $endOfYear =
            $month->copy()->endOfYear();


        /*
         * =========================================================
         * KONTEN
         * =========================================================
         */

        $accounts = $user->accounts()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();


        foreach ($accounts as $account) {

            $income = $account->transactions()
                ->where('type', 'income')
                ->sum('amount');

            $expense = $account->transactions()
                ->where('type', 'expense')
                ->sum('amount');

            $account->calculated_balance =
                (float) $account->opening_balance
                + (float) $income
                - (float) $expense;
        }


        /*
         * =========================================================
         * GESAMTVERMÖGEN
         * =========================================================
         */

        $totalBalance = $accounts
            ->where('include_in_total', true)
            ->sum('calculated_balance');


        /*
         * =========================================================
         * MONATLICHE EINNAHMEN
         * =========================================================
         */

        $monthlyIncome = $user->transactions()
            ->where('type', 'income')
            ->whereBetween('transaction_date', [
                $startOfMonth,
                $endOfMonth,
            ])
            ->sum('amount');


        /*
         * =========================================================
         * MONATLICHE AUSGABEN
         * =========================================================
         */

        $monthlyExpense = $user->transactions()
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [
                $startOfMonth,
                $endOfMonth,
            ])
            ->sum('amount');


        /*
         * =========================================================
         * MONATSSALDO
         * =========================================================
         */

        $monthlyBalance =
            $monthlyIncome - $monthlyExpense;


        /*
         * =========================================================
         * SPARQUOTE
         * =========================================================
         */

        $savingsRate = $monthlyIncome > 0
            ? ($monthlyBalance / $monthlyIncome) * 100
            : 0;


        /*
         * =========================================================
         * JAHRESWERTE
         * =========================================================
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
         * =========================================================
         * LETZTE BUCHUNGEN
         * =========================================================
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
         * =========================================================
         * AUSGABEN NACH KATEGORIE
         * =========================================================
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
                    'category' =>
                        $transactions->first()->category,

                    'amount' =>
                        $transactions->sum('amount'),
                ];

            })
            ->sortByDesc('amount')
            ->values();


        /*
         * =========================================================
         * LETZTE 6 MONATE
         * =========================================================
         */

        $chartMonths = collect();


        for ($i = 5; $i >= 0; $i--) {

            $chartMonth =
                $month->copy()->subMonths($i);

            $chartStart =
                $chartMonth->copy()->startOfMonth();

            $chartEnd =
                $chartMonth->copy()->endOfMonth();


            $income = $user->transactions()
                ->where('type', 'income')
                ->whereBetween('transaction_date', [
                    $chartStart,
                    $chartEnd,
                ])
                ->sum('amount');


            $expense = $user->transactions()
                ->where('type', 'expense')
                ->whereBetween('transaction_date', [
                    $chartStart,
                    $chartEnd,
                ])
                ->sum('amount');


            $chartMonths->push([

                'label' =>
                    $chartMonth->translatedFormat('M'),

                'full_label' =>
                    $chartMonth->translatedFormat('F Y'),

                'income' =>
                    (float) $income,

                'expense' =>
                    (float) $expense,

                'balance' =>
                    (float) ($income - $expense),

            ]);
        }


        /*
         * =========================================================
         * VERMÖGENSENTWICKLUNG
         * =========================================================
         */

        $wealthMonths = collect();


        $includedAccounts = $accounts
            ->where('include_in_total', true);


        $openingBalance = $includedAccounts
            ->sum('opening_balance');


        for ($i = 5; $i >= 0; $i--) {

            $wealthMonth =
                $month->copy()->subMonths($i);


            $wealthEnd =
                $wealthMonth->copy()->endOfMonth();


            $incomeUntil = $user->transactions()
                ->where('type', 'income')
                ->where(
                    'transaction_date',
                    '<=',
                    $wealthEnd
                )
                ->sum('amount');


            $expenseUntil = $user->transactions()
                ->where('type', 'expense')
                ->where(
                    'transaction_date',
                    '<=',
                    $wealthEnd
                )
                ->sum('amount');


            $wealthBalance =
                $openingBalance
                + $incomeUntil
                - $expenseUntil;


            $wealthMonths->push([

                'label' =>
                    $wealthMonth->translatedFormat('M'),

                'full_label' =>
                    $wealthMonth->translatedFormat('F Y'),

                'balance' =>
                    (float) $wealthBalance,

            ]);
        }


        /*
         * =========================================================
         * WERTE FÜR VERMÖGENSDIAGRAMM
         * =========================================================
         */

        $maxWealthValue = max(
            1,
            $wealthMonths->max('balance')
        );


        $minWealthValue = min(
            0,
            $wealthMonths->min('balance')
        );


        /*
 * =========================================================
 * BUDGETS
 * =========================================================
 *
 * Die Budgetberechnung erfolgt ausschließlich
 * über den BudgetService.
 *
 * Dadurch verwenden:
 *
 * - Dashboard
 * - Budgetübersicht
 * - Budgetdetails
 *
 * exakt dieselbe Berechnungslogik.
 *
 * WICHTIG:
 *
 * Es werden auch inaktive Budgets geladen.
 * Dadurch kann das Dashboard zwischen:
 *
 * - Inaktiv
 * - Nicht aktiv / nicht gültig
 * - OK
 * - Achtung
 * - Überschritten
 *
 * unterscheiden.
 */

$budgets = $user->budgets()
    ->with('categories')
    ->orderByDesc('is_active')
    ->orderBy('name')
    ->get();


foreach ($budgets as $budget) {

    /*
     * =====================================================
     * BUDGET BERECHNEN
     * =====================================================
     */

    $calculation =
        $budgetService->calculate(
            $budget,
            $user,
            $month
        );


    /*
     * =====================================================
     * BERECHNETE WERTE AN DAS MODEL HÄNGEN
     * =====================================================
     *
     * Diese Werte werden anschließend
     * direkt von dashboard.blade.php verwendet.
     */

    $budget->calculated_spent =
        (float) $calculation['spent'];


    $budget->calculated_remaining =
        (float) $calculation['remaining'];


    $budget->calculated_percentage =
        (float) $calculation['percentage'];


    $budget->calculated_exceeded =
        (bool) $calculation['exceeded'];


    $budget->calculated_start_date =
        $calculation['start_date'];


    $budget->calculated_end_date =
        $calculation['end_date'];


    $budget->calculated_applicable =
        (bool) $calculation['applicable'];
}


        /*
         * =========================================================
         * VIEW
         * =========================================================
         */

        return view('dashboard', [

            'accounts' =>
                $accounts,

            'totalBalance' =>
                $totalBalance,

            'monthlyIncome' =>
                $monthlyIncome,

            'monthlyExpense' =>
                $monthlyExpense,

            'monthlyBalance' =>
                $monthlyBalance,

            'savingsRate' =>
                $savingsRate,

            'yearlyIncome' =>
                $yearlyIncome,

            'yearlyExpense' =>
                $yearlyExpense,

            'recentTransactions' =>
                $recentTransactions,

            'expensesByCategory' =>
                $expensesByCategory,

            'chartMonths' =>
                $chartMonths,

            'wealthMonths' =>
                $wealthMonths,

            'maxWealthValue' =>
                $maxWealthValue,

            'minWealthValue' =>
                $minWealthValue,

            'budgets' =>
                $budgets,

            'selectedMonth' =>
                $selectedMonth,

            'currentMonth' =>
                $month->translatedFormat('F Y'),

        ]);
    }
}