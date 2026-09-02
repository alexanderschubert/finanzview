@extends('layouts.app')

@section('title', 'Anbieter – Administration')

@section('content')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">

        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                Anbieter
            </h1>

            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Zentrale Verwaltung der Anbieter für Konten, Kreditkarten und Kredite.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a
                href="{{ route('admin.providers.create') }}"
                class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl
                       bg-violet-600 hover:bg-violet-700
                       text-sm font-semibold text-white transition"
            >
                <span class="text-lg leading-none">+</span>
                Anbieter hinzufügen
            </a>

            <a
                href="{{ route('admin.index') }}"
                class="inline-flex items-center justify-center px-4 py-2 rounded-xl
                       bg-slate-100 hover:bg-slate-200
                       dark:bg-slate-800 dark:hover:bg-slate-700
                       text-sm font-medium text-slate-700 dark:text-slate-200 transition"
            >
                ← Administration
            </a>
        </div>

    </div>


    {{-- FLASH MESSAGES --}}
    @if(session('success'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50
                    px-4 py-3 text-sm text-emerald-800
                    dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50
                    px-4 py-3 text-sm text-red-800
                    dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-300">
            {{ session('error') }}
        </div>
    @endif


    {{-- PROVIDERS --}}
    <div class="bg-white dark:bg-slate-900
                border border-slate-200 dark:border-slate-800
                rounded-2xl overflow-hidden shadow-sm">

        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800">

            <h2 class="font-semibold text-slate-900 dark:text-white">
                Anbieter
            </h2>

            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                {{ $providers->count() }} Anbieter vorhanden
            </p>

        </div>


        <div class="divide-y divide-slate-200 dark:divide-slate-800">

            @forelse($providers as $provider)

                <div class="px-5 py-4 flex flex-col lg:flex-row lg:items-center gap-4">

                    {{-- PROVIDER --}}
                    <div class="flex items-center gap-3 flex-1 min-w-0">

                        <x-financial-provider
                            :provider="$provider"
                            fallback-icon="🏦"
                            size="sm"
                        />

                        <div class="min-w-0">

                            <div class="font-medium text-slate-900 dark:text-white truncate">
                                {{ $provider->name }}
                            </div>

                            <div class="text-xs text-slate-500 dark:text-slate-400">
                                {{ $provider->type }}
                                ·
                                {{ $provider->slug }}
                            </div>

                        </div>

                    </div>


                    {{-- VERWENDUNG --}}
                    <div class="flex flex-wrap gap-2 text-xs">

                        <span class="px-2.5 py-1 rounded-lg
                                     bg-slate-100 dark:bg-slate-800
                                     text-slate-600 dark:text-slate-300">
                            {{ $provider->accounts_count }} Konten
                        </span>

                        <span class="px-2.5 py-1 rounded-lg
                                     bg-slate-100 dark:bg-slate-800
                                     text-slate-600 dark:text-slate-300">
                            {{ $provider->credit_cards_count }} Karten
                        </span>

                        <span class="px-2.5 py-1 rounded-lg
                                     bg-slate-100 dark:bg-slate-800
                                     text-slate-600 dark:text-slate-300">
                            {{ $provider->loans_count }} Kredite
                        </span>

                    </div>


                    {{-- STATUS --}}
                    <div>

                        @if($provider->is_active)

                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg
                                         bg-emerald-50 dark:bg-emerald-950/40
                                         text-emerald-700 dark:text-emerald-300 text-xs font-medium">
                                Aktiv
                            </span>

                        @else

                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg
                                         bg-slate-100 dark:bg-slate-800
                                         text-slate-500 dark:text-slate-400 text-xs font-medium">
                                Deaktiviert
                            </span>

                        @endif

                    </div>


                    {{-- ACTIONS --}}
                    <div class="flex items-center gap-2">

                        <a
                            href="{{ route('admin.providers.edit', $provider) }}"
                            class="px-3 py-2 rounded-xl text-xs font-medium
                                   bg-violet-50 hover:bg-violet-100
                                   dark:bg-violet-950/40 dark:hover:bg-violet-950/60
                                   text-violet-700 dark:text-violet-300 transition"
                        >
                            Bearbeiten
                        </a>

                        <form
                            method="POST"
                            action="{{ route('admin.providers.toggle-active', $provider) }}"
                        >
                            @csrf
                            @method('PATCH')

                            <button
                                type="submit"
                                class="px-3 py-2 rounded-xl text-xs font-medium
                                       bg-slate-100 hover:bg-slate-200
                                       dark:bg-slate-800 dark:hover:bg-slate-700
                                       text-slate-700 dark:text-slate-200 transition"
                            >
                                {{ $provider->is_active ? 'Deaktivieren' : 'Aktivieren' }}
                            </button>

                        </form>


                        @if(
                            $provider->accounts_count === 0 &&
                            $provider->credit_cards_count === 0 &&
                            $provider->loans_count === 0
                        )

                            <form
                                method="POST"
                                action="{{ route('admin.providers.destroy', $provider) }}"
                                onsubmit="return confirm('Diesen Anbieter wirklich löschen?');"
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="px-3 py-2 rounded-xl text-xs font-medium
                                           bg-red-50 hover:bg-red-100
                                           dark:bg-red-950/40 dark:hover:bg-red-950/60
                                           text-red-700 dark:text-red-300 transition"
                                >
                                    Löschen
                                </button>

                            </form>

                        @endif

                    </div>

                </div>

            @empty

                <div class="px-5 py-12 text-center">

                    <div class="text-4xl mb-3">
                        🏦
                    </div>

                    <div class="font-medium text-slate-900 dark:text-white">
                        Noch keine Anbieter vorhanden
                    </div>

                    <div class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Lege den ersten Anbieter an.
                    </div>

                </div>

            @endforelse

        </div>

    </div>

</div>

@endsection
