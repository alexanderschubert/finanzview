<?php

namespace App\Services;

use App\Models\CategoryRule;
use App\Models\Transaction;
use Illuminate\Support\Collection;

class CategoryRuleService
{
    /**
     * Aktive Regeln mit aktiver Kategorie. Längere (genauere)
     * Suchbegriffe haben Vorrang: „Amazon Prime“ vor „Amazon“.
     *
     * @return Collection<int, CategoryRule>
     */
    public function rulesFor(int $userId): Collection
    {
        return CategoryRule::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->whereHas('category', fn ($q) => $q->where('is_active', true))
            ->with('category:id,type')
            ->get()
            ->sortBy([
                fn ($a, $b) => mb_strlen($b->pattern) <=> mb_strlen($a->pattern),
                fn ($a, $b) => $a->id <=> $b->id,
            ])
            ->values();
    }

    /**
     * Kategorie der ersten passenden Regel.
     */
    public function match(Collection $rules, string $type, ?string $merchant, ?string $description): ?int
    {
        if (! in_array($type, ['income', 'expense'], true)) {
            return null;
        }

        return $rules
            ->first(fn (CategoryRule $rule) => $rule->matches($type, $merchant, $description))
            ?->category_id;
    }

    /**
     * Ordnet Buchungen ohne Kategorie per Regel zu.
     * Mit $only wird nur diese eine Regel angewendet.
     *
     * @return int Anzahl zugeordneter Buchungen
     */
    public function applyToUncategorized(int $userId, ?CategoryRule $only = null): int
    {
        $rules = $only
            ? collect([$only->loadMissing('category')])
                ->filter(fn ($rule) => $rule->is_active && $rule->category?->is_active)
            : $this->rulesFor($userId);

        if ($rules->isEmpty()) {
            return 0;
        }

        $count = 0;

        Transaction::query()
            ->where('user_id', $userId)
            ->whereNull('category_id')
            ->whereIn('type', ['income', 'expense'])
            ->select(['id', 'type', 'merchant', 'description'])
            ->chunkById(500, function ($transactions) use ($rules, &$count) {
                foreach ($transactions->groupBy(fn ($t) => $this->match($rules, $t->type, $t->merchant, $t->description)) as $categoryId => $matched) {
                    if ($categoryId === '' || $categoryId === null) {
                        continue;
                    }

                    $count += Transaction::query()
                        ->whereIn('id', $matched->pluck('id'))
                        ->update(['category_id' => (int) $categoryId]);
                }
            });

        return $count;
    }
}
