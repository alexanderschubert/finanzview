<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    /**
     * Liste der Buchungen
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | Buchungen
        |--------------------------------------------------------------------------
        */

        $query = Transaction::query()
            ->where('user_id', $user->id)
            ->with([
                'account',
                'category',
                'transferAccount',
            ])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');


        /*
        |--------------------------------------------------------------------------
        | Suche
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search = mb_strtolower(trim($request->input('search')));

            /*
             * LOWER() + LIKE statt ILIKE, damit die Suche
             * sowohl mit PostgreSQL als auch mit SQLite (Tests)
             * ohne Groß-/Kleinschreibung funktioniert.
             */
            $query->where(function ($q) use ($search) {

                $q->whereRaw('LOWER(description) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(merchant) LIKE ?', ["%{$search}%"]);

            });

        }


        /*
        |--------------------------------------------------------------------------
        | Einnahme / Ausgabe
        |--------------------------------------------------------------------------
        */

        if ($request->filled('type')) {

            $type = $request->input('type');

            if (in_array($type, ['income', 'expense', 'transfer'], true)) {

                $query->where('type', $type);

            }

        }


        /*
        |--------------------------------------------------------------------------
        | Konto
        |--------------------------------------------------------------------------
        */

        if ($request->filled('account_id')) {

            $accountId = (int) $request->input('account_id');

            /*
             * Auch Umbuchungen auf das Konto anzeigen.
             */
            $query->where(function ($q) use ($accountId) {

                $q->where('account_id', $accountId)
                    ->orWhere('transfer_account_id', $accountId);

            });

        }


        /*
        |--------------------------------------------------------------------------
        | Kategorie
        |--------------------------------------------------------------------------
        */

        if ($request->filled('category_id')) {

            $query->where('category_id', (int) $request->input('category_id'));

        }


        /*
        |--------------------------------------------------------------------------
        | Monat
        |--------------------------------------------------------------------------
        */

        if ($request->filled('month')) {

            $month = $request->input('month');

            /*
             * Erwartetes Format:
             * YYYY-MM
             */

            if (preg_match('/^\d{4}-\d{2}$/', $month)) {

                $query
                    ->whereYear('transaction_date', substr($month, 0, 4))
                    ->whereMonth('transaction_date', substr($month, 5, 2));

            }

        }


        /*
        |--------------------------------------------------------------------------
        | Summen
        |--------------------------------------------------------------------------
        |
        | Über alle gefilterten Buchungen (nicht nur die aktuelle Seite).
        | Umbuchungen zählen weder als Einnahme noch als Ausgabe.
        */

        $totals = (clone $query)
            ->setEagerLoads([])
            ->reorder()
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS income,
                 COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS expense"
            )
            ->first();

        $totalIncome = (float) $totals->income;
        $totalExpense = (float) $totals->expense;


        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $transactions = $query
            ->paginate(25)
            ->withQueryString();


        /*
        |--------------------------------------------------------------------------
        | Konten für Filter
        |--------------------------------------------------------------------------
        */

        $accounts = Account::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Kategorien
        |--------------------------------------------------------------------------
        */

        $categories = Category::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();


        return view('transactions.index', compact(
            'transactions',
            'accounts',
            'categories',
            'totalIncome',
            'totalExpense'
        ));
    }


    /**
     * Formular für neue Buchung
     */
    public function create()
    {
        $user = Auth::user();

        $accounts = Account::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $categories = Category::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        $creditCards = $this->creditCardsFor($user->id);

        return view('transactions.create', compact(
            'accounts',
            'categories',
            'creditCards'
        ));
    }


    /**
     * Neue Buchung speichern
     */
    public function store(Request $request)
    {
        $user = Auth::user();


        /*
        |--------------------------------------------------------------------------
        | Validierung
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([

            'account_id' => [
                'required',
                'integer',
                'exists:accounts,id',
            ],

            'transfer_account_id' => [
                'nullable',
                'integer',
                'exists:accounts,id',
            ],

            'category_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],

            'type' => [
                'required',
                'in:income,expense,transfer',
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'transaction_date' => [
                'required',
                'date',
            ],

            'description' => [
                'required',
                'string',
                'max:255',
            ],

            'merchant' => [
                'nullable',
                'string',
                'max:255',
            ],

            'notes' => [
                'nullable',
                'string',
            ],

            'is_pending' => [
                'nullable',
                'boolean',
            ],

            // Optionale Kreditkarte: muss dem Benutzer gehören
            // und aktiv sein.
            'credit_card_id' => [
                'nullable',
                'integer',
                Rule::exists('credit_cards', 'id')
                    ->where('user_id', $user->id)
                    ->where('is_active', true)
                    ->whereNull('deleted_at'),
            ],

        ]);


        /*
        |--------------------------------------------------------------------------
        | Konto gehört wirklich zum Benutzer?
        |--------------------------------------------------------------------------
        */

        $account = Account::query()
            ->where('id', $validated['account_id'])
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();


        if (!$account) {

            return back()
                ->withErrors([
                    'account_id' => 'Das ausgewählte Konto ist ungültig.',
                ])
                ->withInput();

        }


        /*
        |--------------------------------------------------------------------------
        | Transfer prüfen
        |--------------------------------------------------------------------------
        */

        $transferAccountId = $validated['transfer_account_id'] ?? null;

        if ($validated['type'] === 'transfer') {

            if ($transferAccountId === null) {
                return back()
                    ->withErrors([
                        'transfer_account_id' =>
                            'Bei einer Umbuchung muss ein Zielkonto ausgewählt werden.',
                    ])
                    ->withInput();
            }

            if ((int) $transferAccountId === (int) $validated['account_id']) {
                return back()
                    ->withErrors([
                        'transfer_account_id' =>
                            'Quell- und Zielkonto dürfen nicht identisch sein.',
                    ])
                    ->withInput();
            }

            $transferAccountExists = Account::query()
                ->whereKey($transferAccountId)
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->exists();

            if (!$transferAccountExists) {
                return back()
                    ->withErrors([
                        'transfer_account_id' =>
                            'Das ausgewählte Zielkonto ist ungültig.',
                    ])
                    ->withInput();
            }

        } else {
            $transferAccountId = null;
        }


        /*
        |--------------------------------------------------------------------------
        | Kategorie prüfen
        |--------------------------------------------------------------------------
        */

        if (!empty($validated['category_id'])) {

            $category = Category::query()
                ->where('id', $validated['category_id'])
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->first();


            if (!$category) {

                return back()
                    ->withErrors([
                        'category_id' => 'Die ausgewählte Kategorie ist ungültig.',
                    ])
                    ->withInput();

            }


            /*
             * Kategorie muss zum Buchungstyp passen.
             *
             * expense -> expense oder both
             * income  -> income oder both
             */

            if (
                $category->type !== 'both'
                && $category->type !== $validated['type']
            ) {

                return back()
                    ->withErrors([
                        'category_id' => 'Die Kategorie passt nicht zur Art der Buchung.',
                    ])
                    ->withInput();

            }

        }


        /*
        |--------------------------------------------------------------------------
        | Buchung erstellen
        |--------------------------------------------------------------------------
        */

        $transaction = new Transaction();

        $transaction->user_id = $user->id;
        $transaction->account_id = $validated['account_id'];
        $transaction->transfer_account_id = $transferAccountId;
        $transaction->category_id = $validated['category_id'] ?? null;
        $transaction->type = $validated['type'];
        $transaction->amount = $validated['amount'];
        $transaction->transaction_date = $validated['transaction_date'];
        $transaction->description = $validated['description'];
        $transaction->merchant = $validated['merchant'] ?? null;
        $transaction->notes = $validated['notes'] ?? null;
        $transaction->is_pending = $request->boolean('is_pending');

        // Kreditkarte nur bei Einnahmen/Ausgaben, nie bei Umbuchungen.
        $transaction->credit_card_id =
            $validated['type'] === 'transfer'
                ? null
                : ($validated['credit_card_id'] ?? null);

        $transaction->save();


        return redirect()
            ->route('transactions.index')
            ->with('success', 'Buchung wurde erfolgreich erstellt.');
    }


    /**
     * Buchung bearbeiten
     */
    public function edit(Transaction $transaction)
    {
        $user = Auth::user();

        abort_unless(
            $transaction->user_id === $user->id,
            403
        );


        $accounts = Account::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();


        $categories = Category::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get();


        // Aktive Kreditkarten plus ggf. die aktuell verknüpfte
        // (auch wenn diese inzwischen deaktiviert wurde).
        $creditCards = $this->creditCardsFor(
            $user->id,
            $transaction->credit_card_id
        );

        return view('transactions.edit', compact(
            'transaction',
            'accounts',
            'categories',
            'creditCards'
        ));
    }


    /**
     * Buchung aktualisieren
     */
    public function update(Request $request, Transaction $transaction)
    {
        $user = Auth::user();


        abort_unless(
            $transaction->user_id === $user->id,
            403
        );


        $validated = $request->validate([

            'account_id' => [
                'required',
                'integer',
                'exists:accounts,id',
            ],

            'transfer_account_id' => [
                'nullable',
                'integer',
                'exists:accounts,id',
            ],

            'category_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],

            'type' => [
                'required',
                'in:income,expense,transfer',
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'transaction_date' => [
                'required',
                'date',
            ],

            'description' => [
                'required',
                'string',
                'max:255',
            ],

            'merchant' => [
                'nullable',
                'string',
                'max:255',
            ],

            'notes' => [
                'nullable',
                'string',
            ],

            'is_pending' => [
                'nullable',
                'boolean',
            ],

            // Optionale Kreditkarte: muss dem Benutzer gehören.
            // Inaktive Karten sind nur erlaubt, wenn sie bereits
            // mit dieser Buchung verknüpft sind.
            'credit_card_id' => [
                'nullable',
                'integer',
                Rule::exists('credit_cards', 'id')
                    ->where('user_id', $user->id)
                    ->whereNull('deleted_at')
                    ->where(function ($query) use ($transaction) {
                        $query->where('is_active', true);

                        if ($transaction->credit_card_id !== null) {
                            $query->orWhere(
                                'id',
                                $transaction->credit_card_id
                            );
                        }
                    }),
            ],

        ]);


        /*
        |--------------------------------------------------------------------------
        | Konto des Benutzers prüfen
        |--------------------------------------------------------------------------
        */

        $accountExists = Account::query()
            ->where('id', $validated['account_id'])
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();


        if (!$accountExists) {

            return back()
                ->withErrors([
                    'account_id' => 'Das ausgewählte Konto ist ungültig.',
                ])
                ->withInput();

        }


        /*
        |--------------------------------------------------------------------------
        | Transfer-Zielkonto prüfen
        |--------------------------------------------------------------------------
        */

        $transferAccountId = $validated['transfer_account_id'] ?? null;

        if ($validated['type'] === 'transfer') {

            if ($transferAccountId === null) {
                return back()
                    ->withErrors([
                        'transfer_account_id' =>
                            'Bei einer Umbuchung muss ein Zielkonto ausgewählt werden.',
                    ])
                    ->withInput();
            }

            if ((int) $transferAccountId === (int) $validated['account_id']) {
                return back()
                    ->withErrors([
                        'transfer_account_id' =>
                            'Quell- und Zielkonto dürfen nicht identisch sein.',
                    ])
                    ->withInput();
            }

            $transferAccountExists = Account::query()
                ->whereKey($transferAccountId)
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->exists();

            if (!$transferAccountExists) {
                return back()
                    ->withErrors([
                        'transfer_account_id' =>
                            'Das ausgewählte Zielkonto ist ungültig.',
                    ])
                    ->withInput();
            }

        } else {
            $transferAccountId = null;
        }


        /*
        |--------------------------------------------------------------------------
        | Kategorie des Benutzers prüfen
        |--------------------------------------------------------------------------
        */

        if (!empty($validated['category_id'])) {

            $category = Category::query()
                ->where('id', $validated['category_id'])
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->first();


            if (!$category) {

                return back()
                    ->withErrors([
                        'category_id' => 'Die ausgewählte Kategorie ist ungültig.',
                    ])
                    ->withInput();

            }


            if (
                $category->type !== 'both'
                && $category->type !== $validated['type']
            ) {

                return back()
                    ->withErrors([
                        'category_id' => 'Die Kategorie passt nicht zur Art der Buchung.',
                    ])
                    ->withInput();

            }

        }


        /*
        |--------------------------------------------------------------------------
        | Aktualisieren
        |--------------------------------------------------------------------------
        */

        $transaction->account_id = $validated['account_id'];
        $transaction->transfer_account_id = $transferAccountId;
        $transaction->category_id = $validated['category_id'] ?? null;
        $transaction->type = $validated['type'];
        $transaction->amount = $validated['amount'];
        $transaction->transaction_date = $validated['transaction_date'];
        $transaction->description = $validated['description'];
        $transaction->merchant = $validated['merchant'] ?? null;
        $transaction->notes = $validated['notes'] ?? null;
        $transaction->is_pending = $request->boolean('is_pending');

        // Kreditkarte nur bei Einnahmen/Ausgaben, nie bei Umbuchungen.
        $transaction->credit_card_id =
            $validated['type'] === 'transfer'
                ? null
                : ($validated['credit_card_id'] ?? null);

        $transaction->save();


        return redirect()
            ->route('transactions.index')
            ->with('success', 'Buchung wurde aktualisiert.');
    }


    /**
     * Kreditkarten des Benutzers für die Formulare.
     *
     * Liefert alle aktiven Karten und optional zusätzlich
     * eine bestimmte (z. B. inaktive, aber bereits verknüpfte) Karte.
     */
    private function creditCardsFor(int $userId, ?int $includeId = null)
    {
        return CreditCard::query()
            ->where('user_id', $userId)
            ->where(function ($query) use ($includeId) {
                $query->where('is_active', true);

                if ($includeId !== null) {
                    $query->orWhere('id', $includeId);
                }
            })
            ->orderBy('name')
            ->get();
    }


    /**
     * Buchung löschen
     */
    public function destroy(Transaction $transaction)
    {
        $user = Auth::user();


        abort_unless(
            $transaction->user_id === $user->id,
            403
        );


        $transaction->delete();


        return redirect()
            ->route('transactions.index')
            ->with('success', 'Buchung wurde gelöscht.');
    }
}