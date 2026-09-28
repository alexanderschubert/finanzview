<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Regel für automatische Kategorien: Enthält Empfänger bzw.
 * Beschreibung einer Buchung den Suchbegriff, wird die Kategorie
 * zugeordnet.
 */
class CategoryRule extends Model
{
    public const FIELDS = [
        'any' => 'Empfänger oder Beschreibung',
        'merchant' => 'Empfänger',
        'description' => 'Beschreibung',
    ];

    protected $fillable = [
        'user_id',
        'category_id',
        'pattern',
        'match_field',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Passt die Regel zu Buchungsart und Texten?
     */
    public function matches(string $type, ?string $merchant, ?string $description): bool
    {
        $categoryType = $this->category?->type;

        if ($categoryType !== 'both' && $categoryType !== $type) {
            return false;
        }

        $needle = mb_strtolower(trim($this->pattern));

        if ($needle === '') {
            return false;
        }

        $haystacks = match ($this->match_field) {
            'merchant' => [$merchant],
            'description' => [$description],
            default => [$merchant, $description],
        };

        foreach ($haystacks as $haystack) {
            if ($haystack !== null && str_contains(mb_strtolower($haystack), $needle)) {
                return true;
            }
        }

        return false;
    }
}
