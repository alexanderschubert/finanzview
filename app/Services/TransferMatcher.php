<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Findet Buchungspaare, die eigentlich eine Umbuchung zwischen zwei
 * eigenen Konten sind – z. B. die Amex-Monatsabrechnung: Ausgabe im
 * Girokonto und gleich hohe Einnahme im Amex-Konto.
 *
 * Solche Paare zählen sonst doppelt (als Ausgabe und als Einnahme).
 */
class TransferMatcher
{
    /** Höchster Abstand zwischen Abbuchung und Gutschrift. */
    public const MAX_DAYS = 7;

    /** Zeitraum, in dem gesucht wird. */
    public const LOOKBACK_DAYS = 400;

    /**
     * @return Collection<int, array{expense: Transaction, income: Transaction, days: int}>
     */
    public function suggestions(int $userId): Collection
    {
        $candidates = Transaction::query()
            ->where('user_id', $userId)
            ->whereIn('type', ['expense', 'income'])
            ->where('transfer_dismissed', false)
            ->whereDate('transaction_date', '>=', now()->subDays(self::LOOKBACK_DAYS)->toDateString())
            ->with(['account', 'category'])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get()
            ->filter(fn (Transaction $t) => $t->account !== null);

        $incomes = $candidates->where('type', 'income')->groupBy(fn (Transaction $t) => $this->amountKey($t));
        $used = [];
        $pairs = [];

        foreach ($candidates->where('type', 'expense') as $expense) {
            $best = null;
            $bestDays = null;

            foreach ($incomes[$this->amountKey($expense)] ?? [] as $income) {
                if (isset($used[$income->id]) || $income->account_id === $expense->account_id) {
                    continue;
                }

                $days = (int) abs($expense->transaction_date->diffInDays($income->transaction_date, false));

                if ($days <= self::MAX_DAYS && ($bestDays === null || $days < $bestDays)) {
                    $best = $income;
                    $bestDays = $days;
                }
            }

            if ($best !== null) {
                $used[$best->id] = true;
                $pairs[] = ['expense' => $expense, 'income' => $best, 'days' => $bestDays];
            }
        }

        return collect($pairs)->sortByDesc(fn ($pair) => $pair['expense']->transaction_date)->values();
    }

    public function count(int $userId): int
    {
        return $this->suggestions($userId)->count();
    }

    /**
     * Fasst ein Paar zu einer Umbuchung zusammen: Die Ausgabe wird zur
     * Umbuchung auf das Konto der Einnahme, die Einnahme wird gelöscht
     * (bleibt als gelöschter Datensatz, damit ein erneuter Import sie
     * nicht wieder anlegt).
     */
    public function merge(Transaction $expense, Transaction $income): bool
    {
        if (! $this->isValidPair($expense, $income)) {
            return false;
        }

        DB::transaction(function () use ($expense, $income) {
            $expense->forceFill([
                'type' => 'transfer',
                'transfer_account_id' => $income->account_id,
                'category_id' => null,
                'credit_card_id' => null,
            ])->save();

            $income->delete();
        });

        return true;
    }

    public function dismiss(Transaction $expense, Transaction $income): void
    {
        foreach ([$expense, $income] as $transaction) {
            $transaction->forceFill(['transfer_dismissed' => true])->save();
        }
    }

    public function isValidPair(Transaction $expense, Transaction $income): bool
    {
        return $expense->user_id === $income->user_id
            && $expense->type === 'expense'
            && $income->type === 'income'
            && $expense->account_id !== $income->account_id
            && $this->amountKey($expense) === $this->amountKey($income)
            && abs($expense->transaction_date->diffInDays($income->transaction_date, false)) <= self::MAX_DAYS;
    }

    private function amountKey(Transaction $transaction): string
    {
        return number_format((float) $transaction->amount, 2, '.', '');
    }
}
