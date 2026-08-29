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
     * Kredit speichern.
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
            'installment_amount' => ['required', 'numeric', 'min:0.01'],

            'total_installments' => ['nullable', 'integer', 'min:1'],
            'paid_installments' => ['nullable', 'integer', 'min:0'],

            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],

            'type' => [
                'required',
                'in:loan,installment,paypal_installment,other',
            ],

            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        if (!empty($validated['account_id'])) {
            Account::where('user_id', auth()->id())
                ->findOrFail($validated['account_id']);
        }

        $validated['user_id'] = auth()->id();
        $validated['paid_amount'] = $validated['paid_amount'] ?? 0;
        $validated['paid_installments'] =
            $validated['paid_installments'] ?? 0;
        $validated['is_active'] =
            $request->boolean('is_active', true);

        $loan = Loan::create($validated);

        return redirect()
            ->route('loans.show', $loan)
            ->with('success', 'Kredit wurde erfolgreich angelegt.');
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
            'principal_amount' => ['required', 'numeric', 'min:0.01'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'interest_rate' => ['nullable', 'numeric', 'min:0'],
            'installment_amount' => ['required', 'numeric', 'min:0.01'],

            'total_installments' => ['nullable', 'integer', 'min:1'],
            'paid_installments' => ['nullable', 'integer', 'min:0'],

            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],

            'type' => [
                'required',
                'in:loan,installment,paypal_installment,other',
            ],

            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        if (!empty($validated['account_id'])) {
            Account::where('user_id', auth()->id())
                ->findOrFail($validated['account_id']);
        }

        $validated['is_active'] =
            $request->boolean('is_active');

        $loan->update($validated);

        return redirect()
            ->route('loans.show', $loan)
            ->with('success', 'Kredit wurde aktualisiert.');
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
            ->with('success', 'Kredit wurde gelöscht.');
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
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use (
            $loan,
            $validated
        ) {
            $nextNumber = ((int) $loan->payments()->max(
                'installment_number'
            )) + 1;

            LoanPayment::create([
                'loan_id' => $loan->id,
                'transaction_id' => null,
                'installment_number' => $nextNumber,
                'due_date' => $validated['paid_date'],
                'amount' => $validated['amount'],
                'payment_type' => 'extra',
                'paid_date' => $validated['paid_date'],
                'status' => 'paid',
            ]);

            $loan->increment(
                'paid_amount',
                $validated['amount']
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