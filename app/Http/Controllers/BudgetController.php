<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BudgetController extends Controller
{
    /**
     * Budgetübersicht
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $budgets = $user->budgets()
            ->with('categories')
            ->orderByDesc('is_active')
            ->orderByDesc('start_date')
            ->orderBy('name')
            ->get();

        /*
         * Für jedes Budget den aktuellen Verbrauch berechnen.
         */
        foreach ($budgets as $budget) {

            $categoryIds = $budget->categories
                ->pluck('id');

            /*
             * Wenn keine Kategorien zugeordnet sind,
             * beträgt der Verbrauch 0 €.
             */
            if ($categoryIds->isEmpty()) {

                $spent = 0;

            } else {

                $spent = $user->transactions()
                    ->where('type', 'expense')
                    ->whereBetween('transaction_date', [
                        $budget->start_date,
                        $budget->end_date,
                    ])
                    ->whereIn('category_id', $categoryIds)
                    ->sum('amount');

            }


            $budgetAmount = (float) $budget->amount;

            $spentAmount = (float) $spent;

            /*
             * Noch verfügbar
             */
            $remaining = $budgetAmount - $spentAmount;


            /*
             * Prozentualer Verbrauch
             */
            if ($budgetAmount > 0) {

                $percentage =
                    ($spentAmount / $budgetAmount) * 100;

            } else {

                $percentage = 0;

            }


            /*
             * Werte für die Blade-Datei
             */
            $budget->calculated_spent =
                $spentAmount;

            $budget->calculated_remaining =
                $remaining;

            $budget->calculated_percentage =
                $percentage;

            $budget->calculated_exceeded =
                $spentAmount > $budgetAmount;

        }


        return view('budgets.index', [

            'budgets' => $budgets,

        ]);
    }


    /**
     * Formular zum Erstellen eines Budgets
     */
    public function create(Request $request)
    {
        $user = $request->user();

        $categories = $user->categories()
            ->where('is_active', true)
            ->whereIn('type', [
                'expense',
                'both',
            ])
            ->orderBy('name')
            ->get();

        return view('budgets.create', [

            'categories' => $categories,

        ]);
    }


    /**
     * Budget speichern
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
                'min:0.01',
            ],

            'start_date' => [
                'required',
                'date',
            ],

            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
            ],

            'period' => [
                'required',
                Rule::in([
                    'monthly',
                    'yearly',
                    'custom',
                ]),
            ],

            'categories' => [
                'nullable',
                'array',
            ],

            'categories.*' => [
                'integer',

                Rule::exists(
                    'categories',
                    'id'
                )->where(function ($query) use ($user) {

                    $query
                        ->where('user_id', $user->id)
                        ->where('is_active', true)
                        ->whereIn('type', [
                            'expense',
                            'both',
                        ]);

                }),
            ],

            'color' => [
                'nullable',
                'string',
                'max:255',
            ],

            'icon' => [
                'nullable',
                'string',
                'max:255',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

        ]);


        /*
         * Budget erstellen
         */
        $budget = $user->budgets()->create([

            'name' => $validated['name'],

            'amount' => $validated['amount'],

            'start_date' => $validated['start_date'],

            'end_date' => $validated['end_date'],

            'period' => $validated['period'],

            'color' => $validated['color'] ?? null,

            'icon' => $validated['icon'] ?? null,

            'is_active' =>
                $request->boolean('is_active'),

        ]);


        /*
         * Kategorien mit dem Budget verknüpfen
         */
        $budget->categories()->sync(
            $validated['categories'] ?? []
        );


        return redirect()
            ->route('budgets.index')
            ->with(
                'success',
                'Budget wurde erfolgreich erstellt.'
            );
    }


    /**
     * Budget anzeigen
     */
    public function show(
        Request $request,
        Budget $budget
    ) {

        $this->authorizeBudget(
            $request,
            $budget
        );


        $budget->load('categories');


        return view('budgets.show', [

            'budget' => $budget,

        ]);
    }


    /**
     * Budget bearbeiten
     */
    public function edit(
        Request $request,
        Budget $budget
    ) {

        $this->authorizeBudget(
            $request,
            $budget
        );


        $user = $request->user();


        $categories = $user->categories()
            ->where('is_active', true)
            ->whereIn('type', [
                'expense',
                'both',
            ])
            ->orderBy('name')
            ->get();


        $budget->load('categories');


        return view('budgets.edit', [

            'budget' => $budget,

            'categories' => $categories,

        ]);
    }


    /**
     * Budget aktualisieren
     */
    public function update(
        Request $request,
        Budget $budget
    ) {

        $this->authorizeBudget(
            $request,
            $budget
        );


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
                'min:0.01',
            ],

            'start_date' => [
                'required',
                'date',
            ],

            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
            ],

            'period' => [
                'required',
                Rule::in([
                    'monthly',
                    'yearly',
                    'custom',
                ]),
            ],

            'categories' => [
                'nullable',
                'array',
            ],

            'categories.*' => [
                'integer',

                Rule::exists(
                    'categories',
                    'id'
                )->where(function ($query) use ($user) {

                    $query
                        ->where('user_id', $user->id)
                        ->where('is_active', true)
                        ->whereIn('type', [
                            'expense',
                            'both',
                        ]);

                }),
            ],

            'color' => [
                'nullable',
                'string',
                'max:255',
            ],

            'icon' => [
                'nullable',
                'string',
                'max:255',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

        ]);


        /*
         * Budget aktualisieren
         */
        $budget->update([

            'name' => $validated['name'],

            'amount' => $validated['amount'],

            'start_date' => $validated['start_date'],

            'end_date' => $validated['end_date'],

            'period' => $validated['period'],

            'color' => $validated['color'] ?? null,

            'icon' => $validated['icon'] ?? null,

            'is_active' =>
                $request->boolean('is_active'),

        ]);


        /*
         * Kategorien aktualisieren
         */
        $budget->categories()->sync(
            $validated['categories'] ?? []
        );


        return redirect()
            ->route('budgets.index')
            ->with(
                'success',
                'Budget wurde erfolgreich aktualisiert.'
            );
    }


    /**
     * Budget löschen
     */
    public function destroy(
        Request $request,
        Budget $budget
    ) {

        $this->authorizeBudget(
            $request,
            $budget
        );


        $budget->delete();


        return redirect()
            ->route('budgets.index')
            ->with(
                'success',
                'Budget wurde gelöscht.'
            );
    }


    /**
     * Sicherheitsprüfung
     *
     * Verhindert, dass ein Benutzer auf
     * fremde Budgets zugreifen kann.
     */
    private function authorizeBudget(
        Request $request,
        Budget $budget
    ): void {

        abort_unless(

            $budget->user_id ===
                $request->user()->id,

            403

        );

    }
}