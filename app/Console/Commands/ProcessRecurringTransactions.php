<?php

namespace App\Console\Commands;

use App\Models\RecurringTransaction;
use App\Services\RecurringTransactionService;
use Illuminate\Console\Command;

class ProcessRecurringTransactions extends Command
{
    /**
     * Der Name und die Signatur des Artisan Commands.
     */
    protected $signature = 'recurring:process';

    /**
     * Beschreibung des Commands.
     */
    protected $description = 'Verarbeitet fällige wiederkehrende Buchungen';

    /**
     * Führt den Command aus.
     */
    public function handle(
        RecurringTransactionService $service
    ): int {

        $this->info('Verarbeite wiederkehrende Buchungen...');

        $processed = 0;
        $created = 0;

        RecurringTransaction::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->each(function (RecurringTransaction $recurring) use (
                $service,
                &$processed,
                &$created
            ) {

                $processed++;

                $count = $service->process($recurring);

                $created += $count;

                if ($count > 0) {

                    $this->line(
                        "✓ {$recurring->description}: {$count} Buchung(en) erstellt."
                    );

                }

            });

        $this->newLine();

        $this->info(
            "Verarbeitet: {$processed} wiederkehrende Buchung(en)"
        );

        $this->info(
            "Erstellt: {$created} Buchung(en)"
        );

        return self::SUCCESS;
    }
}
