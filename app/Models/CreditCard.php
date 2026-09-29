<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreditCard extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'account_id',
        'card_account_id',
        'name',
        'issuer',
        'provider_id',
        'last_four',
        'credit_limit',
        'current_balance',
        'billing_day',
        'payment_due_day',
        'color',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'current_balance' => 'decimal:2',
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

    /**
     * Konto, von dem die Abrechnung bezahlt wird (z. B. Girokonto).
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Konto mit den Kartenumsätzen (z. B. „AMEX“).
     */
    public function cardAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'card_account_id');
    }

    /**
     * Offener Betrag: mit Kartenkonto automatisch aus dessen Saldo
     * (Kontostand −245,30 € = 245,30 € offen), sonst der von Hand
     * eingetragene Wert.
     */
    protected function currentBalance(): Attribute
    {
        return Attribute::get(function ($value) {
            if ($this->card_account_id !== null && $this->cardAccount) {
                // „+ 0.0“ macht aus −0,00 eine saubere 0,00.
                return number_format(round(-$this->cardAccount->current_balance, 2) + 0.0, 2, '.', '');
            }

            return $value === null ? null : number_format((float) $value, 2, '.', '');
        });
    }

    public function hasCardAccount(): bool
    {
        return $this->card_account_id !== null && $this->cardAccount !== null;
    }

    public function statements(): HasMany
    {
        return $this->hasMany(CreditCardStatement::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}