<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Monatsbericht: ein Monat im Vergleich zum Vormonat und zum
 * üblichen Monat (Durchschnitt der letzten sechs Monate mit Buchungen),
 * mit Auffälligkeiten in Worten.
 *
 * Umbuchungen zählen nicht (weder Einnahme noch Ausgabe).
 */
class MonthlyReportService
{
    public const AVERAGE_MONTHS = 6;

    /** Ab wann eine Kategorie als auffällig gilt. */
    private const NOTABLE_RATIO = 0.25;

    private const NOTABLE_MIN_EUROS = 20;

    public function __construct(
        private BudgetService $budgets,
    ) {
    }

    public function build(User $user, Carbon $month): array
    {
        $month = $month->copy()->startOfMonth();
        $historyStart = $month->copy()->subMonths(12);

        $rows = $this->rows($user, $historyStart, $month->copy()->endOfMonth());

        $current = $rows->where('month', $month->format('Y-m'));
        $previousKey = $month->copy()->subMonth()->format('Y-m');
        $previous = $rows->where('month', $previousKey);

        // Durchschnitt: nur Monate, in denen es überhaupt Buchungen gab.
        $averageKeys = collect(range(1, self::AVERAGE_MONTHS))
            ->map(fn ($i) => $month->copy()->subMonths($i)->format('Y-m'))
            ->filter(fn ($key) => $rows->where('month', $key)->isNotEmpty())
            ->values();

        $totals = $this->totals($current);
        $previousTotals = $this->totals($previous);
        $averageTotals = $this->averageTotals($rows, $averageKeys);

        $categories = $this->categories($current, $previous, $rows, $averageKeys, $month->format('Y-m'));

        return [
            'month' => $month,
            'has_data' => $current->isNotEmpty(),
            'average_months' => $averageKeys->count(),

            'totals' => $totals,
            'previous_totals' => $previousTotals,
            'average_totals' => $averageTotals,

            'categories' => $categories,
            'largest' => $current->where('type', 'expense')->sortByDesc('amount')->take(5)->values(),
            'new_merchants' => $this->newMerchants($current, $rows, $month),
            'budgets' => $this->budgetStatus($user, $month),
            'insights' => $this->insights($totals, $previousTotals, $averageTotals, $categories, $averageKeys->count()),

            'transaction_count' => $current->count(),
            'days' => $month->isSameMonth(now()) ? now()->day : $month->daysInMonth,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Daten
    |--------------------------------------------------------------------------
    */

    private function rows(User $user, Carbon $from, Carbon $to): Collection
    {
        return Transaction::query()
            ->where('user_id', $user->id)
            ->whereIn('type', ['income', 'expense'])
            ->whereDate('transaction_date', '>=', $from->toDateString())
            ->whereDate('transaction_date', '<=', $to->toDateString())
            ->whereHas('account')
            ->with(['category:id,parent_id,name,icon,color', 'payee:id,name'])
            ->get(['id', 'category_id', 'payee_id', 'type', 'amount', 'transaction_date', 'description', 'merchant'])
            ->map(fn (Transaction $t) => [
                'id' => $t->id,
                'type' => $t->type,
                'amount' => round((float) $t->amount, 2),
                'date' => $t->transaction_date,
                'month' => $t->transaction_date->format('Y-m'),
                'category_id' => $t->category_id,
                'category_name' => $t->category?->display_name ?? 'Ohne Kategorie',
                'category_icon' => $t->category?->icon,
                'category_color' => $t->category?->color,
                'title' => $t->payee?->name ?: ($t->merchant ?: $t->description),
                'merchant_key' => $this->merchantKey($t->payee?->name ?: ($t->merchant ?: $t->description)),
            ]);
    }

    private function totals(Collection $rows): array
    {
        $income = round($rows->where('type', 'income')->sum('amount'), 2);
        $expense = round($rows->where('type', 'expense')->sum('amount'), 2);

        return [
            'income' => $income,
            'expense' => $expense,
            'balance' => round($income - $expense, 2),
            'savings_rate' => $income > 0 ? round(($income - $expense) / $income * 100, 1) : null,
        ];
    }

    private function averageTotals(Collection $rows, Collection $keys): ?array
    {
        if ($keys->isEmpty()) {
            return null;
        }

        $sum = ['income' => 0.0, 'expense' => 0.0];

        foreach ($keys as $key) {
            $totals = $this->totals($rows->where('month', $key));
            $sum['income'] += $totals['income'];
            $sum['expense'] += $totals['expense'];
        }

        $income = round($sum['income'] / $keys->count(), 2);
        $expense = round($sum['expense'] / $keys->count(), 2);

        return [
            'income' => $income,
            'expense' => $expense,
            'balance' => round($income - $expense, 2),
            'savings_rate' => $income > 0 ? round(($income - $expense) / $income * 100, 1) : null,
        ];
    }

    /**
     * Ausgaben je Kategorie: dieser Monat, Vormonat, Durchschnitt.
     */
    private function categories(Collection $current, Collection $previous, Collection $rows, Collection $averageKeys, string $monthKey): Collection
    {
        $expenses = $rows->where('type', 'expense');
        $keys = $expenses->whereIn('month', [...$averageKeys->all(), $monthKey])
            ->pluck('category_id')
            ->unique();

        $total = $current->where('type', 'expense')->sum('amount');

        return $keys->map(function ($categoryId) use ($current, $previous, $expenses, $averageKeys, $total) {
            $match = fn (Collection $collection) => $collection->where('type', 'expense')->where('category_id', $categoryId);

            $sample = $match($expenses)->first();
            $amount = round($match($current)->sum('amount'), 2);

            $average = $averageKeys->isEmpty()
                ? null
                : round($averageKeys->sum(fn ($key) => $match($expenses->where('month', $key))->sum('amount')) / $averageKeys->count(), 2);

            return [
                'id' => $categoryId,
                'name' => $sample['category_name'],
                'icon' => $sample['category_icon'],
                'color' => $sample['category_color'],
                'amount' => $amount,
                'previous' => round($match($previous)->sum('amount'), 2),
                'average' => $average,
                'share' => $total > 0 ? round($amount / $total * 100, 1) : 0,
                'difference' => $average === null ? null : round($amount - $average, 2),
                'count' => $match($current)->count(),
            ];
        })
            ->filter(fn ($category) => $category['amount'] > 0 || ($category['average'] ?? 0) > 0)
            ->sortByDesc('amount')
            ->values();
    }

    /**
     * Händler, bei denen im Monat zum ersten Mal (seit 12 Monaten)
     * etwas ausgegeben wurde.
     */
    private function newMerchants(Collection $current, Collection $rows, Carbon $month): Collection
    {
        $known = $rows->where('type', 'expense')
            ->where('month', '<', $month->format('Y-m'))
            ->pluck('merchant_key')
            ->filter()
            ->flip();

        return $current->where('type', 'expense')
            ->filter(fn ($row) => $row['merchant_key'] !== '' && ! $known->has($row['merchant_key']))
            ->groupBy('merchant_key')
            ->map(fn (Collection $group) => [
                'title' => $group->first()['title'],
                'amount' => round($group->sum('amount'), 2),
                'count' => $group->count(),
            ])
            ->sortByDesc('amount')
            ->values();
    }

    private function budgetStatus(User $user, Carbon $month): Collection
    {
        return $user->budgets()
            ->where('is_active', true)
            ->with('categories')
            ->get()
            ->map(fn ($budget) => ['budget' => $budget, 'result' => $this->budgets->calculate($budget, $user, $month->copy())])
            ->filter(fn ($item) => $item['result']['applicable'])
            ->sortByDesc(fn ($item) => $item['result']['percentage'])
            ->values();
    }


    /*
    |--------------------------------------------------------------------------
    | Auffälligkeiten
    |--------------------------------------------------------------------------
    */

    /**
     * @return list<array{tone: string, icon: string, text: string}>
     */
    private function insights(array $totals, array $previous, ?array $average, Collection $categories, int $averageMonths): array
    {
        $insights = [];
        $euro = fn (float $value) => number_format($value, 2, ',', '.') . ' €';

        if ($average !== null && $average['expense'] > 0) {
            $change = ($totals['expense'] - $average['expense']) / $average['expense'];

            if (abs($change) >= 0.10) {
                $insights[] = [
                    'tone' => $change > 0 ? 'negative' : 'positive',
                    'icon' => $change > 0 ? 'trending-up' : 'trending-down',
                    'text' => sprintf(
                        'Du hast %s %s ausgegeben als in einem üblichen Monat (Ø %s).',
                        $euro(abs($totals['expense'] - $average['expense'])),
                        $change > 0 ? 'mehr' : 'weniger',
                        $euro($average['expense'])
                    ),
                ];
            }
        }

        if ($totals['savings_rate'] !== null) {
            $insights[] = [
                'tone' => $totals['savings_rate'] >= 10 ? 'positive' : ($totals['savings_rate'] < 0 ? 'negative' : 'neutral'),
                'icon' => 'percent',
                'text' => $totals['savings_rate'] >= 0
                    ? sprintf('Sparquote %s %% – %s von deinen Einnahmen sind übrig geblieben.', number_format($totals['savings_rate'], 1, ',', '.'), $euro($totals['balance']))
                    : sprintf('Du hast %s mehr ausgegeben als eingenommen.', $euro(abs($totals['balance']))),
            ];
        }

        foreach ($categories->filter(fn ($c) => $c['average'] !== null) as $category) {
            $difference = $category['difference'];

            if (abs($difference) < self::NOTABLE_MIN_EUROS || $category['average'] <= 0) {
                continue;
            }

            $ratio = $difference / $category['average'];

            if (abs($ratio) >= self::NOTABLE_RATIO) {
                $insights[] = [
                    'tone' => $ratio > 0 ? 'negative' : 'positive',
                    'icon' => 'tag',
                    'text' => sprintf(
                        '%s: %s – %d %% %s als üblich (Ø %s).',
                        $category['name'],
                        $euro($category['amount']),
                        (int) round(abs($ratio) * 100),
                        $ratio > 0 ? 'mehr' : 'weniger',
                        $euro($category['average'])
                    ),
                ];
            }
        }

        if ($averageMonths === 0 && $totals['expense'] > 0) {
            $insights[] = [
                'tone' => 'neutral',
                'icon' => 'calendar',
                'text' => 'Für Vergleiche mit einem üblichen Monat fehlen noch ältere Buchungen.',
            ];
        }

        return $insights;
    }

    /**
     * Händler-Schlüssel ohne Ziffern/Satzzeichen („REWE Markt 1234“ = „Rewe Markt 99“).
     */
    private function merchantKey(?string $text): string
    {
        $text = mb_strtolower((string) $text);
        $text = preg_replace('/[^\pL ]+/u', ' ', $text);

        return Str::limit(Str::squish($text), 40, '');
    }
}
