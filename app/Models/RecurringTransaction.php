<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecurringTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'account_id',
        'category_id',
        'description',
        'amount',
        'type',
        'frequency',
        'next_date',
        'end_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'next_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Benutzer, dem die wiederkehrende Buchung gehört.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Konto, auf dem die Buchungen erstellt werden.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Kategorie der wiederkehrenden Buchung.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Tatsächlich erzeugte Buchungen dieser wiederkehrenden Buchung.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(
            Transaction::class,
            'recurring_transaction_id'
        );
    }
}