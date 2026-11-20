<?php

namespace App\Services\Fints;

use App\Models\BankConnection;
use App\Models\BankConnectionAccount;
use App\Services\TransferMatcher;

/**
 * Gemeinsame Logik für den Bankabruf (Web-Oberfläche und JSON-API):
 * Umsätze nach erfolgreichem FinTS-Abruf übernehmen.
 */
class BankSyncService
{
    public function __construct(
        private BankTransactionImporter $importer,
    ) {
    }

    /**
     * Parameter für einen Abruf aller verknüpften Konten.
     *
     * @return array{accounts: list<mixed>, from: string, to: string}
     */
    public function params(BankConnection $connection, ?string $from = null): array
    {
        return [
            'accounts' => $connection->linkedAccounts
                ->map(fn (BankConnectionAccount $link) => $link->fintsAccount($connection->bank_code))
                ->values()
                ->all(),
            'from' => $from ?? $this->defaultStart($connection)->toDateString(),
            'to' => now()->toDateString(),
        ];
    }

    /**
     * Seit dem letzten Abruf (14 Tage Überlappung), beim ersten Mal 90 Tage.
     */
    public function defaultStart(BankConnection $connection)
    {
        return $connection->last_synced_at
            ? $connection->last_synced_at->copy()->subDays(14)->startOfDay()
            : now()->subDays(90)->startOfDay();
    }

    /**
     * Übernimmt die abgerufenen Umsätze.
     *
     * @return array{message: string, imported: int, skipped: int}
     */
    public function apply(BankConnection $connection, array $data): array
    {
        $messages = [];
        $totalImported = 0;
        $totalSkipped = 0;

        foreach ($connection->linkedAccounts()->with('account')->get() as $link) {
            $result = $data[$link->id] ?? $data[(string) $link->id] ?? null;

            if ($result === null) {
                continue;
            }

            $summary = $this->importer->import($connection->user_id, $link->account_id, $link->iban, $result['transactions'] ?? []);
            $totalImported += $summary['imported'];
            $totalSkipped += $summary['known'] + $summary['possible'];

            $link->last_synced_at = now();
            $link->last_error = ($result['errors'] ?? []) === [] ? null : implode(' · ', $result['errors']);

            if (($result['balance'] ?? null) !== null) {
                $link->bank_balance = $result['balance']['amount'];
                $link->balance_date = $result['balance']['date'];
            }

            $link->save();

            // Neu angelegtes Konto: Startsaldo so setzen, dass es zur Bank passt.
            if ($link->adopt_balance && $link->bank_balance !== null) {
                $difference = $link->fresh('account')->balanceDifference();
                $link->account->opening_balance = round((float) $link->account->opening_balance + (float) $difference, 2);
                $link->account->save();
                $link->update(['adopt_balance' => false]);
            }

            $part = $link->account->name . ': ' . match ($summary['imported']) {
                0 => 'keine neuen Umsätze',
                1 => '1 neue Buchung',
                default => "{$summary['imported']} neue Buchungen",
            };

            if ($summary['possible'] > 0) {
                $part .= " ({$summary['possible']} schon erfasst, übersprungen)";
            }

            $messages[] = $part;
        }

        $message = implode(' · ', $messages) ?: 'Keine neuen Umsätze.';

        $transfers = app(\App\Services\TransferMatcher::class)->count($connection->user_id);

        if ($transfers > 0) {
            $message .= $transfers === 1
                ? ' · 1 mögliche Umbuchung gefunden (unter Buchungen prüfen)'
                : " · {$transfers} mögliche Umbuchungen gefunden (unter Buchungen prüfen)";
        }

        $connection->update([
            'last_synced_at' => now(),
            'last_result' => $message,
        ]);

        return ['message' => $message, 'imported' => $totalImported, 'skipped' => $totalSkipped];
    }
}
