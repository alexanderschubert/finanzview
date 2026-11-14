<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Monatsplan: feste Einnahmen und Ausgaben eines Monats in Gruppen.
 * Ein Plan erzeugt keine Buchungen.
 */
class PlanMonth extends Model
{
    protected $fillable = [
        'user_id',
        'month',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(PlanGroup::class)->orderBy('sort_order')->orderBy('id');
    }
}
