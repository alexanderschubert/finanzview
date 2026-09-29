<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\RegistrationEnabled;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\NormalizeEmailInput;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // Admin-Middleware
        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'active' => EnsureUserIsActive::class,
        ]);

        $middleware->web(append: [
            RegistrationEnabled::class,
            NormalizeEmailInput::class,
        ]);

        // Nginx Proxy Manager / Reverse Proxy vertrauen – aber nur für
        // Client-IP, Protokoll und Port. X-Forwarded-Host bewusst nicht:
        // Sonst ließe sich die Adresse in Links (z. B. „Passwort vergessen“)
        // per Header auf eine fremde Domain umbiegen. Der Proxy reicht den
        // echten Host ohnehin im Host-Header weiter.
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_AWS_ELB
        );

        $middleware->redirectGuestsTo(
            fn (Request $request) => route('login')
        );

    })
    ->withSchedule(function (Schedule $schedule): void {
        // Wiederkehrende Buchungen
        $schedule->command('recurring:process')
            ->dailyAt('00:05');

        // Kreditkartenabrechnungen
        $schedule->command('credit-cards:generate-statements')
            ->dailyAt('00:10');
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) =>
                $request->is('api/*') ||
                $request->expectsJson(),
        );

    })
    ->create();
