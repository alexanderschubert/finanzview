@extends('layouts.app')

@section('title', 'Profil – Finanzblick')

@section('eyebrow', 'Einstellungen')

@section('page_title', 'Profil')

@section('content')

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- HEADER --}}

    <div class="mb-8">

        <a
            href="{{ route('settings.index') }}"
            class="inline-flex items-center text-sm text-slate-500 hover:text-slate-900 transition"
        >
            ← Zurück zu Einstellungen
        </a>

        <div class="mt-5">

            <p class="text-sm text-slate-500">
                Persönliche Daten
            </p>

            <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900 mt-1">
                Dein Profil
            </h2>

            <p class="text-slate-500 mt-2">
                Verwalte deinen Namen und deine E-Mail-Adresse.
            </p>

        </div>

    </div>


    {{-- ERFOLGSMELDUNG --}}

    @if (session('success'))

        <div
            class="mb-5 rounded-2xl border border-emerald-100 bg-emerald-50 px-5 py-4"
        >

            <div class="flex items-center gap-3">

                <span class="text-lg">
                    ✓
                </span>

                <p class="text-sm font-medium text-emerald-700">
                    {{ session('success') }}
                </p>

            </div>

        </div>

    @endif


    {{-- FEHLER --}}

    @if ($errors->any())

        <div
            class="mb-5 rounded-2xl border border-red-100 bg-red-50 px-5 py-4"
        >

            <p class="text-sm font-semibold text-red-700">
                Bitte überprüfe deine Eingaben.
            </p>

            <ul class="mt-2 text-sm text-red-600 space-y-1">

                @foreach ($errors->all() as $error)

                    <li>
                        • {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif


    {{-- PROFIL --}}

    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">

        {{-- HEADER --}}

        <div class="p-6 sm:p-8 border-b border-slate-100">

            <div class="flex items-center gap-4">

                <div
                    class="w-14 h-14 rounded-2xl bg-slate-950 text-white flex items-center justify-center text-xl font-semibold"
                >
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>

                <div>

                    <h3 class="font-semibold text-slate-900">
                        Persönliche Informationen
                    </h3>

                    <p class="text-sm text-slate-500 mt-1">
                        Diese Informationen werden für dein Finanzblick-Konto verwendet.
                    </p>

                </div>

            </div>

        </div>


        {{-- FORMULAR --}}

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
                        class="block text-sm font-medium text-slate-700"
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
                            focus:ring-slate-100
                        "
                    >

                </div>


                {{-- E-MAIL --}}

                <div>

                    <label
                        for="email"
                        class="block text-sm font-medium text-slate-700"
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
                            focus:ring-slate-100
                        "
                    >

                </div>


                {{-- INFO --}}

                <div class="rounded-2xl bg-slate-50 px-5 py-4">

                    <div class="flex gap-3">

                        <span class="text-lg">
                            ℹ️
                        </span>

                        <div>

                            <p class="text-sm font-medium text-slate-700">
                                Hinweis
                            </p>

                            <p class="text-sm text-slate-500 mt-1">
                                Deine Änderungen werden sofort gespeichert und beim nächsten Seitenaufruf verwendet.
                            </p>

                        </div>

                    </div>

                </div>

            </div>


            {{-- BUTTONS --}}

            <div
                class="
                    flex
                    flex-col-reverse
                    sm:flex-row
                    sm:items-center
                    sm:justify-between
                    gap-3
                    px-6
                    sm:px-8
                    py-5
                    bg-slate-50
                    border-t
                    border-slate-100
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
                        bg-white
                        px-5
                        py-3
                        text-sm
                        font-medium
                        text-slate-700
                        hover:bg-slate-50
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
                        px-5
                        py-3
                        text-sm
                        font-medium
                        text-white
                        hover:bg-slate-800
                        transition
                    "
                >
                    Änderungen speichern
                </button>

            </div>

        </form>

    </div>


    {{-- KONTO --}}

    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm mt-5 overflow-hidden">

        <div class="p-6 sm:p-8">

            <p class="text-xs font-medium uppercase tracking-wider text-slate-400">
                Konto
            </p>

            <h3 class="text-lg font-semibold text-slate-900 mt-1">
                Kontoinformationen
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6">

                <div class="rounded-2xl bg-slate-50 p-4">

                    <p class="text-xs text-slate-400">
                        Benutzer-ID
                    </p>

                    <p class="text-sm font-medium text-slate-700 mt-1">
                        #{{ $user->id }}
                    </p>

                </div>

                <div class="rounded-2xl bg-slate-50 p-4">

                    <p class="text-xs text-slate-400">
                        Konto erstellt
                    </p>

                    <p class="text-sm font-medium text-slate-700 mt-1">
                        {{ $user->created_at?->format('d.m.Y') }}
                    </p>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection