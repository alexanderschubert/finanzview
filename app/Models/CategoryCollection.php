<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;

/**
 * Kategorien mit Baum-Sortierung: jede Unterkategorie direkt hinter
 * ihrer übergeordneten Kategorie, die Reihenfolge innerhalb einer
 * Ebene bleibt wie geliefert (z. B. nach Art und Name).
 */
class CategoryCollection extends Collection
{
    public function inTreeOrder(): static
    {
        $ids = $this->pluck('id')->all();
        $children = $this->filter(fn (Category $c) => $c->parent_id !== null && in_array($c->parent_id, $ids, true))
            ->groupBy('parent_id');

        $ordered = new static();

        foreach ($this as $category) {
            // Unterkategorien kommen hinter ihrem Elternteil dran.
            if ($category->parent_id !== null && in_array($category->parent_id, $ids, true)) {
                continue;
            }

            $ordered->push($category);

            foreach ($children->get($category->id, []) as $child) {
                $ordered->push($child);
            }
        }

        return $ordered;
    }
}
