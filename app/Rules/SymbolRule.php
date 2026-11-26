<?php

namespace App\Rules;

use App\Support\Symbols;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Symbol einer Kategorie oder Plan-Gruppe: Emoji (frei) oder „lucide:<Name>“ mit bekanntem Namen.
 */
class SymbolRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value) || mb_strlen($value) > Symbols::MAX_LENGTH) {
            $fail('Das Symbol ist zu lang.');

            return;
        }

        if (Symbols::isSymbol($value)) {
            if (! Symbols::exists(Symbols::name($value))) {
                $fail('Dieses Symbol gibt es nicht.');
            }

            return;
        }

        if (mb_strlen($value) > 20) {
            $fail('Ein Emoji darf höchstens 20 Zeichen haben.');
        }
    }
}
