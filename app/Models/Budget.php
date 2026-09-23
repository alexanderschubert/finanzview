<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Budget extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'amount',
        'start_date',
        'end_date',
        'period',
        'is_active',
        'color',
        'icon',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    /*
     * Benutzer
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /*
     * Kategorien
     */
    public function categories()
    {
        return $this->belongsToMany(
            Category::class,
            'budget_category',
            'budget_id',
            'category_id'
        );
    }

    /*
     * Prüfen, ob Budget aktuell aktiv ist
     */
    public function isCurrentlyActive(): bool
    {
        $today = now()->toDateString();

        return $this->is_active
            && $this->start_date->toDateString() <= $today
            && ($this->end_date === null || $this->end_date->toDateString() >= $today);
    }
}