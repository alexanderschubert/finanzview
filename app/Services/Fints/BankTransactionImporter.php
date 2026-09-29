<?php

namespace App\Services\Fints;

use App\Models\BankConnection;
use App\Models\Transaction;
use App\Services\CategoryRuleService;
use App\Services\CsvImportService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Legt per FinTS abgerufene Umsätze als Buchungen an.
 *
 * - Bereits abgerufene Umsätze (Fingerabdruck) werden übersprungen.
 * - Gleiche Buchungen, die schon von Hand oder per CSV erfasst wurden
 *   (Datum, Betrag, Art), werden ebenfalls übersprungen und gezählt.
 * - Kategorien: eigene Regeln, sonst aus bisherigen Buchungen gelernt.
 */
class BankTransactionImporter
{
    public function __construct(
        private CategoryRuleService $rules,
        private CsvImportService $csv,
    ) {
    }

    /**
     * @param  list<array{date: ?string, amount: float, name: string, description: string, booking_text: string}>  $rows
     * @return array{imported: int, known: int, possible: int}
     */
    public function import(BankConnection $connection, array $rows): array
    {
        $userId = $connection->user_id;
        $accountId = $connection->account_id;

        $items = $this->prepare($connection, $rows);

        $summary = ['imported' => 0, 'known' => 0, 'possible' => 0];

        if ($items === []) {
            return $summary;
        }

        $known = Transaction::withTrashed()
            ->where('user_id', $userId)
            ->whereIn('external_id', array_column($items, 'external_id'))
            ->pluck('external_id')
            ->flip();

        $dates = array_column($items, 'date');

        $existing = Transaction::query()
            ->where('user_id', $userId)
            ->where('account_id', $accountId)
            ->whereDate('transaction_date', '>=', min($dates))
            ->whereDate('transaction_date', '<=', max($dates))
            ->where(fn ($q) => $q->whereNull('external_id')->orWhere('external_id', 'not like', 'fints:%'))
            ->get(['transaction_date', 'amount', 'type'])
            ->countBy(fn ($t) => $this->matchKey($t->transaction_date->toDateString(), (float) $t->amount, $t->type))
            ->all();

        $rules = $this->rules->rulesFor($userId);
        $learned = $this->csv->categorySuggestions($userId);

        DB::transaction(function () use ($items, $known, &$existing, $rules, $learned, $userId, $accountId, &$summary) {
            foreach ($items as $item) {
                if ($known->has($item['external_id'])) {
                    $summary['known']++;
                    continue;
                }

                $key = $this->matchKey($item['date'], $item['amount'], $item['type']);

                if (($existing[$key] ?? 0) > 0) {
                    $existing[$key]--;
                    $summary['possible']++;
                    continue;
                }

                Transaction::create([
                    'user_id' => $userId,
                    'account_id' => $accountId,
                    'category_id' => $this->rules->match($rules, $item['type'], $item['merchant'], $item['description'])
                        ?? $this->csv->suggestCategory($learned, $item['type'], (string) $item['merchant'], $item['description']),
                    'type' => $item['type'],
                    'amount' => $item['amount'],
                    'transaction_date' => $item['date'],
                    'description' => $item['description'],
                    'merchant' => $item['merchant'],
                    'external_id' => $item['external_id'],
                ]);

                $summary['imported']++;
            }
        });

        return $summary;
    }

    /**
     * Umsätze vereinheitlichen und mit Fingerabdruck versehen.
     */
    private function prepare(BankConnection $connection, array $rows): array
    {
        $items = [];
        $occurrences = [];

        foreach ($rows as $row) {
            $amount = round((float) ($row['amount'] ?? 0), 2);
            $date = $row['date'] ?? null;

            if ($date === null || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $amount == 0) {
                continue;
            }

            $merchant = Str::limit(Str::squish((string) ($row['name'] ?? '')), 250, '');
            $description = Str::limit(Str::squish((string) ($row['description'] ?? '')), 250, '');
            $bookingText = Str::limit(Str::squish((string) ($row['booking_text'] ?? '')), 250, '');

            // Gleiche Umsätze am selben Tag werden durchgezählt.
            $key = implode('|', [$connection->iban, $date, $amount, mb_strtolower($merchant), mb_strtolower($description)]);
            $occurrences[$key] = ($occurrences[$key] ?? 0) + 1;

            $items[] = [
                'date' => $date,
                'amount' => abs($amount),
                'type' => $amount < 0 ? 'expense' : 'income',
                'merchant' => $merchant !== '' ? $merchant : null,
                'description' => $description !== '' ? $description : ($bookingText !== '' ? $bookingText : ($merchant !== '' ? $merchant : 'Bankumsatz')),
                'external_id' => 'fints:' . sha1($key . '|' . $occurrences[$key]),
            ];
        }

        return $items;
    }

    private function matchKey(string $date, float $amount, string $type): string
    {
        return $date . '|' . number_format($amount, 2, '.', '') . '|' . $type;
    }
}
