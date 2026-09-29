<?php

namespace App\Services\Fints;

use Fhp\Action\GetSEPAAccounts;
use Fhp\Action\GetStatementOfAccount;
use Fhp\BaseAction;
use Fhp\FinTs;
use Fhp\Model\SEPAAccount;
use Fhp\Model\StatementOfAccount\Transaction as FintsTransaction;
use Fhp\Options\Credentials;
use Fhp\Options\FinTsOptions;
use Fhp\Protocol\ServerException;
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
        $action = match ($operation) {
            'accounts' => GetSEPAAccounts::create(),
            'statement' => GetStatementOfAccount::create(
                $this->sepaAccount($params['account']),
                new \DateTime($params['from']),
                new \DateTime($params['to']),
                false,
                false
            ),
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
            'statement' => $this->transactions($action),
        };

        try {
            $fints->close();
        } catch (\Throwable) {
            // Abmelden ist optional.
        }

        return array_values($data);
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
