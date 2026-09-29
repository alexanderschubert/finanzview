<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasFactory;

    /**
     * Farbauswahl (Apple-Systemfarben).
     */
    public const COLORS = [
        '#0a84ff' => 'Blau',
        '#30b0c7' => 'Türkis',
        '#34c759' => 'Grün',
        '#ffcc00' => 'Gelb',
        '#ff9500' => 'Orange',
        '#ff3b30' => 'Rot',
        '#ff2d55' => 'Pink',
        '#af52de' => 'Lila',
        '#8e8e93' => 'Grau',
    ];

    protected $fillable = [
        'user_id',
        'name',
        'color',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): BelongsToMany
    {
        return $this->belongsToMany(
            Transaction::class,
            'transaction_tag'
        );
    }

    /**
     * Gewählte Farbe, sonst eine feste Farbe aus dem Namen,
     * damit gleiche Tags immer gleich aussehen.
     */
    public function displayColor(): string
    {
        if (is_string($this->color) && array_key_exists(strtolower($this->color), self::COLORS)) {
            return strtolower($this->color);
        }

        $colors = array_keys(self::COLORS);

        return $colors[crc32(mb_strtolower((string) $this->name)) % (count($colors) - 1)];
    }
}
