<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FintsInstitute extends Model
{
    public $timestamps = false;

    protected $fillable = ['bank_code', 'bic', 'name', 'city', 'url'];

    /**
     * Suche nach Bankname (alle Wörter) oder Bankleitzahl (Anfang).
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = mb_strtolower(trim($term));

        if (preg_match('/^\d{2,8}$/', $term)) {
            return $query->where('bank_code', 'like', $term . '%');
        }

        foreach (preg_split('/\s+/', $term) as $word) {
            $query->whereRaw('LOWER(name) LIKE ?', ['%' . addcslashes($word, '%_\\') . '%']);
        }

        return $query;
    }
}
