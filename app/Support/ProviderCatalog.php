<?php

namespace App\Support;

use App\Models\FinancialProvider;

/**
 * Katalog bekannter Banken, Zahlungsdienste und Kartenanbieter in Deutschland.
 *
 * Logos liegen unter public/images/providers/<slug>.svg. Wo keine Wortmarke
 * frei verfügbar ist, steht ein neutrales Kürzel in der Markenfarbe. Eigene
 * Logos lassen sich in der Administration jederzeit austauschen.
 */
class ProviderCatalog
{
    /**
     * @return list<array{slug: string, name: string, type: string, color: string}>
     */
    public static function all(): array
    {
        return [
            ['slug' => 'sparkasse', 'name' => 'Sparkasse', 'type' => 'bank', 'color' => '#ff0000'],
            ['slug' => 'volksbank', 'name' => 'Volksbank Raiffeisenbank', 'type' => 'bank', 'color' => '#003d8f'],
            ['slug' => 'deutsche-bank', 'name' => 'Deutsche Bank', 'type' => 'bank', 'color' => '#0018a8'],
            ['slug' => 'commerzbank', 'name' => 'Commerzbank', 'type' => 'bank', 'color' => '#ffcc33'],
            ['slug' => 'ing', 'name' => 'ING', 'type' => 'bank', 'color' => '#ff6200'],
            ['slug' => 'dkb', 'name' => 'DKB', 'type' => 'bank', 'color' => '#1a6fc4'],
            ['slug' => 'postbank', 'name' => 'Postbank', 'type' => 'bank', 'color' => '#ffcc00'],
            ['slug' => 'hypovereinsbank', 'name' => 'HypoVereinsbank', 'type' => 'bank', 'color' => '#e2001a'],
            ['slug' => 'comdirect', 'name' => 'comdirect', 'type' => 'bank', 'color' => '#ffdd00'],
            ['slug' => 'consorsbank', 'name' => 'Consorsbank', 'type' => 'bank', 'color' => '#0097d7'],
            ['slug' => 'targobank', 'name' => 'TARGOBANK', 'type' => 'bank', 'color' => '#0b4ea2'],
            ['slug' => 'santander', 'name' => 'Santander', 'type' => 'bank', 'color' => '#ec0000'],
            ['slug' => 'norisbank', 'name' => 'norisbank', 'type' => 'bank', 'color' => '#00508f'],
            ['slug' => 'sparda-bank', 'name' => 'Sparda-Bank', 'type' => 'bank', 'color' => '#0072bc'],
            ['slug' => 'psd-bank', 'name' => 'PSD Bank', 'type' => 'bank', 'color' => '#f08300'],
            ['slug' => 'gls-bank', 'name' => 'GLS Bank', 'type' => 'bank', 'color' => '#009e73'],
            ['slug' => 'triodos', 'name' => 'Triodos Bank', 'type' => 'bank', 'color' => '#0a7d3e'],
            ['slug' => 'apobank', 'name' => 'apoBank', 'type' => 'bank', 'color' => '#d6001c'],
            ['slug' => 'bbbank', 'name' => 'BBBank', 'type' => 'bank', 'color' => '#00629b'],
            ['slug' => 'dz-bank', 'name' => 'DZ Bank', 'type' => 'bank', 'color' => '#00479d'],
            ['slug' => 'degussa-bank', 'name' => 'Degussa Bank', 'type' => 'bank', 'color' => '#0057a8'],
            ['slug' => 'deka', 'name' => 'DekaBank', 'type' => 'bank', 'color' => '#00a859'],
            ['slug' => 'hsbc', 'name' => 'HSBC', 'type' => 'bank', 'color' => '#db0011'],
            ['slug' => 'barclays', 'name' => 'Barclays', 'type' => 'bank', 'color' => '#00aeef'],
            ['slug' => 'n26', 'name' => 'N26', 'type' => 'bank', 'color' => '#36a18b'],
            ['slug' => 'revolut', 'name' => 'Revolut', 'type' => 'bank', 'color' => '#0075eb'],
            ['slug' => 'bunq', 'name' => 'bunq', 'type' => 'bank', 'color' => '#3ac98a'],
            ['slug' => 'vivid', 'name' => 'Vivid Money', 'type' => 'bank', 'color' => '#6d28d9'],
            ['slug' => 'trade-republic', 'name' => 'Trade Republic', 'type' => 'bank', 'color' => '#111111'],
            ['slug' => 'scalable-capital', 'name' => 'Scalable Capital', 'type' => 'bank', 'color' => '#1d2d50'],
            ['slug' => 'tomorrow', 'name' => 'Tomorrow Bank', 'type' => 'bank', 'color' => '#0f766e'],
            ['slug' => 'c24', 'name' => 'C24 Bank', 'type' => 'bank', 'color' => '#1f1f1f'],
            ['slug' => 'openbank', 'name' => 'Openbank', 'type' => 'bank', 'color' => '#e2001a'],
            ['slug' => 'paypal', 'name' => 'PayPal', 'type' => 'payment', 'color' => '#003087'],
            ['slug' => 'klarna', 'name' => 'Klarna', 'type' => 'payment', 'color' => '#ffb3c7'],
            ['slug' => 'wise', 'name' => 'Wise', 'type' => 'payment', 'color' => '#9fe870'],
            ['slug' => 'amazon-pay', 'name' => 'Amazon Pay', 'type' => 'payment', 'color' => '#ff9900'],
            ['slug' => 'visa', 'name' => 'Visa', 'type' => 'card', 'color' => '#1a1f71'],
            ['slug' => 'mastercard', 'name' => 'Mastercard', 'type' => 'card', 'color' => '#eb001b'],
            ['slug' => 'american-express', 'name' => 'American Express', 'type' => 'card', 'color' => '#006fcf'],
            ['slug' => 'hanseatic-bank', 'name' => 'Hanseatic Bank', 'type' => 'card', 'color' => '#e30613'],
            ['slug' => 'advanzia', 'name' => 'Advanzia Bank', 'type' => 'card', 'color' => '#003a70'],
            ['slug' => 'bank-norwegian', 'name' => 'Bank Norwegian', 'type' => 'card', 'color' => '#e60000'],
            ['slug' => 'auxmoney', 'name' => 'auxmoney', 'type' => 'lender', 'color' => '#00a5b5'],
            ['slug' => 'santander-consumer', 'name' => 'Santander Consumer Bank', 'type' => 'lender', 'color' => '#ec0000'],
            ['slug' => 'bmw-bank', 'name' => 'BMW Bank', 'type' => 'lender', 'color' => '#1c69d4'],
            ['slug' => 'mercedes-benz-bank', 'name' => 'Mercedes-Benz Bank', 'type' => 'lender', 'color' => '#222222'],
            ['slug' => 'volkswagen-bank', 'name' => 'Volkswagen Bank', 'type' => 'lender', 'color' => '#001e50'],
        ];
    }

    /**
     * Legt fehlende Anbieter an und ergänzt Logo und Farbe bei vorhandenen,
     * ohne bereits angepasste Werte zu überschreiben.
     *
     * @return array{created: int, updated: int}
     */
    public static function sync(): array
    {
        $created = 0;
        $updated = 0;

        foreach (self::all() as $entry) {
            $logo = 'images/providers/' . $entry['slug'] . '.svg';

            $provider = FinancialProvider::query()
                ->where('slug', $entry['slug'])
                ->orWhereRaw('lower(name) = ?', [mb_strtolower($entry['name'])])
                ->first();

            if (! $provider) {
                FinancialProvider::create([
                    'slug' => $entry['slug'],
                    'name' => $entry['name'],
                    'type' => $entry['type'],
                    'logo' => $logo,
                    'color' => $entry['color'],
                    'emoji' => null,
                    'is_active' => true,
                ]);
                $created++;

                continue;
            }

            $changes = [];

            if (blank($provider->logo)) {
                $changes['logo'] = $logo;
            }

            if (blank($provider->color)) {
                $changes['color'] = $entry['color'];
            }

            if ($changes !== []) {
                $provider->update($changes);
                $updated++;
            }
        }

        return ['created' => $created, 'updated' => $updated];
    }
}
