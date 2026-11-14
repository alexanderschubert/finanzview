<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanItem extends Model
{
    protected $fillable = [
        'plan_group_id',
        'name',
        'amount',
        'note',
        'ends_on',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'ends_on' => 'date',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(PlanGroup::class, 'plan_group_id');
    }
}
