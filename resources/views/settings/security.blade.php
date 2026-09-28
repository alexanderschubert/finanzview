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
        'passkey-deleted' => 'Der Passkey wurde entfernt.',
    ];

    $passkeys = $user->passkeys()->latest()->get();

    // Hinzufügen/Löschen verlangt ein kürzlich bestätigtes Passwort.
    $passwordConfirmed = time() - (int) session('auth.password_confirmed_at', 0) < (int) config('auth.password_timeout', 10800);
@endphp

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-back-link :href="route('settings.index')" label="Einstellungen" />

    <x-page-header title="Sicherheit" subtitle="Passwort und Zwei-Faktor-Authentifizierung." />

    <x-flash />

    @if (request('passkey') === 'added')
        <div class="flex gap-3 rounded-2xl bg-emerald-50 dark:bg-emerald-500/10 p-4 text-sm text-emerald-800 dark:text-emerald-300" role="status">
            <x-icon name="check-circle" class="w-5 h-5" />
            <p>Passkey wurde hinzugefügt. Du kannst dich ab jetzt damit anmelden.</p>
        </div>
    @endif

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
    {{-- PASSKEYS --}}
    {{-- ========================================================= --}}

    <section id="passkeys">
        <h3 class="px-1 pb-2 text-[13px] font-semibold text-slate-500 dark:text-slate-400">Passkeys</h3>

        <div class="fv-card overflow-hidden">
            <div class="flex items-center gap-4 p-5 sm:p-6">
                <span class="w-11 h-11 rounded-xl flex items-center justify-center {{ $passkeys->isNotEmpty() ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-500 dark:bg-white/5 dark:text-slate-400' }}">
                    <x-icon name="passkey" class="w-5 h-5" />
                </span>
                <div class="flex-1">
                    <p class="font-medium text-slate-900 dark:text-white">
                        {{ $passkeys->isEmpty() ? 'Kein Passkey' : ($passkeys->count() === 1 ? '1 Passkey' : $passkeys->count() . ' Passkeys') }}
                    </p>
                    <p class="text-[13px] text-slate-500 dark:text-slate-400">
                        Anmelden mit Face ID, Touch ID, Windows Hello oder Sicherheitsschlüssel – ohne Passwort.
                    </p>
                </div>
            </div>

            @if ($passkeys->isNotEmpty())
                <ul class="border-t border-slate-100 dark:border-white/5 divide-y divide-slate-100 dark:divide-white/5">
                    @foreach ($passkeys as $passkey)
                        <li class="flex items-center gap-3 px-5 sm:px-6 py-3">
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-slate-900 dark:text-white truncate">
                                    {{ $passkey->name }}
                                    @if ($passkey->authenticator && $passkey->authenticator !== $passkey->name)
                                        <span class="font-normal text-slate-500 dark:text-slate-400">· {{ $passkey->authenticator }}</span>
                                    @endif
                                </p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    Hinzugefügt {{ $passkey->created_at?->format('d.m.Y') }}
                                    · {{ $passkey->last_used_at ? 'zuletzt benutzt ' . $passkey->last_used_at->diffForHumans() : 'noch nicht benutzt' }}
                                </p>
                            </div>

                            @if ($passwordConfirmed)
                                <form method="POST" action="{{ route('passkey.destroy', $passkey) }}" onsubmit="return confirm('Passkey „{{ addslashes($passkey->name) }}“ entfernen?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-9 h-9 rounded-lg flex items-center justify-center text-slate-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10 transition" aria-label="Passkey {{ $passkey->name }} entfernen" title="Entfernen">
                                        <x-icon name="trash" class="w-4 h-4" />
                                    </button>
                                </form>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="border-t border-slate-100 dark:border-white/5 p-5 sm:p-6">
                @if ($passwordConfirmed)
                    <form
                        method="POST"
                        action="{{ route('passkey.store') }}"
                        data-passkey-register
                        data-options-url="{{ route('passkey.registration-options') }}"
                        data-confirm-url="{{ route('settings.security.passkeys') }}"
                        data-success-url="{{ route('settings.security', ['passkey' => 'added']) }}#passkeys"
                    >
                        <div data-passkey-fields class="flex flex-col sm:flex-row gap-2">
                            <label for="passkey_name" class="sr-only">Name des Passkeys</label>
                            <input id="passkey_name" name="name" type="text" maxlength="255" required placeholder="z. B. iPhone" class="fv-input sm:flex-1">
                            <button type="submit" class="fv-btn fv-btn-primary">
                                <x-icon name="plus" class="w-4 h-4" />
                                Passkey hinzufügen
                            </button>
                        </div>

                        <p data-passkey-unsupported hidden class="text-[13px] text-slate-500 dark:text-slate-400">
                            Passkeys funktionieren nur über HTTPS mit deiner Domain (nicht über die IP-Adresse) und in einem aktuellen Browser.
                        </p>

                        <p data-passkey-error hidden class="mt-2 text-[13px] text-red-600 dark:text-red-400" role="alert"></p>
                    </form>
                @else
                    <a href="{{ route('settings.security.passkeys') }}" class="fv-btn fv-btn-secondary">
                        <x-icon name="lock" class="w-4 h-4" />
                        {{ $passkeys->isEmpty() ? 'Passkey einrichten' : 'Passkeys verwalten' }}
                    </a>
                    <p class="mt-2 text-[13px] text-slate-500 dark:text-slate-400">Zur Sicherheit musst du dafür dein Passwort bestätigen.</p>
                @endif
            </div>
        </div>

        <p class="mt-2 px-1 text-[13px] text-slate-500 dark:text-slate-400">
            Ein Passkey ersetzt Passwort und Zwei-Faktor-Code: Er ist an dein Gerät gebunden und wird per Face ID, Fingerabdruck oder PIN freigegeben.
        </p>
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
