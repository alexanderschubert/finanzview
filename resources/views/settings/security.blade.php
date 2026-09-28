@extends('layouts.app')

@section('title', 'Sicherheit – FinanzView')
@section('eyebrow', 'Einstellungen')
@section('page_title', 'Sicherheit')

@php
    $user = auth()->user();
    $twoFactorPending = $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null;
    $twoFactorEnabled = $user->hasEnabledTwoFactorAuthentication();
    $showRecoveryCodes = in_array(session('status'), ['two-factor-authentication-confirmed', 'recovery-codes-generated'], true);

    $twoFactorMessages = [
        'two-factor-authentication-confirmed' => 'Die Zwei-Faktor-Authentifizierung ist jetzt aktiv.',
        'two-factor-authentication-disabled' => 'Die Zwei-Faktor-Authentifizierung wurde deaktiviert.',
        'recovery-codes-generated' => 'Es wurden neue Wiederherstellungscodes erzeugt.',
    ];
@endphp

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-back-link :href="route('settings.index')" label="Einstellungen" />

    <x-page-header title="Sicherheit" subtitle="Passwort und Zwei-Faktor-Authentifizierung." />

    <x-flash />

    @if (isset($twoFactorMessages[session('status')]))
        <div class="flex gap-3 rounded-2xl bg-emerald-50 dark:bg-emerald-500/10 p-4 text-sm text-emerald-800 dark:text-emerald-300" role="status">
            <x-icon name="check-circle" class="w-5 h-5" />
            <p>{{ $twoFactorMessages[session('status')] }}</p>
        </div>
    @endif


    {{-- ========================================================= --}}
    {{-- ZWEI-FAKTOR-AUTHENTIFIZIERUNG --}}
    {{-- ========================================================= --}}

    <section id="two-factor">
        <h3 class="px-1 pb-2 text-[13px] font-semibold text-slate-500 dark:text-slate-400">Zwei-Faktor-Authentifizierung</h3>

        <div class="fv-card p-5 sm:p-6 space-y-5">

            <div class="flex items-center gap-4">
                <span class="w-11 h-11 rounded-xl flex items-center justify-center {{ $twoFactorEnabled ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-500 dark:bg-white/5 dark:text-slate-400' }}">
                    <x-icon name="shield" class="w-5 h-5" />
                </span>

                <div class="flex-1">
                    <p class="font-medium text-slate-900 dark:text-white">
                        {{ $twoFactorEnabled ? 'Aktiv' : ($twoFactorPending ? 'Einrichtung läuft' : 'Nicht aktiv') }}
                    </p>
                    <p class="text-[13px] text-slate-500 dark:text-slate-400">
                        Zusätzlicher Code aus einer Authenticator-App (z. B. Aegis, 2FAS, 1Password, Google Authenticator).
                    </p>
                </div>
            </div>


            @if ($twoFactorPending)

                {{-- EINRICHTUNG --}}

                <div class="grid gap-5 sm:grid-cols-[auto_1fr] sm:items-start border-t border-slate-100 dark:border-white/5 pt-5">
                    <div class="mx-auto sm:mx-0 rounded-2xl bg-white p-3 ring-1 ring-slate-200 [&_svg]:h-44 [&_svg]:w-44">
                        {!! $user->twoFactorQrCodeSvg() !!}
                    </div>

                    <div class="space-y-4">
                        <p class="text-sm text-slate-600 dark:text-slate-300">
                            <span class="font-semibold">1.</span> QR-Code mit der Authenticator-App scannen
                            oder den Schlüssel eingeben:
                        </p>
                        <p class="rounded-lg bg-slate-50 dark:bg-white/5 px-3 py-2 font-mono text-sm break-all text-slate-900 dark:text-white select-all">
                            {{ decrypt($user->two_factor_secret) }}
                        </p>

                        <form method="POST" action="{{ url('/user/confirmed-two-factor-authentication') }}" class="space-y-3">
                            @csrf

                            <label for="two_factor_code" class="block text-sm text-slate-600 dark:text-slate-300">
                                <span class="font-semibold">2.</span> Angezeigten 6-stelligen Code eingeben:
                            </label>

                            <div class="flex gap-2">
                                <input id="two_factor_code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" required autofocus
                                    placeholder="000000" class="fv-input w-36 text-center text-lg tracking-[0.3em] tabular-nums">
                                <button type="submit" class="fv-btn fv-btn-primary">Aktivieren</button>
                            </div>

                            @if ($errors->confirmTwoFactorAuthentication->has('code'))
                                <p class="text-[13px] text-red-600 dark:text-red-400">{{ $errors->confirmTwoFactorAuthentication->first('code') }}</p>
                            @endif
                        </form>
                    </div>
                </div>

                <form method="POST" action="{{ url('/user/two-factor-authentication') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="fv-link text-sm">Einrichtung abbrechen</button>
                </form>

            @elseif ($twoFactorEnabled)

                {{-- AKTIV --}}

                @if ($showRecoveryCodes)
                    <div class="rounded-2xl bg-amber-50 dark:bg-amber-500/10 p-4">
                        <p class="text-sm font-semibold text-amber-900 dark:text-amber-200">Wiederherstellungscodes jetzt sicher aufbewahren</p>
                        <p class="mt-1 text-[13px] text-amber-800 dark:text-amber-300">
                            Damit kommst du ohne Authenticator-App in dein Konto. Jeder Code funktioniert einmal und wird nicht erneut angezeigt.
                        </p>
                        <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2 font-mono text-sm text-slate-900 dark:text-white">
                            @foreach ($user->recoveryCodes() as $recoveryCode)
                                <span class="rounded-lg bg-white dark:bg-slate-900 px-3 py-2 select-all">{{ $recoveryCode }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="flex flex-wrap gap-2 border-t border-slate-100 dark:border-white/5 pt-5">
                    <form method="POST" action="{{ url('/user/two-factor-recovery-codes') }}">
                        @csrf
                        <button type="submit" class="fv-btn fv-btn-secondary text-sm">Neue Wiederherstellungscodes erzeugen</button>
                    </form>

                    <form method="POST" action="{{ url('/user/two-factor-authentication') }}" onsubmit="return confirm('Zwei-Faktor-Authentifizierung wirklich deaktivieren?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="fv-btn text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10">Deaktivieren</button>
                    </form>
                </div>

            @else

                {{-- INAKTIV --}}

                <form method="POST" action="{{ url('/user/two-factor-authentication') }}" class="border-t border-slate-100 dark:border-white/5 pt-5">
                    @csrf
                    <button type="submit" class="fv-btn fv-btn-primary">Zwei-Faktor-Authentifizierung einrichten</button>
                    <p class="mt-2 text-[13px] text-slate-500 dark:text-slate-400">Zur Sicherheit musst du dafür dein Passwort bestätigen.</p>
                </form>

            @endif

            @if ($user->oidc_sub !== null)
                <p class="text-[13px] text-slate-500 dark:text-slate-400">
                    Dein Konto ist mit Single Sign-On verknüpft. Bei der Anmeldung über SSO übernimmt dein Anmeldedienst die Zwei-Faktor-Abfrage.
                </p>
            @endif
        </div>
    </section>


    {{-- ========================================================= --}}
    {{-- PASSWORT --}}
    {{-- ========================================================= --}}

    <section>
        <h3 class="px-1 pb-2 text-[13px] font-semibold text-slate-500 dark:text-slate-400">Passwort ändern</h3>

        <form method="POST" action="{{ route('settings.security.password') }}" class="fv-card p-5 sm:p-6 space-y-4">
            @csrf
            @method('PUT')

            <x-field label="Aktuelles Passwort" for="current_password" error="current_password">
                <input id="current_password" name="current_password" type="password" autocomplete="current-password" required class="fv-input">
            </x-field>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-field label="Neues Passwort" for="password" error="password" hint="Mindestens 8 Zeichen.">
                    <input id="password" name="password" type="password" autocomplete="new-password" required class="fv-input">
                </x-field>

                <x-field label="Wiederholen" for="password_confirmation">
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="fv-input">
                </x-field>
            </div>

            @if ($user->oidc_sub !== null)
                <p class="text-[13px] text-slate-500 dark:text-slate-400">
                    Über SSO angelegt und noch kein eigenes Passwort? Melde dich ab und nutze auf der Anmeldeseite „Passwort vergessen“.
                </p>
            @endif

            <div class="flex justify-end">
                <button type="submit" class="fv-btn fv-btn-primary">Passwort ändern</button>
            </div>
        </form>
    </section>

</div>

@endsection
