@extends('layouts.app')

@section('title', 'Daten & Export')

@section('content')

<div class="max-w-5xl mx-auto space-y-8">

    <div>
        <a
            href="{{ route('settings.index') }}"
            class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white mb-4"
        >
            ← Einstellungen
        </a>

        <h1 class="text-3xl font-bold text-slate-900 dark:text-white">
            Daten & Export
        </h1>

        <p class="mt-2 text-slate-500 dark:text-slate-400">
            Exportiere deine Finanzdaten als CSV oder vollständiges JSON-Backup.
        </p>
    </div>


    {{-- TRANSAKTIONS-CSV --}}
    <section class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">

        <div class="p-6 border-b border-slate-200 dark:border-slate-800">

            <div class="flex items-start gap-4">

                <div class="w-12 h-12 rounded-2xl bg-emerald-100 dark:bg-emerald-950/40 flex items-center justify-center text-xl">
                    📊
                </div>

                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                        Transaktionen als CSV
                    </h2>

                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Ideal für Excel, Numbers oder LibreOffice.
                    </p>
                </div>

            </div>

        </div>


        <form
            method="POST"
            action="{{ route('settings.data-export.transactions') }}"
            class="p-6 space-y-6"
        >

            @csrf

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

                <div>
                    <label
                        for="date_from"
                        class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                    >
                        Von
                    </label>

                    <input
                        type="date"
                        id="date_from"
                        name="date_from"
                        value="{{ old('date_from') }}"
                        class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                    >
                </div>


                <div>
                    <label
                        for="date_to"
                        class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                    >
                        Bis
                    </label>

                    <input
                        type="date"
                        id="date_to"
                        name="date_to"
                        value="{{ old('date_to') }}"
                        class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                    >
                </div>


                <div>
                    <label
                        for="account_id"
                        class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                    >
                        Konto
                    </label>

                    <select
                        id="account_id"
                        name="account_id"
                        class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                    >
                        <option value="">Alle Konten</option>

                        @foreach($accounts as $account)
                            <option
                                value="{{ $account->id }}"
                                @selected(old('account_id') == $account->id)
                            >
                                {{ $account->name }}
                            </option>
                        @endforeach

                    </select>
                </div>

            </div>


            <div class="flex justify-end">

                <button
                    type="submit"
                    class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-white dark:hover:bg-slate-200 dark:text-slate-900 text-white font-medium transition"
                >
                    CSV exportieren
                </button>

            </div>

        </form>

    </section>


    {{-- JSON BACKUP --}}
    <section class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">

        <div class="p-6 border-b border-slate-200 dark:border-slate-800">

            <div class="flex items-start gap-4">

                <div class="w-12 h-12 rounded-2xl bg-violet-100 dark:bg-violet-950/40 flex items-center justify-center text-xl">
                    💾
                </div>

                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                        Vollständiges Backup
                    </h2>

                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Exportiert deine Finanzdaten als strukturiertes JSON-Backup.
                    </p>
                </div>

            </div>

        </div>


        <div class="p-6">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm text-slate-600 dark:text-slate-300 mb-6">

                <div class="flex items-center gap-2">
                    <span class="text-emerald-500">✓</span>
                    Konten
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-emerald-500">✓</span>
                    Transaktionen
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-emerald-500">✓</span>
                    Kategorien & Tags
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-emerald-500">✓</span>
                    Budgets
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-emerald-500">✓</span>
                    Wiederkehrende Buchungen
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-emerald-500">✓</span>
                    Kreditkarten & Abrechnungen
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-emerald-500">✓</span>
                    Kredite & Zahlungen
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-emerald-500">✓</span>
                    Dashboard-Einstellungen
                </div>

            </div>


            <div class="flex justify-end">

                <form
                    method="POST"
                    action="{{ route('settings.data-export.json') }}"
                >

                    @csrf

                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-violet-600 hover:bg-violet-700 text-white font-medium transition"
                    >
                        Vollständiges Backup exportieren
                    </button>

                </form>

            </div>

        </div>

    </section>


    {{-- HINWEIS --}}
    <div class="rounded-2xl border border-amber-200 dark:border-amber-900/50 bg-amber-50 dark:bg-amber-950/20 p-5">

        <div class="flex gap-3">

            <div class="text-xl">⚠️</div>

            <div>
                <h3 class="font-semibold text-amber-900 dark:text-amber-200">
                    Hinweis zum Backup
                </h3>

                <p class="text-sm text-amber-800 dark:text-amber-300 mt-1">
                    Bewahre deine Backup-Dateien sicher auf. Ein JSON-Backup kann sensible Finanzinformationen enthalten.
                    Ein Import bzw. eine Wiederherstellung wird in einem späteren Schritt ergänzt.
                </p>
            </div>

        </div>

    </div>

</div>

@endsection
