<?php

namespace App\Http\Controllers;

use App\Models\DashboardSetting;
use App\Models\Transaction;
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
         * DASHBOARD-EINSTELLUNGEN
         * =========================================================
         */

        $dashboardSetting = $user->dashboardSetting;

        if (!$dashboardSetting) {
            $dashboardSetting = new DashboardSetting([
                'widgets' => DashboardSetting::defaultWidgets(),
                'display_mode' => 'standard',
            ]);
        }

        $dashboardWidgets = $dashboardSetting->effectiveWidgets();
        $dashboardWidgetOrder = $dashboardSetting->effectiveWidgetOrder();

        $dashboardCompact = $dashboardSetting->isCompact();


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
                '!Y-m',
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
         * STANDARDWERTE
         * =========================================================
         *
         * Alle Variablen werden initialisiert, damit die View
         * auch bei deaktivierten Widgets stabil bleibt.
         */

        $accounts = collect();
        $creditCards = collect();
        $loans = collect();

        $totalBalance = 0;

        $monthlyIncome = 0;
        $monthlyExpense = 0;
        $monthlyBalance = 0;
        $savingsRate = 0;

        $yearlyIncome = 0;
        $yearlyExpense = 0;

        $recentTransactions = collect();
        $expensesByCategory = collect();
        $chartMonths = collect();
        $wealthMonths = collect();

        $maxWealthValue = 1;
        $minWealthValue = 0;

        $budgets = collect();


        /*
         * =========================================================
         * KONTEN
         * =========================================================
         *
         * Benötigt für:
         * - Gesamtvermögen
         * - Konten
         * - Vermögensentwicklung
         */

        $needsAccounts =
            $dashboardWidgets['summary'] ||
            $dashboardWidgets['accounts'] ||
            $dashboardWidgets['wealth_chart'];

        if ($needsAccounts) {

            $accounts = $user->accounts()
                ->with('provider')
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            foreach ($accounts as $account) {
                $account->calculated_balance =
                    $account->current_balance;
            }
        }


        /*
         * =========================================================
         * GESAMTVERMÖGEN
         * =========================================================
         */

        if ($dashboardWidgets['summary']) {

            $totalBalance = $accounts
                ->where('include_in_total', true)
                ->sum('calculated_balance');
        }


        /*
         * =========================================================
         * KREDITKARTEN
         * =========================================================
         */

        if ($dashboardWidgets['credit_cards']) {

            $creditCards = $user->creditCards()
                ->with('provider')
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }


        /*
         * =========================================================
         * KREDITE
         * =========================================================
         */

        if ($dashboardWidgets['loans']) {

            $loans = $user->loans()
                ->with('provider')
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }


        /*
         * =========================================================
         * MONATLICHE WERTE
         * =========================================================
         */

        $needsMonthlyIncome =
            $dashboardWidgets['income'] ||
            $dashboardWidgets['savings_rate'] ||
            $dashboardWidgets['monthly_balance'] ||
            $dashboardWidgets['yearly'] ||
            $dashboardWidgets['income_expense_chart'];

        $needsMonthlyExpense =
            $dashboardWidgets['expenses'] ||
            $dashboardWidgets['savings_rate'] ||
            $dashboardWidgets['monthly_balance'] ||
            $dashboardWidgets['yearly'] ||
            $dashboardWidgets['income_expense_chart'];


        if ($needsMonthlyIncome) {

            $monthlyIncome = $user->transactions()
                ->where('type', 'income')
                ->whereBetween('transaction_date', [
                    $startOfMonth,
                    $endOfMonth,
                ])
                ->sum('amount');
        }


        if ($needsMonthlyExpense) {

            $monthlyExpense = $user->transactions()
                ->where('type', 'expense')
                ->whereBetween('transaction_date', [
                    $startOfMonth,
                    $endOfMonth,
                ])
                ->sum('amount');
        }


        /*
         * =========================================================
         * MONATSSALDO
         * =========================================================
         */

        /*
         * Immer berechnen: Die Sparquote baut darauf auf, auch wenn
         * das Widget "Monatssaldo" ausgeblendet ist.
         */
        $monthlyBalance =
            $monthlyIncome - $monthlyExpense;


        /*
         * =========================================================
         * SPARQUOTE
         * =========================================================
         */

        if ($dashboardWidgets['savings_rate']) {

            $savingsRate = $monthlyIncome > 0
                ? ($monthlyBalance / $monthlyIncome) * 100
                : 0;
        }


        /*
         * =========================================================
         * JAHRESWERTE
         * =========================================================
         */

        if ($dashboardWidgets['yearly']) {

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
        }


        /*
         * =========================================================
         * LETZTE BUCHUNGEN
         * =========================================================
         */

        if ($dashboardWidgets['recent_transactions']) {

            $recentTransactions = $user->transactions()
                ->with([
                    'account',
                    'category',
                ])
                ->latest('transaction_date')
                ->latest('id')
                ->limit(8)
                ->get();
        }


        /*
         * =========================================================
         * AUSGABEN NACH KATEGORIE
         * =========================================================
         */

        if ($dashboardWidgets['categories']) {

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
        }


        /*
         * =========================================================
         * LETZTE 6 MONATE
         * =========================================================
         */

        if ($dashboardWidgets['income_expense_chart']) {

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
        }


        /*
         * =========================================================
         * VERMÖGENSENTWICKLUNG
         * =========================================================
         */

        if ($dashboardWidgets['wealth_chart']) {

            $includedAccounts = $accounts
                ->where('include_in_total', true);

            $openingBalance = $includedAccounts
                ->sum('opening_balance');

            /*
             * Nur die Konten, die auch im Gesamtvermögen
             * enthalten sind (aktiv + include_in_total).
             */
            $includedAccountIds = $includedAccounts
                ->pluck('id');


            for ($i = 5; $i >= 0; $i--) {

                $wealthMonth =
                    $month->copy()->subMonths($i);

                $wealthEnd =
                    $wealthMonth->copy()->endOfMonth();


                $incomeUntil = $user->transactions()
                    ->whereIn('account_id', $includedAccountIds)
                    ->where('type', 'income')
                    ->where(
                        'transaction_date',
                        '<=',
                        $wealthEnd
                    )
                    ->sum('amount');


                $expenseUntil = $user->transactions()
                    ->whereIn('account_id', $includedAccountIds)
                    ->where('type', 'expense')
                    ->where(
                        'transaction_date',
                        '<=',
                        $wealthEnd
                    )
                    ->sum('amount');


                /*
                 * Transfers:
                 *
                 * Ein Transfer innerhalb der einbezogenen Konten
                 * verändert das Gesamtvermögen nicht.
                 *
                 * Nur Transfers über die Grenze der einbezogenen
                 * Konten verändern den Vermögenswert:
                 *
                 * - aus einem einbezogenen Konto heraus -> minus
                 * - in ein einbezogenes Konto hinein -> plus
                 */

                $transferOutUntil = Transaction::query()
                    ->where('type', 'transfer')
                    ->whereIn('account_id', $includedAccountIds)
                    ->whereNotNull('transfer_account_id')
                    ->whereNotIn(
                        'transfer_account_id',
                        $includedAccountIds
                    )
                    ->where(
                        'transaction_date',
                        '<=',
                        $wealthEnd
                    )
                    ->sum('amount');

                $transferInUntil = Transaction::query()
                    ->where('type', 'transfer')
                    ->whereNotNull('transfer_account_id')
                    ->whereIn(
                        'transfer_account_id',
                        $includedAccountIds
                    )
                    ->whereNotIn(
                        'account_id',
                        $includedAccountIds
                    )
                    ->where(
                        'transaction_date',
                        '<=',
                        $wealthEnd
                    )
                    ->sum('amount');


                $wealthBalance =
                    $openingBalance
                    + $incomeUntil
                    - $expenseUntil
                    - $transferOutUntil
                    + $transferInUntil;


                $wealthMonths->push([

                    'label' =>
                        $wealthMonth->translatedFormat('M'),

                    'full_label' =>
                        $wealthMonth->translatedFormat('F Y'),

                    'balance' =>
                        (float) $wealthBalance,

                ]);
            }


            $maxWealthValue = max(
                1,
                $wealthMonths->max('balance')
            );

            $minWealthValue = min(
                0,
                $wealthMonths->min('balance')
            );
        }


        /*
         * =========================================================
         * BUDGETS
         * =========================================================
         */

        if ($dashboardWidgets['budgets']) {

            $budgets = $user->budgets()
                ->with('categories')
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get();


            foreach ($budgets as $budget) {

                $calculation =
                    $budgetService->calculate(
                        $budget,
                        $user,
                        $month
                    );


                $budget->calculated_spent =
                    $calculation['spent'];

                $budget->calculated_remaining =
                    $calculation['remaining'];

                $budget->calculated_percentage =
                    $calculation['percentage'];

                $budget->calculated_exceeded =
                    $calculation['exceeded'];

                $budget->calculated_start_date =
                    $calculation['start_date'];

                $budget->calculated_end_date =
                    $calculation['end_date'];

                $budget->calculated_applicable =
                    $calculation['applicable'];
            }
        }


        return view('dashboard', [

            'dashboardWidgets' =>
                $dashboardWidgets,

            'dashboardWidgetOrder' =>
                $dashboardWidgetOrder,

            'dashboardCompact' =>
                $dashboardCompact,

            'accounts' =>
                $accounts,

            'creditCards' =>
                $creditCards,

            'loans' =>
                $loans,

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