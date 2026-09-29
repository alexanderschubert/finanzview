<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\BankConnection;
use App\Models\BankConnectionAccount;
use App\Services\Fints\BankTransactionImporter;
use App\Services\Fints\FintsClient;
use App\Services\Fints\FintsConfig;
use App\Services\Fints\FintsException;
use App\Services\Fints\FintsResult;
use App\Services\Fints\PendingFintsSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Bankverbindungen per FinTS: einrichten und Umsätze auf Knopfdruck
 * abrufen. Die Online-Banking-PIN wird bei jedem Vorgang abgefragt
 * und nie dauerhaft gespeichert.
 */
class BankConnectionController extends Controller
{
    public function __construct(
        private FintsClient $client,
        private PendingFintsSession $pending,
        private BankTransactionImporter $importer,
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Übersicht und Stammdaten
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $connections = BankConnection::query()
            ->where('user_id', Auth::id())
            ->with('linkedAccounts.account')
            ->orderBy('name')
            ->get();

        return view('bank-connections.index', [
            'connections' => $connections,
            'enabled' => FintsConfig::enabled(),
            'pending' => $this->pending->get(Auth::id()),
        ]);
    }

    public function create()
    {
        if ($redirect = $this->requireEnabled()) {
            return $redirect;
        }

        return view('bank-connections.create', [
            'connection' => new BankConnection(['name' => 'Girokonto', 'url' => 'https://']),
        ]);
    }

