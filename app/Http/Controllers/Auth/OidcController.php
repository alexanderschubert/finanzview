<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Controllers\Controller;
use App\Models\ApplicationSetting;
use App\Models\User;
use App\Services\OidcService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Anmeldung über OpenID Connect (z. B. Authentik).
 *
 * Die Zwei-Faktor-Abfrage von FinanzView entfällt hier, weil die
 * Anmeldung – inklusive MFA – beim Identity Provider stattfindet.
 */
class OidcController extends Controller
{
    public function __construct(
        private readonly OidcService $oidc
    ) {
    }

    /**
     * Weiterleitung zum Identity Provider.
     */
    public function redirect(Request $request): RedirectResponse
    {
        abort_unless($this->oidc->enabled(), 404);

        $flow = [
            'state' => Str::random(40),
            'nonce' => Str::random(40),
            'verifier' => Str::random(64),
        ];

        $request->session()->put('oidc', $flow);

        try {
            $url = $this->oidc->authorizationUrl(
                route('oidc.callback'),
                $flow['state'],
                $flow['nonce'],
                $flow['verifier']
            );
        } catch (Throwable $e) {
            report($e);

            return $this->fail('Der Anmeldedienst ist derzeit nicht erreichbar.');
        }

        return redirect()->away($url);
    }

    /**
     * Rückkehr vom Identity Provider.
     */
    public function callback(Request $request): RedirectResponse
    {
        abort_unless($this->oidc->enabled(), 404);

        $flow = $request->session()->pull('oidc');

        if ($request->filled('error')) {
            return $this->fail('Die Anmeldung über SSO wurde abgebrochen.');
        }

        $state = $request->query('state');
        $code = $request->query('code');

        if (
            ! is_array($flow)
            || ! is_string($state)
            || ! is_string($code)
            || $code === ''
            || ! hash_equals($flow['state'], $state)
        ) {
            return $this->fail('Ungültige Anmeldeanfrage. Bitte versuche es erneut.');
        }

        try {
            $claims = $this->oidc->claimsFromCode(
                $code,
                $flow['verifier'],
                $flow['nonce'],
                route('oidc.callback')
            );
        } catch (Throwable $e) {
            report($e);

            return $this->fail('Die Anmeldung über SSO ist fehlgeschlagen.');
        }

        $user = $this->resolveUser($claims);

        if (is_string($user)) {
            return $this->fail($user);
        }

        Auth::login($user);

        $request->session()->regenerate();

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        return redirect()->intended(config('fortify.home'));
    }

    /**
     * Sucht, verknüpft oder erstellt das Konto zu den Claims.
     *
     * Gibt bei einem Fehler die Meldung für den Benutzer zurück.
     */
    private function resolveUser(array $claims): User|string
    {
        $user = User::where('oidc_sub', $claims['sub'])->first();

        if (! $user) {
            $email = $claims['email'] ?? null;

            if (! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return 'Der Anmeldedienst hat keine gültige E-Mail-Adresse übermittelt.';
            }

            $user = User::where('email', $email)->first();

            if ($user) {
                if ($user->oidc_sub !== null) {
                    return 'Dieses Konto ist bereits mit einem anderen SSO-Benutzer verknüpft.';
                }

                if (! $this->emailIsTrusted($claims)) {
                    return 'Die E-Mail-Adresse ist beim Anmeldedienst nicht bestätigt. '
                        . 'Das Konto kann deshalb nicht automatisch verknüpft werden.';
                }

                $user->forceFill([
                    'oidc_sub' => $claims['sub'],
                ])->save();
            } else {
                if (! ApplicationSetting::get('registration_enabled', true)) {
                    return 'Für diese E-Mail-Adresse gibt es kein Konto '
                        . 'und die Registrierung ist deaktiviert.';
                }

                $user = $this->createUser($claims, $email);
            }
        }

        if ($user->is_active === false) {
            return 'Dein Benutzerkonto wurde deaktiviert.';
        }

        return $user;
    }

    private function createUser(array $claims, string $email): User
    {
        return DB::transaction(function () use ($claims, $email) {
            $name = $claims['name']
                ?? $claims['preferred_username']
                ?? Str::before($email, '@');

            $user = User::create([
                'name' => Str::limit((string) $name, 255, ''),
                'email' => $email,
                // Zufallspasswort: Login per Passwort erst nach
                // "Passwort vergessen" möglich.
                'password' => Str::password(40),
            ]);

            $user->forceFill([
                'oidc_sub' => $claims['sub'],
                'email_verified_at' => $this->emailIsTrusted($claims) ? now() : null,
            ])->save();

            app(CreateNewUser::class)->createDefaultCategories($user);

            return $user->fresh();
        });
    }

    private function emailIsTrusted(array $claims): bool
    {
        return ($claims['email_verified'] ?? false) === true
            || config('services.oidc.trust_email') === true;
    }

    private function fail(string $message): RedirectResponse
    {
        return redirect()
            ->route('login')
            ->withErrors(['email' => $message]);
    }
}
