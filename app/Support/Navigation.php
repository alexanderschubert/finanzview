<?php

namespace App\Support;

/**
 * Hauptnavigation: sechs Bereiche plus Einstellungen, innerhalb eines Bereichs wechselt man mit Tabs.
 *
 * Tab: [Beschriftung, Route, aktive Routen]
 */
final class Navigation
{
    /**
     * @return array<string, array{label: string, icon: string, route: string, patterns: list<string>, tabs: list<array{0: string, 1: string, 2: list<string>}>}>
     */
    public static function areas(): array
    {
        return [
            'overview' => [
                'label' => 'Übersicht',
                'icon' => 'home',
                'route' => 'dashboard',
                'patterns' => ['dashboard'],
                'tabs' => [],
            ],
            'accounts' => [
                'label' => 'Konten',
                'icon' => 'landmark',
                'route' => 'accounts.index',
                'patterns' => ['accounts.*', 'credit-cards.*', 'bank-connections.*'],
                'tabs' => [
                    ['Konten', 'accounts.index', ['accounts.index']],
                    ['Kreditkarten', 'credit-cards.index', ['credit-cards.index']],
                    ['Bankabruf', 'bank-connections.index', ['bank-connections.index']],
                ],
            ],
            'transactions' => [
                'label' => 'Buchungen',
                'icon' => 'arrows',
                'route' => 'transactions.index',
                'patterns' => ['transactions.*'],
                'tabs' => [
                    ['Liste', 'transactions.index', ['transactions.index']],
                    ['Zu prüfen', 'transactions.review', ['transactions.review']],
                    ['Umbuchungen', 'transactions.transfers', ['transactions.transfers']],
                    ['Importieren', 'transactions.import.create', ['transactions.import.*']],
                ],
            ],
            'planning' => [
                'label' => 'Planung',
                'icon' => 'layout',
                'route' => 'plan.index',
                'patterns' => ['plan.*', 'budgets.*', 'recurring-transactions.*'],
                'tabs' => [
                    ['Monatsplan', 'plan.index', ['plan.index']],
                    ['Budgets', 'budgets.index', ['budgets.index']],
                    ['Wiederkehrend', 'recurring-transactions.index', ['recurring-transactions.index']],
                    ['Kalender', 'plan.calendar', ['plan.calendar']],
                ],
            ],
            'loans' => [
                'label' => 'Kredite',
                'icon' => 'banknote',
                'route' => 'loans.index',
                'patterns' => ['loans.*'],
                'tabs' => [],
            ],
            'reports' => [
                'label' => 'Auswertung',
                'icon' => 'chart',
                'route' => 'reports.month',
                'patterns' => ['reports.*'],
                'tabs' => [
                    ['Monatsbericht', 'reports.month', ['reports.month']],
                    ['Analysen', 'reports.index', ['reports.index']],
                ],
            ],
            'settings' => [
                'label' => 'Einstellungen',
                'icon' => 'settings',
                'route' => 'settings.index',
                'patterns' => ['settings.*', 'categories.*', 'category-rules.*', 'tags.*', 'payees.*'],
                'tabs' => [
                    ['Allgemein', 'settings.index', ['settings.index']],
                    ['Kategorien', 'categories.index', ['categories.index']],
                    ['Empfänger', 'payees.index', ['payees.index']],
                    ['Tags', 'tags.index', ['tags.index']],
                    ['Regeln', 'category-rules.index', ['category-rules.index']],
                ],
            ],
        ];
    }

    /**
     * Bereich, zu dem die aktuelle Seite gehört.
     */
    public static function current(): ?string
    {
        foreach (self::areas() as $key => $area) {
            if (request()->routeIs(...$area['patterns'])) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Tabs des aktuellen Bereichs – nur auf den Hauptseiten des Bereichs
     * (nicht auf Formularen und Detailseiten).
     *
     * @return list<array{label: string, route: string, active: bool, badge: int|null}>
     */
    public static function visibleTabs(): array
    {
        $key = self::current();
        $tabs = $key ? self::areas()[$key]['tabs'] : [];

        if (count($tabs) < 2) {
            return [];
        }

        $items = array_map(fn (array $tab) => [
            'label' => $tab[0],
            'route' => $tab[1],
            'active' => request()->routeIs(...$tab[2]),
            'badge' => $tab[1] === 'transactions.review' ? self::uncategorizedCount() : null,
        ], $tabs);

        return in_array(true, array_column($items, 'active'), true) ? $items : [];
    }

    /**
     * Buchungen ohne Kategorie (Zähler am Tab „Zu prüfen“).
     */
    private static function uncategorizedCount(): int
    {
        $user = request()->user();

        return $user
            ? $user->transactions()->whereNull('category_id')->whereIn('type', ['income', 'expense'])->count()
            : 0;
    }

    /**
     * Alle Hauptseiten (ohne Zurück-Pfeil in der installierten App).
     *
     * @return list<string>
     */
    public static function rootRoutes(): array
    {
        $routes = [];

        foreach (self::areas() as $area) {
            $routes[] = $area['route'];

            foreach ($area['tabs'] as $tab) {
                $routes[] = $tab[1];
            }
        }

        return array_values(array_unique($routes));
    }
}
