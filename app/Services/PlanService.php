<?php

namespace App\Services;

use App\Models\PlanGroup;
use App\Models\PlanItem;
use App\Models\PlanMonth;
use App\Models\User;
use App\Support\CsvCell;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Monatsplan: feste Einnahmen und Ausgaben pro Monat, in Gruppen.
 *
 * Der Plan dient nur zum Planen. Er erzeugt nie Buchungen und
 * verändert keine Kontostände.
 */
class PlanService
{
    public function __construct(
        private CsvImportService $csv,
    ) {
    }

    public static function monthStart(string $key): Carbon
    {
        $date = Carbon::createFromFormat('!Y-m', $key);

        return $date->startOfMonth();
    }

    public function find(User $user, Carbon $month): ?PlanMonth
    {
        return PlanMonth::query()
            ->where('user_id', $user->id)
            ->whereDate('month', $month->copy()->startOfMonth()->toDateString())
            ->with('groups.items')
            ->first();
    }

    /**
     * Letzter Plan vor dem angegebenen Monat.
     */
    public function previous(User $user, Carbon $month): ?PlanMonth
    {
        return PlanMonth::query()
            ->where('user_id', $user->id)
            ->whereDate('month', '<', $month->copy()->startOfMonth()->toDateString())
            ->orderByDesc('month')
            ->first();
    }

    /**
     * Monate, für die es einen Plan gibt (Format Y-m).
     *
     * @return Collection<int, string>
     */
    public function plannedMonths(User $user): Collection
    {
        return PlanMonth::query()
            ->where('user_id', $user->id)
            ->orderByDesc('month')
            ->pluck('month')
            ->map(fn ($month) => $month->format('Y-m'));
    }

    /**
     * Plan des Monats holen oder anlegen (wasRecentlyCreated zeigt, ob neu).
     */
    private function open(User $user, Carbon $start): PlanMonth
    {
        $existing = PlanMonth::query()
            ->where('user_id', $user->id)
            ->whereDate('month', $start->toDateString())
            ->first();

        return $existing ?? PlanMonth::create(['user_id' => $user->id, 'month' => $start->toDateString()]);
    }

    /**
     * Leerer Plan mit einer Gruppe für Einnahmen.
     */
    public function createEmpty(User $user, Carbon $month): PlanMonth
    {
        return DB::transaction(function () use ($user, $month) {
            $plan = $this->open($user, $month->copy()->startOfMonth());

            if ($plan->wasRecentlyCreated) {
                $plan->groups()->create(['name' => 'Einnahmen', 'kind' => PlanGroup::KIND_INCOME, 'sort_order' => 0]);
            }

            return $plan;
        });
    }

    /**
     * Plan aus dem letzten vorhandenen Monat übernehmen. Posten, deren
     * letzte Rate vor dem neuen Monat liegt, fallen weg.
     */
    public function copyFromPrevious(User $user, Carbon $month): ?PlanMonth
    {
        $source = $this->previous($user, $month);

        if (! $source) {
            return null;
        }

        $start = $month->copy()->startOfMonth();

        return DB::transaction(function () use ($user, $source, $start) {
            $plan = $this->open($user, $start);

            if (! $plan->wasRecentlyCreated) {
                return $plan;
            }

            foreach ($source->groups()->with('items')->get() as $group) {
                $copy = $plan->groups()->create($group->only(['name', 'kind', 'icon', 'color', 'sort_order']));

                foreach ($group->items as $item) {
                    if ($item->ends_on && $item->ends_on->lt($start)) {
                        continue;
                    }

                    $copy->items()->create($item->only(['name', 'amount', 'note', 'interval', 'starts_on', 'ends_on', 'sort_order']));
                }
            }

            return $plan;
        });
    }


    /*
    |--------------------------------------------------------------------------
    | Summen
    |--------------------------------------------------------------------------
    */

    /**
     * @return array{income: float, expense: float, balance: float, rates: float, loans: float, has_rates: bool, has_loans: bool, groups: Collection}
     */
    public function totals(PlanMonth $plan): array
    {
        $plan->loadMissing('groups.items');

        $sum = fn (PlanGroup $group) => self::groupSum($group, $plan->month);

        $income = round($plan->groups->filter->isIncome()->sum($sum), 2);
        $expenseGroups = $plan->groups->reject->isIncome();
        $expense = round($expenseGroups->sum($sum), 2);

        $kind = fn (string $kind) => $plan->groups->where('kind', $kind);

        return [
            'income' => $income,
            'expense' => $expense,
            'balance' => round($income - $expense, 2),
            'rates' => round($kind(PlanGroup::KIND_RATE)->sum($sum), 2),
            'loans' => round($kind(PlanGroup::KIND_LOAN)->sum($sum), 2),
            'has_rates' => $kind(PlanGroup::KIND_RATE)->isNotEmpty(),
            'has_loans' => $kind(PlanGroup::KIND_LOAN)->isNotEmpty(),
            'groups' => $expenseGroups
                ->map(fn (PlanGroup $group) => ['group' => $group, 'sum' => $sum($group)])
                ->values(),
        ];
    }

