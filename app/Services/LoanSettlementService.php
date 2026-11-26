<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Raten automatisch abhaken, sobald ihr Fälligkeitstag erreicht ist.
 *
 * Eine fällige reguläre Rate gilt als bezahlt. Ihr Tilgungsanteil erhöht den
 * getilgten Betrag des Kredits (Restschuld sinkt), die Zahl bezahlter Raten steigt.
 *
 * Raten, die schon vor dem Anlegen des Kredits in FinanzView fällig waren, werden
 * nur abgehakt: Ihr Tilgungsanteil steckt bereits im Betrag, den man beim Anlegen
 * als „bereits getilgt“ eingetragen hat, und würde sonst doppelt zählen.
 */
class LoanSettlementService
{
    /**
     * @return array{loans: int, payments: int}
     */
    public function settleDue(?Carbon $today = null): array
    {
        $today = ($today ?? now())->copy()->startOfDay();
        $loans = 0;
        $payments = 0;

        Loan::query()
            ->where('is_active', true)
            ->whereHas('payments', fn ($q) => $this->due($q, $today))
            ->pluck('id')
            ->each(function (int $id) use ($today, &$loans, &$payments) {
                $count = $this->settleLoan($id, $today);

                if ($count > 0) {
                    $loans++;
                    $payments += $count;
                }
            });

        return ['loans' => $loans, 'payments' => $payments];
    }

    private function settleLoan(int $id, Carbon $today): int
    {
        return DB::transaction(function () use ($id, $today) {
            $loan = Loan::query()->whereKey($id)->lockForUpdate()->first();

            if (! $loan || ! $loan->is_active) {
                return 0;
            }

            $due = $this->due($loan->payments(), $today)->orderBy('installment_number')->get();

            if ($due->isEmpty()) {
                return 0;
            }

            $since = $loan->created_at?->copy()->startOfDay();
            $principal = 0.0;

            foreach ($due as $payment) {
                $payment->update(['status' => 'paid', 'paid_date' => $payment->due_date]);

                if (! $since || $payment->due_date->gte($since)) {
                    $principal += (float) $payment->principal_amount;
                }
            }

            $paid = min((float) $loan->principal_amount, (float) $loan->paid_amount + $principal);

            $loan->update([
                'paid_amount' => round($paid, 2),
                'paid_installments' => (int) $loan->paid_installments + $due->count(),
                'is_active' => $paid < (float) $loan->principal_amount,
            ]);

            return $due->count();
        });
    }

    private function due($query, Carbon $today)
    {
        return $query
            ->where('payment_type', 'regular')
            ->where('status', 'planned')
            ->whereDate('due_date', '<=', $today->toDateString());
    }
}
