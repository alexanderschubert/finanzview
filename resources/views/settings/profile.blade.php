@extends('layouts.app')

@section('title', 'Profil – FinanzView')

@section('eyebrow', 'Einstellungen')

@section('page_title', 'Profil')

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
            Dein Profil
        </h2>

        <p class="text-slate-500 dark:text-slate-400 mt-2">
            Verwalte deinen Namen und deine E-Mail-Adresse.
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
    {{-- PROFIL --}}
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
                        bg-blue-50
                        dark:bg-blue-500/10
                        flex
                        items-center
                        justify-center
                        text-xl
                        font-semibold
                        text-blue-600
                        dark:text-blue-400
                    "
                >
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>

                <div>

                    <h3 class="font-semibold text-slate-900 dark:text-white">
                        Persönliche Informationen
                    </h3>

                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Diese Informationen werden für dein FinanzView-Konto verwendet.
                    </p>

                </div>

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- FORMULAR --}}
        {{-- ===================================================== --}}

        <form
            method="POST"
            action="{{ route('settings.profile.update') }}"
        >

            @csrf
            @method('PUT')


            <div class="p-6 sm:p-8 space-y-6">

                {{-- NAME --}}

                <div>

                    <label
                        for="name"
                        class="
                            block
                            text-sm
                            font-medium
                            text-slate-700
                            dark:text-slate-300
                            mb-2
                        "
                    >
                        Name
                    </label>

                    <input
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name', $user->name) }}"
                        required
                        autocomplete="name"
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


                {{-- E-MAIL --}}

                <div>

                    <label
                        for="email"
                        class="
                            block
                            text-sm
                            font-medium
                            text-slate-700
                            dark:text-slate-300
                            mb-2
                        "
                    >
                        E-Mail-Adresse
                    </label>

                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email', $user->email) }}"
                        required
                        autocomplete="email"
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
                            ℹ️
                        </span>

                        <div>

                            <p class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                Hinweis
                            </p>

                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                                Deine Änderungen werden sofort gespeichert und beim nächsten Seitenaufruf verwendet.
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
                    Änderungen speichern
                </button>

            </div>

        </form>

    </section>


    {{-- ========================================================= --}}
    {{-- KONTOINFORMATIONEN --}}
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

            <div class="mb-6">

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
                    Kontoinformationen
                </h3>

                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Übersicht über dein FinanzView-Konto.
                </p>

            </div>


            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                {{-- BENUTZER-ID --}}

                <div
                    class="
                        rounded-2xl
                        bg-slate-50
                        dark:bg-slate-800/60
                        border
                        border-slate-100
                        dark:border-slate-700
                        p-4
                    "
                >

                    <p class="text-xs text-slate-400 dark:text-slate-500">
                        Benutzer-ID
                    </p>

                    <p class="text-sm font-medium text-slate-700 dark:text-slate-200 mt-1">
                        #{{ $user->id }}
                    </p>

                </div>


                {{-- KONTO ERSTELLT --}}

                <div
                    class="
                        rounded-2xl
                        bg-slate-50
                        dark:bg-slate-800/60
                        border
                        border-slate-100
                        dark:border-slate-700
                        p-4
                    "
                >

                    <p class="text-xs text-slate-400 dark:text-slate-500">
                        Konto erstellt
                    </p>

                    <p class="text-sm font-medium text-slate-700 dark:text-slate-200 mt-1">
                        {{ $user->created_at?->format('d.m.Y') }}
                    </p>

                </div>

            </div>

        </div>

    </section>

</div>

@endsection