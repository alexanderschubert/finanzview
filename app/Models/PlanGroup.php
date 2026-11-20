<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanGroup extends Model
{
    public const KIND_EXPENSE = 'expense';

    public const KIND_INCOME = 'income';

    public const KIND_RATE = 'rate';

    public const KIND_LOAN = 'loan';

    /**
     * Art der Gruppe. Raten und Kredite zählen als Ausgaben und
     * bekommen zusätzlich eine eigene Summe.
     */
    public const KINDS = [
        self::KIND_EXPENSE => 'Ausgaben',
        self::KIND_RATE => 'Raten',
        self::KIND_LOAN => 'Kredite',
        self::KIND_INCOME => 'Einnahmen',
    ];

    protected $fillable = [
        'plan_month_id',
        'name',
        'kind',
        'icon',
        'color',
        'sort_order',
    ];

    public function planMonth(): BelongsTo
    {
        return $this->belongsTo(PlanMonth::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PlanItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function isIncome(): bool
    {
        return $this->kind === self::KIND_INCOME;
    }
}
