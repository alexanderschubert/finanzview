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
                'patterns' => ['settings.*', 'categories.*', 'category-rules.*', 'tags.*', 'payees.*', 'admin.*'],
                'tabs' => [
                    ['Allgemein', 'settings.index', ['settings.index']],
                    ['Kategorien', 'categories.index', ['categories.index']],
                    ['Empfänger', 'payees.index', ['payees.index']],
                    ['Tags', 'tags.index', ['tags.index']],
                    ['Regeln', 'category-rules.index', ['category-rules.index']],
                    // Anbieter und Benutzer gelten für die ganze Instanz – nur für Administratoren.
                    ['Anbieter', 'admin.providers.index', ['admin.providers.index'], 'admin'],
                    ['Benutzer', 'admin.index', ['admin.index'], 'admin'],
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

        $isAdmin = (bool) request()->user()?->is_admin;
        $tabs = array_values(array_filter($tabs, fn (array $tab) => ($tab[3] ?? null) !== 'admin' || $isAdmin));

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

    /**
     * Einträge der Befehlspalette (⌘K): Seiten, Aktionen und Kurzbefehle.
     * Eintrag: [Beschriftung, Gruppe, Adresse, Schlagwörter, Kurzbefehl, Aktion]
     *
     * @return list<array{label: string, group: string, url: string, keywords: string, keys: string, action: string|null}>
     */
    public static function paletteItems(bool $isAdmin): array
    {
        $item = fn (string $label, string $group, string $route, string $keywords = '', string $keys = '', ?string $action = null) => [
            'label' => $label,
            'group' => $group,
            'url' => route($route),
            'keywords' => mb_strtolower($keywords),
            'keys' => $keys,
            'action' => $action,
        ];

        $items = [
            $item('Neue Buchung', 'Aktionen', 'transactions.create', 'erfassen hinzufügen ausgabe einnahme', 'N', 'quick-add'),
            $item('Neues Konto', 'Aktionen', 'accounts.create', 'konto anlegen'),
            $item('Neue Kategorie', 'Aktionen', 'categories.create', 'kategorie anlegen'),
            $item('Neues Budget', 'Aktionen', 'budgets.create', 'budget anlegen'),
            $item('Neuer Kredit', 'Aktionen', 'loans.create', 'kredit darlehen anlegen'),
            $item('Neue Kreditkarte', 'Aktionen', 'credit-cards.create', 'karte anlegen'),

            $item('Übersicht', 'Gehe zu', 'dashboard', 'dashboard start cockpit', 'G D'),
            $item('Buchungen', 'Gehe zu', 'transactions.index', 'liste umsätze', 'G B'),
            $item('Zu prüfen', 'Gehe zu', 'transactions.review', 'ohne kategorie prüfen', 'G Z'),
            $item('Umbuchungen', 'Gehe zu', 'transactions.transfers', 'transfer'),
            $item('Buchungen importieren', 'Gehe zu', 'transactions.import.create', 'csv import'),
            $item('Konten', 'Gehe zu', 'accounts.index', 'girokonto bank', 'G K'),
            $item('Kreditkarten', 'Gehe zu', 'credit-cards.index', 'karte visa'),
            $item('Bankabruf', 'Gehe zu', 'bank-connections.index', 'fints bankverbindung'),
            $item('Monatsplan', 'Gehe zu', 'plan.index', 'plan planung', 'G P'),
            $item('Kalender', 'Gehe zu', 'plan.calendar', 'zahlungen termine'),
            $item('Budgets', 'Gehe zu', 'budgets.index', 'budget'),
            $item('Wiederkehrende Buchungen', 'Gehe zu', 'recurring-transactions.index', 'daueraufträge abos'),
            $item('Kredite', 'Gehe zu', 'loans.index', 'darlehen tilgungsplan', 'G R'),
            $item('Monatsbericht', 'Gehe zu', 'reports.month', 'auswertung bericht', 'G M'),
            $item('Analysen', 'Gehe zu', 'reports.index', 'auswertung diagramme'),

            $item('Einstellungen', 'Einstellungen', 'settings.index', 'optionen', 'G E'),
            $item('Kategorien', 'Einstellungen', 'categories.index', 'kategorie'),
            $item('Empfänger', 'Einstellungen', 'payees.index', 'payee händler'),
            $item('Tags', 'Einstellungen', 'tags.index', 'schlagwörter'),
            $item('Regeln', 'Einstellungen', 'category-rules.index', 'automatisch zuordnen'),
            $item('Erscheinungsbild', 'Einstellungen', 'settings.appearance', 'dunkel hell theme'),
            $item('Dashboard anpassen', 'Einstellungen', 'settings.dashboard', 'widgets layout cockpit'),
            $item('Finanzen', 'Einstellungen', 'settings.financial', 'währung standardkonto'),
            $item('Daten & Export', 'Einstellungen', 'settings.data-export', 'backup sichern wiederherstellen csv'),
            $item('Profil', 'Einstellungen', 'settings.profile', 'name e-mail profilbild foto'),
            $item('Sicherheit', 'Einstellungen', 'settings.security', 'passwort passkey zwei-faktor 2fa'),
        ];

        if ($isAdmin) {
            $items[] = $item('Benutzer', 'Einstellungen', 'admin.index', 'administration registrierung');
            $items[] = $item('Finanzanbieter', 'Einstellungen', 'admin.providers.index', 'administration banken logos');
        }

        return $items;
    }
}