    /**
     * Zahlungen eines Monats für den Kalender.
     *
     * Plan-Posten mit Zahlungstag liegen auf ihrem Tag (kürzere Monate: letzter Tag),
     * Posten ohne Tag stehen in „unscheduled“. Dazu kommen die Raten der Kredite.
     *
     * @return array{days: array<int, list<array{title: string, amount: float, kind: string, group: string, source: string}>>, unscheduled: list<array{title: string, amount: float, kind: string, group: string}>, plan: ?PlanMonth, totals: array{income: float, expense: float}}
     */
    public function calendar(User $user, Carbon $month): array
    {
        $month = $month->copy()->startOfMonth();
        $lastDay = $month->daysInMonth;

        $days = [];
        $unscheduled = [];
        $plan = $this->find($user, $month);

        if ($plan) {
            foreach ($plan->groups as $group) {
                foreach ($group->items as $item) {
                    if (! $item->isDueIn($month)) {
                        continue;
                    }

                    $entry = [
                        'title' => $item->name,
                        'amount' => round((float) $item->amount, 2),
                        'kind' => $group->kind,
                        'group' => $group->name,
                        'source' => 'plan',
                    ];

                    if ($item->due_day) {
                        $days[min($item->due_day, $lastDay)][] = $entry;
                    } else {
                        $unscheduled[] = $entry;
                    }
                }
            }
        }

        // Kreditraten des Monats (nur wenn der Plan keinen Kredit-Posten gleichen Namens enthält).
        $planned = collect($days)->flatten(1)->merge($unscheduled)->pluck('title')->map(fn ($title) => mb_strtolower($title))->all();

        $payments = \App\Models\LoanPayment::query()
            ->whereHas('loan', fn ($query) => $query->where('user_id', $user->id)->where('is_active', true))
            ->where('payment_type', 'regular')
            ->whereBetween('due_date', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString()])
            ->with('loan:id,name')
            ->orderBy('due_date')
            ->get();

        foreach ($payments as $payment) {
            if (in_array(mb_strtolower($payment->loan->name), $planned, true)) {
                continue;
            }

            $days[(int) $payment->due_date->format('j')][] = [
                'title' => $payment->loan->name,
                'amount' => round((float) $payment->amount, 2),
                'kind' => PlanGroup::KIND_LOAN,
                'group' => 'Kredite',
                'source' => 'loan',
            ];
        }

        ksort($days);

        $all = collect($days)->flatten(1)->merge($unscheduled);

