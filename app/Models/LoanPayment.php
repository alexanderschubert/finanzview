<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'transaction_id',
        'installment_number',
        'due_date',
        'amount',
        'payment_type',
        'paid_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'paid_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function isExtraPayment(): bool
    {
        return $this->payment_type === 'extra';
    }

    public function isRegularPayment(): bool
    {
        return $this->payment_type === 'regular';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}