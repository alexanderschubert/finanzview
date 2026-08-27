
@extends('layouts.app')

@section('title', 'Einstellungen – Finanzblick')

@section('eyebrow', 'Verwaltung')

@section('page_title', 'Einstellungen')

@section('content')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- HEADER --}}

    <div class="mb-8">

        <p class="text-sm text-slate-500">

            Verwalte deine persönlichen Einstellungen.

        </p>

        <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900 mt-1">

            Einstellungen

        </h2>

        <p class="text-slate-500 mt-2">

            Passe Finanzblick an deine Bedürfnisse an.

        </p>

    </div>

    {{-- EINSTELLUNGEN --}}

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- PROFIL --}}

        <a

            href="#"

            class="group bg-white rounded-3xl border border-slate-100 shadow-sm p-6 hover:shadow-md hover:border-slate-200 transition"

        >

            <div class="flex items-start justify-between">

                <div

                    class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center text-2xl"

                >

                    👤

                </div>

                <span class="text-slate-300 group-hover:text-slate-500 transition">

                    →

                </span>

            </div>

            <h3 class="font-semibold text-slate-900 mt-5">

                Profil

            </h3>

            <p class="text-sm text-slate-500 mt-2">

                Name, E-Mail-Adresse und persönliche Daten verwalten.

            </p>

        </a>

        {{-- DARSTELLUNG --}}

        <a

            href="#"

            class="group bg-white rounded-3xl border border-slate-100 shadow-sm p-6 hover:shadow-md hover:border-slate-200 transition"

        >

            <div class="flex items-start justify-between">

                <div

                    class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center text-2xl"

                >

                    🎨

                </div>

                <span class="text-slate-300 group-hover:text-slate-500 transition">

                    →

                </span>

            </div>

            <h3 class="font-semibold text-slate-900 mt-5">

                Darstellung

            </h3>

            <p class="text-sm text-slate-500 mt-2">

                Erscheinungsbild und Anzeigeoptionen von Finanzblick.

            </p>

        </a>

        {{-- FINANZEN --}}

        <a

            href="#"

            class="group bg-white rounded-3xl border border-slate-100 shadow-sm p-6 hover:shadow-md hover:border-slate-200 transition"

        >

            <div class="flex items-start justify-between">

                <div

                    class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center text-2xl"

                >

                    💶

                </div>

                <span class="text-slate-300 group-hover:text-slate-500 transition">

                    →

                </span>

            </div>

            <h3 class="font-semibold text-slate-900 mt-5">

                Finanzen

            </h3>

            <p class="text-sm text-slate-500 mt-2">

                Währung, Zahlenformat und weitere finanzielle Einstellungen.

            </p>

        </a>

        {{-- BENACHRICHTIGUNGEN --}}

        <a

            href="#"

            class="group bg-white rounded-3xl border border-slate-100 shadow-sm p-6 hover:shadow-md hover:border-slate-200 transition"

        >

            <div class="flex items-start justify-between">

                <div

                    class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center text-2xl"

                >

                    🔔

                </div>

                <span class="text-slate-300 group-hover:text-slate-500 transition">

                    →

                </span>

            </div>

            <h3 class="font-semibold text-slate-900 mt-5">

                Benachrichtigungen

            </h3>

            <p class="text-sm text-slate-500 mt-2">

                Einstellungen für Hinweise und Benachrichtigungen.

            </p>

        </a>

        {{-- SICHERHEIT --}}

        <a

            href="#"

            class="group bg-white rounded-3xl border border-slate-100 shadow-sm p-6 hover:shadow-md hover:border-slate-200 transition"

        >

            <div class="flex items-start justify-between">

                <div

                    class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center text-2xl"

                >

                    🔐

                </div>

                <span class="text-slate-300 group-hover:text-slate-500 transition">

                    →

                </span>

            </div>

            <h3 class="font-semibold text-slate-900 mt-5">

                Sicherheit

            </h3>

            <p class="text-sm text-slate-500 mt-2">

                Passwort und Sicherheit deines Kontos verwalten.

            </p>

        </a>

        {{-- DATEN --}}

        <a

            href="#"

            class="group bg-white rounded-3xl border border-slate-100 shadow-sm p-6 hover:shadow-md hover:border-slate-200 transition"

        >

            <div class="flex items-start justify-between">

                <div

                    class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center text-2xl"

                >

                    💾

                </div>

                <span class="text-slate-300 group-hover:text-slate-500 transition">

                    →

                </span>

            </div>

            <h3 class="font-semibold text-slate-900 mt-5">

                Daten & Export

            </h3>

            <p class="text-sm text-slate-500 mt-2">

                Deine Finanzdaten exportieren oder verwalten.

            </p>

        </a>

    </div>

    {{-- KONTOINFORMATIONEN --}}

    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm mt-5 overflow-hidden">

        <div class="p-6 sm:p-8">

            <div class="flex items-center gap-4">

                <div

                    class="w-12 h-12 rounded-2xl bg-slate-950 text-white flex items-center justify-center font-semibold text-lg"

                >

                    F

                </div>

                <div>

                    <h3 class="font-semibold text-slate-900">

                        Finanzblick

                    </h3>

                    <p class="text-sm text-slate-500 mt-1">

                        Deine persönliche Finanzverwaltung

                    </p>

                </div>

            </div>

            <div class="border-t border-slate-100 mt-6 pt-6">

                <div class="flex items-center justify-between">

                    <div>

                        <p class="text-sm font-medium text-slate-700">

                            Angemeldet als

                        </p>

                        <p class="text-sm text-slate-500 mt-1">

                            {{ auth()->user()->email }}

                        </p>

                    </div>

                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-600">

                        Konto aktiv

                    </span>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection

