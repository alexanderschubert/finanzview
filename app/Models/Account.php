<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'institution',
        'provider_id',
        'type',
        'currency',
        'opening_balance',
        'credit_limit',
        'iban',
        'account_number',
        'color',
        'icon',
        'notes',
        'include_in_total',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'credit_limit' => 'decimal:2',
            'include_in_total' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(FinancialProvider::class, 'provider_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Aktueller Kontostand.
     *
     * Startsaldo + Einnahmen - Ausgaben
     */
    public function getCurrentBalanceAttribute(): float
{
    $income = $this->transactions()
        ->where('type', 'income')
        ->sum('amount');

    $expenses = $this->transactions()
        ->where('type', 'expense')
        ->sum('amount');

    return (float) $this->opening_balance + $income - $expenses;
}
}