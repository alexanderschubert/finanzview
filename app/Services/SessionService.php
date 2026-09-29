<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Angemeldete Geräte (Sitzungen) eines Benutzers.
 *
 * Die Geräteliste gibt es nur mit dem Session-Treiber „database“
 * (Standard). Das Abmelden anderer Geräte wirkt zusätzlich über die
 * Middleware „auth.session“ (Passwort-Fingerabdruck in der Sitzung)
 * und über ein neues „Angemeldet bleiben“-Token.
 */
class SessionService
{
    public function available(): bool
    {
        return config('session.driver') === 'database';
    }

    /**
     * @return Collection<int, array{id: string, current: bool, ip: ?string, device: string, last_active: \Carbon\Carbon}>
     */
    public function forUser(Request $request): Collection
    {
        if (! $this->available()) {
            return collect();
        }

        $currentId = $request->session()->getId();

        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->id)
            ->orderByDesc('last_activity')
            ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(fn ($session) => [
                'id' => $session->id,
                'current' => hash_equals((string) $currentId, (string) $session->id),
                'ip' => $session->ip_address,
                'device' => $this->describe((string) $session->user_agent),
                'last_active' => \Carbon\Carbon::createFromTimestamp($session->last_activity),
            ])
            ->sortByDesc('current')
            ->values();
    }

    /**
     * Alle anderen Sitzungen beenden. Die aktuelle bleibt angemeldet.
     *
     * @return int Anzahl beendeter Sitzungen (0, wenn keine Liste verfügbar)
     */
    public function logoutOtherDevices(Request $request): int
    {
        $user = $request->user();
        $count = 0;

        if ($this->available()) {
            $count = DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        // „Angemeldet bleiben“-Cookies anderer Geräte ungültig machen …
        $user->forceFill(['remember_token' => Str::random(60)])->save();

        // … und dieses Gerät mit neuem Token angemeldet lassen.
        if ($request->cookies->has(Auth::guard()->getRecallerName())) {
            Auth::guard()->login($user, true);
        }

        return $count;
    }

    /**
     * „Safari auf iPhone“, „Chrome auf Mac“, „FinanzView-App auf iPhone“ …
     */
    private function describe(string $agent): string
    {
        $platform = match (true) {
            str_contains($agent, 'iPhone') => 'iPhone',
            str_contains($agent, 'iPad') => 'iPad',
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'Macintosh') => 'Mac',
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Linux') => 'Linux',
            default => null,
        };

        $browser = match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'Firefox/') || str_contains($agent, 'FxiOS') => 'Firefox',
            str_contains($agent, 'Chrome/') || str_contains($agent, 'CriOS') => 'Chrome',
            str_contains($agent, 'Safari/') => 'Safari',
            default => null,
        };

        return match (true) {
            $browser && $platform => "{$browser} auf {$platform}",
            $platform !== null => $platform,
            $browser !== null => $browser,
            default => 'Unbekanntes Gerät',
        };
    }
}
