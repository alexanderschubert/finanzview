<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Minimaler OpenID-Connect-Client (Authorization Code Flow mit PKCE).
 *
 * Die Endpunkte werden über das Discovery-Dokument des Providers
 * ermittelt. Das ID-Token kommt direkt per TLS vom Token-Endpunkt,
 * daher wird statt der Signatur die TLS-Verbindung zur Prüfung des
 * Ausstellers verwendet (OIDC Core 1.0, Abschnitt 3.1.3.7).
 * Issuer, Audience, Ablaufzeit und Nonce werden trotzdem geprüft.
 */
class OidcService
{
    /**
     * Erlaubte Zeitabweichung in Sekunden.
     */
    private const LEEWAY = 60;

    /**
     * Ist OIDC aktiviert und vollständig konfiguriert?
     */
    public function enabled(): bool
    {
        return config('services.oidc.enabled') === true
            && filled(config('services.oidc.issuer'))
            && filled(config('services.oidc.client_id'));
    }

    /**
     * Beschriftung des Login-Buttons.
     */
    public function buttonLabel(): string
    {
        return (string) config('services.oidc.button_label', 'Mit SSO anmelden');
    }

    /**
     * Discovery-Dokument des Providers (1 Stunde gecacht).
     */
    public function discovery(): array
    {
        $issuer = rtrim((string) config('services.oidc.issuer'), '/');

        return Cache::remember(
            'oidc.discovery.' . md5($issuer),
            3600,
            function () use ($issuer) {
                $document = Http::acceptJson()
                    ->timeout(10)
                    ->get($issuer . '/.well-known/openid-configuration')
                    ->throw()
                    ->json();

                foreach (['issuer', 'authorization_endpoint', 'token_endpoint'] as $key) {
                    if (empty($document[$key]) || ! is_string($document[$key])) {
                        throw new RuntimeException(
                            "OIDC-Discovery enthält kein gültiges Feld '{$key}'."
                        );
                    }
                }

                return $document;
            }
        );
    }

    /**
     * URL, auf die der Benutzer zur Anmeldung weitergeleitet wird.
     */
    public function authorizationUrl(
        string $redirectUri,
        string $state,
        string $nonce,
        string $codeVerifier
    ): string {
        $endpoint = $this->discovery()['authorization_endpoint'];

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => config('services.oidc.client_id'),
            'redirect_uri' => $redirectUri,
            'scope' => config('services.oidc.scopes'),
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => $this->codeChallenge($codeVerifier),
            'code_challenge_method' => 'S256',
        ], '', '&', PHP_QUERY_RFC3986);

        $separator = str_contains($endpoint, '?') ? '&' : '?';

        return $endpoint . $separator . $query;
    }

    /**
     * Tauscht den Autorisierungscode gegen Tokens und liefert
     * die geprüften Claims des Benutzers.
     */
    public function claimsFromCode(
        string $code,
        string $codeVerifier,
        string $nonce,
        string $redirectUri
    ): array {
        $discovery = $this->discovery();

        $tokens = Http::asForm()
            ->acceptJson()
            ->timeout(10)
            ->post($discovery['token_endpoint'], [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $redirectUri,
                'client_id' => config('services.oidc.client_id'),
                'client_secret' => config('services.oidc.client_secret'),
                'code_verifier' => $codeVerifier,
            ])
            ->throw()
            ->json();

        if (empty($tokens['id_token']) || ! is_string($tokens['id_token'])) {
            throw new RuntimeException('Der Token-Endpunkt hat kein ID-Token geliefert.');
        }

        $claims = $this->validateIdToken(
            $tokens['id_token'],
            $discovery['issuer'],
            $nonce
        );

        /*
         * Zusätzliche Angaben (E-Mail, Name) aus dem UserInfo-Endpunkt,
         * falls das ID-Token sie nicht enthält.
         */
        if (
            ! empty($discovery['userinfo_endpoint'])
            && ! empty($tokens['access_token'])
        ) {
            $userInfo = Http::acceptJson()
                ->timeout(10)
                ->withToken($tokens['access_token'])
                ->get($discovery['userinfo_endpoint']);

            if ($userInfo->successful() && is_array($userInfo->json())) {
                $info = $userInfo->json();

                if (($info['sub'] ?? null) === $claims['sub']) {
                    $claims = array_merge($info, $claims);
                }
            }
        }

        return $claims;
    }

    /**
     * Prüft die Claims des ID-Tokens.
     */
    private function validateIdToken(string $idToken, string $issuer, string $nonce): array
    {
        $parts = explode('.', $idToken);

        if (count($parts) !== 3) {
            throw new RuntimeException('Ungültiges ID-Token.');
        }

        $claims = json_decode($this->base64UrlDecode($parts[1]), true);

        if (! is_array($claims)) {
            throw new RuntimeException('ID-Token enthält keine gültigen Claims.');
        }

        if (($claims['iss'] ?? null) !== $issuer) {
            throw new RuntimeException('ID-Token hat einen falschen Aussteller.');
        }

        $audience = (array) ($claims['aud'] ?? []);

        if (! in_array(config('services.oidc.client_id'), $audience, true)) {
            throw new RuntimeException('ID-Token ist nicht für diese Anwendung ausgestellt.');
        }

        if (! isset($claims['exp']) || (int) $claims['exp'] < time() - self::LEEWAY) {
            throw new RuntimeException('ID-Token ist abgelaufen.');
        }

        if (! is_string($claims['nonce'] ?? null) || ! hash_equals($nonce, $claims['nonce'])) {
            throw new RuntimeException('ID-Token hat eine ungültige Nonce.');
        }

        if (empty($claims['sub']) || ! is_string($claims['sub'])) {
            throw new RuntimeException('ID-Token enthält keine Benutzer-ID (sub).');
        }

        return $claims;
    }

    private function codeChallenge(string $codeVerifier): string
    {
        return rtrim(
            strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'),
            '='
        );
    }

    private function base64UrlDecode(string $value): string
    {
        $value = strtr($value, '-_', '+/');

        return (string) base64_decode(
            $value . str_repeat('=', (4 - strlen($value) % 4) % 4)
        );
    }
}
