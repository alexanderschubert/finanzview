<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bankkonto einer Bankverbindung → FinanzView-Konto.
 */
class BankConnectionAccount extends Model
{
    protected $fillable = [
        'bank_connection_id',
        'account_id',
        'iban',
        'bic',
        'account_number',
        'sub_account',
        'bank_balance',
        'balance_date',
        'adopt_balance',
        'last_synced_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'bank_balance' => 'decimal:2',
            'balance_date' => 'date',
            'adopt_balance' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(BankConnection::class, 'bank_connection_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function maskedIban(): string
    {
        return substr($this->iban, 0, 4) . ' •••• ' . substr($this->iban, -4);
    }

    /**
     * Unterschied Bank minus FinanzView (null, wenn kein Kontostand bekannt).
     */
    public function balanceDifference(): ?float
    {
        if ($this->bank_balance === null || ! $this->account) {
            return null;
        }

        return round((float) $this->bank_balance - $this->account->current_balance, 2);
    }

    /**
     * Parameter für den FinTS-Abruf.
     */
    public function fintsAccount(string $bankCode): array
    {
        return [
            'id' => $this->id,
            'iban' => $this->iban,
            'bic' => $this->bic,
            'account_number' => $this->account_number,
            'sub_account' => $this->sub_account,
            'blz' => $bankCode,
        ];
    }
}
