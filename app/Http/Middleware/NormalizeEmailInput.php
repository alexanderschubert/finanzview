<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * E-Mail-Adressen einheitlich klein schreiben (Anmelden, Registrieren,
 * Passwort vergessen, Profil, Administration). PostgreSQL vergleicht
 * mit Groß-/Kleinschreibung – sonst scheitert „Max@…“ an „max@…“ und
 * dieselbe Adresse könnte zweimal registriert werden.
 */
class NormalizeEmailInput
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') && is_string($request->input('email'))) {
            $request->merge(['email' => mb_strtolower(trim($request->input('email')))]);
        }

        return $next($request);
    }
}
