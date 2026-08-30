<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\User;
use Carbon\Carbon;

class BudgetService
{
    /**
     * =========================================================
     * BUDGET BERECHNEN
     * =========================================================
     *
     * Berechnet die Budgetwerte für den ausgewählten Bezugsmonat.
     *
     * MONTHLY:
     * - Budgetbetrag gilt pro Monat.
     * - Im Startmonat ab start_date.
     * - Danach jeweils kompletter Monat.
     * - Optionales end_date begrenzt das Budget.
     *
     * YEARLY:
     * - Budgetbetrag gilt pro Kalenderjahr.
     * - Verbrauch wird kumulativ vom Jahresbeginn
     *   bzw. start_date bis zum ausgewählten Monat berechnet.
     *
     * CUSTOM:
     * - Budget gilt ausschließlich zwischen start_date
     *   und end_date.
     * - Der ausgewählte Monat wird auf diesen Zeitraum begrenzt.
     */
    public function calculate(
        Budget $budget,
        User $user,
        Carbon $referenceMonth
    ): array {

        /*
         * =========================================================
         * BEZUGSMONAT
         * =========================================================
         */

        $referenceMonth = $referenceMonth
            ->copy()
            ->startOfMonth();

        $referenceMonthStart = $referenceMonth
            ->copy()
            ->startOfMonth();

        $referenceMonthEnd = $referenceMonth
            ->copy()
            ->endOfMonth();


        /*
         * =========================================================
         * BUDGET-DATEN
         * =========================================================
         */

        $startDate = $budget->start_date
            ->copy()
            ->startOfDay();

        /*
         * end_date darf bei monatlichen/jährlichen
         * Budgets NULL sein.
         */

        $endDate = $budget->end_date
            ? $budget->end_date->copy()->endOfDay()
            : null;


        /*
         * =========================================================
         * STARTDATUM PRÜFEN
         * =========================================================
         *
         * Liegt der ausgewählte Monat vollständig
         * vor dem Budgetbeginn?
         */

        if ($referenceMonthEnd->lt($startDate)) {

            return $this->emptyResult(
                $budget,
                $referenceMonth
            );
        }


        /*
         * =========================================================
         * ENDZEITRAUM PRÜFEN
         * =========================================================
         *
         * Wenn ein Enddatum existiert und der ausgewählte
         * Monat vollständig danach liegt, ist das Budget
         * nicht mehr gültig.
         */

        if (
            $endDate !== null
            &&
            $referenceMonthStart->gt($endDate)
        ) {

            return $this->emptyResult(
                $budget,
                $referenceMonth
            );
        }


        /*
         * =========================================================
         * ZEITRAUM ERMITTELN
         * =========================================================
         */

        switch ($budget->period) {


            /*
             * =====================================================
             * MONATLICH
             * =====================================================
             *
             * Nur der ausgewählte Monat wird berücksichtigt.
             */

            case 'monthly':

                $budgetStart =
                    $referenceMonthStart->copy();

                $budgetEnd =
                    $referenceMonthEnd->copy();


                /*
                 * Im ersten Monat erst ab dem tatsächlichen
                 * Budgetbeginn zählen.
                 */

                if ($budgetStart->lt($startDate)) {

                    $budgetStart =
                        $startDate->copy();
                }


                /*
                 * Falls ein Enddatum existiert,
                 * Budget bis maximal zu diesem Datum.
                 */

                if (
                    $endDate !== null
                    &&
                    $budgetEnd->gt($endDate)
                ) {

                    $budgetEnd =
                        $endDate->copy();
                }


                break;


            /*
             * =====================================================
             * JÄHRLICH
             * =====================================================
             *
             * Der Verbrauch ist kumulativ.
             *
             * Beispiel:
             *
             * Jahresbudget: 4.800 €
             *
             * Start: 15.08.2026
             *
             * August:
             * 15.08. – 31.08.
             *
             * September:
             * 15.08. – 30.09.
             *
             * Oktober:
             * 15.08. – 31.10.
             *
             * usw.
             *
             * Ab dem Folgejahr:
             *
             * 01.01. – ausgewählter Monat.
             */

            case 'yearly':

                $budgetStart =
                    $referenceMonth
                        ->copy()
                        ->startOfYear();

                $budgetEnd =
                    $referenceMonthEnd->copy();


                /*
                 * Im ersten Jahr erst ab dem tatsächlichen
                 * Startdatum zählen.
                 */

                if ($budgetStart->lt($startDate)) {

                    $budgetStart =
                        $startDate->copy();
                }


                /*
                 * Enddatum begrenzen.
                 */

                if (
                    $endDate !== null
                    &&
                    $budgetEnd->gt($endDate)
                ) {

                    $budgetEnd =
                        $endDate->copy();
                }


                break;


            /*
             * =====================================================
             * BENUTZERDEFINIERT
             * =====================================================
             *
             * Das Budget gilt ausschließlich innerhalb
             * des definierten Zeitraums.
             */

            case 'custom':

                /*
                 * Custom-Budget benötigt zwingend ein Enddatum.
                 */

                if ($endDate === null) {

                    return $this->emptyResult(
                        $budget,
                        $referenceMonth
                    );
                }


                /*
                 * Prüfen, ob der ausgewählte Monat
                 * überhaupt mit dem Budgetzeitraum
                 * überschneidet.
                 */

                if (
                    $referenceMonthEnd->lt($startDate)
                    ||
                    $referenceMonthStart->gt($endDate)
                ) {

                    return $this->emptyResult(
                        $budget,
                        $referenceMonth
                    );
                }


                /*
                 * Tatsächlichen Budgetzeitraum setzen.
                 */

                $budgetStart =
                    $startDate->copy();

                $budgetEnd =
                    $endDate->copy();


                /*
                 * Auf den ausgewählten Monat begrenzen.
                 */

                if ($budgetStart->lt($referenceMonthStart)) {

                    $budgetStart =
                        $referenceMonthStart->copy();
                }


                if ($budgetEnd->gt($referenceMonthEnd)) {

                    $budgetEnd =
                        $referenceMonthEnd
                            ->copy()
                            ->endOfDay();
                }


                break;


            /*
             * =====================================================
             * UNBEKANNTE PERIODE
             * =====================================================
             */

            default:

                return $this->emptyResult(
                    $budget,
                    $referenceMonth
                );
        }


        /*
         * =========================================================
         * SICHERHEITSPRÜFUNG
         * =========================================================
         */

        if ($budgetStart->gt($budgetEnd)) {

            return $this->emptyResult(
                $budget,
                $referenceMonth
            );
        }


        /*
         * =========================================================
         * KATEGORIEN
         * =========================================================
         */

        $categoryIds = $budget->categories
            ->pluck('id');


        /*
         * =========================================================
         * VERBRAUCH
         * =========================================================
         *
         * Ein Budget ohne Kategorien hat aktuell
         * keinen Verbrauch.
         */

        if ($categoryIds->isEmpty()) {

            $spent = 0;

        } else {

            $spent = $user->transactions()
                ->where('type', 'expense')
                ->whereBetween(
                    'transaction_date',
                    [
                        $budgetStart,
                        $budgetEnd,
                    ]
                )
                ->whereIn(
                    'category_id',
                    $categoryIds
                )
                ->sum('amount');
        }


        /*
         * =========================================================
         * BETRÄGE
         * =========================================================
         */

        $budgetAmount =
            (float) $budget->amount;

        $spentAmount =
            (float) $spent;


        /*
         * =========================================================
         * RESTBETRAG
         * =========================================================
         */

        $remaining =
            $budgetAmount -
            $spentAmount;


        /*
         * =========================================================
         * PROZENT
         * =========================================================
         */

        $percentage =
            $budgetAmount > 0
                ? (
                    $spentAmount /
                    $budgetAmount
                ) * 100
                : 0;


        /*
         * =========================================================
         * ERGEBNIS
         * =========================================================
         */

        return [

            'spent' =>
                $spentAmount,

            'remaining' =>
                $remaining,

            'percentage' =>
                $percentage,

            'exceeded' =>
                $spentAmount >
                $budgetAmount,

            'start_date' =>
                $budgetStart,

            'end_date' =>
                $budgetEnd,

            'applicable' =>
                true,
        ];
    }


    /**
     * =========================================================
     * LEERES ERGEBNIS
     * =========================================================
     *
     * Wird zurückgegeben, wenn das Budget im ausgewählten
     * Zeitraum nicht gültig ist.
     */
    private function emptyResult(
        Budget $budget,
        Carbon $referenceMonth
    ): array {

        return [

            'spent' =>
                0,

            'remaining' =>
                (float) $budget->amount,

            'percentage' =>
                0,

            'exceeded' =>
                false,

            'start_date' =>
                $referenceMonth
                    ->copy()
                    ->startOfMonth(),

            'end_date' =>
                $referenceMonth
                    ->copy()
                    ->endOfMonth(),

            'applicable' =>
                false,
        ];
    }
}