    public function store(Request $request)
    {
        if ($redirect = $this->requireEnabled()) {
            return $redirect;
        }

        $connection = BankConnection::create([
            ...$this->validated($request),
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('bank-connections.setup', $connection);
    }

    public function edit(BankConnection $bankConnection)
    {
        $this->authorizeConnection($bankConnection);

        return view('bank-connections.edit', [
            'connection' => $bankConnection->load('linkedAccounts.account'),
        ]);
    }

    public function update(Request $request, BankConnection $bankConnection)
    {
        $this->authorizeConnection($bankConnection);

        $validated = $this->validated($request);

        // Andere Bank oder anderer Zugang: TAN-Verfahren und Konto neu wählen.
        $changedAccess = $validated['bank_code'] !== $bankConnection->bank_code
            || $validated['url'] !== $bankConnection->url
            || $validated['username'] !== $bankConnection->username;

        if ($changedAccess) {
            $validated += ['tan_mode' => null, 'tan_mode_name' => null, 'tan_medium' => null];

            BankConnectionAccount::where('bank_connection_id', $bankConnection->id)->delete();
        }

        $bankConnection->update($validated);

        return $changedAccess
            ? redirect()->route('bank-connections.setup', $bankConnection)->with('success', 'Gespeichert. Bitte die Verbindung neu einrichten.')
            : redirect()->route('bank-connections.index')->with('success', 'Bankverbindung wurde gespeichert.');
    }

    public function destroy(BankConnection $bankConnection)
    {
        $this->authorizeConnection($bankConnection);

        $bankConnection->delete();
        $this->pending->forget(Auth::id());

        return redirect()
            ->route('bank-connections.index')
            ->with('success', 'Bankverbindung wurde entfernt. Bereits abgerufene Buchungen bleiben erhalten.');
    }


    /*
    |--------------------------------------------------------------------------
    | Einrichtung: TAN-Verfahren und Bankkonto
    |--------------------------------------------------------------------------
    */

    public function setup(BankConnection $bankConnection)
    {
        $this->authorizeConnection($bankConnection);

        if ($redirect = $this->requireEnabled()) {
            return $redirect;
        }

        $pending = $this->pending->get(Auth::id(), $bankConnection->id);

        return view('bank-connections.setup', [
            'connection' => $bankConnection,
            'stage' => $pending['stage'] ?? 'pin',
            'modes' => $pending['modes'] ?? [],
            'media' => $pending['media'] ?? [],
            'selectedMode' => $pending['selected_mode'] ?? null,
        ]);
    }

    public function tanModes(Request $request, BankConnection $bankConnection)
    {
        $this->authorizeConnection($bankConnection);

        if ($redirect = $this->requireEnabled()) {
            return $redirect;
        }

        $pin = $request->validate(['pin' => ['required', 'string', 'max:100']])['pin'];

        try {
            $modes = $this->client->tanModes(FintsConfig::fromConnection($bankConnection), $pin);
        } catch (FintsException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($modes === []) {
            return back()->with('error', 'Die Bank bietet kein TAN-Verfahren für FinTS an.');
        }

        $this->pending->put(Auth::id(), [
            'connection_id' => $bankConnection->id,
            'pin' => $pin,
            'stage' => 'tan_modes',
            'modes' => $modes,
        ]);

        return redirect()->route('bank-connections.setup', $bankConnection);
    }

    public function selectTanMode(Request $request, BankConnection $bankConnection)
    {
        $this->authorizeConnection($bankConnection);

        $pending = $this->pending->get(Auth::id(), $bankConnection->id);

        if (! in_array($pending['stage'] ?? null, ['tan_modes', 'tan_media'], true)) {
            return $this->expired($bankConnection);
        }

        $modes = collect($pending['modes']);

        $validated = $request->validate([
            'tan_mode' => ['required', 'integer', Rule::in($modes->pluck('id')->all())],
            'tan_medium' => ['nullable', 'string', 'max:100'],
        ]);

        $mode = $modes->firstWhere('id', (int) $validated['tan_mode']);
        $medium = $validated['tan_medium'] ?? null;

        try {
            // Verfahren braucht ein TAN-Medium (z. B. Gerät mit der pushTAN-App).
            if ($mode['needs_medium'] && $medium === null) {
                $media = $this->client->tanMedia(FintsConfig::fromConnection($bankConnection), $pending['pin'], $mode['id']);

                if (count($media) > 1) {
                    $this->pending->update(Auth::id(), ['stage' => 'tan_media', 'selected_mode' => $mode['id'], 'media' => $media]);

                    return redirect()->route('bank-connections.setup', $bankConnection);
                }

                $medium = $media[0]['name'] ?? null;
            }

            $bankConnection->update([
                'tan_mode' => $mode['id'],
                'tan_mode_name' => $mode['name'],
                'tan_medium' => $medium,
            ]);

            $result = $this->client->begin(FintsConfig::fromConnection($bankConnection), $pending['pin'], 'accounts');
        } catch (FintsException $e) {
            return back()->with('error', $e->getMessage());
        }

        return $this->handle($bankConnection, $pending['pin'], 'accounts', [], $result);
    }

    public function accounts(BankConnection $bankConnection)
    {
        $this->authorizeConnection($bankConnection);

        $pending = $this->pending->get(Auth::id(), $bankConnection->id);

        if (($pending['stage'] ?? null) !== 'choose_account') {
            return $this->expired($bankConnection);
        }

        $accounts = $this->userAccounts();

        // Vorbelegung: bisherige Zuordnung, sonst FinanzView-Konto mit gleicher IBAN.
        $links = BankConnectionAccount::where('bank_connection_id', $bankConnection->id)->pluck('account_id', 'iban');
        $defaults = [];

        foreach ($pending['accounts'] as $bankAccount) {
            $iban = $bankAccount['iban'];
            $defaults[$iban] = $links[$iban]
                ?? $accounts->first(fn ($account) => $account->iban && str_replace(' ', '', strtoupper($account->iban)) === $iban)?->id;
        }

        // Nichts zugeordnet: erstes Bankkonto → erstes FinanzView-Konto.
        if (array_filter($defaults) === [] && $accounts->isNotEmpty()) {
            $defaults[$pending['accounts'][0]['iban']] = $accounts->first()->id;
        }

        return view('bank-connections.accounts', [
            'connection' => $bankConnection,
            'bankAccounts' => $pending['accounts'],
            'accounts' => $accounts,
            'defaults' => $defaults,
        ]);
    }

    public function saveAccount(Request $request, BankConnection $bankConnection)
    {
        $this->authorizeConnection($bankConnection);

        $pending = $this->pending->get(Auth::id(), $bankConnection->id);

        if (($pending['stage'] ?? null) !== 'choose_account') {
            return $this->expired($bankConnection);
        }

        $bankAccounts = collect($pending['accounts'])->keyBy('iban');
        $accountIds = $this->userAccounts()->pluck('id')->map(fn ($id) => (string) $id)->all();

        $links = collect((array) $request->input('link', []))
            ->only($bankAccounts->keys()->all())
            ->map(fn ($value) => (string) $value)
            ->filter(fn ($value) => $value !== '');

        $invalid = $links->reject(fn ($value) => in_array($value, [...$accountIds, 'new:checking', 'new:savings'], true));
        $existing = $links->reject(fn ($value) => str_starts_with($value, 'new:'));

        if ($links->isEmpty() || $invalid->isNotEmpty()) {
            return back()->withInput()->withErrors(['link' => 'Bitte mindestens ein Bankkonto einem FinanzView-Konto zuordnen.']);
        }

        if ($existing->count() !== $existing->unique()->count()) {
            return back()->withInput()->withErrors(['link' => 'Jedes FinanzView-Konto kann nur einem Bankkonto zugeordnet werden.']);
        }

        DB::transaction(function () use ($bankConnection, $bankAccounts, $links) {
            BankConnectionAccount::where('bank_connection_id', $bankConnection->id)
                ->whereNotIn('iban', $links->keys()->all())
                ->delete();

            foreach ($links as $iban => $value) {
                $bankAccount = $bankAccounts[$iban];
                $isNew = str_starts_with($value, 'new:');

                $accountId = $isNew ? $this->createAccount($bankConnection, $bankAccount, substr($value, 4))->id : (int) $value;

                $link = BankConnectionAccount::firstOrNew([
                    'bank_connection_id' => $bankConnection->id,
                    'iban' => $iban,
                ]);

                $link->fill([
                    'account_id' => $accountId,
                    'bic' => $bankAccount['bic'] ?? null,
                    'account_number' => $bankAccount['account_number'] ?? null,
                    'sub_account' => $bankAccount['sub_account'] ?? null,
                ]);

                if ($isNew) {
                    $link->adopt_balance = true;
                }

                $link->save();
            }
        });

        $this->pending->forget(Auth::id());

        return redirect()
            ->route('bank-connections.index')
            ->with('success', 'Bankverbindung ist eingerichtet. Mit „Umsätze abrufen“ holst du die Buchungen.');
    }

    /**
     * Saldo angleichen: Startsaldo des FinanzView-Kontos so ändern,
     * dass der Kontostand dem der Bank entspricht.
     */
    public function reconcile(BankConnectionAccount $link)
    {
        $link->load('connection', 'account');

        abort_unless($link->connection && (int) $link->connection->user_id === (int) Auth::id(), 404);

        $difference = $link->balanceDifference();

        if ($difference === null || abs($difference) < 0.005) {
            return back()->with('success', 'Der Kontostand stimmt bereits.');
        }

        $account = $link->account;
        $account->opening_balance = round((float) $account->opening_balance + $difference, 2);
        $account->save();

        return back()->with('success', "Startsaldo von „{$account->name}“ wurde um " . number_format($difference, 2, ',', '.') . ' € angepasst.');
    }


    /*
    |--------------------------------------------------------------------------
    | Umsätze abrufen
    |--------------------------------------------------------------------------
    */

    public function syncForm(BankConnection $bankConnection)
    {
        $this->authorizeConnection($bankConnection);

        if ($redirect = $this->requireEnabled()) {
            return $redirect;
        }

        if (! $bankConnection->isReady()) {
            return redirect()->route('bank-connections.setup', $bankConnection);
        }

        return view('bank-connections.sync', ['connection' => $bankConnection->load('linkedAccounts.account')]);
    }

    public function sync(Request $request, BankConnection $bankConnection)
    {
        $this->authorizeConnection($bankConnection);

        if ($redirect = $this->requireEnabled()) {
            return $redirect;
        }

        if (! $bankConnection->isReady()) {
            return redirect()->route('bank-connections.setup', $bankConnection);
        }

        $validated = $request->validate([
            'pin' => ['required', 'string', 'max:100'],
            'period' => ['required', Rule::in(['auto', '30', '90', '180', '365'])],
        ]);

        $params = [
            'accounts' => $bankConnection->linkedAccounts
                ->map(fn (BankConnectionAccount $link) => $link->fintsAccount($bankConnection->bank_code))
                ->values()
                ->all(),
            'from' => $this->syncStart($bankConnection, $validated['period'])->toDateString(),
            'to' => now()->toDateString(),
        ];

        try {
            $result = $this->client->begin(FintsConfig::fromConnection($bankConnection), $validated['pin'], 'sync', $params);
        } catch (FintsException $e) {
            return back()->with('error', $e->getMessage());
        }

        return $this->handle($bankConnection, $validated['pin'], 'sync', $params, $result);
    }


    /*
    |--------------------------------------------------------------------------
    | Freigabe (TAN bzw. pushTAN-App)
    |--------------------------------------------------------------------------
    */

    public function challenge()
    {
        $pending = $this->pending->get(Auth::id());

        if (($pending['stage'] ?? null) !== 'challenge') {
            return redirect()->route('bank-connections.index')->with('error', 'Es wartet kein Bankvorgang auf Freigabe.');
        }

        return view('bank-connections.challenge', [
            'connection' => $this->ownConnection($pending['connection_id']),
            'challenge' => $pending['challenge'],
            'tanMedium' => $pending['tan_medium'],
            'decoupled' => $pending['decoupled'],
            'operation' => $pending['operation'],
        ]);
    }

    public function confirm(Request $request): RedirectResponse|JsonResponse
    {
        $pending = $this->pending->get(Auth::id());

        if (($pending['stage'] ?? null) !== 'challenge') {
            return $this->respond($request, redirect()
                ->route('bank-connections.index')
                ->with('error', 'Die Freigabe ist abgelaufen. Bitte den Vorgang neu starten.'));
        }

        $connection = $this->ownConnection($pending['connection_id']);

        $tan = $pending['decoupled']
            ? null
            : $request->validate(['tan' => ['required', 'string', 'max:50']])['tan'];

        try {
            $result = $this->client->resume(FintsConfig::fromConnection($connection), $pending['pin'], $pending['state'], $tan);
        } catch (FintsException $e) {
            $this->pending->forget(Auth::id());

            return $this->respond($request, redirect()->route('bank-connections.index')->with('error', $e->getMessage()));
        }

        // Noch nicht freigegeben (bzw. weitere TAN nötig).
        if (! $result->isDone()) {
            $this->pending->update(Auth::id(), [
                'state' => $result->state,
                'challenge' => $result->challenge ?? $pending['challenge'],
            ]);

            return $request->wantsJson()
                ? response()->json(['status' => 'pending'])
                : back()->with('status', 'Noch nicht freigegeben – bitte in der App bestätigen.');
        }

        return $this->respond($request, $this->finish($connection, $pending['operation'], $result->data));
    }

    public function cancel()
    {
        $this->pending->forget(Auth::id());

        return redirect()->route('bank-connections.index')->with('success', 'Bankvorgang wurde abgebrochen.');
    }


    /*
    |--------------------------------------------------------------------------
    | Ablauf
    |--------------------------------------------------------------------------
    */

    private function handle(BankConnection $connection, string $pin, string $operation, array $params, FintsResult $result): RedirectResponse
    {
        if ($result->isDone()) {
            return $this->finish($connection, $operation, $result->data);
        }

        $this->pending->put(Auth::id(), [
            'connection_id' => $connection->id,
            'pin' => $pin,
            'stage' => 'challenge',
            'operation' => $operation,
            'params' => $params,
            'state' => $result->state,
            'challenge' => $result->challenge,
            'tan_medium' => $result->tanMedium,
            'decoupled' => $result->decoupled,
        ]);

        return redirect()->route('bank-connections.challenge');
    }

    private function finish(BankConnection $connection, string $operation, array $data): RedirectResponse
    {
        if ($operation === 'accounts') {
            $accounts = array_values(array_filter($data, fn ($account) => filled($account['iban'] ?? null)));

            if ($accounts === []) {
                $this->pending->forget(Auth::id());

                return redirect()->route('bank-connections.index')->with('error', 'Die Bank hat keine Konten mit IBAN geliefert.');
            }

            // PIN wird ab hier nicht mehr gebraucht.
            $this->pending->put(Auth::id(), [
                'connection_id' => $connection->id,
                'stage' => 'choose_account',
                'accounts' => $accounts,
            ]);

            return redirect()->route('bank-connections.accounts', $connection);
        }

        $messages = [];
        $totalImported = 0;

        foreach ($connection->linkedAccounts()->with('account')->get() as $link) {
            $result = $data[$link->id] ?? $data[(string) $link->id] ?? null;

            if ($result === null) {
                continue;
            }

            $summary = $this->importer->import($connection->user_id, $link->account_id, $link->iban, $result['transactions'] ?? []);
            $totalImported += $summary['imported'];

            $link->last_synced_at = now();
            $link->last_error = ($result['errors'] ?? []) === [] ? null : implode(' · ', $result['errors']);

            if (($result['balance'] ?? null) !== null) {
                $link->bank_balance = $result['balance']['amount'];
                $link->balance_date = $result['balance']['date'];
            }

            $link->save();

            // Neu angelegtes Konto: Startsaldo so setzen, dass es zur Bank passt.
            if ($link->adopt_balance && $link->bank_balance !== null) {
                $difference = $link->fresh('account')->balanceDifference();
                $link->account->opening_balance = round((float) $link->account->opening_balance + (float) $difference, 2);
                $link->account->save();
                $link->update(['adopt_balance' => false]);
            }

            $part = $link->account->name . ': ' . match ($summary['imported']) {
                0 => 'keine neuen Umsätze',
                1 => '1 neue Buchung',
                default => "{$summary['imported']} neue Buchungen",
            };

            if ($summary['possible'] > 0) {
                $part .= " ({$summary['possible']} schon erfasst, übersprungen)";
            }

            $messages[] = $part;
        }

        $this->pending->forget(Auth::id());

        $message = implode(' · ', $messages) ?: 'Keine neuen Umsätze.';

        $connection->update([
            'last_synced_at' => now(),
            'last_result' => $message,
        ]);

        return redirect()
            ->route('bank-connections.index')
            ->with('success', $message);
    }

    /**
     * Neues FinanzView-Konto für ein Bankkonto (z. B. Sparbuch).
     */
    private function createAccount(BankConnection $connection, array $bankAccount, string $type): Account
    {
        $label = $type === 'savings' ? 'Sparkonto' : 'Girokonto';

        return Account::create([
            'user_id' => Auth::id(),
            'name' => $label . ' ' . substr($bankAccount['iban'], -4),
            'institution' => $connection->name,
            'type' => $type === 'savings' ? 'savings' : 'checking',
            'currency' => 'EUR',
            'opening_balance' => 0,
            'iban' => $bankAccount['iban'],
            'include_in_total' => true,
            'is_active' => true,
        ]);
    }

    /**
     * Zeitraum: seit dem letzten Abruf (mit 14 Tagen Überlappung für
     * spät gebuchte Umsätze), beim ersten Mal 90 Tage.
     */
    private function syncStart(BankConnection $connection, string $period)
    {
        if ($period !== 'auto') {
            return now()->subDays((int) $period)->startOfDay();
        }

        return $connection->last_synced_at
            ? $connection->last_synced_at->copy()->subDays(14)->startOfDay()
            : now()->subDays(90)->startOfDay();
    }

    private function respond(Request $request, RedirectResponse $redirect): RedirectResponse|JsonResponse
    {
        return $request->wantsJson()
            ? response()->json(['status' => 'done', 'redirect' => $redirect->getTargetUrl()])
            : $redirect;
    }

    private function expired(BankConnection $connection): RedirectResponse
    {
        return redirect()
            ->route('bank-connections.setup', $connection)
            ->with('error', 'Der Vorgang ist abgelaufen. Bitte erneut die PIN eingeben.');
    }

    private function requireEnabled(): ?RedirectResponse
    {
        return FintsConfig::enabled()
            ? null
            : redirect()->route('bank-connections.index');
    }

    private function validated(Request $request): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'bank_code' => ['required', 'regex:/^\d{8}$/'],
            'url' => ['required', 'url:https', 'max:255'],
            'username' => ['required', 'string', 'max:100'],
        ];

        $validated = $request->validate($rules, [
            'bank_code.regex' => 'Die Bankleitzahl hat 8 Ziffern.',
            'url.url' => 'Bitte die vollständige FinTS-Adresse mit https:// angeben.',
        ]);

        $validated['bank_code'] = preg_replace('/\s+/', '', $validated['bank_code']);
        $validated['url'] = trim($validated['url']);
        $validated['username'] = trim($validated['username']);

        return $validated;
    }

    private function userAccounts()
    {
        return Account::query()
            ->where('user_id', Auth::id())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function ownConnection(int $id): BankConnection
    {
        return BankConnection::query()
            ->where('user_id', Auth::id())
            ->findOrFail($id);
    }

    private function authorizeConnection(BankConnection $connection): void
    {
        abort_unless((int) $connection->user_id === (int) Auth::id(), 404);
    }
}
