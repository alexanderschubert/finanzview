<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use App\Actions\Fortify\CreateNewUser;
use Laravel\Passkeys\Passkeys;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);

        /*
         * =========================================================
         * LOGIN
         * =========================================================
         *
         * Deaktivierte Benutzer dürfen sich nicht anmelden.
         * Bei erfolgreichem Login wird der letzte Login gespeichert.
         */
        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('email', $request->email)->first();

            if (! $user || ! $user->is_active) {
                return null;
            }

            if (! Hash::check($request->password, $user->password)) {
                return null;
            }

            $user->forceFill([
                'last_login_at' => now(),
            ])->save();

            return $user;
        });

        Fortify::loginView(function () {
            return view('auth.login');
        });

        Fortify::registerView(function () {
            return view('auth.register');
        });

        Fortify::requestPasswordResetLinkView(function () {
            return view('auth.forgot-password');
        });

        Fortify::resetPasswordView(function (Request $request) {
            return view('auth.reset-password', ['request' => $request]);
        });

        Fortify::confirmPasswordView(function () {
            return view('auth.confirm-password');
        });

        Fortify::twoFactorChallengeView(function () {
            return view('auth.two-factor-challenge');
        });

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;

            return Limit::perMinute(5)->by(
                $email . '|' . $request->ip()
            );
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by(
                $request->session()->get('login.id')
            );
        });


        /*
         * =========================================================
         * PASSKEYS
         * =========================================================
         *
         * Relying Party (Domain) und erlaubte Herkunft: aus der
         * Konfiguration, sonst aus der aufgerufenen Adresse. So klappt
         * es hinter dem Reverse Proxy auch ohne passende APP_URL; der
         * Browser bindet jeden Passkey ohnehin fest an die Domain.
         */
        $request = $this->app['request'];

        config([
            'passkeys.relying_party_id' => config('fortify.passkeys.relying_party_id') ?: $request->getHost(),
            'passkeys.allowed_origins' => config('fortify.passkeys.allowed_origins') ?: [$request->getSchemeAndHttpHost()],
        ]);

        // Deaktivierte Benutzer dürfen sich auch per Passkey nicht anmelden.
        Passkeys::authorizeLoginUsing(function (Request $request, User $user) {
            if (! $user->is_active) {
                return false;
            }

            $user->forceFill(['last_login_at' => now()])->save();

            return true;
        });

        RateLimiter::for('passkeys', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    }
}