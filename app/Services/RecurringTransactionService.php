<?php

namespace App\Services;

use App\Models\RecurringTransaction;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

            /*
             * Keine Buchungen auf gelöschte oder deaktivierte Konten.
             *
             * Bewusst wird next_date NICHT weitergeschoben und die
             * Vorlage NICHT deaktiviert: Wird das Konto wieder
             * aktiviert/wiederhergestellt, werden die verpassten
             * Termine nachgeholt. Gelöschte Konten werden per
             * Soft-Delete ausgeblendet (account() liefert dann null).
             */
            $account = $recurring->account()->first();

            if (! $account || ! $account->is_active) {
                Log::info(
                    'Wiederkehrende Buchung übersprungen: Konto gelöscht oder inaktiv.',
                    [
                        'recurring_transaction_id' => $recurring->id,
                        'account_id' => $recurring->account_id,
                    ]
                );

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
                    $recurring->frequency,
                    $recurring->anchor_day
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
     * Die nächsten Ausführungstermine (für die Vorschau), mit
     * derselben Berechnung wie bei der tatsächlichen Buchung.
     * Ein Enddatum wird berücksichtigt.
     *
     * @return array<int, Carbon>
     */
    public function upcomingDates(RecurringTransaction $recurring, int $count = 3): array
    {
        if (! $recurring->is_active || ! $recurring->next_date) {
            return [];
        }

        $dates = [];
        $date = Carbon::parse($recurring->next_date)->startOfDay();

        while (count($dates) < $count) {
            if ($recurring->end_date && $date->gt(Carbon::parse($recurring->end_date)->endOfDay())) {
                break;
            }

            $dates[] = $date->copy();

            $date = $this->calculateNextDate($date, $recurring->frequency, $recurring->anchor_day);
        }

        return $dates;
    }

    /**
     * Berechnet das nächste Ausführungsdatum.
     *
     * Monatliche, quartalsweise und jährliche Termine werden über
     * den Ankertag berechnet, damit kein Tagesdrift entsteht:
     * 31.01. -> 28.02. -> 31.03. bzw. 29.02.2028 -> 28.02.2029
     * -> ... -> 29.02.2032.
     */
    private function calculateNextDate(
        Carbon $date,
        string $frequency,
        ?int $anchorDay = null
    ): Carbon {

        $months = match ($frequency) {
            'weekly' => null,
            'monthly' => 1,
            'quarterly' => 3,
            'yearly' => 12,
            default =>
                throw new \InvalidArgumentException(
                    "Unbekanntes Intervall: {$frequency}"
                ),
        };

        if ($months === null) {
            return $date->copy()->addWeek();
        }

        $anchorDay = $anchorDay && $anchorDay >= 1 && $anchorDay <= 31
            ? $anchorDay
            : (int) $date->day;

        /*
         * Zuerst auf den Monatsersten setzen (kein Überlauf),
         * dann den Ankertag auf die Monatslänge begrenzen.
         */
        $next = $date->copy()
            ->startOfMonth()
            ->addMonths($months);

        return $next->day(
            min($anchorDay, $next->daysInMonth)
        );
    }
}
