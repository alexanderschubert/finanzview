<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\AuthController;
use App\Models\ApiToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Verbindet die native App: Nach der normalen Web-Anmeldung bestätigt der
 * Benutzer, die App erhält über finanzview:// einen Einmal-Code.
 */
class AppConnectController extends Controller
{
    private const CODE_TTL_SECONDS = 300;

    public function show(Request $request)
    {
        $data = $this->validated($request);

        return view('app-connect', [
            'state' => $data['state'],
            'redirectUri' => $data['redirect_uri'],
            'deviceName' => $data['device_name'],
        ]);
    }

    public function approve(Request $request)
    {
        $data = $this->validated($request);

        $code = Str::random(48);

        Cache::put(AuthController::cacheKey($code), [
            'user_id' => $request->user()->id,
            'device_name' => $data['device_name'],
        ], self::CODE_TTL_SECONDS);

        return redirect()->away($data['redirect_uri'] . '?' . http_build_query(['code' => $code, 'state' => $data['state']]));
    }

    public function destroy(Request $request, ApiToken $apiToken)
    {
        abort_unless($apiToken->user_id === $request->user()->id, 404);

        $apiToken->delete();

        return redirect()
            ->to(route('settings.security') . '#apps')
            ->with('success', 'Die Verbindung zu „' . $apiToken->name . '“ wurde getrennt.');
    }

    private function validated(Request $request): array
    {
        $data = validator($request->all(), [
            'state' => ['required', 'string', 'max:200'],
            'redirect_uri' => ['required', 'string', 'max:200', 'regex:#^finanzview://[A-Za-z0-9._~/-]*$#'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ], [
            'redirect_uri.regex' => 'Ungültige Weiterleitung.',
        ])->validate();

        $data['device_name'] = trim((string) ($data['device_name'] ?? '')) ?: 'iPad';

        return $data;
    }
}
