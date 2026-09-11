<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

            $search = trim($request->input('search'));

            $query->where(function ($q) use ($search) {

                $q->where('description', 'ILIKE', "%{$search}%")
                    ->orWhere('merchant', 'ILIKE', "%{$search}%");

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

            $accountId = $request->input('account_id');

            $query->where('account_id', $accountId);

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
            'categories'
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

        return view('transactions.create', compact(
            'accounts',
            'categories'
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


        return view('transactions.edit', compact(
            'transaction',
            'accounts',
            'categories'
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

        $transaction->save();


        return redirect()
            ->route('transactions.index')
            ->with('success', 'Buchung wurde aktualisiert.');
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