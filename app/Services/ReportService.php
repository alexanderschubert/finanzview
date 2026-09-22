<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Auswertungen über Einnahmen und Ausgaben.
 *
 * Alle Beträge werden intern in Cent (int) summiert, damit
 * keine Rundungsfehler durch Fließkommazahlen entstehen.
 * Umbuchungen (type = transfer) sind keine Einnahmen oder
 * Ausgaben und werden deshalb nicht berücksichtigt.
 */
class ReportService
{
    /**
     * Wählbare Zeiträume für die Oberfläche.
     */
    public const PERIODS = [
        'this_month' => 'Dieser Monat',
        'last_month' => 'Letzter Monat',
        'last_3_months' => 'Letzte 3 Monate',
        'last_6_months' => 'Letzte 6 Monate',
        'last_12_months' => 'Letzte 12 Monate',
        'this_year' => 'Dieses Jahr',
        'last_year' => 'Letztes Jahr',
        'custom' => 'Benutzerdefiniert',
    ];

    /**
     * Anzahl der Kategorien, die in der Monatsmatrix einzeln
     * angezeigt werden. Der Rest landet in "Sonstige".
     */
    private const MATRIX_CATEGORIES = 8;

    /**
     * Zeitraum (Start und Ende, jeweils ganze Tage) aus dem
     * gewählten Preset bzw. den eigenen Datumswerten ermitteln.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function resolvePeriod(
        string $period,
        ?string $from = null,
        ?string $to = null,
        ?CarbonImmutable $today = null
    ): array {
        $today = ($today ?? CarbonImmutable::now())->startOfDay();

        [$start, $end] = match ($period) {
            'last_month' => [
                $today->subMonthNoOverflow()->startOfMonth(),
                $today->subMonthNoOverflow()->endOfMonth(),
            ],
            'last_3_months' => [
                $today->subMonthsNoOverflow(2)->startOfMonth(),
                $today->endOfMonth(),
            ],
            'last_6_months' => [
                $today->subMonthsNoOverflow(5)->startOfMonth(),
                $today->endOfMonth(),
            ],
            'last_12_months' => [
                $today->subMonthsNoOverflow(11)->startOfMonth(),
                $today->endOfMonth(),
            ],
            'this_year' => [
                $today->startOfYear(),
                $today->endOfYear(),
            ],
            'last_year' => [
                $today->subYear()->startOfYear(),
                $today->subYear()->endOfYear(),
            ],
            'custom' => [
                $this->parseDate($from) ?? $today->startOfMonth(),
                $this->parseDate($to) ?? $today->endOfMonth(),
            ],
            default => [
                $today->startOfMonth(),
                $today->endOfMonth(),
            ],
        };

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end, $start];
        }

        return [$start->startOfDay(), $end->endOfDay()];
    }

    /**
     * Vergleichszeitraum direkt davor mit gleicher Länge.
     *
     * Umfasst der Zeitraum ganze Monate, wird auch der
     * Vergleich in ganzen Monaten gebildet (z. B. Februar
     * wird mit Januar verglichen, nicht mit 28 Tagen davor).
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function previousPeriod(
        CarbonImmutable $start,
        CarbonImmutable $end
    ): array {
        $wholeMonths =
            $start->day === 1
            && $end->isSameDay($end->endOfMonth());

        if ($wholeMonths) {
            $months = $this->monthKeys($start, $end)->count();

            return [
                $start->subMonthsNoOverflow($months)->startOfMonth(),
                $start->subDay()->endOfDay(),
            ];
        }

        $days = (int) $start->diffInDays($end->startOfDay()) + 1;

        return [
            $start->subDays($days)->startOfDay(),
            $start->subDay()->endOfDay(),
        ];
    }

    /**
     * Vollständigen Bericht erstellen.
     */
    public function build(
        User $user,
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?int $accountId = null
    ): array {
        [$previousStart, $previousEnd] =
            $this->previousPeriod($start, $end);

        $rows = $this->rows($user, $start, $end, $accountId);

        $previousRows = $this->rows(
            $user,
            $previousStart,
            $previousEnd,
            $accountId
        );

        $totals = $this->totals($rows);
        $previousTotals = $this->totals($previousRows);

        $monthKeys = $this->monthKeys($start, $end);

        return [
            'start' => $start,
            'end' => $end,
            'previous_start' => $previousStart,
            'previous_end' => $previousEnd,
            'month_count' => $monthKeys->count(),

            'totals' => $totals,
            'previous_totals' => $previousTotals,

            'changes' => [
                'income' => $this->change(
                    $totals['income'],
                    $previousTotals['income']
                ),
                'expense' => $this->change(
                    $totals['expense'],
                    $previousTotals['expense']
                ),
                'balance' => $this->change(
                    $totals['balance'],
                    $previousTotals['balance']
                ),
            ],

            'average_monthly_expense' => $monthKeys->count() > 0
                ? round($totals['expense'] / $monthKeys->count(), 2)
                : 0.0,

            'monthly' => $this->monthly($rows, $monthKeys),

            'expense_categories' => $this->byCategory(
                $rows,
                $previousRows,
                'expense'
            ),

            'income_categories' => $this->byCategory(
                $rows,
                $previousRows,
                'income'
            ),

            'category_matrix' => $this->categoryMatrix($rows, $monthKeys),

            'top_merchants' => $this->topMerchants($rows),

            'largest_expenses' => $rows
                ->where('type', 'expense')
                ->sortByDesc('cents')
                ->take(10)
                ->map(fn (array $row) => [
                    'date' => $row['date'],
                    'description' => $row['description'],
                    'merchant' => $row['merchant'],
                    'category' => $row['category_name'],
                    'amount' => $this->euros($row['cents']),
                ])
                ->values(),

            'transaction_count' => $rows->count(),
        ];
    }

