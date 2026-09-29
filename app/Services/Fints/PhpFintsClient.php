<?php

namespace App\Services\Fints;

use Fhp\Action\GetBalance;
use Fhp\Action\GetSEPAAccounts;
use Fhp\Action\GetStatementOfAccount;
use Fhp\BaseAction;
use Fhp\FinTs;
use Fhp\Model\SEPAAccount;
use Fhp\Model\StatementOfAccount\Transaction as FintsTransaction;
use Fhp\Options\Credentials;
use Fhp\Options\FinTsOptions;
use Fhp\Protocol\ServerException;
use Fhp\UnsupportedException;
use Illuminate\Support\Facades\Log;

/**
 * FinTS über die Bibliothek nemiah/php-fints.
 *
 * Zwischen zwei HTTP-Anfragen (z. B. während der Freigabe in der
 * S-pushTAN-App) wird der Dialog mit persist() gesichert und im
 * nächsten Aufruf fortgesetzt.
 */
class PhpFintsClient implements FintsClient
{
    public function tanModes(FintsConfig $config, string $pin): array
    {
        return $this->guard(function () use ($config, $pin) {
            $modes = [];

            foreach ($this->make($config, $pin)->getTanModes() as $mode) {
                $modes[] = [
                    'id' => $mode->getId(),
                    'name' => $mode->getName(),
                    'decoupled' => $mode->isDecoupled(),
                    'needs_medium' => $mode->needsTanMedium(),
                ];
            }

            return $modes;
        });
    }

    public function tanMedia(FintsConfig $config, string $pin, int $tanMode): array
    {
        return $this->guard(function () use ($config, $pin, $tanMode) {
            return array_values(array_map(fn ($medium) => [
                'name' => $medium->getName(),
                'phone' => $medium->getPhoneNumber(),
            ], $this->make($config, $pin)->getTanMedia($tanMode)));
        });
    }

    public function begin(FintsConfig $config, string $pin, string $operation, array $params = []): FintsResult
    {
        return $this->guard(function () use ($config, $pin, $operation, $params) {
            $fints = $this->make($config, $pin);
            $fints->selectTanMode($config->tanMode, $config->tanMedium);

            $login = $fints->login();

            if ($login->needsTan()) {
                return $this->suspend($fints, $login, 'login', $operation, $params);
            }

            return $this->runOperation($fints, $operation, $params);
        });
    }

    public function resume(FintsConfig $config, string $pin, string $state, ?string $tan = null): FintsResult
    {
        return $this->guard(function () use ($config, $pin, $state, $tan) {
            $saved = @unserialize(base64_decode($state, true) ?: '', ['allowed_classes' => false]);

            if (! is_array($saved) || ! isset($saved['fints'], $saved['action'], $saved['phase'])) {
                throw new FintsException('Die Freigabe ist abgelaufen. Bitte den Abruf neu starten.');
            }

            $fints = $this->make($config, $pin, $saved['fints']);
            $action = unserialize($saved['action']);

            if ($tan !== null) {
                $fints->submitTan($action, $tan);
            } elseif (! $fints->checkDecoupledSubmission($action)) {
                // Noch nicht freigegeben: Zustand neu sichern (jeder Stand gilt nur einmal).
                return $this->suspend($fints, $action, $saved['phase'], $saved['operation'], $saved['params']);
            }

            if ($saved['phase'] === 'login') {
                return $this->runOperation($fints, $saved['operation'], $saved['params']);
            }

            if ($saved['operation'] === 'sync') {
                $params = $saved['params'];
                $params['results'] = $this->record($params['results'] ?? [], $this->syncSteps($params)[$params['step']], $params, $action);
                $params['step']++;

                return $this->runSync($fints, $params);
            }

            return FintsResult::done($this->collect($fints, $action, $saved['operation']));
        });
    }


    /*
    |--------------------------------------------------------------------------
    | Intern
    |--------------------------------------------------------------------------
    */

    private function make(FintsConfig $config, string $pin, ?string $persisted = null): FinTs
    {
        $options = new FinTsOptions();
        $options->url = $config->url;
        $options->bankCode = $config->bankCode;
        $options->productName = $config->productId;
        $options->productVersion = $config->productVersion;
        $options->timeoutResponse = 60;

        return FinTs::new($options, Credentials::create($config->username, $pin), $persisted);
    }

    private function runOperation(FinTs $fints, string $operation, array $params): FintsResult
    {
        if ($operation === 'sync') {
            return $this->runSync($fints, $params + ['step' => 0, 'results' => []]);
        }

        $action = match ($operation) {
            'accounts' => GetSEPAAccounts::create(),
            default => throw new \InvalidArgumentException("Unbekannte Aktion {$operation}"),
        };

        $fints->execute($action);

        if ($action->needsTan()) {
            return $this->suspend($fints, $action, 'operation', $operation, $params);
        }

        return FintsResult::done($this->collect($fints, $action, $operation));
    }

    private function collect(FinTs $fints, BaseAction $action, string $operation): array
    {
        $data = match ($operation) {
            'accounts' => array_map(fn (SEPAAccount $account) => [
                'iban' => $account->getIban(),
                'bic' => $account->getBic(),
                'account_number' => $account->getAccountNumber(),
                'sub_account' => $account->getSubAccount(),
                'blz' => $account->getBlz(),
            ], $action->getAccounts()),
        };

        try {
            $fints->close();
        } catch (\Throwable) {
            // Abmelden ist optional.
        }

        return array_values($data);
    }

