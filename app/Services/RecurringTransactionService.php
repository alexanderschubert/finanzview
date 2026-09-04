<?php

namespace App\Services;

use App\Models\RecurringTransaction;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RecurringTransactionService
{
    /**
     * Verarbeitet eine wiederkehrende Buchung,
     * sofern sie fällig ist.
     *
     * Es können auch mehrere fällige Ausführungen
     * nachgeholt werden, falls der Prozess längere
     * Zeit nicht ausgeführt wurde.
     */
    public function process(RecurringTransaction $recurring): int
    {
        if (! $recurring->is_active) {
            return 0;
        }

        $created = 0;

        DB::transaction(function () use ($recurring, &$created) {

            /*
             * Den aktuellen Datensatz innerhalb der Transaktion
             * laden und für die Dauer der Verarbeitung sperren.
             *
             * Dadurch können zwei parallel laufende Scheduler/Worker
             * dieselbe wiederkehrende Buchung nicht gleichzeitig
             * verarbeiten.
             */
            $recurring = RecurringTransaction::query()
                ->whereKey($recurring->getKey())
                ->lockForUpdate()
                ->first();

            if (! $recurring || ! $recurring->is_active) {
                return;
            }

            $today = Carbon::today();

            /*
             * Solange die nächste Ausführung heute
             * oder in der Vergangenheit liegt,
             * wird eine Buchung erzeugt.
             */
            while (
                $recurring->next_date
                && $recurring->next_date->lte($today)
            ) {

                /*
                 * Enddatum prüfen.
                 *
                 * Eine Ausführung am Enddatum ist noch erlaubt.
                 */
                if (
                    $recurring->end_date
                    && $recurring->next_date->gt($recurring->end_date)
                ) {

                    $recurring->is_active = false;
                    $recurring->save();

                    break;
                }

                /*
                 * Prüfen, ob diese konkrete Ausführung
                 * bereits als Buchung existiert.
                 *
                 * Dadurch verhindern wir doppelte Buchungen.
                 */
                $alreadyExists = Transaction::query()
                    ->where('recurring_transaction_id', $recurring->id)
                    ->whereDate(
                        'transaction_date',
                        $recurring->next_date
                    )
                    ->exists();

                if (! $alreadyExists) {

                    Transaction::create([
                        'user_id' => $recurring->user_id,
                        'account_id' => $recurring->account_id,
                        'category_id' => $recurring->category_id,
                        'type' => $recurring->type,
                        'amount' => $recurring->amount,
                        'transaction_date' => $recurring->next_date,
                        'description' => $recurring->description,
                        'merchant' => null,
                        'reference' => null,
                        'notes' => null,
                        'is_pending' => false,
                        'is_recurring' => true,
                        'recurring_transaction_id' => $recurring->id,
                    ]);

                    $created++;
                }

                /*
                 * Nächsten Ausführungstermin berechnen.
                 */
                $nextDate = $this->calculateNextDate(
                    $recurring->next_date,
                    $recurring->frequency
                );

                /*
                 * Enddatum erreicht?
                 *
                 * Die Buchung für das Enddatum darf noch
                 * erstellt werden. Danach wird deaktiviert.
                 */
                if (
                    $recurring->end_date
                    && $nextDate->gt($recurring->end_date)
                ) {

                    $recurring->next_date = $nextDate;
                    $recurring->is_active = false;
                    $recurring->save();

                    break;
                }

                $recurring->next_date = $nextDate;
                $recurring->save();
            }
        });

        return $created;
    }


    /**
     * Berechnet das nächste Ausführungsdatum.
     */
    private function calculateNextDate(
        Carbon $date,
        string $frequency
    ): Carbon {

        return match ($frequency) {

            'weekly' =>
                $date->copy()->addWeek(),

            'monthly' =>
                $date->copy()->addMonthNoOverflow(),

            'quarterly' =>
                $date->copy()->addMonthsNoOverflow(3),

            'yearly' =>
                $date->copy()->addYearNoOverflow(),

            default =>
                throw new \InvalidArgumentException(
                    "Unbekanntes Intervall: {$frequency}"
                ),
        };
    }
}
