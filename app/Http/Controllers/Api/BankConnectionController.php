<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankConnection;
use App\Services\Fints\BankSyncService;
use App\Services\Fints\FintsClient;
use App\Services\Fints\FintsConfig;
use App\Services\Fints\FintsException;
use App\Services\Fints\FintsResult;
use App\Services\Fints\PendingFintsSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Bankabruf per FinTS für die native App. PIN und TAN gehen nur an die Bank
 * (bzw. verschlüsselt in den Zwischenspeicher) und erscheinen nie in
 * Antworten, Fehlermeldungen oder Logs.
 */
class BankConnectionController extends Controller
{
    public function __construct(
        private FintsClient $client,
        private PendingFintsSession $pending,
        private BankSyncService $sync,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $connections = BankConnection::query()
            ->where('user_id', $request->user()->id)
            ->with('linkedAccounts.account')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $connections->map(fn (BankConnection $connection) => [
                'id' => $connection->id,
                'name' => $connection->name,
                'bank_name' => $connection->name,
                'last_synced_at' => $connection->last_synced_at?->toIso8601String(),
                'accounts' => $connection->linkedAccounts
                    ->filter(fn ($link) => $link->account)
                    ->map(fn ($link) => ['id' => $link->account->id, 'name' => $link->account->name])
                    ->values(),
            ])->values(),
            'fints_enabled' => FintsConfig::enabled(),
        ]);
    }

    public function sync(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $connection = BankConnection::query()
            ->where('user_id', $user->id)
            ->with('linkedAccounts.account')
            ->findOrFail($id);

        if (! FintsConfig::enabled()) {
            return response()->json(['message' => 'Der Bankabruf ist auf diesem Server nicht eingerichtet.'], 422);
        }

        if (! $connection->isReady()) {
            return response()->json(['message' => 'Die Bankverbindung ist noch nicht fertig eingerichtet (bitte in der Weboberfläche abschließen).'], 422);
        }

        $validated = $request->validate([
            'pin' => ['required', 'string', 'max:100'],
            'from' => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        $params = $this->sync->params(
            $connection,
            isset($validated['from']) ? \Illuminate\Support\Carbon::parse($validated['from'])->toDateString() : null
        );

        try {
            $result = $this->client->begin(FintsConfig::fromConnection($connection), $validated['pin'], 'sync', $params);
        } catch (FintsException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable) {
            return response()->json(['message' => 'Die Bank ist gerade nicht erreichbar.'], 502);
        }

        if ($result->isDone()) {
            return $this->done($connection, $result->data, $user->id);
        }

        $this->pending->put($user->id, [
            'connection_id' => $connection->id,
            'pin' => $validated['pin'],
            'stage' => 'challenge',
            'operation' => 'sync',
            'params' => $params,
            'state' => $result->state,
            'challenge' => $result->challenge,
            'tan_medium' => $result->tanMedium,
            'decoupled' => $result->decoupled,
        ]);

        return $this->challenge($result);
    }

    public function confirm(Request $request): JsonResponse
    {
        $user = $request->user();
        $pending = $this->pending->get($user->id);

        if (($pending['stage'] ?? null) !== 'challenge' || ($pending['operation'] ?? null) !== 'sync') {
            return response()->json(['message' => 'Die Freigabe ist abgelaufen. Bitte den Vorgang neu starten.'], 404);
        }

        $connection = BankConnection::query()
            ->where('user_id', $user->id)
            ->with('linkedAccounts.account')
            ->find($pending['connection_id']);

        if (! $connection) {
            $this->pending->forget($user->id);

            return response()->json(['message' => 'Die Bankverbindung existiert nicht mehr.'], 404);
        }

        $tan = $pending['decoupled']
            ? null
            : $request->validate(['tan' => ['required', 'string', 'max:50']])['tan'];

        try {
            $result = $this->client->resume(FintsConfig::fromConnection($connection), $pending['pin'], $pending['state'], $tan);
        } catch (FintsException $e) {
            $this->pending->forget($user->id);

            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable) {
            $this->pending->forget($user->id);

            return response()->json(['message' => 'Die Bank ist gerade nicht erreichbar.'], 502);
        }

        if ($result->isDone()) {
            $this->pending->forget($user->id);

            return $this->done($connection, $result->data, $user->id);
        }

        $this->pending->update($user->id, [
            'state' => $result->state,
            'challenge' => $result->challenge ?? $pending['challenge'],
        ]);

        // Entkoppelt (App-Freigabe): noch nicht bestätigt. Sonst weitere TAN nötig.
        if ($pending['decoupled']) {
            return response()->json(['status' => 'pending'], 409);
        }

        return $this->challenge($result, $pending);
    }

    public function cancel(Request $request): JsonResponse
    {
        $this->pending->forget($request->user()->id);

        return response()->json(['status' => 'cancelled']);
    }

    private function done(BankConnection $connection, mixed $data, int $userId): JsonResponse
    {
        $summary = $this->sync->apply($connection, is_array($data) ? $data : []);

        $this->pending->forget($userId);

        return response()->json([
            'status' => 'done',
            'imported' => $summary['imported'],
            'skipped' => $summary['skipped'],
            'message' => $summary['message'],
        ]);
    }

    private function challenge(FintsResult $result, ?array $fallback = null): JsonResponse
    {
        return response()->json([
            'status' => 'challenge',
            'challenge' => [
                'message' => $result->challenge ?? $fallback['challenge'] ?? null,
                'tan_medium' => $result->tanMedium ?? $fallback['tan_medium'] ?? null,
                'decoupled' => $result->decoupled,
                'image' => null,
            ],
        ], 202);
    }
}
