<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Anmeldung per „Authorization: Bearer <token>“ für die JSON-API.
 */
class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken();

        $token = $plain
            ? ApiToken::query()->where('token_hash', ApiToken::hash($plain))->with('user')->first()
            : null;

        if (! $token || ! $token->user || ! $token->user->is_active) {
            return response()->json(['message' => 'Nicht angemeldet.'], 401);
        }

        // Nur gelegentlich schreiben, nicht bei jeder Anfrage.
        if ($token->last_used_at === null || $token->last_used_at->lt(now()->subMinutes(5))) {
            $token->forceFill(['last_used_at' => now()])->save();
        }

        Auth::guard('web')->setUser($token->user);
        $request->setUserResolver(fn () => $token->user);
        $request->attributes->set('api_token', $token);

        return $next($request);
    }
}
