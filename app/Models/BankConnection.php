<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FinTS-Zugang zu einer Bank. Die Online-Banking-PIN wird nie
 * gespeichert, sondern bei jedem Abruf abgefragt.
 */
class BankConnection extends Model
{
    protected $fillable = [
        'user_id',
        'account_id',
        'name',
        'bank_code',
        'url',
        'username',
        'tan_mode',
        'tan_mode_name',
        'tan_medium',
        'iban',
        'bic',
        'bank_account_number',
        'bank_sub_account',
        'last_synced_at',
        'last_result',
    ];

    protected $hidden = [
        'username',
    ];

    protected function casts(): array
    {
        return [
            'username' => 'encrypted',
            'tan_mode' => 'integer',
            'last_synced_at' => 'datetime',
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

    public function hasTanMode(): bool
    {
        return $this->tan_mode !== null;
    }

    /**
     * Bereit zum Abruf: TAN-Verfahren, Bankkonto und Zielkonto gewählt.
     */
    public function isReady(): bool
    {
        return $this->hasTanMode() && $this->iban !== null && $this->account_id !== null;
    }

    public function maskedIban(): ?string
    {
        if ($this->iban === null) {
            return null;
        }

        return substr($this->iban, 0, 4) . ' •••• ' . substr($this->iban, -4);
    }
}
