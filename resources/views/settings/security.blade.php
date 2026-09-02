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
                Dein FinanzView-Konto ist durch dein Passwort geschützt.
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