@extends('layouts.app')

@section('title', 'Sicherheit – FinanzView')

@section('eyebrow', 'Einstellungen')

@section('page_title', 'Sicherheit')

@section('content')

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <div class="mb-8">

        <a
            href="{{ route('settings.index') }}"
            class="
                inline-flex
                items-center
                text-sm
                text-slate-500
                dark:text-slate-400
                hover:text-slate-900
                dark:hover:text-white
                transition
            "
        >
            ← Einstellungen
        </a>

        <h2
            class="
                text-3xl
                sm:text-4xl
                font-semibold
                tracking-tight
                text-slate-900
                dark:text-white
                mt-5
            "
        >
            Sicherheit
        </h2>

        <p class="text-slate-500 dark:text-slate-400 mt-2">
            Verwalte dein Passwort und die Sicherheit deines Kontos.
        </p>

    </div>


    {{-- ========================================================= --}}
    {{-- ERFOLG --}}
    {{-- ========================================================= --}}

    @if(session('success'))

        <div
            class="
                mb-6
                rounded-2xl
                border
                border-emerald-200
                dark:border-emerald-900
                bg-emerald-50
                dark:bg-emerald-950/30
                px-5
                py-4
            "
        >

            <div class="flex items-center gap-3">

                <span class="text-lg text-emerald-600 dark:text-emerald-400">
                    ✓
                </span>

                <p class="text-sm font-medium text-emerald-700 dark:text-emerald-400">
                    {{ session('success') }}
                </p>

            </div>

        </div>

    @endif


    {{-- ========================================================= --}}
    {{-- FEHLER --}}
    {{-- ========================================================= --}}

    @if($errors->any())

        <div
            class="
                mb-6
                rounded-2xl
                border
                border-red-200
                dark:border-red-900
                bg-red-50
                dark:bg-red-950/30
                px-5
                py-4
            "
        >

            <p class="text-sm font-semibold text-red-700 dark:text-red-400">
                Bitte überprüfe deine Eingaben.
            </p>

            <ul class="mt-2 text-sm text-red-600 dark:text-red-400 space-y-1">

                @foreach($errors->all() as $error)

                    <li>
                        • {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif


    {{-- ========================================================= --}}
    {{-- PASSWORT --}}
    {{-- ========================================================= --}}

    <section
        class="
            rounded-3xl
            bg-white
            dark:bg-slate-900
            border
            border-slate-200
            dark:border-slate-800
            overflow-hidden
        "
    >

        {{-- ===================================================== --}}
        {{-- KOPFBEREICH --}}
        {{-- ===================================================== --}}

        <div
            class="
                p-6
                sm:p-8
                border-b
                border-slate-200
                dark:border-slate-800
            "
        >

            <div class="flex items-center gap-4">

                <div
                    class="
                        w-14
                        h-14
                        shrink-0
                        rounded-2xl
                        bg-red-50
                        dark:bg-red-500/10
                        flex
                        items-center
                        justify-center
                        text-xl
                    "
                >
                    🔐
                </div>

                <div>

                    <h3 class="font-semibold text-slate-900 dark:text-white">
                        Passwort ändern
                    </h3>

                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Ändere hier das Passwort für dein FinanzView-Konto.
                    </p>

                </div>

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- FORMULAR --}}
        {{-- ===================================================== --}}

        <form
            method="POST"
            action="{{ route('settings.security.password') }}"
        >

            @csrf
            @method('PUT')


            <div class="p-6 sm:p-8 space-y-6">

                {{-- ================================================= --}}
                {{-- AKTUELLES PASSWORT --}}
                {{-- ================================================= --}}

                <div>

                    <label
                        for="current_password"
                        class="
                            block
                            text-sm
                            font-medium
                            text-slate-700
                            dark:text-slate-300
                            mb-2
                        "
                    >
                        Aktuelles Passwort
                    </label>

                    <input
                        id="current_password"
                        name="current_password"
                        type="password"
                        autocomplete="current-password"
                        required
                        class="
                            w-full
                            rounded-xl
                            border
                            border-slate-200
                            dark:border-slate-700
                            bg-white
                            dark:bg-slate-800
                            px-4
                            py-3
                            text-sm
                            text-slate-900
                            dark:text-white
                            placeholder:text-slate-400
                            outline-none
                            transition
                            focus:border-emerald-500
                            focus:ring-2
                            focus:ring-emerald-500/20
                        "
                    >

                    @error('current_password')

                        <p class="text-sm text-red-600 dark:text-red-400 mt-2">
                            {{ $message }}
                        </p>

                    @enderror

                </div>


                {{-- ================================================= --}}
                {{-- NEUES PASSWORT --}}
                {{-- ================================================= --}}

                <div>

                    <label
                        for="password"
                        class="
                            block
                            text-sm
                            font-medium
                            text-slate-700
                            dark:text-slate-300
                            mb-2
                        "
                    >
                        Neues Passwort
                    </label>

                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="new-password"
                        required
                        class="
                            w-full
                            rounded-xl
                            border
                            border-slate-200
                            dark:border-slate-700
                            bg-white
                            dark:bg-slate-800
                            px-4
                            py-3
                            text-sm
                            text-slate-900
                            dark:text-white
                            placeholder:text-slate-400
                            outline-none
                            transition
                            focus:border-emerald-500
                            focus:ring-2
                            focus:ring-emerald-500/20
                        "
                    >

                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">
                        Das Passwort muss mindestens 8 Zeichen lang sein.
                    </p>

                    @error('password')

                        <p class="text-sm text-red-600 dark:text-red-400 mt-2">
                            {{ $message }}
                        </p>

                    @enderror

                </div>


                {{-- ================================================= --}}
                {{-- PASSWORT BESTÄTIGEN --}}
                {{-- ================================================= --}}

                <div>

                    <label
                        for="password_confirmation"
                        class="
                            block
                            text-sm
                            font-medium
                            text-slate-700
                            dark:text-slate-300
                            mb-2
                        "
                    >
                        Neues Passwort bestätigen
                    </label>

                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        required
                        class="
                            w-full
                            rounded-xl
                            border
                            border-slate-200
                            dark:border-slate-700
                            bg-white
                            dark:bg-slate-800
                            px-4
                            py-3
                            text-sm
                            text-slate-900
                            dark:text-white
                            placeholder:text-slate-400
                            outline-none
                            transition
                            focus:border-emerald-500
                            focus:ring-2
                            focus:ring-emerald-500/20
                        "
                    >

                </div>


                {{-- ================================================= --}}
                {{-- INFO --}}
                {{-- ================================================= --}}

                <div
                    class="
                        rounded-2xl
                        bg-slate-50
                        dark:bg-slate-800/60
                        border
                        border-slate-100
                        dark:border-slate-700
                        px-5
                        py-4
                    "
                >

                    <div class="flex gap-3">

                        <span class="text-lg">
                            🛡️
                        </span>

                        <div>

                            <p class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                Sicherheitshinweis
                            </p>

                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                                Verwende ein starkes Passwort, das du nicht für andere Dienste verwendest.
                            </p>

                        </div>

                    </div>

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- BUTTONS --}}
            {{-- ===================================================== --}}

            <div
                class="
                    flex
                    flex-col-reverse
                    sm:flex-row
                    sm:items-center
                    sm:justify-end
                    gap-3
                    px-6
                    sm:px-8
                    py-5
                    border-t
                    border-slate-200
                    dark:border-slate-800
                "
            >

                <a
                    href="{{ route('settings.index') }}"
                    class="
                        inline-flex
                        items-center
                        justify-center
                        rounded-xl
                        border
                        border-slate-200
                        dark:border-slate-700
                        bg-white
                        dark:bg-slate-800
                        px-5
                        py-3
                        text-sm
                        font-medium
                        text-slate-700
                        dark:text-slate-200
                        hover:bg-slate-100
                        dark:hover:bg-slate-700
                        transition
                    "
                >
                    Abbrechen
                </a>


                <button
                    type="submit"
                    class="
                        inline-flex
                        items-center
                        justify-center
                        rounded-xl
                        bg-slate-950
                        dark:bg-white
                        px-5
                        py-3
                        text-sm
                        font-medium
                        text-white
                        dark:text-slate-950
                        hover:bg-slate-800
                        dark:hover:bg-slate-200
                        transition
                    "
                >
                    Passwort ändern
                </button>

            </div>

        </form>

    </section>


    {{-- ========================================================= --}}
    {{-- ZWEI-FAKTOR-AUTHENTIFIZIERUNG --}}
    {{-- ========================================================= --}}

    @php
        $user = auth()->user();
        $twoFactorPending = $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null;
        $showRecoveryCodes = in_array(session('status'), ['two-factor-authentication-confirmed', 'recovery-codes-generated'], true);

        $twoFactorMessages = [
            'two-factor-authentication-confirmed' => 'Die Zwei-Faktor-Authentifizierung ist jetzt aktiv.',
            'two-factor-authentication-disabled' => 'Die Zwei-Faktor-Authentifizierung wurde deaktiviert.',
            'recovery-codes-generated' => 'Es wurden neue Wiederherstellungscodes erzeugt.',
        ];
    @endphp

    <section
        id="two-factor"
        class="
            mt-6
            rounded-3xl
            bg-white
            dark:bg-slate-900
            border
            border-slate-200
            dark:border-slate-800
            overflow-hidden
        "
    >

        <div
            class="
                p-6
                sm:p-8
                border-b
                border-slate-200
                dark:border-slate-800
            "
        >

            <div class="flex items-center gap-4">

                <div
                    class="
                        w-14
                        h-14
                        shrink-0
                        rounded-2xl
                        bg-emerald-50
                        dark:bg-emerald-500/10
                        flex
                        items-center
                        justify-center
                        text-xl
                    "
                >
                    📱
                </div>

                <div class="flex-1">

                    <h3 class="font-semibold text-slate-900 dark:text-white">
                        Zwei-Faktor-Authentifizierung
                    </h3>

                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Schütze dein Konto zusätzlich mit einem Code aus einer Authenticator-App
                        (z. B. Aegis, 2FAS, Google Authenticator oder 1Password).
                    </p>

                </div>

                @if($user->hasEnabledTwoFactorAuthentication())
                    <span class="shrink-0 rounded-full bg-emerald-50 dark:bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                        Aktiv
                    </span>
                @else
                    <span class="shrink-0 rounded-full bg-slate-100 dark:bg-slate-800 px-3 py-1 text-xs font-medium text-slate-500 dark:text-slate-400">
                        Inaktiv
                    </span>
                @endif

            </div>

        </div>


        <div class="p-6 sm:p-8 space-y-6">

            @if(isset($twoFactorMessages[session('status')]))
                <div class="rounded-2xl border border-emerald-200 dark:border-emerald-900 bg-emerald-50 dark:bg-emerald-950/30 px-5 py-4">
                    <p class="text-sm font-medium text-emerald-700 dark:text-emerald-400">
                        ✓ {{ $twoFactorMessages[session('status')] }}
                    </p>
                </div>
            @endif


            @if($twoFactorPending)

                {{-- ================================================= --}}
                {{-- EINRICHTUNG: QR-CODE SCANNEN UND BESTÄTIGEN --}}
                {{-- ================================================= --}}

                <div class="grid gap-6 sm:grid-cols-[auto_1fr] sm:items-start">

                    <div class="mx-auto sm:mx-0 rounded-2xl bg-white p-4 border border-slate-200 [&_svg]:h-48 [&_svg]:w-48">
                        {!! $user->twoFactorQrCodeSvg() !!}
                    </div>

                    <div class="space-y-4">

                        <p class="text-sm text-slate-600 dark:text-slate-300">
                            1. Scanne den QR-Code mit deiner Authenticator-App.
                        </p>

                        <div>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Oder gib den Schlüssel manuell ein:
                            </p>
                            <p class="mt-1 font-mono text-sm break-all text-slate-900 dark:text-white">
                                {{ decrypt($user->two_factor_secret) }}
                            </p>
                        </div>

                        <form method="POST" action="{{ url('/user/confirmed-two-factor-authentication') }}" class="space-y-3">
                            @csrf

                            <label for="two_factor_code" class="block text-sm text-slate-600 dark:text-slate-300">
                                2. Gib den angezeigten 6-stelligen Code ein:
                            </label>

                            <div class="flex flex-col sm:flex-row gap-3">
                                <input
                                    id="two_factor_code"
                                    name="code"
                                    type="text"
                                    inputmode="numeric"
                                    autocomplete="one-time-code"
                                    required
                                    autofocus
                                    placeholder="123456"
                                    class="
                                        w-full
                                        sm:w-40
                                        rounded-xl
                                        border
                                        border-slate-200
                                        dark:border-slate-700
                                        bg-white
                                        dark:bg-slate-800
                                        px-4
                                        py-3
                                        text-sm
                                        tracking-widest
                                        text-slate-900
                                        dark:text-white
                                        outline-none
                                        transition
                                        focus:border-emerald-500
                                        focus:ring-2
                                        focus:ring-emerald-500/20
                                    "
                                >

                                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-slate-950 dark:bg-white px-5 py-3 text-sm font-medium text-white dark:text-slate-950 hover:bg-slate-800 dark:hover:bg-slate-200 transition">
                                    Aktivieren
                                </button>
                            </div>

                            @if($errors->confirmTwoFactorAuthentication->has('code'))
                                <p class="text-sm text-red-600 dark:text-red-400">
                                    {{ $errors->confirmTwoFactorAuthentication->first('code') }}
                                </p>
                            @endif
                        </form>

                    </div>

                </div>

                <form method="POST" action="{{ url('/user/two-factor-authentication') }}">
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition">
                        Einrichtung abbrechen
                    </button>
                </form>

            @elseif($user->hasEnabledTwoFactorAuthentication())

                {{-- ================================================= --}}
                {{-- AKTIV --}}
                {{-- ================================================= --}}

                @if($showRecoveryCodes)
                    <div class="rounded-2xl border border-amber-200 dark:border-amber-900 bg-amber-50 dark:bg-amber-950/30 px-5 py-4">
                        <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">
                            Wiederherstellungscodes – jetzt sicher aufbewahren!
                        </p>
                        <p class="text-sm text-amber-700 dark:text-amber-400 mt-1">
                            Falls du keinen Zugriff auf deine Authenticator-App hast, kannst du dich mit einem
                            dieser Codes anmelden. Jeder Code funktioniert nur einmal. Sie werden nicht erneut angezeigt.
                        </p>
                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-2 font-mono text-sm text-slate-900 dark:text-white">
                            @foreach($user->recoveryCodes() as $recoveryCode)
                                <span class="rounded-lg bg-white dark:bg-slate-900 px-3 py-2">{{ $recoveryCode }}</span>
                            @endforeach
                        </div>
                    </div>
                @else
                    <p class="text-sm text-slate-600 dark:text-slate-300">
                        Bei der Anmeldung wird nach dem Passwort zusätzlich ein Code aus deiner Authenticator-App abgefragt.
                    </p>
                @endif

                <div class="flex flex-col sm:flex-row gap-3">

                    <form method="POST" action="{{ url('/user/two-factor-recovery-codes') }}">
                        @csrf

                        <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-5 py-3 text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                            Neue Wiederherstellungscodes erzeugen
                        </button>
                    </form>

                    <form method="POST" action="{{ url('/user/two-factor-authentication') }}" onsubmit="return confirm('Zwei-Faktor-Authentifizierung wirklich deaktivieren?')">
                        @csrf
                        @method('DELETE')

                        <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center rounded-xl border border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-950/30 px-5 py-3 text-sm font-medium text-red-700 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-950/60 transition">
                            Deaktivieren
                        </button>
                    </form>

                </div>

            @else

                {{-- ================================================= --}}
                {{-- INAKTIV --}}
                {{-- ================================================= --}}

                <p class="text-sm text-slate-600 dark:text-slate-300">
                    Nach dem Aktivieren wird ein QR-Code angezeigt, den du mit deiner Authenticator-App scannst.
                    Zur Sicherheit musst du dafür dein Passwort bestätigen.
                </p>

                <form method="POST" action="{{ url('/user/two-factor-authentication') }}">
                    @csrf

                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-slate-950 dark:bg-white px-5 py-3 text-sm font-medium text-white dark:text-slate-950 hover:bg-slate-800 dark:hover:bg-slate-200 transition">
                        Zwei-Faktor-Authentifizierung einrichten
                    </button>
                </form>

            @endif

            @if($user->oidc_sub !== null)
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Hinweis: Dein Konto ist mit Single Sign-On verknüpft. Bei der Anmeldung über SSO
                    übernimmt dein Anmeldedienst die Zwei-Faktor-Abfrage.
                </p>
            @endif

        </div>

    </section>


    {{-- ========================================================= --}}
    {{-- KONTOSTATUS --}}
    {{-- ========================================================= --}}

    <section
        class="
            mt-6
            rounded-3xl
            bg-white
            dark:bg-slate-900
            border
            border-slate-200
            dark:border-slate-800
            overflow-hidden
        "
    >

        <div class="p-6 sm:p-8">

            <p
                class="
                    text-xs
                    font-medium
                    uppercase
                    tracking-wider
                    text-slate-400
                    dark:text-slate-500
                "
            >
                Konto
            </p>

            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mt-1">
                Sicherheitsstatus
            </h3>

            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                @if($user->hasEnabledTwoFactorAuthentication())
                    Dein FinanzView-Konto ist durch dein Passwort und einen zweiten Faktor geschützt.
                @else
                    Dein FinanzView-Konto ist durch dein Passwort geschützt.
                    <a href="#two-factor" class="font-medium text-emerald-600 dark:text-emerald-400 hover:underline">Zwei-Faktor-Authentifizierung einrichten</a>
                @endif
            </p>


            <div class="mt-6">

                <div
                    class="
                        flex
                        items-center
                        justify-between
                        gap-4
                        rounded-2xl
                        bg-slate-50
                        dark:bg-slate-800/60
                        border
                        border-slate-100
                        dark:border-slate-700
                        px-5
                        py-4
                    "
                >

                    <div class="flex items-center gap-3">

                        <div
                            class="
                                w-10
                                h-10
                                rounded-xl
                                bg-emerald-50
                                dark:bg-emerald-500/10
                                flex
                                items-center
                                justify-center
                                text-emerald-600
                                dark:text-emerald-400
                            "
                        >
                            ✓
                        </div>

                        <div>

                            <p class="text-sm font-medium text-slate-700 dark:text-slate-200">
                                Passwortschutz aktiv
                            </p>

                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                Dein Konto ist geschützt.
                            </p>

                        </div>

                    </div>

                    <span
                        class="
                            rounded-full
                            bg-emerald-50
                            dark:bg-emerald-500/10
                            px-3
                            py-1
                            text-xs
                            font-medium
                            text-emerald-600
                            dark:text-emerald-400
                        "
                    >
                        Aktiv
                    </span>

                </div>

            </div>

        </div>

    </section>

</div>

@endsection