    /**
     * Relevante Buchungen laden und vereinheitlichen.
     */
    private function rows(
        User $user,
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?int $accountId
    ): Collection {
        return Transaction::query()
            ->where('user_id', $user->id)
            ->whereIn('type', ['income', 'expense'])
            // Obergrenze exklusiv (Folgetag), damit auch Werte mit
            // Uhrzeit (SQLite speichert "Y-m-d 00:00:00") passen.
            ->where('transaction_date', '>=', $start->toDateString())
            ->where('transaction_date', '<', $end->addDay()->toDateString())
            ->whereHas('account')
            ->when(
                $accountId,
                fn ($query) => $query->where('account_id', $accountId)
            )
            ->with('category:id,name,icon,color')
            ->get([
                'id',
                'account_id',
                'category_id',
                'type',
                'amount',
                'transaction_date',
                'description',
                'merchant',
            ])
            ->map(function (Transaction $transaction) {
                $date = CarbonImmutable::parse(
                    $transaction->transaction_date
                );

                return [
                    'type' => $transaction->type,
                    'cents' => (int) round(
                        ((float) $transaction->amount) * 100
                    ),
                    'date' => $date,
                    'month' => $date->format('Y-m'),
                    'category_id' => $transaction->category_id !== null
                        ? (int) $transaction->category_id
                        : null,
                    'category_name' =>
                        $transaction->category?->name ?? 'Ohne Kategorie',
                    'category_icon' =>
                        $transaction->category?->icon ?? '📦',
                    'category_color' =>
                        $transaction->category?->color,
                    'description' => (string) $transaction->description,
                    'merchant' => $transaction->merchant,
                ];
            });
    }

    private function totals(Collection $rows): array
    {
        $income = $rows->where('type', 'income')->sum('cents');
        $expense = $rows->where('type', 'expense')->sum('cents');

        return [
            'income' => $this->euros($income),
            'expense' => $this->euros($expense),
            'balance' => $this->euros(($income - $expense)),
            'savings_rate' => $income > 0
                ? round((($income - $expense) / $income) * 100, 1)
                : null,
        ];
    }

    /**
     * Veränderung in Prozent. Null, wenn es keinen sinnvollen
     * Vergleichswert gibt (Vorperiode = 0).
     */
    private function change(float $current, float $previous): ?float
    {
        if (abs($previous) < 0.005) {
            return null;
        }

        return round((($current - $previous) / abs($previous)) * 100, 1);
    }

    /**
     * Monate (Y-m) im Zeitraum.
     */
    private function monthKeys(
        CarbonImmutable $start,
        CarbonImmutable $end
    ): Collection {
        return collect(
            CarbonPeriod::create(
                $start->startOfMonth(),
                '1 month',
                $end->startOfMonth()
            )
        )->map(fn ($month) => $month->format('Y-m'))->values();
    }

    private function monthly(
        Collection $rows,
        Collection $monthKeys
    ): Collection {
        $byMonth = $rows->groupBy('month');

        return $monthKeys->map(function (string $key) use ($byMonth) {
            $monthRows = $byMonth->get($key, collect());

            $income = $monthRows->where('type', 'income')->sum('cents');
            $expense = $monthRows->where('type', 'expense')->sum('cents');

            $month = CarbonImmutable::createFromFormat('!Y-m', $key);

            return [
                'key' => $key,
                'label' => $month->translatedFormat('M Y'),
                'short_label' => $month->translatedFormat('M'),
                'income' => $this->euros($income),
                'expense' => $this->euros($expense),
                'balance' => $this->euros(($income - $expense)),
            ];
        });
    }

