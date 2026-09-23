<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenID Connect (Single Sign-On)
    |--------------------------------------------------------------------------
    |
    | Anmeldung über einen externen Identity Provider (z. B. Authentik).
    | OIDC_ISSUER ist die Issuer-URL des Providers, bei Authentik z. B.
    | https://auth.example.com/application/o/finanzview/
    |
    | OIDC_TRUST_EMAIL erlaubt die Verknüpfung bestehender Konten über die
    | E-Mail-Adresse auch dann, wenn der Provider email_verified nicht
    | auf true setzt. Nur aktivieren, wenn der Provider E-Mail-Adressen
    | selbst verwaltet und Benutzer sie nicht frei ändern können.
    |
    */

    'oidc' => [
        'enabled' => (bool) env('OIDC_ENABLED', false),
        'issuer' => env('OIDC_ISSUER'),
        'client_id' => env('OIDC_CLIENT_ID'),
        'client_secret' => env('OIDC_CLIENT_SECRET'),
        'scopes' => env('OIDC_SCOPES', 'openid profile email'),
        'button_label' => env('OIDC_BUTTON_LABEL', 'Mit SSO anmelden'),
        'trust_email' => (bool) env('OIDC_TRUST_EMAIL', false),
    ],

];
