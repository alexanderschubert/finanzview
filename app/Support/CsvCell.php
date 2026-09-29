<?php

namespace App\Support;

/**
 * Schutz vor CSV-Injection: Texte, die mit =, +, -, @ oder einem
 * Steuerzeichen beginnen, würden Excel/Numbers als Formel ausführen –
 * z. B. ein präparierter Verwendungszweck einer Überweisung. Solche
 * Werte bekommen ein führendes Apostroph. Zahlen bleiben unverändert.
 */
final class CsvCell
{
    public static function safe(mixed $value): mixed
    {
        // Reine Zahlen (auch „-12,34“) sind ungefährlich und bleiben unverändert.
        if (! is_string($value) || $value === '' || preg_match('/^[+-]?\d+(?:[.,]\d+)?$/', $value)) {
            return $value;
        }

        return in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)
            ? "'" . $value
            : $value;
    }

    public static function row(array $values): array
    {
        return array_map([self::class, 'safe'], $values);
    }
}
