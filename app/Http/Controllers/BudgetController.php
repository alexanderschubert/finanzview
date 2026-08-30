<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Transaction;
use App\Services\BudgetService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    /**
     * =========================================================
     * BUDGETÜBERSICHT
     * =========================================================
     */
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


        /*
         * =========================================================
         * BUDGETS LADEN
         * =========================================================
         */

        $budgets = $user->budgets()
            ->with('categories')
            ->orderBy('name')
            ->get();


        /*
         * =========================================================
         * BUDGETWERTE BERECHNEN
         * =========================================================
         */

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


        /*
         * =========================================================
         * VIEW
         * =========================================================
         */

        return view('budgets.index', [
            'budgets' =>
                $budgets,

            'selectedMonth' =>
                $selectedMonth,

            'referenceMonth' =>
                $month,
        ]);
    }


    /**
     * =========================================================
     * BUDGET ERSTELLEN
     * =========================================================
     */
    public function create(Request $request)
    {
        $user = $request->user();


        $categories = $user->categories()
            ->orderBy('name')
            ->get();


        return view('budgets.create', [
            'categories' =>
                $categories,
        ]);
    }


    /**
     * =========================================================
     * BUDGET SPEICHERN
     * =========================================================
     */
    public function store(Request $request)
    {
        $user = $request->user();


        $validated = $request->validate([

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0',
            ],

            'period' => [
                'required',
                'in:monthly,yearly,custom',
            ],

            'start_date' => [
                'required',
                'date',
            ],

            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],

            'color' => [
                'nullable',
                'string',
                'max:20',
            ],

            'icon' => [
                'nullable',
                'string',
                'max:20',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'category_ids' => [
                'nullable',
                'array',
            ],

            'category_ids.*' => [
                'integer',
                'exists:categories,id',
            ],
        ]);


        /*
         * =========================================================
         * CUSTOM BUDGET
         * =========================================================
         */

        if (
            $validated['period'] === 'custom'
            &&
            empty($validated['end_date'])
        ) {

            return back()
                ->withErrors([
                    'end_date' =>
                        'Für ein benutzerdefiniertes Budget muss ein Enddatum angegeben werden.',
                ])
                ->withInput();
        }


        /*
         * =========================================================
         * BUDGET ERSTELLEN
         * =========================================================
         */

        $budget =
            $user->budgets()->create([

                'name' =>
                    $validated['name'],

                'amount' =>
                    $validated['amount'],

                'period' =>
                    $validated['period'],

                'start_date' =>
                    $validated['start_date'],

                'end_date' =>
                    $validated['end_date'] ?? null,

                'color' =>
                    $validated['color'] ?? null,

                'icon' =>
                    $validated['icon'] ?? null,

                'is_active' =>
                    $request->boolean(
                        'is_active',
                        true
                    ),
            ]);


        /*
         * =========================================================
         * KATEGORIEN ZUORDNEN
         * =========================================================
         */

        $categoryIds =
            $validated['category_ids'] ?? [];


        $categoryIds =
            $user->categories()
                ->whereIn(
                    'id',
                    $categoryIds
                )
                ->pluck('id')
                ->toArray();


        $budget->categories()->sync(
            $categoryIds
        );


        return redirect()
            ->route('budgets.index')
            ->with(
                'success',
                'Budget wurde erfolgreich erstellt.'
            );
    }


    /**
     * =========================================================
     * BUDGET DETAILS
     * =========================================================
     */
    public function show(
        Request $request,
        Budget $budget,
        BudgetService $budgetService
    ) {
        $user = $request->user();


        /*
         * =========================================================
         * SICHERHEIT
         * =========================================================
         */

        abort_unless(
            $budget->user_id === $user->id,
            403
        );


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

            $month =
                now()->startOfMonth();

            $selectedMonth =
                $month->format('Y-m');
        }


        /*
         * =========================================================
         * BUDGET LADEN
         * =========================================================
         */

        $budget->load('categories');


        /*
         * =========================================================
         * BUDGET BERECHNEN
         * =========================================================
         */

        $calculation =
            $budgetService->calculate(
                $budget,
                $user,
                $month
            );


        /*
         * =========================================================
         * BERECHNETE WERTE AM MODEL
         * =========================================================
         */

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


        /*
         * =========================================================
         * TRANSAKTIONEN
         * =========================================================
         *
         * Die Detailansicht erwartet immer
         * die Variable $transactions.
         *
         * Deshalb wird sie bereits vor der Abfrage
         * als leere Collection definiert.
         */

        $transactions =
            collect();


        /*
         * =========================================================
         * TRANSAKTIONEN LADEN
         * =========================================================
         *
         * Es werden exakt dieselben Kriterien verwendet
         * wie bei der Budgetberechnung:
         *
         * - aktueller Benutzer
         * - Ausgaben
         * - berechneter Budgetzeitraum
         * - Budgetkategorien
         */

        if (
            $calculation['applicable']
            &&
            $calculation['start_date']
            &&
            $calculation['end_date']
        ) {

            $categoryIds =
                $budget->categories
                    ->pluck('id')
                    ->toArray();


            /*
             * Ohne Kategorien gibt es keine
             * zugeordneten Budgetbuchungen.
             */

            if (!empty($categoryIds)) {

                $transactions =
                    Transaction::query()

                        ->where(
                            'user_id',
                            $user->id
                        )

                        ->where(
                            'type',
                            'expense'
                        )

                        ->whereBetween(
                            'transaction_date',
                            [
                                $calculation['start_date'],
                                $calculation['end_date'],
                            ]
                        )

                        ->whereIn(
                            'category_id',
                            $categoryIds
                        )

                        ->with([
                            'category',
                            'account',
                        ])

                        ->orderByDesc(
                            'transaction_date'
                        )

                        ->get();
            }
        }


        /*
         * =========================================================
         * VIEW
         * =========================================================
         */

        return view('budgets.show', [

            'budget' =>
                $budget,

            'selectedMonth' =>
                $selectedMonth,

            'referenceMonth' =>
                $month,

            'calculation' =>
                $calculation,

            'transactions' =>
                $transactions,
        ]);
    }


    /**
     * =========================================================
     * BUDGET BEARBEITEN
     * =========================================================
     */
    public function edit(
        Request $request,
        Budget $budget
    ) {
        $user = $request->user();


        /*
         * Sicherheit:
         * Budget muss dem angemeldeten Benutzer gehören.
         */

        abort_unless(
            $budget->user_id === $user->id,
            403
        );


        $categories =
            $user->categories()
                ->orderBy('name')
                ->get();


        $budget->load('categories');


        return view('budgets.edit', [

            'budget' =>
                $budget,

            'categories' =>
                $categories,
        ]);
    }


    /**
     * =========================================================
     * BUDGET AKTUALISIEREN
     * =========================================================
     */
    public function update(
        Request $request,
        Budget $budget
    ) {
        $user = $request->user();


        /*
         * Sicherheit:
         * Budget muss dem angemeldeten Benutzer gehören.
         */

        abort_unless(
            $budget->user_id === $user->id,
            403
        );


        $validated = $request->validate([

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0',
            ],

            'period' => [
                'required',
                'in:monthly,yearly,custom',
            ],

            'start_date' => [
                'required',
                'date',
            ],

            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],

            'color' => [
                'nullable',
                'string',
                'max:20',
            ],

            'icon' => [
                'nullable',
                'string',
                'max:20',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'category_ids' => [
                'nullable',
                'array',
            ],

            'category_ids.*' => [
                'integer',
                'exists:categories,id',
            ],
        ]);


        /*
         * =========================================================
         * CUSTOM BUDGET
         * =========================================================
         */

        if (
            $validated['period'] === 'custom'
            &&
            empty($validated['end_date'])
        ) {

            return back()
                ->withErrors([
                    'end_date' =>
                        'Für ein benutzerdefiniertes Budget muss ein Enddatum angegeben werden.',
                ])
                ->withInput();
        }


        /*
         * =========================================================
         * BUDGET AKTUALISIEREN
         * =========================================================
         */

        $budget->update([

            'name' =>
                $validated['name'],

            'amount' =>
                $validated['amount'],

            'period' =>
                $validated['period'],

            'start_date' =>
                $validated['start_date'],

            'end_date' =>
                $validated['end_date'] ?? null,

            'color' =>
                $validated['color'] ?? null,

            'icon' =>
                $validated['icon'] ?? null,

            'is_active' =>
                $request->boolean(
                    'is_active',
                    false
                ),
        ]);


        /*
         * =========================================================
         * KATEGORIEN AKTUALISIEREN
         * =========================================================
         */

        $categoryIds =
            $validated['category_ids'] ?? [];


        $categoryIds =
            $user->categories()
                ->whereIn(
                    'id',
                    $categoryIds
                )
                ->pluck('id')
                ->toArray();


        $budget->categories()->sync(
            $categoryIds
        );


        return redirect()
            ->route('budgets.index')
            ->with(
                'success',
                'Budget wurde erfolgreich aktualisiert.'
            );
    }


    /**
     * =========================================================
     * BUDGET LÖSCHEN
     * =========================================================
     */
    public function destroy(
        Request $request,
        Budget $budget
    ) {
        $user = $request->user();


        /*
         * Sicherheit:
         * Budget muss dem angemeldeten Benutzer gehören.
         */

        abort_unless(
            $budget->user_id === $user->id,
            403
        );


        /*
         * Kategorie-Verknüpfungen entfernen.
         */

        $budget->categories()->detach();


        /*
         * Budget löschen.
         */

        $budget->delete();


        return redirect()
            ->route('budgets.index')
            ->with(
                'success',
                'Budget wurde erfolgreich gelöscht.'
            );
    }
}