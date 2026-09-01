<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'logo',
        'emoji',
        'color',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'provider_id');
    }

    public function creditCards(): HasMany
    {
        return $this->hasMany(CreditCard::class, 'provider_id');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class, 'provider_id');
    }
}
