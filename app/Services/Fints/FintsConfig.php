<?php

namespace App\Services\Fints;

use App\Models\BankConnection;

/**
 * Zugangsdaten und Einstellungen für einen FinTS-Aufruf.
 */
final class FintsConfig
{
    public function __construct(
        public readonly string $url,
        public readonly string $bankCode,
        public readonly string $username,
        public readonly string $productId,
        public readonly string $productVersion,
        public readonly ?int $tanMode = null,
        public readonly ?string $tanMedium = null,
    ) {
    }

    public static function enabled(): bool
    {
        return filled(config('services.fints.product_id'));
    }

    public static function fromConnection(BankConnection $connection): self
    {
        return new self(
            url: $connection->url,
            bankCode: $connection->bank_code,
            username: $connection->username,
            productId: (string) config('services.fints.product_id'),
            productVersion: (string) config('services.fints.product_version', '1.0'),
            tanMode: $connection->tan_mode,
            tanMedium: $connection->tan_medium,
        );
    }
}
