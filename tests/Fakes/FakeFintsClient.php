<?php

namespace Tests\Fakes;

use App\Services\Fints\FintsClient;
use App\Services\Fints\FintsConfig;
use App\Services\Fints\FintsException;
use App\Services\Fints\FintsResult;

/**
 * Simulierte Bank für Tests: Antworten werden vorab festgelegt,
 * Aufrufe mitprotokolliert.
 */
class FakeFintsClient implements FintsClient
{
    public array|\Throwable $modes = [];

    public array $media = [];

    /** @var list<FintsResult|\Throwable> */
    public array $beginResults = [];

    /** @var list<FintsResult|\Throwable> */
    public array $resumeResults = [];

    public array $calls = [];

    public function tanModes(FintsConfig $config, string $pin): array
    {
        $this->calls[] = ['tanModes', $config, $pin];

        if ($this->modes instanceof \Throwable) {
            throw $this->modes;
        }

        return $this->modes;
    }

    public function tanMedia(FintsConfig $config, string $pin, int $tanMode): array
    {
        $this->calls[] = ['tanMedia', $config, $pin, $tanMode];

        return $this->media;
    }

    public function begin(FintsConfig $config, string $pin, string $operation, array $params = []): FintsResult
    {
        $this->calls[] = ['begin', $config, $pin, $operation, $params];

        return $this->next($this->beginResults);
    }

    public function resume(FintsConfig $config, string $pin, string $state, ?string $tan = null): FintsResult
    {
        $this->calls[] = ['resume', $config, $pin, $state, $tan];

        return $this->next($this->resumeResults);
    }

    public function callsTo(string $method): array
    {
        return array_values(array_filter($this->calls, fn ($call) => $call[0] === $method));
    }

    private function next(array &$queue): FintsResult
    {
        $result = array_shift($queue) ?? throw new FintsException('Keine simulierte Antwort mehr.');

        if ($result instanceof \Throwable) {
            throw $result;
        }

        return $result;
    }
}
