<?php

namespace App\Http\Middleware;

use App\Models\ApplicationSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RegistrationEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            $request->routeIs('register', 'register.store') &&
            ! ApplicationSetting::get('registration_enabled', true)
        ) {
            abort(404);
        }

        return $next($request);
    }
}