    /**
     * Abruf für mehrere Konten in einem Dialog: je Konto Umsätze und
     * Kontostand. Verlangt die Bank zwischendurch eine Freigabe, wird
     * der Fortschritt (Schritt + bisherige Ergebnisse) mitgesichert.
     *
     * Ergebnis: [Konto-ID => ['transactions' => [...], 'balance' => ?[...], 'errors' => [...]]]
     */
    private function runSync(FinTs $fints, array $params): FintsResult
    {
        $steps = $this->syncSteps($params);

        for ($step = $params['step']; $step < count($steps); $step++) {
            [$type, $index] = $steps[$step];
            $account = $this->sepaAccount($params['accounts'][$index]);

            $action = $type === 'statement'
                ? GetStatementOfAccount::create($account, new \DateTime($params['from']), new \DateTime($params['to']), false, false)
                : GetBalance::create($account, false);

            try {
                $fints->execute($action);
            } catch (ServerException|UnsupportedException $e) {
                // Z. B. Sparbuch ohne Umsatzabruf: Fehler merken, mit dem nächsten Schritt weitermachen.
                $id = $params['accounts'][$index]['id'];
                $params['results'][$id] ??= ['transactions' => [], 'balance' => null, 'errors' => []];
                $params['results'][$id]['errors'][] = $this->stepError($type, $e);
                continue;
            }

            if ($action->needsTan()) {
                $params['step'] = $step;

                return $this->suspend($fints, $action, 'sync', 'sync', $params);
            }

            $params['results'] = $this->record($params['results'], $steps[$step], $params, $action);
        }

        try {
            $fints->close();
        } catch (\Throwable) {
            // Abmelden ist optional.
        }

        return FintsResult::done($params['results']);
    }

    /**
     * @return list<array{0: string, 1: int}>
     */
    private function syncSteps(array $params): array
    {
        $steps = [];

        foreach (array_keys($params['accounts']) as $index) {
            $steps[] = ['statement', $index];
            $steps[] = ['balance', $index];
        }

        return $steps;
    }

    private function record(array $results, array $step, array $params, BaseAction $action): array
    {
        [$type, $index] = $step;
        $id = $params['accounts'][$index]['id'];

        $results[$id] ??= ['transactions' => [], 'balance' => null, 'errors' => []];

        if ($type === 'statement') {
            $results[$id]['transactions'] = $this->transactions($action);
        } else {
            $balance = $action->getBalances()[0] ?? null;
            $saldo = $balance?->getGebuchterSaldo();

            $results[$id]['balance'] = $saldo ? [
                'amount' => round($saldo->getAmount(), 2),
                'date' => $saldo->getTimestamp()->format('Y-m-d'),
            ] : null;
        }

        return $results;
    }

    private function stepError(string $type, \Throwable $e): string
    {
        $what = $type === 'statement' ? 'Umsätze' : 'Kontostand';

        return $e instanceof UnsupportedException
            ? "{$what}: von der Bank für dieses Konto nicht angeboten"
            : "{$what}: " . mb_strimwidth(trim($e->getMessage()), 0, 200, '…');
    }

    private function transactions(GetStatementOfAccount $action): array
    {
        $rows = [];

        foreach ($action->getStatement()->getStatements() as $statement) {
            /** @var FintsTransaction $transaction */
            foreach ($statement->getTransactions() as $transaction) {
                $date = $transaction->getBookingDate() ?? $transaction->getValutaDate() ?? $statement->getDate();
                $sign = $transaction->getCreditDebit() === FintsTransaction::CD_DEBIT ? -1 : 1;

                $rows[] = [
                    'date' => $date?->format('Y-m-d'),
                    'amount' => round($sign * $transaction->getAmount(), 2),
                    'name' => trim($transaction->getName()),
                    'description' => trim($transaction->getMainDescription()),
                    'booking_text' => trim($transaction->getBookingText()),
                    'end_to_end_id' => trim($transaction->getEndToEndID()),
                ];
            }
        }

        return $rows;
    }

    private function sepaAccount(array $data): SEPAAccount
    {
        $account = new SEPAAccount();
        $account->setIban($data['iban'] ?? null);
        $account->setBic($data['bic'] ?? null);
        $account->setAccountNumber($data['account_number'] ?? null);
        $account->setSubAccount($data['sub_account'] ?? null);
        $account->setBlz($data['blz'] ?? null);

        return $account;
    }

    private function suspend(FinTs $fints, BaseAction $action, string $phase, string $operation, array $params): FintsResult
    {
        $tanRequest = $action->getTanRequest();

        $state = base64_encode(serialize([
            'fints' => $fints->persist(),
            'action' => serialize($action),
            'phase' => $phase,
            'operation' => $operation,
            'params' => $params,
        ]));

        return FintsResult::needsTan(
            $state,
            $tanRequest?->getChallenge(),
            $tanRequest?->getTanMediumName(),
            (bool) $fints->getSelectedTanMode()?->isDecoupled()
        );
    }

    /**
     * Bibliotheks- und Serverfehler in verständliche Meldungen übersetzen.
     */
    private function guard(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (FintsException $e) {
            throw $e;
        } catch (ServerException $e) {
            Log::warning('FinTS-Serverfehler', ['message' => $e->getMessage()]);

            throw new FintsException('Die Bank hat den Vorgang abgelehnt: ' . $this->bankMessage($e));
        } catch (\Fhp\CurlException $e) {
            throw new FintsException('Die Bank ist nicht erreichbar. Bitte FinTS-Adresse prüfen und später erneut versuchen.');
        } catch (\Throwable $e) {
            Log::error('FinTS-Fehler', ['exception' => $e]);

            throw new FintsException('Der Bankabruf ist fehlgeschlagen. Details stehen im Protokoll.');
        }
    }

    /**
     * Meldung der Bank im Wortlaut (z. B. „PIN falsch“), gekürzt.
     */
    private function bankMessage(ServerException $e): string
    {
        return mb_strimwidth(trim($e->getMessage()), 0, 300, '…');
    }
}
