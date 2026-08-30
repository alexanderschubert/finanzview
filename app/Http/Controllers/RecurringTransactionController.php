<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\RecurringTransaction;
use Illuminate\Http\Request;

class RecurringTransactionController extends Controller
{
    /**
     * Übersicht der wiederkehrenden Buchungen.
     */
    public function index(Request $request)
    {
        $recurringTransactions = RecurringTransaction::query()
            ->where('user_id', $request->user()->id)
            ->with([
                'account',
                'category',
            ])
            ->orderBy('is_active', 'desc')
            ->orderBy('next_date')
            ->get();

        return view('recurring_transactions.index', [
            'recurringTransactions' => $recurringTransactions,
        ]);
    }


    /**
     * Formular für eine neue wiederkehrende Buchung.
     */
    public function create(Request $request)
    {
        $accounts = Account::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get();

        $categories = Category::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get();

        return view('recurring_transactions.create', [
            'accounts' => $accounts,
            'categories' => $categories,
        ]);
    }


    /**
     * Neue wiederkehrende Buchung speichern.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_id' => [
                'required',
                'integer',
                'exists:accounts,id',
            ],

            'category_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],

            'description' => [
                'required',
                'string',
                'max:255',
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'type' => [
                'required',
                'in:income,expense',
            ],

            'frequency' => [
                'required',
                'in:weekly,monthly,quarterly,yearly',
            ],

            'next_date' => [
                'required',
                'date',
            ],

            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:next_date',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ], [
            'account_id.required' =>
                'Bitte wähle ein Konto aus.',

            'account_id.exists' =>
                'Das ausgewählte Konto ist nicht gültig.',

            'category_id.exists' =>
                'Die ausgewählte Kategorie ist nicht gültig.',

            'description.required' =>
                'Bitte gib eine Bezeichnung ein.',

            'description.max' =>
                'Die Bezeichnung darf maximal 255 Zeichen lang sein.',

            'amount.required' =>
                'Bitte gib einen Betrag ein.',

            'amount.numeric' =>
                'Der Betrag muss eine gültige Zahl sein.',

            'amount.min' =>
                'Der Betrag muss größer als 0 sein.',

            'type.required' =>
                'Bitte wähle Einnahme oder Ausgabe.',

            'type.in' =>
                'Der ausgewählte Buchungstyp ist nicht gültig.',

            'frequency.required' =>
                'Bitte wähle ein Intervall.',

            'frequency.in' =>
                'Das ausgewählte Intervall ist nicht gültig.',

            'next_date.required' =>
                'Bitte gib das Datum der nächsten Ausführung an.',

            'next_date.date' =>
                'Das Datum der nächsten Ausführung ist nicht gültig.',

            'end_date.date' =>
                'Das Enddatum ist nicht gültig.',

            'end_date.after_or_equal' =>
                'Das Enddatum muss am oder nach der nächsten Ausführung liegen.',
        ]);

        /*
         * Wichtig:
         * Das Konto und die Kategorie müssen dem angemeldeten
         * Benutzer gehören. Die normalen exists-Regeln oben
         * reichen dafür alleine nicht aus.
         */

        $accountExists = Account::query()
            ->where('id', $validated['account_id'])
            ->where('user_id', $request->user()->id)
            ->exists();

        if (! $accountExists) {
            return back()
                ->withErrors([
                    'account_id' => 'Das ausgewählte Konto gehört nicht zu deinem Benutzerkonto.',
                ])
                ->withInput();
        }

        if (! empty($validated['category_id'])) {

            $categoryExists = Category::query()
                ->where('id', $validated['category_id'])
                ->where('user_id', $request->user()->id)
                ->exists();

            if (! $categoryExists) {
                return back()
                    ->withErrors([
                        'category_id' => 'Die ausgewählte Kategorie gehört nicht zu deinem Benutzerkonto.',
                    ])
                    ->withInput();
            }
        }

