<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Felder, die per Mass Assignment gesetzt werden dürfen.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'theme',
        'is_admin',
        'is_active',
        'last_login_at',
    ];

    /**
     * Felder, die verborgen werden.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Prüft, ob der Benutzer Administrator ist.
     */
    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }

    /**
     * Konten des Benutzers.
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    /**
     * Buchungen des Benutzers.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Kategorien des Benutzers.
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Budgets des Benutzers.
     */
    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    /**
     * Kredite des Benutzers.
     */
    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    /**
     * Kreditkarten des Benutzers.
     */
    public function creditCards(): HasMany
    {
        return $this->hasMany(CreditCard::class);
    }

    /**
     * Wiederkehrende Buchungen des Benutzers.
     */
    public function recurringTransactions(): HasMany
    {
        return $this->hasMany(RecurringTransaction::class);
    }

    /**
     * Einstellungen des Benutzers.
     */
    public function setting(): HasOne
    {
        return $this->hasOne(Setting::class);
    }

    public function dashboardSetting(): HasOne
    {
        return $this->hasOne(DashboardSetting::class);
    }

}
