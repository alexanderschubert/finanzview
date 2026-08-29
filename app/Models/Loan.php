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
        'creditor_name',
        'creditor_icon',
        'creditor_color',
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

    /*
     * =========================================================
     * BEZIEHUNGEN
     * =========================================================
     */

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


    /*
     * =========================================================
     * BERECHNETE WERTE
     * =========================================================
     */

    /**
     * Aktuelle Restschuld.
     */
    public function getRemainingAmountAttribute(): float
    {
        return max(
            0,
            (float) $this->principal_amount - (float) $this->paid_amount
        );
    }


    /**
     * Tilgungsfortschritt in Prozent.
     */
    public function getProgressAttribute(): float
    {
        $principal = (float) $this->principal_amount;

        if ($principal <= 0) {
            return 0;
        }

        return min(
            100,
            max(
                0,
                ((float) $this->paid_amount / $principal) * 100
            )
        );
    }


    /**
     * Noch verbleibende Raten.
     */
    public function getRemainingInstallmentsAttribute(): ?int
    {
        if ($this->total_installments === null) {
            return null;
        }

        return max(
            0,
            (int) $this->total_installments -
            (int) $this->paid_installments
        );
    }


    /**
     * Bereits bezahlte reguläre Raten.
     */
    public function getRegularPaidAmountAttribute(): float
    {
        if (!$this->relationLoaded('payments')) {
            return (float) $this->payments()
                ->where('payment_type', 'regular')
                ->where('status', 'paid')
                ->sum('amount');
        }

        return (float) $this->payments
            ->where('payment_type', 'regular')
            ->where('status', 'paid')
            ->sum('amount');
    }


    /**
     * Summe aller Sondertilgungen.
     */
    public function getExtraPaidAmountAttribute(): float
    {
        if (!$this->relationLoaded('payments')) {
            return (float) $this->payments()
                ->where('payment_type', 'extra')
                ->where('status', 'paid')
                ->sum('amount');
        }

        return (float) $this->payments
            ->where('payment_type', 'extra')
            ->where('status', 'paid')
            ->sum('amount');
    }
}