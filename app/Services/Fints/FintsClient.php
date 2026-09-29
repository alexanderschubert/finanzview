<?php

namespace App\Services\Fints;

interface FintsClient
{
    /**
     * Verfügbare TAN-Verfahren.
     *
     * @return list<array{id: int, name: string, decoupled: bool, needs_medium: bool}>
     */
    public function tanModes(FintsConfig $config, string $pin): array;

    /**
     * TAN-Medien (z. B. Name des Geräts mit der pushTAN-App).
     *
     * @return list<array{name: string, phone: ?string}>
     */
    public function tanMedia(FintsConfig $config, string $pin, int $tanMode): array;

    /**
     * Anmelden und eine Aktion ausführen.
     *
     * „accounts“ → list<array{iban, bic, account_number, sub_account, blz}>
     * „statement“ (params: account, from, to) →
     *     list<array{date, amount, name, description, booking_text, end_to_end_id}>
     */
    public function begin(FintsConfig $config, string $pin, string $operation, array $params = []): FintsResult;

    /**
     * Nach einer Freigabe fortsetzen. Ohne $tan wird bei der Bank
     * nachgefragt, ob die Freigabe in der App erfolgt ist.
     */
    public function resume(FintsConfig $config, string $pin, string $state, ?string $tan = null): FintsResult;
}
