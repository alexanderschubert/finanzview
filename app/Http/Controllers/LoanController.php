<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Loan;
use App\Models\LoanPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LoanController extends Controller
{
    /**
     * Übersicht aller Kredite.
     */
    public function index(): View
    {
        $loans = Loan::where('user_id', auth()->id())
            ->with('account')
            ->withCount([
                'payments as paid_payment_count' => function ($query) {
                    $query->where('status', 'paid');
                },
            ])
            ->orderBy('is_active', 'desc')
            ->orderBy('name')
            ->get();

        $totalPrincipal = $loans->sum(
            fn (Loan $loan) => (float) $loan->principal_amount
        );

        $totalPaid = $loans->sum(
            fn (Loan $loan) => (float) $loan->paid_amount
        );

        $totalRemaining = max(
            0,
            $totalPrincipal - $totalPaid
        );

        $totalInstallments = $loans->sum(
            fn (Loan $loan) => (int) ($loan->total_installments ?? 0)
        );

        $paidInstallments = $loans->sum(
            fn (Loan $loan) => (int) $loan->paid_installments
        );

        $monthlyInstallments = $loans
            ->where('is_active', true)
            ->sum(
                fn (Loan $loan) => (float) $loan->installment_amount
            );

        $overallProgress = $totalPrincipal > 0
            ? min(100, max(0, ($totalPaid / $totalPrincipal) * 100))
            : 0;

        return view('loans.index', compact(
            'loans',
            'totalPrincipal',
            'totalPaid',
            'totalRemaining',
            'totalInstallments',
            'paidInstallments',
            'monthlyInstallments',
            'overallProgress'
        ));
    }


    /**
     * Formular zum Erstellen eines Kredits.
     */
    public function create(): View
    {
        $accounts = Account::where('user_id', auth()->id())
            ->orderBy('name')
            ->get();

        return view('loans.create', compact('accounts'));
    }


    /**
     * Kredit speichern und automatisch Tilgungsplan erstellen.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],

            'creditor_name' => ['nullable', 'string', 'max:255'],
            'creditor_icon' => ['nullable', 'string', 'max:20'],
            'creditor_color' => ['nullable', 'string', 'max:20'],

            'account_id' => ['nullable', 'integer'],

            'principal_amount' => ['required', 'numeric', 'min:0.01'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],

            'interest_rate' => ['nullable', 'numeric', 'min:0'],

            'installment_amount' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'total_installments' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'paid_installments' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'start_date' => [
                'nullable',
                'date',
            ],

            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],

            'type' => [
                'required',
                'in:loan,installment,paypal_installment,other',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ]);

        /*
         * Konto muss zum angemeldeten Benutzer gehören.
         */
        if (!empty($validated['account_id'])) {
            Account::where('user_id', auth()->id())
                ->findOrFail($validated['account_id']);
        }

        $validated['user_id'] = auth()->id();

        $validated['paid_amount'] =
            (float) ($validated['paid_amount'] ?? 0);

        $validated['paid_installments'] =
            (int) ($validated['paid_installments'] ?? 0);

        $validated['is_active'] =
            $request->boolean('is_active', true);

        /*
         * Bereits getilgter Betrag darf den Kreditbetrag
         * nicht überschreiten.
         */
        if ($validated['paid_amount'] > $validated['principal_amount']) {
            return back()
                ->withErrors([
                    'paid_amount' =>
                        'Der bereits getilgte Betrag darf nicht größer als der Kreditbetrag sein.',
                ])
                ->withInput();
        }

        /*
         * Bereits bezahlte Raten dürfen nicht größer
         * als die Gesamtzahl der Raten sein.
         */
        if (
            !empty($validated['total_installments']) &&
            $validated['paid_installments'] >
            $validated['total_installments']
        ) {
            return back()
                ->withErrors([
                    'paid_installments' =>
                        'Die bereits bezahlten Raten dürfen nicht größer als die Gesamtzahl der Raten sein.',
                ])
                ->withInput();
        }

        DB::transaction(function () use (
            $validated,
            &$loan
        ) {
            /*
             * Kredit anlegen.
             */
            $loan = Loan::create($validated);

            /*
             * Tilgungsplan erzeugen.
             */
            $this->generatePaymentPlan($loan);
        });

        return redirect()
            ->route('loans.show', $loan)
            ->with(
                'success',
                'Kredit wurde erfolgreich angelegt und der Tilgungsplan wurde erstellt.'
            );
    }


    /**
     * Kreditdetails.
     */
    public function show(Loan $loan): View
    {
        $this->authorizeLoan($loan);

        $loan->load([
            'account',
            'payments.transaction',
        ]);

        $payments = $loan->payments()
            ->orderBy('due_date')
            ->orderBy('installment_number')
            ->get();

        $regularPayments = $payments
            ->where('payment_type', 'regular');

        $extraPayments = $payments
            ->where('payment_type', 'extra');

        return view('loans.show', compact(
            'loan',
            'payments',
            'regularPayments',
            'extraPayments'
        ));
    }


    /**
     * Bearbeitungsformular.
     */
    public function edit(Loan $loan): View
    {
        $this->authorizeLoan($loan);

        $accounts = Account::where('user_id', auth()->id())
            ->orderBy('name')
            ->get();

        return view('loans.edit', compact(
            'loan',
            'accounts'
        ));
    }


    /**
     * Kredit aktualisieren.
     */
    public function update(
        Request $request,
        Loan $loan
    ): RedirectResponse {
        $this->authorizeLoan($loan);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],

            'creditor_name' => ['nullable', 'string', 'max:255'],
            'creditor_icon' => ['nullable', 'string', 'max:20'],
            'creditor_color' => ['nullable', 'string', 'max:20'],

            'account_id' => ['nullable', 'integer'],

            'principal_amount' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'paid_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'interest_rate' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'installment_amount' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'total_installments' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'paid_installments' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'start_date' => [
                'nullable',
                'date',
            ],

            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],

            'type' => [
                'required',
                'in:loan,installment,paypal_installment,other',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ]);

        if (!empty($validated['account_id'])) {
            Account::where('user_id', auth()->id())
                ->findOrFail($validated['account_id']);
        }

        if (
            isset($validated['paid_amount']) &&
            $validated['paid_amount'] >
            $validated['principal_amount']
        ) {
            return back()
                ->withErrors([
                    'paid_amount' =>
                        'Der bereits getilgte Betrag darf nicht größer als der Kreditbetrag sein.',
                ])
                ->withInput();
        }

        if (
            !empty($validated['total_installments']) &&
            isset($validated['paid_installments']) &&
            $validated['paid_installments'] >
            $validated['total_installments']
        ) {
            return back()
                ->withErrors([
                    'paid_installments' =>
                        'Die bereits bezahlten Raten dürfen nicht größer als die Gesamtzahl der Raten sein.',
                ])
                ->withInput();
        }

        $validated['is_active'] =
            $request->boolean('is_active');

        $loan->update($validated);

        return redirect()
            ->route('loans.show', $loan)
            ->with(
                'success',
                'Kredit wurde aktualisiert.'
            );
    }


    /**
     * Kredit löschen.
     */
    public function destroy(Loan $loan): RedirectResponse
    {
        $this->authorizeLoan($loan);

        $loan->delete();

        return redirect()
            ->route('loans.index')
            ->with(
                'success',
                'Kredit wurde gelöscht.'
            );
    }


    /**
     * Sondertilgung speichern.
     */
    public function storeExtraPayment(
        Request $request,
        Loan $loan
    ): RedirectResponse {
        $this->authorizeLoan($loan);

        $validated = $request->validate([
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'paid_date' => [
                'required',
                'date',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $amount = (float) $validated['amount'];

        /*
         * Eine Sondertilgung darf die Restschuld
         * nicht überschreiten.
         */
        if ($amount > $loan->remaining_amount) {
            return back()
                ->withErrors([
                    'amount' =>
                        'Die Sondertilgung darf nicht größer als die aktuelle Restschuld sein.',
                ])
                ->withInput();
        }

        DB::transaction(function () use (
            $loan,
            $validated,
            $amount
        ) {
            /*
             * Sondertilgungen bekommen eine eigene Nummer,
             * damit sie nicht als normale Rate gezählt werden.
             *
             * Negative Zahlen werden dafür verwendet.
             *
             * Beispiel:
             *
             * -1 = erste Sondertilgung
             * -2 = zweite Sondertilgung
             */
            $nextExtraNumber = ((int) $loan->payments()
                ->where('payment_type', 'extra')
                ->min('installment_number'));

            if ($nextExtraNumber >= 0 || $nextExtraNumber === null) {
                $nextExtraNumber = -1;
            } else {
                $nextExtraNumber--;
            }

            LoanPayment::create([
                'loan_id' => $loan->id,
                'transaction_id' => null,

                'installment_number' => abs($nextExtraNumber),

                'due_date' => $validated['paid_date'],

                'amount' => $amount,

                'payment_type' => 'extra',

                'paid_date' => $validated['paid_date'],

                'status' => 'paid',
            ]);

            /*
             * Gesamte Tilgung erhöhen.
             */
            $loan->increment(
                'paid_amount',
                $amount
            );
        });

        return redirect()
            ->route('loans.show', $loan)
            ->with(
                'success',
                'Sondertilgung wurde erfolgreich erfasst.'
            );
    }


    /**
     * Automatischen Tilgungsplan erzeugen.
     */
    private function generatePaymentPlan(Loan $loan): void
    {
        /*
         * Ohne Startdatum oder Anzahl der Raten
         * kann kein automatischer Plan erstellt werden.
         */
        if (
            !$loan->start_date ||
            !$loan->total_installments
        ) {
            return;
        }

        /*
         * Falls bereits ein Tilgungsplan existiert,
         * nichts doppelt anlegen.
         */
        if ($loan->payments()->exists()) {
            return;
        }

        $startDate = $loan->start_date->copy();

        $totalInstallments =
            (int) $loan->total_installments;

        $paidInstallments =
            min(
                (int) $loan->paid_installments,
                $totalInstallments
            );

        $installmentAmount =
            (float) $loan->installment_amount;

        for (
            $number = 1;
            $number <= $totalInstallments;
            $number++
        ) {
            $dueDate = $startDate
                ->copy()
                ->addMonths($number - 1);

            $isPaid = $number <= $paidInstallments;

            LoanPayment::create([
                'loan_id' => $loan->id,

                'transaction_id' => null,

                'installment_number' => $number,

                'due_date' => $dueDate,

                'amount' => $installmentAmount,

                'payment_type' => 'regular',

                'paid_date' => $isPaid
                    ? $dueDate
                    : null,

                'status' => $isPaid
                    ? 'paid'
                    : 'planned',
            ]);
        }
    }


    /**
     * Route-Sicherheit.
     */
    private function authorizeLoan(Loan $loan): void
    {
        abort_unless(
            $loan->user_id === auth()->id(),
            403
        );
    }
}