        RecurringTransaction::create([
            'user_id' => $request->user()->id,
            'account_id' => $validated['account_id'],
            'category_id' => $validated['category_id'] ?? null,
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'type' => $validated['type'],
            'frequency' => $validated['frequency'],
            'next_date' => $validated['next_date'],
            'end_date' => $validated['end_date'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('recurring-transactions.index')
            ->with(
                'success',
                'Die wiederkehrende Buchung wurde erfolgreich erstellt.'
            );
    }


    /**
     * Details einer wiederkehrenden Buchung anzeigen.
     */
    public function show(Request $request, RecurringTransaction $recurringTransaction)
    {
        $this->authorizeOwnership($request, $recurringTransaction);

        $recurringTransaction->load([
            'account',
            'category',
        ]);

        return view('recurring_transactions.show', [
            'recurringTransaction' => $recurringTransaction,
        ]);
    }


    /**
     * Formular zum Bearbeiten anzeigen.
     */
    public function edit(Request $request, RecurringTransaction $recurringTransaction)
    {
        $this->authorizeOwnership($request, $recurringTransaction);

        $accounts = Account::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get();

        $categories = Category::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get();

        return view('recurring_transactions.edit', [
            'recurringTransaction' => $recurringTransaction,
            'accounts' => $accounts,
            'categories' => $categories,
        ]);
    }


    /**
     * Wiederkehrende Buchung aktualisieren.
     */
    public function update(
        Request $request,
        RecurringTransaction $recurringTransaction
    ) {
        $this->authorizeOwnership($request, $recurringTransaction);

        $validated = $request->validate([
            'account_id' => [
                'required',
                'integer',
                'exists:accounts,id',
            ],

            'category_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],

            'description' => [
                'required',
                'string',
                'max:255',
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'type' => [
                'required',
                'in:income,expense',
            ],

            'frequency' => [
                'required',
                'in:weekly,monthly,quarterly,yearly',
            ],

            'next_date' => [
                'required',
                'date',
            ],

            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:next_date',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ], [
            'account_id.required' =>
                'Bitte wähle ein Konto aus.',

            'account_id.exists' =>
                'Das ausgewählte Konto ist nicht gültig.',

            'category_id.exists' =>
                'Die ausgewählte Kategorie ist nicht gültig.',

            'description.required' =>
                'Bitte gib eine Bezeichnung ein.',

            'description.max' =>
                'Die Bezeichnung darf maximal 255 Zeichen lang sein.',

            'amount.required' =>
                'Bitte gib einen Betrag ein.',

            'amount.numeric' =>
                'Der Betrag muss eine gültige Zahl sein.',

            'amount.min' =>
                'Der Betrag muss größer als 0 sein.',

            'type.required' =>
                'Bitte wähle Einnahme oder Ausgabe.',

            'type.in' =>
                'Der ausgewählte Buchungstyp ist nicht gültig.',

            'frequency.required' =>
                'Bitte wähle ein Intervall.',

            'frequency.in' =>
                'Das ausgewählte Intervall ist nicht gültig.',

            'next_date.required' =>
                'Bitte gib das Datum der nächsten Ausführung an.',

            'next_date.date' =>
                'Das Datum der nächsten Ausführung ist nicht gültig.',

            'end_date.date' =>
                'Das Enddatum ist nicht gültig.',

            'end_date.after_or_equal' =>
                'Das Enddatum muss am oder nach der nächsten Ausführung liegen.',
        ]);

        $accountExists = Account::query()
            ->where('id', $validated['account_id'])
            ->where('user_id', $request->user()->id)
            ->exists();

        if (! $accountExists) {
            return back()
                ->withErrors([
                    'account_id' => 'Das ausgewählte Konto gehört nicht zu deinem Benutzerkonto.',
                ])
                ->withInput();
        }

        if (! empty($validated['category_id'])) {

            $categoryExists = Category::query()
                ->where('id', $validated['category_id'])
                ->where('user_id', $request->user()->id)
                ->exists();

            if (! $categoryExists) {
                return back()
                    ->withErrors([
                        'category_id' => 'Die ausgewählte Kategorie gehört nicht zu deinem Benutzerkonto.',
                    ])
                    ->withInput();
            }
        }

        $recurringTransaction->update([
            'account_id' => $validated['account_id'],
            'category_id' => $validated['category_id'] ?? null,
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'type' => $validated['type'],
            'frequency' => $validated['frequency'],
            'next_date' => $validated['next_date'],
            'end_date' => $validated['end_date'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('recurring-transactions.index')
            ->with(
                'success',
                'Die wiederkehrende Buchung wurde erfolgreich aktualisiert.'
            );
    }


    /**
     * Wiederkehrende Buchung löschen.
     */
    public function destroy(
        Request $request,
        RecurringTransaction $recurringTransaction
    ) {
        $this->authorizeOwnership($request, $recurringTransaction);

        $recurringTransaction->delete();

        return redirect()
            ->route('recurring-transactions.index')
            ->with(
                'success',
                'Die wiederkehrende Buchung wurde gelöscht.'
            );
    }


    /**
     * Eigentümer prüfen.
     */
    private function authorizeOwnership(
        Request $request,
        RecurringTransaction $recurringTransaction
    ): void {
        abort_unless(
            $recurringTransaction->user_id === $request->user()->id,
            403
        );
    }
}
