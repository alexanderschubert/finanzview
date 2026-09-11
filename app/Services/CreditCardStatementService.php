<?php

namespace App\Services;

use App\Models\CreditCard;
use App\Models\CreditCardStatement;
use App\Models\Transaction;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use InvalidArgumentException;

class CreditCardStatementService
{
    /**
     * Berechnet den Abrechnungszeitraum für den Zyklus,
     * der den angegebenen Tag enthält.
     *
     * Beispiel:
     * billing_day = 15
     * date        = 2026-08-20
     *
     * Ergebnis:
     * 2026-08-16 bis 2026-09-15
     */
    public function periodFor(
        CreditCard $creditCard,
        CarbonInterface $date
    ): array {
        $billingDay = $creditCard->billing_day;

        if ($billingDay === null) {
            throw new InvalidArgumentException(
                'Die Kreditkarte besitzt keinen Abrechnungstag.'
            );
        }

        $date = $date->copy()->startOfDay();

        $billingDate = $this->billingDateForMonth(
            $date->year,
            $date->month,
            $billingDay
        );

        if ($date->greaterThan($billingDate)) {
            $nextMonth = $date->copy()->addMonthNoOverflow();

            $periodEnd = $this->billingDateForMonth(
                $nextMonth->year,
                $nextMonth->month,
                $billingDay
            );
        } else {
            $periodEnd = $billingDate;
        }

        $periodStart = $periodEnd
            ->copy()
            ->subMonthNoOverflow()
            ->addDay();

        $dueDate = $this->dueDateAfterPeriod(
            $periodEnd,
            $creditCard->payment_due_day
        );

        return [
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'due_date' => $dueDate,
        ];
    }

    /**
     * Erzeugt die Abrechnung für den Zyklus, der das angegebene
     * Datum enthält.
     *
     * Eine neue Abrechnung erhält direkt den aktuell berechneten
     * Betrag. Eine bereits vorhandene offene Abrechnung wird ebenfalls
     * aktualisiert.
     *
     * Bereits ausgegebene, bezahlte oder überfällige Abrechnungen
     * werden nicht automatisch verändert.
     */
    public function generateFor(
        CreditCard $creditCard,
        CarbonInterface $date
    ): CreditCardStatement {
        $period = $this->periodFor($creditCard, $date);

        $periodStart = $period['period_start']->copy()->startOfDay();
        $periodEnd = $period['period_end']->copy()->startOfDay();

        // Vorhandene Abrechnung suchen.
        // Die Spalten werden als DATETIME gespeichert, daher vergleichen
        // wir hier bewusst den vollständigen Zeitpunkt.
        $existing = CreditCardStatement::query()
            ->where('credit_card_id', $creditCard->id)
            ->where(
                'period_start',
                $periodStart->toDateTimeString()
            )
            ->where(
                'period_end',
                $periodEnd->toDateTimeString()
            )
            ->first();

        if ($existing !== null) {
            return $this->updateAmount($existing);
        }

        try {
            $statement = CreditCardStatement::query()->create([
                'credit_card_id' => $creditCard->id,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'due_date' => $period['due_date']?->startOfDay(),
                'amount' => 0,
                'status' => 'open',
            ]);

            return $this->updateAmount($statement);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Race Condition: Ein anderer Prozess hat die Abrechnung
            // zwischen SELECT und INSERT bereits angelegt.
            $existing = CreditCardStatement::query()
                ->where('credit_card_id', $creditCard->id)
                ->where(
                    'period_start',
                    $periodStart->toDateTimeString()
                )
                ->where(
                    'period_end',
                    $periodEnd->toDateTimeString()
                )
                ->first();

            if ($existing !== null) {
                return $this->updateAmount($existing);
            }

            throw $e;
        }
    }

    /**
     * Berechnet den aktuellen Betrag einer Kreditkartenabrechnung.
     *
     * expense  -> erhöht den Abrechnungsbetrag
     * income   -> reduziert den Abrechnungsbetrag
     * transfer -> wird ignoriert
     *
     * Berücksichtigt werden ausschließlich:
     * - Transaktionen der betreffenden Kreditkarte
     * - Transaktionen innerhalb des Abrechnungszeitraums
     * - nicht ausstehende Transaktionen
     * - nicht gelöschte Transaktionen
     *
     * Das Ergebnis wird als Geldbetrag mit exakt zwei Nachkommastellen
     * zurückgegeben.
     */
    public function calculateAmount(
        CreditCardStatement $statement
    ): string {
        $expenseAmount = Transaction::query()
            ->where('credit_card_id', $statement->credit_card_id)
            ->whereDate('transaction_date', '>=', $statement->period_start->toDateString())
            ->whereDate('transaction_date', '<=', $statement->period_end->toDateString())
            ->where('is_pending', false)
            ->where('type', 'expense')
            ->sum('amount');

        $incomeAmount = Transaction::query()
            ->where('credit_card_id', $statement->credit_card_id)
            ->whereDate('transaction_date', '>=', $statement->period_start->toDateString())
            ->whereDate('transaction_date', '<=', $statement->period_end->toDateString())
            ->where('is_pending', false)
            ->where('type', 'income')
            ->sum('amount');

        $amount = (float) $expenseAmount - (float) $incomeAmount;

        return number_format(
            $amount,
            2,
            '.',
            ''
        );
    }

    /**
     * Aktualisiert den Betrag einer offenen Kreditkartenabrechnung.
     *
     * Bereits ausgegebene, bezahlte oder überfällige Abrechnungen
     * werden bewusst nicht automatisch verändert.
     */
    public function updateAmount(
        CreditCardStatement $statement
    ): CreditCardStatement {
        if ($statement->status !== 'open') {
            return $statement;
        }

        $statement->amount = $this->calculateAmount($statement);
        $statement->save();

        return $statement->refresh();
    }

    /**
     * Liefert das nächste Fälligkeitsdatum nach dem Ende
     * des Abrechnungszeitraums.
     */
    public function dueDateAfterPeriod(
        CarbonInterface $periodEnd,
        ?int $paymentDueDay
    ): ?Carbon {
        if ($paymentDueDay === null) {
            return null;
        }

        if ($paymentDueDay < 1 || $paymentDueDay > 31) {
            throw new InvalidArgumentException(
                'Der Zahlungstermin muss zwischen 1 und 31 liegen.'
            );
        }

        $periodEnd = $periodEnd->copy()->startOfDay();

        $candidate = $this->billingDateForMonth(
            $periodEnd->year,
            $periodEnd->month,
            $paymentDueDay
        );

        if ($candidate->lessThanOrEqualTo($periodEnd)) {
            $nextMonth = $periodEnd->copy()->addMonthNoOverflow();

            $candidate = $this->billingDateForMonth(
                $nextMonth->year,
                $nextMonth->month,
                $paymentDueDay
            );
        }

        return $candidate;
    }

    /**
     * Erzeugt einen gültigen Tag innerhalb des jeweiligen Monats.
     *
     * Beispiel:
     * billing_day = 31
     * Februar 2026 => 28.02.2026
     */
    private function billingDateForMonth(
        int $year,
        int $month,
        int $day
    ): Carbon {
        $date = Carbon::create(
            $year,
            $month,
            1,
            0,
            0,
            0
        );

        $day = min(
            $day,
            $date->daysInMonth
        );

        return $date->day($day)->startOfDay();
    }
}
