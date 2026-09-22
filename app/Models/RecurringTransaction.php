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
        'anchor_day',
        'end_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'next_date' => 'date',
            'anchor_day' => 'integer',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Ausführungstag (anchor_day) automatisch pflegen.
     *
     * Der Ankertag wird aus next_date übernommen, wenn noch keiner
     * gesetzt ist oder next_date auf einen anderen Tag geändert
     * wurde. Ein durch Monatsende gekürzter Termin (z. B. 28.02.
     * bei Ankertag 31) ändert den Ankertag NICHT.
     */
    protected static function booted(): void
    {
        static::saving(function (RecurringTransaction $recurring) {
            if (! $recurring->next_date) {
                return;
            }

            if ($recurring->isDirty('anchor_day') && $recurring->anchor_day) {
                return;
            }

            $day = (int) $recurring->next_date->day;
            $anchor = (int) $recurring->anchor_day;

            if (
                $anchor < 1
                || $day !== min($anchor, $recurring->next_date->daysInMonth)
            ) {
                $recurring->anchor_day = $day;
            }
        });
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