@extends('layouts.app')

@section('title', 'Sicherheit – Finanzblick')

@section('eyebrow', 'Einstellungen')

@section('page_title', 'Sicherheit')

@section('content')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- HEADER --}}

    <div class="mb-8">

        <a
            href="{{ route('settings.index') }}"
            class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-900 transition"
        >
            ← Einstellungen
        </a>

        <p class="text-sm text-slate-500 mt-6">
            Schütze dein Finanzblick-Konto.
        </p>

        <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900 mt-1">
            Sicherheit
        </h2>

        <p class="text-slate-500 mt-2">
            Verwalte dein Passwort und die Sicherheit deines Kontos.
        </p>

    </div>


    {{-- ERFOLGSMELDUNG --}}

    @if (session('success'))

        <div
            class="
                mb-5
                rounded-2xl
                border
                border-emerald-100
                bg-emerald-50
                px-5
                py-4
                text-sm
                text-emerald-700
            "
        >
            <div class="flex items-center gap-3">

                <span class="text-lg">
                    ✓
                </span>

                <span>
                    {{ session('success') }}
                </span>

            </div>
        </div>

    @endif


    {{-- FEHLER --}}

    @if ($errors->any())

        <div
            class="
                mb-5
                rounded-2xl
                border
                border-red-100
                bg-red-50
                px-5
                py-4
                text-sm
                text-red-700
            "
        >

            <p class="font-medium">
                Bitte überprüfe deine Eingaben.
            </p>

            <ul class="mt-2 space-y-1">

                @foreach ($errors->all() as $error)

                    <li>
                        • {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif


    {{-- PASSWORT --}}

    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">

        <div class="p-6 sm:p-8">

            <div class="flex items-start gap-4">

                <div
                    class="
                        w-12
                        h-12
                        rounded-2xl
                        bg-slate-950
                        text-white
                        flex
                        items-center
                        justify-center
                        text-xl
                        flex-shrink-0
                    "
                >
                    🔐
                </div>

                <div>

                    <h3 class="text-lg font-semibold text-slate-900">
                        Passwort ändern
                    </h3>

                    <p class="text-sm text-slate-500 mt-1">
                        Ändere hier das Passwort für dein Finanzblick-Konto.
                    </p>

                </div>

            </div>


            <form
                method="POST"
                action="{{ route('settings.security.password') }}"
                class="mt-8 max-w-2xl"
            >

                @csrf

                @method('PUT')


                {{-- AKTUELLES PASSWORT --}}

                <div>

                    <label
                        for="current_password"
                        class="block text-sm font-medium text-slate-700"
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
                            mt-2
                            block
                            w-full
                            rounded-xl
                            border
                            border-slate-200
                            bg-white
                            px-4
                            py-3
                            text-sm
                            text-slate-900
                            outline-none
                            transition
                            focus:border-slate-400
                            focus:ring-2
                            focus:ring-slate-200
                        "
                    >

                    @error('current_password')

                        <p class="text-sm text-red-600 mt-2">
                            {{ $message }}
                        </p>

                    @enderror

                </div>


                {{-- NEUES PASSWORT --}}

                <div class="mt-5">

                    <label
                        for="password"
                        class="block text-sm font-medium text-slate-700"
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
                            mt-2
                            block
                            w-full
                            rounded-xl
                            border
                            border-slate-200
                            bg-white
                            px-4
                            py-3
                            text-sm
                            text-slate-900
                            outline-none
                            transition
                            focus:border-slate-400
                            focus:ring-2
                            focus:ring-slate-200
                        "
                    >

                    <p class="text-xs text-slate-400 mt-2">
                        Das Passwort muss mindestens 8 Zeichen lang sein.
                    </p>

                    @error('password')

                        <p class="text-sm text-red-600 mt-2">
                            {{ $message }}
                        </p>

                    @enderror

                </div>


                {{-- PASSWORT BESTÄTIGEN --}}

                <div class="mt-5">

                    <label
                        for="password_confirmation"
                        class="block text-sm font-medium text-slate-700"
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
                            mt-2
                            block
                            w-full
                            rounded-xl
                            border
                            border-slate-200
                            bg-white
                            px-4
                            py-3
                            text-sm
                            text-slate-900
                            outline-none
                            transition
                            focus:border-slate-400
                            focus:ring-2
                            focus:ring-slate-200
                        "
                    >

                </div>


                {{-- BUTTON --}}

                <div class="flex items-center gap-3 mt-8">

                    <button
                        type="submit"
                        class="
                            inline-flex
                            items-center
                            justify-center
                            rounded-xl
                            bg-slate-950
                            px-5
                            py-3
                            text-sm
                            font-medium
                            text-white
                            hover:bg-slate-800
                            transition
                        "
                    >
                        Passwort ändern
                    </button>

                    <a
                        href="{{ route('settings.index') }}"
                        class="
                            inline-flex
                            items-center
                            justify-center
                            rounded-xl
                            border
                            border-slate-200
                            bg-white
                            px-5
                            py-3
                            text-sm
                            font-medium
                            text-slate-600
                            hover:bg-slate-50
                            transition
                        "
                    >
                        Abbrechen
                    </a>

                </div>

            </form>

        </div>

    </div>


    {{-- SICHERHEITSHINWEIS --}}

    <div
        class="
            mt-5
            rounded-3xl
            border
            border-slate-100
            bg-slate-50
            p-6
        "
    >

        <div class="flex items-start gap-4">

            <div class="text-xl">
                🛡️
            </div>

            <div>

                <p class="text-sm font-medium text-slate-700">
                    Sicherheitshinweis
                </p>

                <p class="text-sm text-slate-500 mt-1">
                    Verwende ein starkes Passwort, das du nicht für andere
                    Dienste verwendest.
                </p>

            </div>

        </div>

    </div>

</div>

@endsection