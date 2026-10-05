<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Payee;
use App\Models\PayeeAlias;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Empfänger-Verwaltung: einheitliche Namen für Händler und Auftraggeber.
 *
 * - Jeder Händlername einer Buchung wird zum Empfänger („Rewe“).
 * - Mehrere Schreibweisen lassen sich zu einem Empfänger zusammenführen;
 *   die alten Schreibweisen merkt sich der Empfänger als Alias und
 *   ordnet künftige Buchungen (Import, Bankabruf, Eingabe) automatisch zu.
 * - Ein Empfänger kann eine Standardkategorie haben.
 */
class PayeeService
{
    /** Ab hier gelten Namen als zu kurz für Ähnlichkeitsvorschläge. */
    private const MIN_SUGGESTION_LENGTH = 4;

    /**
     * Abgleichschlüssel: klein geschrieben, Leerzeichen vereinheitlicht.
     */
    public static function key(?string $name): string
    {
        return mb_strtolower(Str::squish((string) $name));
    }

    /**
     * Schlüssel für Ähnlichkeitsvorschläge: zusätzlich ohne Ziffern und Satzzeichen
     * („REWE Markt 1234“ ≈ „Rewe Markt 99“).
     */
    public static function similarityKey(?string $name): string
    {
        return Str::squish((string) preg_replace('/[^\pL ]+/u', ' ', self::key($name)));
    }

    private static function cleanName(string $name): string
    {
        return Str::limit(Str::squish($name), 255, '');
    }


    /*
    |--------------------------------------------------------------------------
    | Abgleich
    |--------------------------------------------------------------------------
    */

    public function lookup(int $userId): PayeeLookup
    {
        $byKey = PayeeAlias::query()
            ->where('payee_aliases.user_id', $userId)
            ->join('payees', 'payees.id', '=', 'payee_aliases.payee_id')
            ->get(['payee_aliases.alias_key', 'payees.id', 'payees.name', 'payees.default_category_id'])
            ->mapWithKeys(fn ($row) => [
                $row->alias_key => ['id' => (int) $row->id, 'name' => $row->name, 'category_id' => $row->default_category_id ? (int) $row->default_category_id : null],
            ])
            ->all();

        $categoryTypes = Category::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->pluck('type', 'id')
            ->all();

        return new PayeeLookup($byKey, $categoryTypes);
    }

    /**
     * Empfänger zu einem Händlernamen – legt ihn an, falls er noch fehlt.
     */
    public function ensure(int $userId, ?string $merchant): ?Payee
    {
        $name = self::cleanName((string) $merchant);
        $key = self::key($name);

        if ($key === '') {
            return null;
        }

        $alias = PayeeAlias::query()->where('user_id', $userId)->where('alias_key', $key)->first();

        if ($alias) {
            return $alias->payee;
        }

        return DB::transaction(function () use ($userId, $name, $key) {
            $payee = Payee::create(['user_id' => $userId, 'name' => $name]);
            $payee->aliases()->create(['user_id' => $userId, 'alias' => $name, 'alias_key' => $key]);

            return $payee;
        });
    }

    /**
     * @param  iterable<?string>  $merchants
     */
    public function ensureMany(int $userId, iterable $merchants): void
    {
        collect($merchants)
            ->filter()
            ->unique(fn ($merchant) => self::key($merchant))
            ->each(fn ($merchant) => $this->ensure($userId, $merchant));
    }

    /**
     * Legt für alle Händlernamen vorhandener Buchungen einen Empfänger an
     * (einmalig für ältere Buchungen; neue entstehen beim Speichern).
     */
    public function syncFromTransactions(int $userId): void
    {
        $known = PayeeAlias::query()->where('user_id', $userId)->pluck('alias_key')->flip();

        $this->merchantRows($userId)
            ->groupBy(fn ($row) => self::key($row->merchant))
            ->reject(fn ($rows, $key) => $key === '' || $known->has($key))
            ->each(fn ($rows) => $this->ensure($userId, $rows->sortByDesc('n')->first()->merchant));
    }


    /*
    |--------------------------------------------------------------------------
    | Auswertung
    |--------------------------------------------------------------------------
    */

    /**
     * Anzahl und Summen je Empfänger-Schlüssel (alle Schreibweisen zusammen).
     *
     * @return Collection<string, array{count: int, expense: float, income: float, last: ?string, variants: list<string>}>
     */
    public function stats(int $userId): Collection
    {
        return $this->merchantRows($userId)
            ->groupBy(fn ($row) => self::key($row->merchant))
            ->map(fn ($rows) => [
                'count' => (int) $rows->sum('n'),
                'expense' => round((float) $rows->sum('expense'), 2),
                'income' => round((float) $rows->sum('income'), 2),
                'last' => $rows->max('last'),
                'variants' => $rows->pluck('merchant')->all(),
            ]);
    }

