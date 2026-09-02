<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Einstellung auslesen.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        return match ($setting->value) {
            'true', '1' => true,
            'false', '0' => false,
            default => $setting->value,
        };
    }

    /**
     * Einstellung speichern.
     */
    public static function set(string $key, mixed $value): static
    {
        $storedValue = match (true) {
            is_bool($value) => $value ? '1' : '0',
            default => (string) $value,
        };

        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $storedValue]
        );
    }
}
