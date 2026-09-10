<?php

namespace App\Services;

use App\Models\Loan;

class LoanAmortizationService
{
    /**
     * Berechnet den Tilgungsplan eines Kredits
     * ab der ursprünglichen Kreditsumme.
     *
     * @return array<int, array{
     *     installment_number: int,
     *     amount: float,
     *     interest_amount: float,
     *     principal_amount: float,
     *     remaining_amount: float
     * }>
     */
    public function calculate(Loan $loan): array
    {
        return $this->calculateFromBalance(
            (float) $loan->principal_amount,
            (float) ($loan->interest_rate ?? 0),
            (float) $loan->installment_amount,
            $loan->total_installments
                ? (int) $loan->total_installments
                : null
        );
    }

    /**
     * Berechnet einen Tilgungsplan ab einer beliebigen
     * aktuellen Restschuld.
     *
     * Variante A:
     * Die monatliche Rate bleibt konstant.
     * Eine Sondertilgung verkürzt dadurch die Laufzeit.
     *
     * @return array<int, array{
     *     installment_number: int,
     *     amount: float,
     *     interest_amount: float,
     *     principal_amount: float,
     *     remaining_amount: float
     * }>
     */
    public function calculateFromBalance(
        float $remainingPrincipal,
        float $annualInterestRate,
        float $installmentAmount,
        ?int $maximumInstallments = null
    ): array {
        $remaining = round(
            max(0, $remainingPrincipal),
            2
        );

        $annualRate = max(
            0,
            $annualInterestRate
        );

        $installment = round(
            max(0, $installmentAmount),
            2
        );

        if (
            $remaining <= 0 ||
            $installment <= 0
        ) {
            return [];
        }

        /*
         * Ohne vorgegebene Laufzeit verwenden wir
         * eine ausreichend große Sicherheitsgrenze.
         */
        $maximumInstallments = $maximumInstallments !== null
            ? max(0, $maximumInstallments)
            : 1200;

        if ($maximumInstallments <= 0) {
            return [];
        }

        $monthlyRate = $annualRate / 100 / 12;

        /*
         * Bei 0 % Zinsen ist die komplette Rate Tilgung.
         */
        if ($monthlyRate <= 0) {
            return $this->calculateWithoutInterest(
                $remaining,
                $installment,
                $maximumInstallments
            );
        }

        $plan = [];

        for (
            $number = 1;
            $number <= $maximumInstallments;
            $number++
        ) {
            if ($remaining <= 0) {
                break;
            }

            /*
             * Monatszinsen auf die aktuelle Restschuld.
             */
            $interest = round(
                $remaining * $monthlyRate,
                2
            );

            /*
             * Die Rate darf die Restschuld inklusive
             * Zinsen nicht überschreiten.
             */
            $amount = min(
                $installment,
                round($remaining + $interest, 2)
            );

            /*
             * Rest der Rate ist Tilgung.
             */
            $principalPayment = round(
                $amount - $interest,
                2
            );

            /*
             * Schutz gegen Rundungsfehler und
             * negative Tilgung.
             */
            $principalPayment = min(
                $principalPayment,
                $remaining
            );

            $principalPayment = max(
                0,
                round($principalPayment, 2)
            );

            $amount = round(
                $interest + $principalPayment,
                2
            );

            $remaining = round(
                max(
                    0,
                    $remaining - $principalPayment
                ),
                2
            );

            $plan[] = [
                'installment_number' => $number,
                'amount' => $amount,
                'interest_amount' => $interest,
                'principal_amount' => $principalPayment,
                'remaining_amount' => $remaining,
            ];

            if ($remaining <= 0) {
                break;
            }
        }

        return $plan;
    }

    /**
     * Tilgungsplan ohne Zinsen.
     *
     * @return array<int, array{
     *     installment_number: int,
     *     amount: float,
     *     interest_amount: float,
     *     principal_amount: float,
     *     remaining_amount: float
     * }>
     */
    private function calculateWithoutInterest(
        float $remaining,
        float $installmentAmount,
        int $maximumInstallments
    ): array {
        $plan = [];

        for (
            $number = 1;
            $number <= $maximumInstallments;
            $number++
        ) {
            if ($remaining <= 0) {
                break;
            }

            $principalPayment = min(
                $installmentAmount,
                $remaining
            );

            $principalPayment = round(
                $principalPayment,
                2
            );

            $remaining = round(
                max(
                    0,
                    $remaining - $principalPayment
                ),
                2
            );

            $plan[] = [
                'installment_number' => $number,
                'amount' => $principalPayment,
                'interest_amount' => 0.00,
                'principal_amount' => $principalPayment,
                'remaining_amount' => $remaining,
            ];
        }

        return $plan;
    }
}
