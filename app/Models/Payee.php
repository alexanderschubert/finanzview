<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payee extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'default_category_id',
        'ignore_suggestions',
    ];

    protected function casts(): array
    {
        return [
            'ignore_suggestions' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function defaultCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'default_category_id');
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(PayeeAlias::class);
    }
}