    /**
     * Gruppen ähnlicher Empfänger (Namen unterscheiden sich nur in Ziffern,
     * Satzzeichen oder Schreibweise) – Kandidaten zum Zusammenführen.
     *
     * @return Collection<int, Collection<int, Payee>>
     */
    public function suggestions(int $userId): Collection
    {
        $stats = $this->stats($userId);

        return Payee::query()
            ->where('user_id', $userId)
            ->where('ignore_suggestions', false)
            ->get()
            ->filter(fn (Payee $payee) => mb_strlen(self::similarityKey($payee->name)) >= self::MIN_SUGGESTION_LENGTH)
            ->groupBy(fn (Payee $payee) => self::similarityKey($payee->name))
            ->filter(fn (Collection $group) => $group->count() >= 2)
            ->map(fn (Collection $group) => $group
                ->sortByDesc(fn (Payee $payee) => $stats[self::key($payee->name)]['count'] ?? 0)
                ->values())
            ->values();
    }

    private function merchantRows(int $userId): Collection
    {
        return Transaction::query()
            ->where('user_id', $userId)
            ->whereNotNull('merchant')
            ->where('merchant', '!=', '')
            ->selectRaw("merchant,
                COUNT(*) AS n,
                COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS expense,
                COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS income,
                MAX(transaction_date) AS last")
            ->groupBy('merchant')
            ->get();
    }


    /*
    |--------------------------------------------------------------------------
    | Ändern
    |--------------------------------------------------------------------------
    */

    /**
     * Empfänger zusammenführen (bzw. bei nur einem: umbenennen und
     * Standardkategorie setzen).
     *
     * Alle Buchungen mit einer der Schreibweisen bekommen den neuen Namen;
     * die Namen bleiben als Aliase erhalten.
     *
     * @param  Collection<int, Payee>  $payees
     */
    public function merge(int $userId, Collection $payees, string $targetName, ?int $categoryId, bool $applyToUncategorized): Payee
    {
        $targetName = self::cleanName($targetName);
        $targetKey = self::key($targetName);

        if ($targetKey === '') {
            throw ValidationException::withMessages(['name' => 'Bitte einen Namen eingeben.']);
        }

        $ids = $payees->pluck('id');

        // Der Zielname darf keinem anderen Empfänger gehören.
        $taken = PayeeAlias::query()
            ->where('user_id', $userId)
            ->where('alias_key', $targetKey)
            ->whereNotIn('payee_id', $ids)
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages(['name' => 'Diesen Namen hat bereits ein anderer Empfänger. Wähle ihn mit aus, um sie zusammenzuführen.']);
        }

        return DB::transaction(function () use ($userId, $payees, $ids, $targetName, $targetKey, $categoryId, $applyToUncategorized) {
            $target = $payees->first(fn (Payee $payee) => self::key($payee->name) === $targetKey) ?? $payees->first();

            // Alle Schreibweisen in den Buchungen, die zu den gewählten Empfängern gehören.
            $aliasKeys = PayeeAlias::query()->whereIn('payee_id', $ids)->pluck('alias_key')->flip();
            $variants = $this->merchantRows($userId)
                ->pluck('merchant')
                ->filter(fn ($merchant) => $aliasKeys->has(self::key($merchant)))
                ->values();

            // Aliase der übrigen Empfänger übernehmen, sie selbst entfernen.
            foreach ($payees->reject(fn (Payee $payee) => $payee->id === $target->id) as $other) {
                PayeeAlias::query()->where('payee_id', $other->id)->update(['payee_id' => $target->id]);

                if ($categoryId === null && $target->default_category_id === null && $other->default_category_id !== null) {
                    $target->default_category_id = $other->default_category_id;
                }

                $other->delete();
            }

            $target->name = $targetName;

            if ($categoryId !== null) {
                $target->default_category_id = $this->ownCategoryId($userId, $categoryId);
            }

            $target->save();

            PayeeAlias::query()->firstOrCreate(
                ['user_id' => $userId, 'alias_key' => $targetKey],
                ['payee_id' => $target->id, 'alias' => $targetName]
            );

            if ($variants->isNotEmpty()) {
                Transaction::query()
                    ->where('user_id', $userId)
                    ->whereIn('merchant', $variants)
                    ->update(['merchant' => $targetName]);
            }

            if ($applyToUncategorized && $target->default_category_id) {
                $this->applyCategory($userId, $target);
            }

            return $target->fresh();
        });
    }

    /**
     * Standardkategorie auf vorhandene Buchungen ohne Kategorie anwenden.
     *
     * @return int Anzahl geänderter Buchungen
     */
    public function applyCategory(int $userId, Payee $payee): int
    {
        $category = $payee->default_category_id
            ? Category::query()->where('user_id', $userId)->find($payee->default_category_id)
            : null;

        if (! $category) {
            return 0;
        }

        $types = match ($category->type) {
            'income' => ['income'],
            'expense' => ['expense'],
            default => ['income', 'expense'],
        };

        return Transaction::query()
            ->where('user_id', $userId)
            ->whereNull('category_id')
            ->whereIn('type', $types)
            ->where('merchant', $payee->name)
            ->update(['category_id' => $category->id]);
    }

    /**
     * Schreibweise entfernen (außer dem Namen des Empfängers selbst).
     */
    public function removeAlias(Payee $payee, PayeeAlias $alias): bool
    {
        if ($alias->payee_id !== $payee->id || $alias->alias_key === self::key($payee->name)) {
            return false;
        }

        $alias->delete();

        return true;
    }

    private function ownCategoryId(int $userId, int $categoryId): ?int
    {
        return Category::query()->where('user_id', $userId)->whereKey($categoryId)->value('id');
    }
}
