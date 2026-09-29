<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\BankConnection;
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
            ->with('account')
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
            'connection' => $bankConnection,
            'accounts' => $this->userAccounts(),
        ]);
    }

    public function update(Request $request, BankConnection $bankConnection)
    {
        $this->authorizeConnection($bankConnection);

        $validated = $this->validated($request, withAccount: true);

        // Andere Bank oder anderer Zugang: TAN-Verfahren und Konto neu wählen.
        $changedAccess = $validated['bank_code'] !== $bankConnection->bank_code
            || $validated['url'] !== $bankConnection->url
            || $validated['username'] !== $bankConnection->username;

        if ($changedAccess) {
            $validated += [
                'tan_mode' => null, 'tan_mode_name' => null, 'tan_medium' => null,
                'iban' => null, 'bic' => null, 'bank_account_number' => null, 'bank_sub_account' => null,
            ];
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

        return view('bank-connections.accounts', [
            'connection' => $bankConnection,
            'bankAccounts' => $pending['accounts'],
            'accounts' => $this->userAccounts(),
        ]);
    }

    public function saveAccount(Request $request, BankConnection $bankConnection)
    {
        $this->authorizeConnection($bankConnection);

        $pending = $this->pending->get(Auth::id(), $bankConnection->id);

        if (($pending['stage'] ?? null) !== 'choose_account') {
            return $this->expired($bankConnection);
        }

        $bankAccounts = collect($pending['accounts']);

        $validated = $request->validate([
            'iban' => ['required', 'string', Rule::in($bankAccounts->pluck('iban')->filter()->all())],
            'account_id' => ['required', 'integer', Rule::in($this->userAccounts()->pluck('id')->all())],
        ], [
            'iban.in' => 'Bitte ein Bankkonto auswählen.',
            'account_id.in' => 'Bitte ein FinanzView-Konto auswählen.',
        ]);

        $bankAccount = $bankAccounts->firstWhere('iban', $validated['iban']);

        $bankConnection->update([
            'account_id' => (int) $validated['account_id'],
            'iban' => $bankAccount['iban'],
            'bic' => $bankAccount['bic'] ?? null,
            'bank_account_number' => $bankAccount['account_number'] ?? null,
            'bank_sub_account' => $bankAccount['sub_account'] ?? null,
        ]);

        $this->pending->forget(Auth::id());

        return redirect()
            ->route('bank-connections.index')
            ->with('success', 'Bankverbindung ist eingerichtet. Mit „Umsätze abrufen“ holst du die Buchungen.');
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

        return view('bank-connections.sync', ['connection' => $bankConnection->load('account')]);
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
            'account' => [
                'iban' => $bankConnection->iban,
                'bic' => $bankConnection->bic,
                'account_number' => $bankConnection->bank_account_number,
                'sub_account' => $bankConnection->bank_sub_account,
                'blz' => $bankConnection->bank_code,
            ],
            'from' => $this->syncStart($bankConnection, $validated['period'])->toDateString(),
            'to' => now()->toDateString(),
        ];

        try {
            $result = $this->client->begin(FintsConfig::fromConnection($bankConnection), $validated['pin'], 'statement', $params);
        } catch (FintsException $e) {
            return back()->with('error', $e->getMessage());
        }

        return $this->handle($bankConnection, $validated['pin'], 'statement', $params, $result);
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

        $summary = $this->importer->import($connection, $data);
        $this->pending->forget(Auth::id());

        $message = match ($summary['imported']) {
            0 => 'Keine neuen Umsätze.',
            1 => '1 neue Buchung wurde abgerufen.',
            default => "{$summary['imported']} neue Buchungen wurden abgerufen.",
        };

        if ($summary['possible'] > 0) {
            $message .= " {$summary['possible']} übersprungen, weil sie schon erfasst waren (gleiches Datum und gleicher Betrag).";
        }

        $connection->update([
            'last_synced_at' => now(),
            'last_result' => $message,
        ]);

        return redirect()
            ->route('transactions.index', ['account_id' => $connection->account_id])
            ->with('success', $message);
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

    private function validated(Request $request, bool $withAccount = false): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'bank_code' => ['required', 'regex:/^\d{8}$/'],
            'url' => ['required', 'url:https', 'max:255'],
            'username' => ['required', 'string', 'max:100'],
        ];

        if ($withAccount) {
            $rules['account_id'] = ['nullable', 'integer', Rule::in($this->userAccounts()->pluck('id')->all())];
        }

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
