<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Die übergeordnete Kategorie wird immer mitgeladen, damit sich der
     * volle Name („Lebensmittel › Supermarkt“) ohne Zusatzabfragen bilden lässt.
     */
    protected $with = ['parent'];

    protected $fillable = [
        'user_id',
        'parent_id',
        'name',
        'type',
        'icon',
        'color',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];


    public function newCollection(array $models = []): CategoryCollection
    {
        return new CategoryCollection($models);
    }

    /**
     * Übergeordnete Kategorie (null bei Hauptkategorien).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id')->without('parent');
    }

    /**
     * Unterkategorien.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->without('parent');
    }

    public function isChild(): bool
    {
        return $this->parent_id !== null;
    }

    /**
     * „Lebensmittel › Supermarkt“ bei Unterkategorien, sonst nur der Name.
     */
    public function getDisplayNameAttribute(): string
    {
        $parent = $this->relationLoaded('parent') ? $this->parent : $this->parent()->first();

        return $parent ? $parent->name . ' › ' . $this->name : $this->name;
    }

    /**
     * IDs der Kategorien samt aller Unterkategorien (für Budgets und Filter).
     *
     * @param  iterable<int>  $ids
     * @return array<int, int>
     */
    public static function withDescendantIds(iterable $ids): array
    {
        $ids = collect($ids)->map(fn ($id) => (int) $id)->unique()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $childIds = static::query()->whereIn('parent_id', $ids)->pluck('id');

        return $ids->merge($childIds)->unique()->values()->all();
    }

    /**
     * Benutzer, dem die Kategorie gehört.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    /**
     * Buchungen dieser Kategorie.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }


    /**
     * Budgets, denen diese Kategorie zugeordnet ist.
     */
    public function budgets(): BelongsToMany
    {
        return $this->belongsToMany(
            Budget::class,
            'budget_category',
            'category_id',
            'budget_id'
        );
    }
}