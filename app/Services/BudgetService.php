<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\User;
use Carbon\Carbon;

class BudgetService
{
    /**
     * Berechnet die Budgetwerte für einen bestimmten Bezugsmonat.
     *
     * =============================================================
     * MONTHLY
     * =============================================================
     *
     * Das Budget gilt jeden Monat ab start_date.
     *
     * Beispiel:
     *
     * Start: 15.08.2026
     * Betrag: 400 €
     *
     * August:
     * 15.08. – 31.08.
     *
     * September:
     * 01.09. – 30.09.
     *
     *
     * =============================================================
     * YEARLY
     * =============================================================
     *
     * Das Budget gilt pro Kalenderjahr.
     *
     * Beispiel:
     *
     * Start: 15.08.2026
     * Betrag: 4.800 €
     *
     * Jahr 2026:
     * 15.08. – 31.12.
     *
     * Jahr 2027:
     * 01.01. – 31.12.
     *
     *
     * =============================================================
     * CUSTOM
     * =============================================================
     *
     * Das Budget gilt ausschließlich zwischen start_date
     * und end_date.
     *
     * Der ausgewählte Monat muss den definierten Zeitraum
     * überschneiden.
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


        $endDate = $budget->end_date
            ->copy()
            ->endOfDay();


        /*
         * =========================================================
         * ALLGEMEINE PRÜFUNG
         * =========================================================
         *
         * Liegt der ausgewählte Monat vollständig vor
         * dem Startdatum?
         */

        if ($referenceMonthEnd->lt($startDate)) {

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
             * Der Budgetbetrag gilt für jeden Monat.
             */

            case 'monthly':

                $budgetStart = $referenceMonthStart->copy();

                $budgetEnd = $referenceMonthEnd->copy();


                /*
                 * Im ersten Monat erst ab dem tatsächlichen
                 * Startdatum zählen.
                 */

                if ($budgetStart->lt($startDate)) {

                    $budgetStart = $startDate->copy();
                }


                /*
                 * Falls ein Enddatum existiert und dieses
                 * vor dem ausgewählten Monat liegt, ist das
                 * Budget nicht mehr gültig.
                 */

                if ($referenceMonthStart->gt($endDate)) {

                    return $this->emptyResult(
                        $budget,
                        $referenceMonth
                    );
                }


                /*
                 * Im letzten Monat bis zum tatsächlichen
                 * Enddatum zählen.
                 */

                if ($budgetEnd->gt($endDate)) {

                    $budgetEnd = $endDate->copy();
                }


                break;


            /*
             * =====================================================
             * JÄHRLICH
             * =====================================================
             *
             * Der Budgetbetrag gilt für das gesamte Kalenderjahr.
             */

            case 'yearly':

                $budgetStart = $referenceMonth
                    ->copy()
                    ->startOfYear();


                $budgetEnd = $referenceMonth
                    ->copy()
                    ->endOfYear();


                /*
                 * Im ersten Jahr erst ab dem tatsächlichen
                 * Startdatum zählen.
                 */

                if ($budgetStart->lt($startDate)) {

                    $budgetStart = $startDate->copy();
                }


                /*
                 * Falls das Enddatum vor dem ausgewählten
                 * Jahr liegt, ist das Budget nicht mehr gültig.
                 */

                if ($referenceMonthStart->gt($endDate)) {

                    return $this->emptyResult(
                        $budget,
                        $referenceMonth
                    );
                }


                /*
                 * Falls ein Enddatum innerhalb des Jahres liegt,
                 * wird der Zeitraum entsprechend begrenzt.
                 */

                if ($budgetEnd->gt($endDate)) {

                    $budgetEnd = $endDate->copy();
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
                 * Prüfen, ob der ausgewählte Monat überhaupt
                 * mit dem Custom-Zeitraum überschneidet.
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
                 * Tatsächlichen Zeitraum setzen.
                 */

                $budgetStart = $startDate->copy();

                $budgetEnd = $endDate->copy();


                /*
                 * Zeitraum auf den ausgewählten Monat
                 * begrenzen.
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
         * SICHERHEITSPRÜFUNG ZEITRAUM
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
         * ========================================================= */

        $remaining =
            $budgetAmount -
            $spentAmount;


        /*
         * =========================================================
         * PROZENT
         * ========================================================= */

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
         * ========================================================= */

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
     * Leeres Ergebnis für einen Zeitraum,
     * in dem das Budget nicht gültig ist.
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