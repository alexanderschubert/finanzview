<?php

namespace App\Services\Fints;

/**
 * Ergebnis eines FinTS-Schritts: fertig (mit Daten) oder die Bank
 * verlangt eine Freigabe (TAN-Eingabe oder Bestätigung in der App).
 */
final class FintsResult
{
    private function __construct(
        public readonly string $status,
        public readonly mixed $data = null,
        public readonly ?string $state = null,
        public readonly ?string $challenge = null,
        public readonly ?string $tanMedium = null,
        public readonly bool $decoupled = false,
    ) {
    }

    public static function done(mixed $data): self
    {
        return new self('done', data: $data);
    }

    public static function needsTan(string $state, ?string $challenge, ?string $tanMedium, bool $decoupled): self
    {
        return new self('tan', state: $state, challenge: $challenge, tanMedium: $tanMedium, decoupled: $decoupled);
    }

    public function isDone(): bool
    {
        return $this->status === 'done';
    }
}
