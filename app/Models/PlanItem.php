<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanItem extends Model
{
    public const MONTHLY = 'monthly';

    /**
     * Rhythmus: Beschriftung und Abstand in Monaten.
     */
    public const INTERVALS = [
        'monthly' => ['label' => 'Monatlich', 'months' => 1],
        'quarterly' => ['label' => 'Vierteljährlich', 'months' => 3],
        'semiannual' => ['label' => 'Halbjährlich', 'months' => 6],
        'yearly' => ['label' => 'Jährlich', 'months' => 12],
    ];

    protected $fillable = [
        'plan_group_id',
        'name',
        'amount',
        'note',
        'interval',
        'due_day',
        'starts_on',
        'ends_on',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    private static function monthIndex(Carbon $date): int
    {
        return $date->year * 12 + $date->month;
    }

    public function intervalMonths(): int
    {
        return self::INTERVALS[$this->interval]['months'] ?? 1;
    }

    public function intervalLabel(): string
    {
        return self::INTERVALS[$this->interval]['label'] ?? self::INTERVALS[self::MONTHLY]['label'];
    }

    /**
     * Zustand des Postens in einem Monat:
     * due = wird in diesem Monat gezahlt, upcoming = beginnt später,
     * ended = ist vorbei, off = läuft, aber in diesem Monat ist nichts fällig.
     */
    public function stateIn(Carbon $month): string
    {
        $index = self::monthIndex($month);

        if ($this->starts_on && $index < self::monthIndex($this->starts_on)) {
            return 'upcoming';
        }

        if ($this->ends_on && $index > self::monthIndex($this->ends_on)) {
            return 'ended';
        }

        $step = $this->intervalMonths();

        if ($step > 1 && $this->starts_on && ($index - self::monthIndex($this->starts_on)) % $step !== 0) {
            return 'off';
        }

        return 'due';
    }

    public function isDueIn(Carbon $month): bool
    {
        return $this->stateIn($month) === 'due';
    }

    /**
     * Nächster Monat mit einer Zahlung (für Posten ohne Fälligkeit im Monat).
     */
    public function nextDueAfter(Carbon $month): ?Carbon
    {
        if ($this->stateIn($month) === 'upcoming') {
            return $this->starts_on->copy()->startOfMonth();
        }

        if ($this->stateIn($month) !== 'off') {
            return null;
        }

        $step = $this->intervalMonths();
        $offset = (self::monthIndex($month) - self::monthIndex($this->starts_on)) % $step;
        $next = $month->copy()->startOfMonth()->addMonthsNoOverflow($step - $offset);

        return $this->ends_on && self::monthIndex($next) > self::monthIndex($this->ends_on) ? null : $next;
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(PlanGroup::class, 'plan_group_id');
    }
}
