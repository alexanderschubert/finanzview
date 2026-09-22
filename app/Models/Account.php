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
     * Startsaldo
     * + Einnahmen
     * - Ausgaben
     * - ausgehende Transfers
     * + eingehende Transfers
     */
    public function getCurrentBalanceAttribute(): float
    {
        /*
         * Eine einzige Abfrage statt vier: Summen per CASE
         * über alle Buchungen, an denen das Konto beteiligt ist.
         * Soft-gelöschte Buchungen werden ignoriert.
         */
        $sums = Transaction::query()
            ->where(function ($query) {
                $query->where('account_id', $this->id)
                    ->orWhere('transfer_account_id', $this->id);
            })
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN account_id = ? AND type = 'income' THEN amount ELSE 0 END), 0) AS income,
                 COALESCE(SUM(CASE WHEN account_id = ? AND type IN ('expense', 'transfer') THEN amount ELSE 0 END), 0) AS outgoing,
                 COALESCE(SUM(CASE WHEN transfer_account_id = ? AND type = 'transfer' THEN amount ELSE 0 END), 0) AS incoming",
                [$this->id, $this->id, $this->id]
            )
            ->first();

        return round(
            (float) $this->opening_balance
                + (float) $sums->income
                - (float) $sums->outgoing
                + (float) $sums->incoming,
            2
        );
    }
}
