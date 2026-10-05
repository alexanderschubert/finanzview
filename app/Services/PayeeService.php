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
 * Der Buchungstext der Bank (Händler, Verwendungszweck) wird nie
 * verändert. Der Empfänger kommt als eigene Zuordnung (payee_id) dazu:
 *
 * - Jeder Händlername einer Buchung gehört zu einem Empfänger („Rewe“).
 * - Mehrere Schreibweisen lassen sich zu einem Empfänger zusammenführen;
 *   die Schreibweisen merkt sich der Empfänger als Alias und ordnet
 *   künftige Buchungen (Import, Bankabruf, Eingabe) automatisch zu.
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
     * Empfänger für eine Buchung bestimmen.
     *
     * Mit ausdrücklich gewähltem Empfänger wird dieser verwendet (und der
     * Händlertext als Schreibweise gemerkt, sofern frei); sonst wird er aus
     * dem Händlertext abgeleitet.
     */
    public function forTransaction(int $userId, ?string $merchant, ?string $chosenName = null): ?Payee
    {
        if (self::key($chosenName) === '') {
            return $this->ensure($userId, $merchant);
        }

        $payee = $this->ensure($userId, $chosenName);
        $merchantKey = self::key($merchant);

        if ($payee && $merchantKey !== '') {
            PayeeAlias::query()->firstOrCreate(
                ['user_id' => $userId, 'alias_key' => $merchantKey],
                ['payee_id' => $payee->id, 'alias' => self::cleanName((string) $merchant)]
            );
        }

        return $payee;
    }

    /**
     * Legt für alle Händlernamen vorhandener Buchungen einen Empfänger an
     * und ordnet Buchungen ohne Empfänger zu (einmalig für ältere Buchungen;
     * neue Buchungen werden beim Speichern zugeordnet).
     */
    public function assignUnassigned(int $userId): void
    {
        $known = PayeeAlias::query()->where('user_id', $userId)->pluck('alias_key')->flip();

        $this->merchantRows($userId)
            ->groupBy(fn ($row) => self::key($row->merchant))
            ->reject(fn ($rows, $key) => $key === '' || $known->has($key))
            ->each(fn ($rows) => $this->ensure($userId, $rows->sortByDesc('n')->first()->merchant));

        $payeeByKey = PayeeAlias::query()->where('user_id', $userId)->pluck('payee_id', 'alias_key');

        Transaction::query()
            ->where('user_id', $userId)
            ->whereNull('payee_id')
            ->whereNotNull('merchant')
            ->where('merchant', '!=', '')
            ->distinct()
            ->pluck('merchant')
            ->each(function (string $merchant) use ($userId, $payeeByKey) {
                $payeeId = $payeeByKey[self::key($merchant)] ?? null;

                if ($payeeId !== null) {
                    Transaction::query()
                        ->where('user_id', $userId)
                        ->whereNull('payee_id')
                        ->where('merchant', $merchant)
                        ->update(['payee_id' => $payeeId]);
                }
            });
    }

    private function merchantRows(int $userId): Collection
    {
        return Transaction::query()
            ->where('user_id', $userId)
            ->whereNotNull('merchant')
            ->where('merchant', '!=', '')
            ->selectRaw('merchant, COUNT(*) AS n')
            ->groupBy('merchant')
            ->get();
    }


    /*
    |--------------------------------------------------------------------------
    | Auswertung
    |--------------------------------------------------------------------------
    */

    /**
     * Anzahl und Summen je Empfänger (Schlüssel = Empfänger-ID).
     *
     * @return Collection<int, array{count: int, expense: float, income: float, last: ?string}>
     */
    public function stats(int $userId): Collection
    {
        return Transaction::query()
            ->where('user_id', $userId)
            ->whereNotNull('payee_id')
            ->selectRaw("payee_id,
                COUNT(*) AS n,
                COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS expense,
                COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS income,
                MAX(transaction_date) AS last")
            ->groupBy('payee_id')
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->payee_id => [
                'count' => (int) $row->n,
                'expense' => round((float) $row->expense, 2),
                'income' => round((float) $row->income, 2),
                'last' => $row->last,
            ]]);
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
                ->sortByDesc(fn (Payee $payee) => $stats[$payee->id]['count'] ?? 0)
                ->values())
            ->values();
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
     * Die Buchungen der zusammengeführten Empfänger gehören danach zum
     * Ziel-Empfänger; ihre Schreibweisen bleiben als Aliase erhalten.
     * Der Buchungstext der Bank wird nicht verändert.
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

        return DB::transaction(function () use ($userId, $payees, $targetName, $targetKey, $categoryId, $applyToUncategorized) {
            $target = $payees->first(fn (Payee $payee) => self::key($payee->name) === $targetKey) ?? $payees->first();

            // Buchungen und Aliase der übrigen Empfänger übernehmen, sie selbst entfernen.
            foreach ($payees->reject(fn (Payee $payee) => $payee->id === $target->id) as $other) {
                Transaction::query()->where('user_id', $userId)->where('payee_id', $other->id)->update(['payee_id' => $target->id]);
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

            // Buchungen, die bisher keinem Empfänger gehörten, aber zum neuen Namen passen.
            $this->assignUnassigned($userId);

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
            ->where('payee_id', $payee->id)
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
