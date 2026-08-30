@extends('layouts.app')

@section('title', 'Einstellungen – Finanzblick')

@section('eyebrow', 'System')

@section('page_title', 'Einstellungen')

@section('content')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <div class="mb-8">

        <p class="text-sm text-slate-500 dark:text-slate-400">
            System
        </p>

        <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900 dark:text-white mt-1">
            Einstellungen
        </h2>

        <p class="text-slate-500 dark:text-slate-400 mt-2">
            Verwalte dein Profil, deine Finanzen, Sicherheit und das Erscheinungsbild von Finanzblick.
        </p>

    </div>


    {{-- ========================================================= --}}
    {{-- EINSTELLUNGEN --}}
    {{-- ========================================================= --}}

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">


        {{-- ===================================================== --}}
        {{-- PROFIL --}}
        {{-- ===================================================== --}}

        <a
            href="{{ route('settings.profile') }}"
            class="
                group
                bg-white
                dark:bg-slate-900
                rounded-3xl
                border
                border-slate-200
                dark:border-slate-800
                p-6
                shadow-sm
                hover:shadow-md
                hover:-translate-y-0.5
                transition
            "
        >

            <div class="flex items-start justify-between gap-4">

                <div
                    class="
                        w-12
                        h-12
                        rounded-2xl
                        bg-blue-50
                        dark:bg-blue-500/10
                        flex
                        items-center
                        justify-center
                        text-2xl
                    "
                >
                    👤
                </div>

                <span class="text-slate-400 group-hover:translate-x-1 transition-transform">
                    →
                </span>

            </div>

            <h3 class="font-semibold text-slate-900 dark:text-white mt-5">
                Profil
            </h3>

            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">
                Verwalte deinen Namen und deine persönlichen Kontodaten.
            </p>

        </a>


        {{-- ===================================================== --}}
        {{-- SICHERHEIT --}}
        {{-- ===================================================== --}}

        <a
            href="{{ route('settings.security') }}"
            class="
                group
                bg-white
                dark:bg-slate-900
                rounded-3xl
                border
                border-slate-200
                dark:border-slate-800
                p-6
                shadow-sm
                hover:shadow-md
                hover:-translate-y-0.5
                transition
            "
        >

            <div class="flex items-start justify-between gap-4">

                <div
                    class="
                        w-12
                        h-12
                        rounded-2xl
                        bg-red-50
                        dark:bg-red-500/10
                        flex
                        items-center
                        justify-center
                        text-2xl
                    "
                >
                    🔐
                </div>

                <span class="text-slate-400 group-hover:translate-x-1 transition-transform">
                    →
                </span>

            </div>

            <h3 class="font-semibold text-slate-900 dark:text-white mt-5">
                Sicherheit
            </h3>

            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">
                Ändere dein Passwort und verwalte deine Sicherheitsoptionen.
            </p>

        </a>


        {{-- ===================================================== --}}
        {{-- ERSCHEINUNGSBILD --}}
        {{-- ===================================================== --}}

        <a
            href="{{ route('settings.appearance') }}"
            class="
                group
                bg-white
                dark:bg-slate-900
                rounded-3xl
                border
                border-slate-200
                dark:border-slate-800
                p-6
                shadow-sm
                hover:shadow-md
                hover:-translate-y-0.5
                transition
            "
        >

            <div class="flex items-start justify-between gap-4">

                <div
                    class="
                        w-12
                        h-12
                        rounded-2xl
                        bg-violet-50
                        dark:bg-violet-500/10
                        flex
                        items-center
                        justify-center
                        text-2xl
                    "
                >
                    🎨
                </div>

                <span class="text-slate-400 group-hover:translate-x-1 transition-transform">
                    →
                </span>

            </div>

            <h3 class="font-semibold text-slate-900 dark:text-white mt-5">
                Erscheinungsbild
            </h3>

            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">
                Passe Darstellung, Farbschema und Theme von Finanzblick an.
            </p>

        </a>


        {{-- ===================================================== --}}
        {{-- FINANZEN --}}
        {{-- ===================================================== --}}

        <a
            href="{{ route('settings.financial') }}"
            class="
                group
                bg-white
                dark:bg-slate-900
                rounded-3xl
                border
                border-slate-200
                dark:border-slate-800
                p-6
                shadow-sm
                hover:shadow-md
                hover:-translate-y-0.5
                transition
            "
        >

            <div class="flex items-start justify-between gap-4">

                <div
                    class="
                        w-12
                        h-12
                        rounded-2xl
                        bg-emerald-50
                        dark:bg-emerald-500/10
                        flex
                        items-center
                        justify-center
                        text-2xl
                    "
                >
                    💶
                </div>

                <span class="text-slate-400 group-hover:translate-x-1 transition-transform">
                    →
                </span>

            </div>

            <h3 class="font-semibold text-slate-900 dark:text-white mt-5">
                Finanzen
            </h3>

            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">
                Währung, Standardkonto und weitere finanzielle Grundeinstellungen.
            </p>

        </a>


        {{-- ===================================================== --}}
        {{-- DATEN --}}
        {{-- ===================================================== --}}

        <div
            class="
                bg-white
                dark:bg-slate-900
                rounded-3xl
                border
                border-slate-200
                dark:border-slate-800
                p-6
                shadow-sm
                opacity-60
            "
        >

            <div class="flex items-start justify-between gap-4">

                <div
                    class="
                        w-12
                        h-12
                        rounded-2xl
                        bg-slate-100
                        dark:bg-slate-800
                        flex
                        items-center
                        justify-center
                        text-2xl
                    "
                >
                    💾
                </div>

                <span
                    class="
                        rounded-full
                        bg-slate-100
                        dark:bg-slate-800
                        px-2.5
                        py-1
                        text-xs
                        font-medium
                        text-slate-500
                        dark:text-slate-400
                    "
                >
                    Bald
                </span>

            </div>

            <h3 class="font-semibold text-slate-900 dark:text-white mt-5">
                Daten & Export
            </h3>

            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">
                Deine Finanzdaten exportieren, importieren und verwalten.
            </p>

        </div>


        {{-- ===================================================== --}}
        {{-- BENACHRICHTIGUNGEN --}}
        {{-- ===================================================== --}}

        <div
            class="
                bg-white
                dark:bg-slate-900
                rounded-3xl
                border
                border-slate-200
                dark:border-slate-800
                p-6
                shadow-sm
                opacity-60
            "
        >

            <div class="flex items-start justify-between gap-4">

                <div
                    class="
                        w-12
                        h-12
                        rounded-2xl
                        bg-amber-50
                        dark:bg-amber-500/10
                        flex
                        items-center
                        justify-center
                        text-2xl
                    "
                >
                    🔔
                </div>

                <span
                    class="
                        rounded-full
                        bg-slate-100
                        dark:bg-slate-800
                        px-2.5
                        py-1
                        text-xs
                        font-medium
                        text-slate-500
                        dark:text-slate-400
                    "
                >
                    Bald
                </span>

            </div>

            <h3 class="font-semibold text-slate-900 dark:text-white mt-5">
                Benachrichtigungen
            </h3>

            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">
                Einstellungen für Hinweise und Benachrichtigungen.
            </p>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- KONTOINFORMATIONEN --}}
    {{-- ========================================================= --}}

    <div class="mt-8">

        <div
            class="
                bg-white
                dark:bg-slate-900
                rounded-3xl
                border
                border-slate-200
                dark:border-slate-800
                p-6
                sm:p-8
            "
        >

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5">


                {{-- KONTO --}}

                <div>

                    <p class="text-xs font-medium uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        Konto
                    </p>

                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mt-1">
                        {{ auth()->user()->name }}
                    </h3>

                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        {{ auth()->user()->email }}
                    </p>

                </div>


                {{-- STATUS + LOGOUT --}}

                <div class="flex items-center gap-3">

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
                        Konto aktiv
                    </span>


                    <form
                        method="POST"
                        action="{{ route('logout') }}"
                    >

                        @csrf

                        <button
                            type="submit"
                            class="
                                inline-flex
                                items-center
                                justify-center
                                rounded-xl
                                border
                                border-red-200
                                dark:border-red-900
                                px-4
                                py-2.5
                                text-sm
                                font-medium
                                text-red-600
                                dark:text-red-400
                                hover:bg-red-50
                                dark:hover:bg-red-950/30
                                transition
                            "
                        >
                            Abmelden
                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection