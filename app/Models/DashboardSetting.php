<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardSetting extends Model
{
    protected $fillable = [
        'user_id',
        'widgets',
        'display_mode',
    ];

    protected function casts(): array
    {
        return [
            'widgets' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Alle verfügbaren Dashboard-Widgets.
     *
     * Die Reihenfolge entspricht gleichzeitig der
     * Standardreihenfolge des Dashboards.
     */
    public static function availableWidgets(): array
    {
        return [
            'summary' => [
                'label' => 'Gesamtvermögen',
                'description' => 'Aktuelles Gesamtvermögen',
                'icon' => '💰',
            ],

            'income' => [
                'label' => 'Einnahmen',
                'description' => 'Einnahmen des ausgewählten Monats',
                'icon' => '📈',
            ],

            'expenses' => [
                'label' => 'Ausgaben',
                'description' => 'Ausgaben des ausgewählten Monats',
                'icon' => '📉',
            ],

            'savings_rate' => [
                'label' => 'Sparquote',
                'description' => 'Sparquote des ausgewählten Monats',
                'icon' => '💾',
            ],

            'monthly_balance' => [
                'label' => 'Monatsbilanz',
                'description' => 'Bilanz des ausgewählten Monats',
                'icon' => '⚖️',
            ],

            'yearly' => [
                'label' => 'Jahresübersicht',
                'description' => 'Einnahmen und Ausgaben des laufenden Jahres',
                'icon' => '📅',
            ],

            'income_expense_chart' => [
                'label' => 'Einnahmen & Ausgaben',
                'description' => 'Entwicklung der letzten sechs Monate',
                'icon' => '📊',
            ],

            'wealth_chart' => [
                'label' => 'Vermögensentwicklung',
                'description' => 'Entwicklung des Vermögens',
                'icon' => '📈',
            ],

            'budgets' => [
                'label' => 'Budgets',
                'description' => 'Aktuelle Budgets und deren Auslastung',
                'icon' => '🎯',
            ],

            'credit_cards' => [
                'label' => 'Kreditkarten',
                'description' => 'Aktive Kreditkarten und Auslastung',
                'icon' => '💳',
            ],

            'loans' => [
                'label' => 'Kredite',
                'description' => 'Aktive Kredite und deren Status',
                'icon' => '💶',
            ],

            'accounts' => [
                'label' => 'Konten',
                'description' => 'Aktive Konten',
                'icon' => '🏦',
            ],

            'categories' => [
                'label' => 'Ausgaben nach Kategorie',
                'description' => 'Verteilung der monatlichen Ausgaben',
                'icon' => '🏷️',
            ],

            'recent_transactions' => [
                'label' => 'Letzte Buchungen',
                'description' => 'Zuletzt erfasste Transaktionen',
                'icon' => '🧾',
            ],

            'quick_actions' => [
                'label' => 'Schnellzugriff',
                'description' => 'Direkte Zugriffe auf wichtige Bereiche',
                'icon' => '⚡',
            ],
        ];
    }

    /**
     * Standardmäßig sind alle Widgets aktiviert.
     */
    public static function defaultWidgets(): array
    {
        return array_fill_keys(
            array_keys(static::availableWidgets()),
            true
        );
    }

    /**
     * Gibt die effektive Widget-Konfiguration zurück.
     *
     * Fehlende Widgets werden automatisch mit dem Standardwert
     * ergänzt. Dadurch bleiben neue Widgets später automatisch
     * sichtbar, ohne bestehende Benutzer zu migrieren.
     */
    public function effectiveWidgets(): array
    {
        return array_merge(
            static::defaultWidgets(),
            array_intersect_key(
                $this->widgets ?? [],
                static::availableWidgets()
            )
        );
    }

    public function isWidgetEnabled(string $widget): bool
    {
        return (bool) ($this->effectiveWidgets()[$widget] ?? false);
    }

    public function isCompact(): bool
    {
        return $this->display_mode === 'compact';
    }
}
