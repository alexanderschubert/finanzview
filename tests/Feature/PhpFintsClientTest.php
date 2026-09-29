<?php

namespace Tests\Feature;

use App\Services\Fints\FintsClient;
use App\Services\Fints\FintsConfig;
use App\Services\Fints\FintsException;
use App\Services\Fints\PhpFintsClient;
use Tests\TestCase;

/**
 * Prüft die Anbindung an php-fints ohne echte Bank.
 */
class PhpFintsClientTest extends TestCase
{
    private function config(): FintsConfig
    {
        return new FintsConfig(
            url: 'https://127.0.0.1:9/fints30',
            bankCode: '10050000',
            username: 'test',
            productId: 'TEST123',
            productVersion: '1.0',
            tanMode: 923,
        );
    }

    public function test_real_client_is_bound(): void
    {
        $this->assertInstanceOf(PhpFintsClient::class, app(FintsClient::class));
    }

    public function test_unreachable_bank_gives_friendly_error(): void
    {
        $this->expectException(FintsException::class);
        $this->expectExceptionMessage('nicht erreichbar');

        (new PhpFintsClient())->tanModes($this->config(), 'geheim');
    }

    public function test_invalid_state_is_rejected(): void
    {
        $this->expectException(FintsException::class);
        $this->expectExceptionMessage('Freigabe ist abgelaufen');

        (new PhpFintsClient())->resume($this->config(), 'geheim', 'kaputt');
    }
}
