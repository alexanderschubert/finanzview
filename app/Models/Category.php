<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'icon',
        'color',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];


    /**
     * Benutzer, dem die Kategorie gehört.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    /**
     * Buchungen dieser Kategorie.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }


    /**
     * Budgets, denen diese Kategorie zugeordnet ist.
     */
    public function budgets(): BelongsToMany
    {
        return $this->belongsToMany(
            Budget::class,
            'budget_category',
            'category_id',
            'budget_id'
        );
    }
}