        return [
            'days' => $days,
            'unscheduled' => $unscheduled,
            'plan' => $plan,
            'totals' => [
                'income' => round($all->where('kind', PlanGroup::KIND_INCOME)->sum('amount'), 2),
                'expense' => round($all->where('kind', '!=', PlanGroup::KIND_INCOME)->sum('amount'), 2),
            ],
        ];
    }

    /**
     * Nächste Zahlungen für das Cockpit: ab heute (laufender Monat) bzw. ab dem
     * Monatsersten; sind es wenige, folgt der Anfang des nächsten Monats.
     *
     * @return Collection<int, array>
     */
    public function upcoming(User $user, Carbon $month, int $limit = 5): Collection
    {
        $today = now();
        $isCurrentMonth = $month->isSameMonth($today);

        $collect = function (Carbon $forMonth, int $fromDay) use ($user) {
            $calendar = $this->calendar($user, $forMonth);

            return collect($calendar['days'])
                ->filter(fn ($entries, $day) => $day >= $fromDay)
                ->flatMap(fn ($entries, $day) => collect($entries)->map(fn ($entry) => $entry + ['date' => $forMonth->copy()->day($day)]));
        };

        $upcoming = $collect($month, $isCurrentMonth ? $today->day : 1);

        if ($isCurrentMonth && $upcoming->count() < $limit) {
            $upcoming = $upcoming->merge($collect($month->copy()->addMonth()->startOfMonth(), 1));
        }

        return $upcoming->take($limit)->values();
    }

    /**
     * Summe einer Gruppe im Monat: nur Posten, die in diesem Monat fällig sind.
     */
    public static function groupSum(PlanGroup $group, Carbon $month): float
    {
        return round((float) $group->items->filter(fn (PlanItem $item) => $item->isDueIn($month))->sum('amount'), 2);
    }

    /**
     * Wie viele Monate der Posten noch läuft: null ohne Enddatum,
     * 0 = letzte Rate in diesem Monat, negativ = bereits beendet.
     */
    public static function monthsLeft(?Carbon $endsOn, Carbon $month): ?int
    {
        if (! $endsOn) {
            return null;
        }

        return ($endsOn->year - $month->year) * 12 + $endsOn->month - $month->month;
    }


    /*
    |--------------------------------------------------------------------------
    | Eingaben
    |--------------------------------------------------------------------------
    */

    /**
     * Betrag aus Eingabe wie „1.234,56“, „12,5“ oder „12.50“. Negative
     * und ungültige Werte ergeben null.
     */
    public static function parseAmount(?string $value): ?float
    {
        $value = trim(str_replace(['€', ' ', "\u{00A0}"], '', (string) $value));

        if ($value === '' || ! preg_match('/^\d[\d.,]*$/', $value)) {
            return null;
        }

        if (str_contains($value, ',')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif (substr_count($value, '.') > 1 || preg_match('/\.\d{3}$/', $value)) {
            // „1.234“ oder „1.234.567“: Punkt als Tausendertrenner.
            $value = str_replace('.', '', $value);
        }

        return is_numeric($value) ? round((float) $value, 2) : null;
    }

    public function nextSortOrder(PlanMonth $plan): int
    {
        return (int) $plan->groups()->max('sort_order') + 1;
    }

    public function nextItemSortOrder(PlanGroup $group): int
    {
        return (int) $group->items()->max('sort_order') + 1;
    }


    /*
    |--------------------------------------------------------------------------
    | CSV-Import
    |--------------------------------------------------------------------------
    */

    private const KIND_WORDS = [
        'ausgabe' => PlanGroup::KIND_EXPENSE,
        'ausgaben' => PlanGroup::KIND_EXPENSE,
        'einnahme' => PlanGroup::KIND_INCOME,
        'einnahmen' => PlanGroup::KIND_INCOME,
        'einkünfte' => PlanGroup::KIND_INCOME,
        'rate' => PlanGroup::KIND_RATE,
        'raten' => PlanGroup::KIND_RATE,
        'kredit' => PlanGroup::KIND_LOAN,
        'kredite' => PlanGroup::KIND_LOAN,
    ];

    private const RHYTHM_WORDS = [
        'monatlich' => PlanItem::MONTHLY,
        'vierteljährlich' => 'quarterly',
        'quartalsweise' => 'quarterly',
        'halbjährlich' => 'semiannual',
        'jährlich' => 'yearly',
    ];

    private const KIND_LABELS = [
        PlanGroup::KIND_EXPENSE => 'Ausgabe',
        PlanGroup::KIND_INCOME => 'Einnahme',
        PlanGroup::KIND_RATE => 'Rate',
        PlanGroup::KIND_LOAN => 'Kredit',
    ];

    public const CSV_HEADER = ['Gruppe', 'Typ', 'Beschreibung', 'Betrag', 'Bemerkung', 'Rhythmus', 'Tag', 'Beginn', 'Letzte Rate'];

    /**
     * Zeilen für den CSV-Export des Plans, im selben Aufbau wie der Import
     * (Betrag mit Komma, Datum als TT.MM.JJJJ), damit sich die Datei wieder
     * einlesen lässt. Texte sind gegen Formeln in Tabellenprogrammen geschützt.
     *
     * @return list<list<string>>
     */
    public function csvRows(PlanMonth $plan): array
    {
        $plan->loadMissing('groups.items');

        $rows = [];

        foreach ($plan->groups as $group) {
            foreach ($group->items as $item) {
                $rows[] = [
                    CsvCell::safe($group->name),
                    self::KIND_LABELS[$group->kind] ?? self::KIND_LABELS[PlanGroup::KIND_EXPENSE],
                    CsvCell::safe($item->name),
                    number_format((float) $item->amount, 2, ',', ''),
                    CsvCell::safe((string) $item->note),
                    $item->intervalLabel(),
                    $item->due_day ? (string) $item->due_day : '',
                    $item->starts_on?->format('d.m.Y') ?? '',
                    $item->ends_on?->format('d.m.Y') ?? '',
                ];
            }
        }

        return $rows;
    }

    /**
     * Posten aus einer CSV-Datei in den Plan übernehmen (bestehende
     * Posten bleiben). Spalten: Gruppe, Typ, Beschreibung, Betrag,
     * Bemerkung, Rhythmus, Tag, Beginn, Letzte Rate. Alles außer Gruppe,
     * Beschreibung und Betrag ist optional.
     *
     * @return array{groups: int, items: int, skipped: int}
     */
    public function import(PlanMonth $plan, string $content): array
    {
        $content = $this->csv->normalize($content);
        $rows = $this->csv->rows($content, $this->csv->detectDelimiter($content));

        if (count($rows) < 2) {
            throw ValidationException::withMessages(['file' => 'Die Datei enthält keine Zeilen.']);
        }

        $headers = array_map(fn ($header) => mb_strtolower(trim($header)), array_shift($rows));
        $column = fn (array $names) => collect($names)->map(fn ($name) => array_search($name, $headers, true))->first(fn ($index) => $index !== false);

        $group = $column(['gruppe']);
        $kind = $column(['typ', 'art']);
        $name = $column(['beschreibung', 'name', 'posten']);
        $amount = $column(['betrag']);
        $note = $column(['bemerkung', 'notiz']);
        $ends = $column(['letzte rate', 'ende']);
        $starts = $column(['beginn', 'start', 'erste fälligkeit']);
        $day = $column(['tag', 'zahlungstag']);
        $rhythm = $column(['rhythmus', 'intervall']);

        if ($group === null || $name === null || $amount === null) {
            throw ValidationException::withMessages(['file' => 'Die Kopfzeile braucht die Spalten „Gruppe“, „Beschreibung“ und „Betrag“.']);
        }

        $stats = ['groups' => 0, 'items' => 0, 'skipped' => 0];

        DB::transaction(function () use ($plan, $rows, $group, $kind, $name, $amount, $note, $ends, $starts, $rhythm, $day, &$stats) {
            $groups = $plan->groups()->get()->keyBy(fn (PlanGroup $g) => mb_strtolower($g->name));
            $sort = $this->nextSortOrder($plan);

            foreach ($rows as $row) {
                $groupName = trim($row[$group] ?? '');
                $itemName = trim($row[$name] ?? '');
                $value = $this->csv->parseAmount($row[$amount] ?? '');

                if ($groupName === '' || $itemName === '' || $value === null || $value < 0) {
                    $stats['skipped']++;

                    continue;
                }

                $key = mb_strtolower($groupName);

                if (! $groups->has($key)) {
                    $kindWord = $kind !== null ? mb_strtolower(trim($row[$kind] ?? '')) : '';

                    $groups[$key] = $plan->groups()->create([
                        'name' => mb_substr($groupName, 0, 255),
                        'kind' => self::KIND_WORDS[$kindWord] ?? PlanGroup::KIND_EXPENSE,
                        'sort_order' => $sort++,
                    ]);
                    $stats['groups']++;
                }

                $target = $groups[$key];

                $rhythmWord = $rhythm !== null ? mb_strtolower(trim($row[$rhythm] ?? '')) : '';
                $startsOn = $starts !== null ? $this->csv->parseDate($row[$starts] ?? '') : null;
                $interval = self::RHYTHM_WORDS[$rhythmWord] ?? PlanItem::MONTHLY;

                // Ohne Beginn lässt sich nicht sagen, wann gezahlt wird: dann monatlich.
                if ($interval !== PlanItem::MONTHLY && ! $startsOn) {
                    $interval = PlanItem::MONTHLY;
                }

                $target->items()->create([
                    'name' => mb_substr($itemName, 0, 255),
                    'amount' => $value,
                    'interval' => $interval,
                    'due_day' => $day !== null && ctype_digit(trim($row[$day] ?? '')) && (int) trim($row[$day]) >= 1 && (int) trim($row[$day]) <= 31 ? (int) trim($row[$day]) : null,
                    'starts_on' => $startsOn?->toDateString(),
                    'note' => $note !== null && trim($row[$note] ?? '') !== '' ? mb_substr(trim($row[$note]), 0, 255) : null,
                    'ends_on' => $ends !== null ? $this->csv->parseDate($row[$ends] ?? '')?->toDateString() : null,
                    'sort_order' => $this->nextItemSortOrder($target),
                ]);
                $stats['items']++;
            }
        });

        return $stats;
    }
}