    private function byCategory(
        Collection $rows,
        Collection $previousRows,
        string $type
    ): Collection {
        $rows = $rows->where('type', $type);
        $total = max(1, $rows->sum('cents'));

        $previous = $previousRows
            ->where('type', $type)
            ->groupBy(fn ($row) => $row['category_id'] ?? 0)
            ->map(fn ($group) => $group->sum('cents'));

        return $rows
            ->groupBy(fn ($row) => $row['category_id'] ?? 0)
            ->map(function (Collection $group, $categoryId) use ($total, $previous) {
                $first = $group->first();
                $cents = $group->sum('cents');
                $previousCents = $previous->get($categoryId, 0);

                return [
                    'id' => $categoryId ?: null,
                    'name' => $first['category_name'],
                    'icon' => $first['category_icon'],
                    'color' => $first['category_color'],
                    'amount' => $this->euros($cents),
                    'count' => $group->count(),
                    'share' => round(($cents / $total) * 100, 1),
                    'previous_amount' => $this->euros($previousCents),
                    'change' => $this->change(
                        $this->euros($cents),
                        $this->euros($previousCents)
                    ),
                ];
            })
            ->sortByDesc('amount')
            ->values();
    }

    /**
     * Ausgaben je Kategorie und Monat (Top-Kategorien +
     * "Sonstige").
     */
    private function categoryMatrix(
        Collection $rows,
        Collection $monthKeys
    ): array {
        $expenses = $rows->where('type', 'expense');

        $ranked = $expenses
            ->groupBy(fn ($row) => $row['category_id'] ?? 0)
            ->map(fn ($group) => [
                'id' => $group->first()['category_id'] ?? 0,
                'name' => $group->first()['category_name'],
                'icon' => $group->first()['category_icon'],
                'total' => $group->sum('cents'),
            ])
            ->sortByDesc('total')
            ->values();

        $top = $ranked->take(self::MATRIX_CATEGORIES);
        $topIds = $top->pluck('id')->all();

        $matrixRows = $top->map(function ($category) use ($expenses, $monthKeys) {
            $categoryRows = $expenses->filter(
                fn ($row) => ($row['category_id'] ?? 0) === $category['id']
            );

            return [
                'name' => $category['name'],
                'icon' => $category['icon'],
                'months' => $monthKeys->mapWithKeys(fn ($key) => [
                    $key => $this->euros($categoryRows
                        ->where('month', $key)
                        ->sum('cents')),
                ])->all(),
                'total' => $this->euros($category['total']),
            ];
        });

        if ($ranked->count() > self::MATRIX_CATEGORIES) {
            $otherRows = $expenses->reject(
                fn ($row) => in_array($row['category_id'] ?? 0, $topIds, true)
            );

            $matrixRows->push([
                'name' => 'Sonstige',
                'icon' => '➕',
                'months' => $monthKeys->mapWithKeys(fn ($key) => [
                    $key => $this->euros($otherRows
                        ->where('month', $key)
                        ->sum('cents')),
                ])->all(),
                'total' => $this->euros($otherRows->sum('cents')),
            ]);
        }

        $max = $matrixRows
            ->flatMap(fn ($row) => array_values($row['months']))
            ->max() ?: 0;

        return [
            'rows' => $matrixRows->values(),
            'max' => $max,
        ];
    }

    /**
     * Ausgaben nach Händler (Feld "merchant"; falls leer, die
     * Beschreibung). Groß-/Kleinschreibung wird ignoriert.
     */
    private function topMerchants(Collection $rows): Collection
    {
        return $rows
            ->where('type', 'expense')
            ->map(function ($row) {
                $name = trim((string) ($row['merchant'] ?: $row['description']));

                return $row + [
                    'merchant_name' => $name !== '' ? $name : 'Unbekannt',
                ];
            })
            ->groupBy(fn ($row) => mb_strtolower($row['merchant_name']))
            ->map(fn (Collection $group) => [
                'name' => $group->first()['merchant_name'],
                'amount' => $this->euros($group->sum('cents')),
                'count' => $group->count(),
                'average' => round($group->sum('cents') / $group->count() / 100, 2),
            ])
            ->sortByDesc('amount')
            ->take(10)
            ->values();
    }

    private function euros(int|float $cents): float
    {
        return round($cents / 100, 2);
    }

    private function parseDate(?string $value): ?CarbonImmutable
    {
        if (!$value) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('!Y-m-d', $value) ?: null;
        } catch (\Throwable) {
            return null;
        }
    }
}
