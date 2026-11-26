<?php

namespace App\Support;

/**
 * Symbole für Kategorien und Plan-Gruppen: ein Emoji oder ein Lucide-Symbol.
 *
 * Gespeichert wird entweder das Emoji selbst („🛒“) oder „lucide:<name>“
 * („lucide:shopping-cart“). Die Zeichnungen stehen in public/symbols/lucide.json
 * (erzeugt mit bin/build-symbols.py, Lizenz: public/symbols/LICENSE-lucide.txt).
 */
class Symbols
{
    public const PREFIX = 'lucide:';

    public const MAX_LENGTH = 60;

    /** @var array<string, array{n: list<array{0: string, 1: array<string, string>}>, t: string}>|null */
    private static ?array $icons = null;

    public static function isSymbol(?string $icon): bool
    {
        return is_string($icon) && str_starts_with($icon, self::PREFIX);
    }

    public static function name(?string $icon): ?string
    {
        return self::isSymbol($icon) ? substr($icon, strlen(self::PREFIX)) : null;
    }

    public static function exists(string $name): bool
    {
        return isset(self::icons()[$name]);
    }

    /**
     * Emoji für reinen Text (Auswahllisten, Beschriftungen); bei Lucide-Symbolen leer.
     */
    public static function text(?string $icon): string
    {
        return self::isSymbol($icon) ? '' : (string) $icon;
    }

    /**
     * Formen eines Symbols für die API und den Client: Liste von [Tag, Attribute].
     *
     * @return list<array{0: string, 1: array<string, string>}>|null
     */
    public static function shapes(string $name): ?array
    {
        return self::icons()[$name]['n'] ?? null;
    }

    /**
     * Inline-SVG eines Lucide-Symbols (Farbe über currentColor), sonst null.
     */
    public static function svg(?string $icon, string $class = 'w-[55%] h-[55%]'): ?string
    {
        $name = self::name($icon);
        $shapes = $name !== null ? self::shapes($name) : null;

        if ($shapes === null) {
            return null;
        }

        $inner = '';
        foreach ($shapes as [$tag, $attrs]) {
            $inner .= '<' . $tag;
            foreach ($attrs as $key => $value) {
                $inner .= ' ' . e($key) . '="' . e($value) . '"';
            }
            $inner .= '/>';
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"'
            . ' stroke-linecap="round" stroke-linejoin="round" class="' . e($class) . '" aria-hidden="true">' . $inner . '</svg>';
    }

    /**
     * @return array<string, array{n: list<array{0: string, 1: array<string, string>}>, t: string}>
     */
    public static function icons(): array
    {
        if (self::$icons === null) {
            $file = public_path('symbols/lucide.json');
            $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
            self::$icons = is_array($data['icons'] ?? null) ? $data['icons'] : [];
        }

        return self::$icons;
    }
}
