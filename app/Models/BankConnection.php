<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * FinTS-Zugang zu einer Bank. Die Online-Banking-PIN wird nie
 * gespeichert, sondern bei jedem Abruf abgefragt.
 */
class BankConnection extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'bank_code',
        'url',
        'username',
        'tan_mode',
        'tan_mode_name',
        'tan_medium',
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

    /**
     * Verknüpfte Bankkonten (nur solche, deren FinanzView-Konto noch existiert).
     */
    public function linkedAccounts(): HasMany
    {
        return $this->hasMany(BankConnectionAccount::class)
            ->whereHas('account')
            ->orderBy('id');
    }

    public function hasTanMode(): bool
    {
        return $this->tan_mode !== null;
    }

    /**
     * Bereit zum Abruf: TAN-Verfahren gewählt und mindestens ein Konto verknüpft.
     */
    public function isReady(): bool
    {
        return $this->hasTanMode() && $this->linkedAccounts()->exists();
    }
}
