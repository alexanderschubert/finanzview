<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiPresenter;
use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Einmal-Code (aus /app/connect) gegen ein Token tauschen, Token widerrufen.
 */
class AuthController extends Controller
{
    public function token(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:200'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        // pull: der Code ist nur einmal nutzbar.
        $entry = Cache::pull(self::cacheKey($validated['code']));
        $user = is_array($entry) ? User::find($entry['user_id'] ?? 0) : null;

        if (! $user || ! $user->is_active) {
            return response()->json([
                'message' => 'Der Code ist ungültig oder abgelaufen.',
                'errors' => ['code' => ['Der Code ist ungültig oder abgelaufen.']],
            ], 422);
        }

        [, $plain] = ApiToken::issue($user, $validated['device_name'] ?? $entry['device_name'] ?? 'App');

        return response()->json(['token' => $plain, 'user' => ApiPresenter::user($user)]);
    }

    public function destroy(Request $request)
    {
        $request->attributes->get('api_token')->delete();

        return response()->noContent();
    }

    public static function cacheKey(string $code): string
    {
        return 'app_connect:' . hash('sha256', $code);
    }
}
