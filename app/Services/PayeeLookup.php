<?php

namespace App\Services;

/**
 * Schneller Abgleich von Händlernamen mit den Empfängern eines Benutzers
 * (einmal laden, dann beliebig oft abfragen – z. B. bei einem Import).
 */
class PayeeLookup
{
    /**
     * @param  array<string, array{id: int, name: string, category_id: ?int}>  $byKey
     * @param  array<int, string>  $categoryTypes  Kategorie-ID => Art (nur aktive Kategorien)
     */
    public function __construct(
        private array $byKey,
        private array $categoryTypes,
    ) {
    }

    /**
     * @return array{id: int, name: string, category_id: ?int}|null
     */
    public function find(?string $merchant): ?array
    {
        $key = PayeeService::key($merchant);

        return $key === '' ? null : ($this->byKey[$key] ?? null);
    }

    /**
     * Standardkategorie des Empfängers, wenn sie zur Buchungsart passt.
     */
    public function categoryFor(?array $payee, string $type): ?int
    {
        $categoryId = $payee['category_id'] ?? null;

        if ($categoryId === null) {
            return null;
        }

        $categoryType = $this->categoryTypes[$categoryId] ?? null;

        return ($categoryType === 'both' || $categoryType === $type) ? $categoryId : null;
    }
}
