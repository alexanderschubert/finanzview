<?php

namespace App\Console\Commands;

use App\Models\CreditCard;
use App\Services\CreditCardStatementService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

class GenerateCreditCardStatements extends Command
{
    protected $signature = 'credit-cards:generate-statements';

    protected $description = 'Erzeugt und aktualisiert Kreditkartenabrechnungen';

    public function handle(CreditCardStatementService $statementService): int
    {
        $today = Carbon::today();

        $cards = CreditCard::query()
            ->where('is_active', true)
            ->whereNotNull('billing_day')
            ->with('user')
            ->get();

        if ($cards->isEmpty()) {
            $this->info('Keine aktiven Kreditkarten mit Abrechnungstag gefunden.');

            return self::SUCCESS;
        }

        $createdOrUpdated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($cards as $card) {
            try {
                $statement = $statementService->generateFor(
                    $card,
                    $today
                );

                $createdOrUpdated++;

                $this->line(sprintf(
                    '✓ %s: %s – %s | %s € | Status: %s',
                    $card->name,
                    $statement->period_start->format('d.m.Y'),
                    $statement->period_end->format('d.m.Y'),
                    number_format((float) $statement->amount, 2, ',', '.'),
                    $statement->status
                ));
            } catch (Throwable $e) {
                $failed++;

                $this->error(sprintf(
                    '✗ %s: %s',
                    $card->name,
                    $e->getMessage()
                ));
            }
        }

        $this->newLine();

        $this->info(sprintf(
            'Fertig: %d verarbeitet, %d übersprungen, %d Fehler.',
            $createdOrUpdated,
            $skipped,
            $failed
        ));

        return $failed > 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}
