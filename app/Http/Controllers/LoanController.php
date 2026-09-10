<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\FinancialProvider;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Services\LoanAmortizationService;
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
            ->with(['account', 'provider'])
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
            ? min(
                100,
                max(
                    0,
                    ($totalPaid / $totalPrincipal) * 100
                )
            )
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

        $providers = FinancialProvider::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view(
            'loans.create',
            compact('accounts', 'providers')
        );
    }

    /**
     * Kredit speichern.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'creditor_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'provider_id' => [
                'nullable',
                'integer',
                'exists:financial_providers,id',
            ],

            'creditor_icon' => [
                'nullable',
                'string',
                'max:20',
            ],

            'creditor_color' => [
                'nullable',
                'string',
                'max:20',
            ],

            'account_id' => [
                'nullable',
                'integer',
            ],

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

        /*
         * Prüfen, ob das gewählte Konto
         * tatsächlich dem angemeldeten Benutzer gehört.
         */
        if (!empty($validated['account_id'])) {
            Account::where('user_id', auth()->id())
                ->findOrFail($validated['account_id']);
        }

        $validated['user_id'] = auth()->id();

        $validated['paid_amount'] =
            $validated['paid_amount'] ?? 0;

        $validated['paid_installments'] =
            $validated['paid_installments'] ?? 0;

        $validated['is_active'] =
            $request->boolean(
                'is_active',
                true
            );

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
                'Kredit wurde erfolgreich angelegt.'
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
            'provider',
            'payments.transaction',
        ]);

        $payments = $loan->payments()
            ->orderBy('installment_number')
            ->get();

        $regularPayments = $payments
            ->where(
                'payment_type',
                'regular'
            );

        $extraPayments = $payments
            ->where(
                'payment_type',
                'extra'
            );

        return view(
            'loans.show',
            compact(
                'loan',
                'payments',
                'regularPayments',
                'extraPayments'
            )
        );
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

        $providers = FinancialProvider::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view(
            'loans.edit',
            compact(
                'loan',
                'accounts',
                'providers'
            )
        );
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
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'creditor_name' => [
                'nullable',
                'string',
                'max:255',
            ],


            'provider_id' => [
                'nullable',
                'integer',
                'exists:financial_providers,id',
            ],

            'creditor_icon' => [
                'nullable',
                'string',
                'max:20',
            ],

            'creditor_color' => [
                'nullable',
                'string',
                'max:20',
            ],

            'account_id' => [
                'nullable',
                'integer',
            ],

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

        /*
         * Konto-Zugriff absichern.
         */
        if (!empty($validated['account_id'])) {
            Account::where('user_id', auth()->id())
                ->findOrFail($validated['account_id']);
        }

        $validated['is_active'] =
            $request->boolean('is_active');

        $loan->update($validated);

        return redirect()
            ->route(
                'loans.show',
                $loan
            )
            ->with(
                'success',
                'Kredit wurde aktualisiert.'
            );
    }

    /**
     * Kredit löschen.
     */
    public function destroy(
        Loan $loan
    ): RedirectResponse {
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

        DB::transaction(function () use (
            $loan,
            $validated,
            $amount
        ) {
            /*
             * Kredit innerhalb der Transaktion erneut laden
             * und sperren. Dadurch können parallele
             * Sondertilgungen nicht dieselbe Restschuld
             * gleichzeitig verwenden.
             */
            $lockedLoan = Loan::whereKey($loan->id)
                ->lockForUpdate()
                ->firstOrFail();

            /*
             * Aktuelle Restschuld anhand des gesperrten
             * Datensatzes berechnen.
             */
            $remainingAmount = max(
                0,
                (float) $lockedLoan->principal_amount
                    - (float) $lockedLoan->paid_amount
            );

            /*
             * Sondertilgung darf die Restschuld
             * nicht überschreiten.
             */
            if ($amount > $remainingAmount) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'amount' => 'Die Sondertilgung darf nicht größer als die aktuelle Restschuld sein.',
                ]);
            }

            /*
             * Nächste freie Nummer verwenden.
             *
             * Sondertilgungen werden bewusst hinter den
             * normalen Raten geführt.
             */
            $nextNumber = (
                (int) $lockedLoan->payments()
                    ->max('installment_number')
            ) + 1;

            LoanPayment::create([
                'loan_id' => $lockedLoan->id,

                'transaction_id' => null,

                'installment_number' => $nextNumber,

                'due_date' => $validated['paid_date'],

                'amount' => $amount,

                'payment_type' => 'extra',

                'paid_date' => $validated['paid_date'],

                'status' => 'paid',

                'notes' => $validated['notes'] ?? null,
            ]);

            /*
             * Gesamte bisherige Tilgung erhöhen.
             */
            $newPaidAmount = min(
                (float) $lockedLoan->principal_amount,
                (float) $lockedLoan->paid_amount + $amount
            );

            /*
             * Ist der Kredit vollständig getilgt,
             * wird er automatisch deaktiviert.
             */
            $lockedLoan->update([
                'paid_amount' => $newPaidAmount,
                'is_active' => $newPaidAmount
                    < (float) $lockedLoan->principal_amount,
            ]);

            /*
             * Bei vollständiger Tilgung werden alle noch
             * geplanten zukünftigen Raten storniert.
             *
             * Bereits bezahlte Raten und die Sondertilgung
             * selbst bleiben unverändert.
             */
            if (
                $newPaidAmount >=
                (float) $lockedLoan->principal_amount
            ) {
                $lockedLoan->payments()
                    ->where('status', 'planned')
                    ->update([
                        'status' => 'cancelled',
                    ]);
            } else {
                /*
                 * Variante A:
                 *
                 * Die Monatsrate und der Zinssatz bleiben gleich.
                 * Durch die reduzierte Restschuld wird der
                 * zukünftige Zahlungsplan neu berechnet.
                 *
                 * Nur zukünftige geplante Raten werden ersetzt.
                 */
                $this->regenerateFuturePaymentPlan(
                    $lockedLoan
                );
            }
        });

        return redirect()
            ->route(
                'loans.show',
                $loan
            )
            ->with(
                'success',
                'Sondertilgung wurde erfolgreich erfasst.'
            );
    }

    /**
     * Zukünftigen Tilgungsplan nach einer Sondertilgung
     * neu berechnen.
     *
     * Variante A:
     * - Monatsrate bleibt unverändert.
     * - Zinssatz bleibt unverändert.
     * - Restschuld wird reduziert.
     * - Laufzeit verkürzt sich.
     * - Historische Zahlungen bleiben unverändert.
     */
    private function regenerateFuturePaymentPlan(
        Loan $loan
    ): void {
        /*
         * Ohne Rate oder Zinssatz kann kein
         * amortisierter Zahlungsplan berechnet werden.
         */
        if (
            !$loan->installment_amount ||
            (float) $loan->installment_amount <= 0
        ) {
            return;
        }

        /*
         * Alle zukünftigen geplanten Raten entfernen.
         *
         * Bereits bezahlte Raten und Sondertilgungen
         * werden nicht verändert.
         */
        $loan->payments()
            ->where('status', 'planned')
            ->where('payment_type', 'regular')
            ->delete();

        /*
         * Aktuelle Restschuld nach der Sondertilgung.
         */
        $remainingPrincipal = max(
            0,
            (float) $loan->principal_amount
                - (float) $loan->paid_amount
        );

        if ($remainingPrincipal <= 0) {
            return;
        }

        /*
         * Neue Amortisation ab der aktuellen Restschuld.
         *
         * Die ursprüngliche Monatsrate und der Zinssatz
         * des Kredits bleiben unverändert.
         */
        $amortizationService = app(
            LoanAmortizationService::class
        );

        $plan = $amortizationService->calculateFromBalance(
            $remainingPrincipal,
            (float) ($loan->interest_rate ?? 0),
            (float) $loan->installment_amount,
            null
        );

        /*
         * Die nächste freie Ratenummer ermitteln.
         *
         * Reguläre Raten und Sondertilgungen teilen sich
         * aufgrund des UNIQUE-Index denselben Nummernraum.
         * Deshalb muss über ALLE vorhandenen Zahlungen
         * gesucht werden.
         */
        $nextRegularNumber = (
            (int) $loan->payments()
                ->max('installment_number')
        ) + 1;

        /*
         * Neue reguläre Raten anlegen.
         */
        foreach ($plan as $payment) {
            $installmentNumber =
                $nextRegularNumber
                + $payment['installment_number']
                - 1;

            $dueDate = $loan->start_date
                ? $loan->start_date
                    ->copy()
                    ->addMonths($installmentNumber - 1)
                : now()->startOfMonth()
                    ->addMonths($payment['installment_number'] - 1);

            LoanPayment::create([
                'loan_id' => $loan->id,

                'transaction_id' => null,

                'installment_number' =>
                    $installmentNumber,

                'due_date' => $dueDate,

                'amount' => $payment['amount'],

                'interest_amount' =>
                    $payment['interest_amount'],

                'principal_amount' =>
                    $payment['principal_amount'],

                'remaining_amount' =>
                    $payment['remaining_amount'],

                'payment_type' => 'regular',

                'paid_date' => null,

                'status' => 'planned',

                'notes' => null,
            ]);
        }
    }

    /**
     * Tilgungsplan erzeugen.
     */
    private function generatePaymentPlan(
        Loan $loan
    ): void {
        /*
         * Ohne Startdatum, Ratenanzahl oder Rate
         * kann kein Tilgungsplan erzeugt werden.
         */
        if (
            !$loan->start_date ||
            !$loan->total_installments ||
            !$loan->installment_amount
        ) {
            return;
        }

        /*
         * Bereits vorhandene Zahlungen vermeiden.
         */
        $existingNumbers = $loan->payments()
            ->pluck('installment_number')
            ->map(fn ($number) => (int) $number)
            ->all();

        /*
         * Der Amortisationsservice berechnet den
         * Zahlungsplan ab der ursprünglichen Kreditsumme.
         *
         * Sondertilgungen werden später separat behandelt.
         */
        $amortizationService = app(
            LoanAmortizationService::class
        );

        $plan = $amortizationService->calculate($loan);

        $startDate = $loan->start_date->copy();

        $paidInstallments = (int) $loan->paid_installments;

        foreach ($plan as $payment) {
            $number = $payment['installment_number'];

            /*
             * Falls die Rate bereits existiert,
             * nichts neu anlegen.
             */
            if (
                in_array(
                    $number,
                    $existingNumbers,
                    true
                )
            ) {
                continue;
            }

            $dueDate = $startDate
                ->copy()
                ->addMonths($number - 1);

            $status = $number <= $paidInstallments
                ? 'paid'
                : 'planned';

            $paidDate = $status === 'paid'
                ? $dueDate
                : null;

            LoanPayment::create([
                'loan_id' => $loan->id,

                'transaction_id' => null,

                'installment_number' => $number,

                'due_date' => $dueDate,

                'amount' => $payment['amount'],

                'interest_amount' =>
                    $payment['interest_amount'],

                'principal_amount' =>
                    $payment['principal_amount'],

                'remaining_amount' =>
                    $payment['remaining_amount'],

                'payment_type' => 'regular',

                'paid_date' => $paidDate,

                'status' => $status,
            ]);
        }
    }

    /**
     * Route-Sicherheit.
     */
    private function authorizeLoan(
        Loan $loan
    ): void {
        abort_unless(
            $loan->user_id === auth()->id(),
            403
        );
    }
}