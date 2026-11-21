<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

/**
 * Kredite und Tilgungsplan für die native App (nur lesen).
 */
class LoanController extends Controller
{
    public function index(Request $request)
    {
        $loans = $request->user()->loans()->with('provider')->orderBy('name')->get();

        return response()->json(['data' => $loans->map(fn (Loan $loan) => $this->summary($loan))->values()]);
    }

    public function show(Request $request, int $id)
    {
        $loan = $request->user()->loans()->with('provider')->findOrFail($id);
        $payments = $loan->payments()->orderBy('installment_number')->get();
        $regular = $payments->where('payment_type', 'regular');

        return response()->json($this->summary($loan) + [
            'contract' => [
                'effective_rate' => $loan->effective_rate !== null ? (float) $loan->effective_rate : null,
                'contract_total' => $loan->contract_total !== null ? round((float) $loan->contract_total, 2) : null,
                'financed_costs' => $loan->financed_costs !== null ? round((float) $loan->financed_costs, 2) : null,
            ],
            'totals' => [
                'interest' => round((float) $regular->sum('interest_amount'), 2),
                'principal' => round((float) $regular->sum('principal_amount'), 2),
                'total' => round((float) $regular->sum('amount'), 2),
                'count' => $regular->count(),
            ],
            'schedule' => $payments->map(fn ($payment) => [
                'id' => $payment->id,
                'number' => $payment->installment_number,
                'date' => $payment->due_date?->toDateString(),
                'amount' => round((float) $payment->amount, 2),
                'interest' => round((float) $payment->interest_amount, 2),
                'principal' => round((float) $payment->principal_amount, 2),
                'remaining' => round((float) $payment->remaining_amount, 2),
                'type' => $payment->payment_type,
                'status' => $payment->status,
                'paid_date' => $payment->paid_date?->toDateString(),
            ])->values(),
        ]);
    }

    /**
     * Kredit ändern. Betrifft die Änderung den Tilgungsplan (Darlehen, Zins, Rate, Raten, Start),
     * werden die geplanten Raten wie in der Web-Oberfläche neu berechnet.
     */
    public function update(Request $request, int $id)
    {
        $loan = $request->user()->loans()->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'creditor_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'principal_amount' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'interest_rate' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'effective_rate' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'contract_total' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'financed_costs' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'installment_amount' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'total_installments' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ]);

        if (isset($validated['end_date'], $validated['start_date']) && $validated['end_date'] < $validated['start_date']) {
            return response()->json(['message' => 'Das Ende darf nicht vor dem Start liegen.', 'errors' => ['end_date' => ['Das Ende darf nicht vor dem Start liegen.']]], 422);
        }

        DB::transaction(function () use ($loan, $validated) {
            $loan->fill($validated);

            $scheduleChanged = $loan->isDirty(['principal_amount', 'interest_rate', 'installment_amount', 'total_installments', 'start_date']);

            $loan->save();

            if ($scheduleChanged) {
                app(\App\Http\Controllers\LoanController::class)->rebuildPlannedPayments($loan->fresh());
            }
        });

        return $this->show($request, $id);
    }

    private function summary(Loan $loan): array
    {
        $provider = $loan->provider;

        return [
            'id' => $loan->id,
            'name' => $loan->name,
            'creditor' => $loan->creditor_name,
            'type' => $loan->type,
            'is_active' => (bool) $loan->is_active,
            'principal' => round((float) $loan->principal_amount, 2),
            'paid' => round((float) $loan->paid_amount, 2),
            'remaining' => round((float) $loan->remaining_amount, 2),
            'progress' => round((float) $loan->progress, 1),
            'interest_rate' => $loan->interest_rate !== null ? (float) $loan->interest_rate : null,
            'installment_amount' => $loan->installment_amount !== null ? round((float) $loan->installment_amount, 2) : null,
            'total_installments' => $loan->total_installments,
            'paid_installments' => $loan->paid_installments,
            'start_date' => $loan->start_date?->toDateString(),
            'end_date' => $loan->end_date?->toDateString(),
            'provider' => $provider ? [
                'id' => $provider->id,
                'name' => $provider->name,
                'logo_url' => $provider->logo ? asset($provider->logo) : null,
                'color' => $provider->color,
            ] : null,
        ];
    }
}
