<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Loan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'account_id',
        'name',
        'principal_amount',
        'paid_amount',
        'interest_rate',
        'installment_amount',
        'total_installments',
        'paid_installments',
        'start_date',
        'end_date',
        'type',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'principal_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'interest_rate' => 'decimal:3',
            'installment_amount' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LoanPayment::class);
    }
}