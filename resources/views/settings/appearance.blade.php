@extends('layouts.app')

@section('title', 'Darstellung – FinanzView')

@section('eyebrow', 'Einstellungen')

@section('page_title', 'Darstellung')

@section('content')

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- HEADER --}}

    <div class="mb-8">

        <a
            href="{{ route('settings.index') }}"
            class="inline-flex items-center text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition"
        >
            ← Zurück zu Einstellungen
        </a>

        <p class="text-sm text-slate-500 dark:text-slate-400 mt-6">
            Darstellung
        </p>

        <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900 dark:text-white mt-1">
            Erscheinungsbild
        </h2>

        <p class="text-slate-500 dark:text-slate-400 mt-2">
            Wähle aus, wie FinanzView dargestellt werden soll.
        </p>

    </div>


    {{-- ERFOLGSMELDUNG --}}

    @if (session('success'))

        <div class="mb-5 rounded-2xl border border-emerald-200 dark:border-emerald-900 bg-emerald-50 dark:bg-emerald-950/40 px-5 py-4">

            <div class="flex items-center gap-3">

                <span class="text-lg text-emerald-600 dark:text-emerald-400">
                    ✓
                </span>

                <p class="text-sm font-medium text-emerald-700 dark:text-emerald-300">
                    {{ session('success') }}
                </p>

            </div>

        </div>

    @endif


    {{-- FEHLER --}}

    @if ($errors->any())

        <div class="mb-5 rounded-2xl border border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-950/40 px-5 py-4">

            <p class="text-sm font-semibold text-red-700 dark:text-red-300">
                Bitte überprüfe deine Eingaben.
            </p>

            <ul class="mt-2 space-y-1">

                @foreach ($errors->all() as $error)

                    <li class="text-sm text-red-600 dark:text-red-400">
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif


    {{-- DARSTELLUNG --}}

    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden">

        <div class="p-6 sm:p-8 border-b border-slate-100 dark:border-slate-800">

            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                Farbschema
            </h3>

            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Lege fest, welches Erscheinungsbild FinanzView verwenden soll.
            </p>

        </div>


        <form
            method="POST"
            action="{{ route('settings.appearance.update') }}"
            class="p-6 sm:p-8"
        >

            @csrf
            @method('PUT')


            <div class="space-y-4">

                {{-- SYSTEM --}}

                <label class="block cursor-pointer">

                    <input
                        type="radio"
                        name="theme"
                        value="system"
                        class="peer sr-only"
                        {{ ($user->theme ?? 'system') === 'system' ? 'checked' : '' }}
                    >

                    <div
                        class="
                            flex items-center gap-4
                            rounded-2xl
                            border border-slate-200 dark:border-slate-700
                            p-5
                            transition
                            hover:border-slate-300 dark:hover:border-slate-600
                            peer-checked:border-slate-950 dark:peer-checked:border-white
                            peer-checked:bg-slate-50 dark:peer-checked:bg-slate-800
                        "
                    >

                        <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-2xl flex-shrink-0">
                            🖥️
                        </div>

                        <div class="flex-1">

                            <p class="font-semibold text-slate-900 dark:text-white">
                                System
                            </p>

                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                                FinanzView verwendet automatisch die Einstellung deines Geräts.
                            </p>

                        </div>

                        <div class="w-5 h-5 rounded-full border-2 border-slate-300 dark:border-slate-600 flex items-center justify-center">

                            <div class="w-2.5 h-2.5 rounded-full bg-slate-950 dark:bg-white"></div>

                        </div>

                    </div>

                </label>


                {{-- HELL --}}

                <label class="block cursor-pointer">

                    <input
                        type="radio"
                        name="theme"
                        value="light"
                        class="peer sr-only"
                        {{ ($user->theme ?? 'system') === 'light' ? 'checked' : '' }}
                    >

                    <div
                        class="
                            flex items-center gap-4
                            rounded-2xl
                            border border-slate-200 dark:border-slate-700
                            p-5
                            transition
                            hover:border-slate-300 dark:hover:border-slate-600
                            peer-checked:border-slate-950 dark:peer-checked:border-white
                            peer-checked:bg-slate-50 dark:peer-checked:bg-slate-800
                        "
                    >

                        <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/40 flex items-center justify-center text-2xl flex-shrink-0">
                            ☀️
                        </div>

                        <div class="flex-1">

                            <p class="font-semibold text-slate-900 dark:text-white">
                                Hell
                            </p>

                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                                Verwende das helle Erscheinungsbild von FinanzView.
                            </p>

                        </div>

                        <div class="w-5 h-5 rounded-full border-2 border-slate-300 dark:border-slate-600 flex items-center justify-center">

                            <div class="w-2.5 h-2.5 rounded-full bg-slate-950 dark:bg-white"></div>

                        </div>

                    </div>

                </label>


                {{-- DUNKEL --}}

                <label class="block cursor-pointer">

                    <input
                        type="radio"
                        name="theme"
                        value="dark"
                        class="peer sr-only"
                        {{ ($user->theme ?? 'system') === 'dark' ? 'checked' : '' }}
                    >

                    <div
                        class="
                            flex items-center gap-4
                            rounded-2xl
                            border border-slate-200 dark:border-slate-700
                            p-5
                            transition
                            hover:border-slate-300 dark:hover:border-slate-600
                            peer-checked:border-slate-950 dark:peer-checked:border-white
                            peer-checked:bg-slate-50 dark:peer-checked:bg-slate-800
                        "
                    >

                        <div class="w-12 h-12 rounded-2xl bg-slate-900 dark:bg-slate-800 flex items-center justify-center text-2xl flex-shrink-0">
                            🌙
                        </div>

                        <div class="flex-1">

                            <p class="font-semibold text-slate-900 dark:text-white">
                                Dunkel
                            </p>

                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                                Verwende das dunkle Erscheinungsbild von FinanzView.
                            </p>

                        </div>

                        <div class="w-5 h-5 rounded-full border-2 border-slate-300 dark:border-slate-600 flex items-center justify-center">

                            <div class="w-2.5 h-2.5 rounded-full bg-slate-950 dark:bg-white"></div>

                        </div>

                    </div>

                </label>

            </div>


            {{-- SPEICHERN --}}

            <div class="flex items-center justify-end mt-8 pt-6 border-t border-slate-100 dark:border-slate-800">

                <button
                    type="submit"
                    class="
                        inline-flex
                        items-center
                        justify-center
                        rounded-xl
                        bg-slate-950
                        dark:bg-white
                        px-6
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
                    Darstellung speichern
                </button>

            </div>

        </form>

    </div>


    {{-- HINWEIS --}}

    <div class="mt-5 rounded-2xl bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800 p-5">

        <div class="flex gap-3">

            <span class="text-lg">
                💡
            </span>

            <div>

                <p class="text-sm font-medium text-slate-700 dark:text-slate-200">
                    Hinweis
                </p>

                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Die Auswahl wird deinem Benutzerkonto zugeordnet und bleibt auch nach
                    dem nächsten Login erhalten.
                </p>

            </div>

        </div>

    </div>

</div>

@endsection