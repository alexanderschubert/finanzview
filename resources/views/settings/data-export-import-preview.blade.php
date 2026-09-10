@extends('layouts.app')

@section('title', 'Backup prüfen')

@section('content')

<div class="max-w-4xl mx-auto space-y-8">

    <div>

        <a
            href="{{ route('settings.data-export') }}"
            class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white mb-4"
        >
            ← Daten & Export
        </a>

        <h1 class="text-3xl font-bold text-slate-900 dark:text-white">
            Backup prüfen
        </h1>

        <p class="mt-2 text-slate-500 dark:text-slate-400">
            Das Backup wurde erfolgreich validiert. Prüfe die enthaltenen Daten,
            bevor du die Wiederherstellung startest.
        </p>

    </div>


    {{-- METADATA --}}
    <section class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">

        <div class="p-6 border-b border-slate-200 dark:border-slate-800">

            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                Backup-Informationen
            </h2>

        </div>

        <div class="p-6 grid grid-cols-1 sm:grid-cols-3 gap-5">

            <div>
                <div class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">
                    Anwendung
                </div>

                <div class="mt-1 font-medium text-slate-900 dark:text-white">
                    {{ $metadata['application'] ?? 'Unbekannt' }}
                </div>
            </div>

            <div>
                <div class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">
                    Format
                </div>

                <div class="mt-1 font-medium text-slate-900 dark:text-white">
                    {{ $metadata['format'] ?? 'Unbekannt' }}
                </div>
            </div>

            <div>
                <div class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">
                    Version
                </div>

                <div class="mt-1 font-medium text-slate-900 dark:text-white">
                    {{ $metadata['version'] ?? 'Unbekannt' }}
                </div>
            </div>

        </div>

    </section>


    {{-- COUNTS --}}
    <section class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">

        <div class="p-6 border-b border-slate-200 dark:border-slate-800">

            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                Enthaltene Daten
            </h2>

        </div>


        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

            @php
                $labels = [
                    'accounts' => 'Konten',
                    'categories' => 'Kategorien',
                    'tags' => 'Tags',
                    'transactions' => 'Transaktionen',
                    'budgets' => 'Budgets',
                    'budget_categories' => 'Budget-Kategorien',
                    'recurring_transactions' => 'Wiederkehrende Buchungen',
                    'credit_cards' => 'Kreditkarten',
                    'credit_card_statements' => 'Kreditkarten-Abrechnungen',
                    'loans' => 'Kredite',
                    'loan_payments' => 'Kreditzahlungen',
                    'dashboard_settings' => 'Dashboard-Einstellungen',
                ];
            @endphp

            @foreach($labels as $key => $label)

                <div class="rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 p-4">

                    <div class="text-sm text-slate-500 dark:text-slate-400">
                        {{ $label }}
                    </div>

                    <div class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">
                        {{ $counts[$key] ?? 0 }}
                    </div>

                </div>

            @endforeach

        </div>

    </section>


    {{-- WARNING --}}
    <section class="rounded-2xl border border-amber-300 dark:border-amber-900/60 bg-amber-50 dark:bg-amber-950/20 p-5">

        <div class="flex gap-3">

            <div class="text-xl">
                ⚠️
            </div>

            <div>

                <h3 class="font-semibold text-amber-900 dark:text-amber-200">
                    Wichtige Hinweise
                </h3>

                <ul class="mt-2 space-y-1 text-sm text-amber-800 dark:text-amber-300 list-disc list-inside">

                    <li>
                        Das Backup wird dem aktuell angemeldeten Benutzer zugeordnet.
                    </li>

                    <li>
                        Benutzer-IDs aus dem Backup werden niemals übernommen.
                    </li>

                    <li>
                        Bereits vorhandene passende Daten werden nicht doppelt angelegt.
                    </li>

                    <li>
                        Die Wiederherstellung erfolgt vollständig innerhalb einer Datenbank-Transaktion.
                    </li>

                    <li>
                        Bei einem Fehler werden sämtliche Änderungen zurückgerollt.
                    </li>

                </ul>

            </div>

        </div>

    </section>


    {{-- CONFIRM --}}
    <section class="bg-white dark:bg-slate-900 rounded-3xl border border-red-200 dark:border-red-900/50 shadow-sm overflow-hidden">

        <div class="p-6">

            <form
                method="POST"
                action="{{ route('settings.data-export.import.restore') }}"
                class="space-y-5"
            >

                @csrf

                <input
                    type="hidden"
                    name="token"
                    value="{{ $token }}"
                >

                <label class="flex items-start gap-3 cursor-pointer">

                    <input
                        type="checkbox"
                        name="confirm"
                        value="1"
                        required
                        class="mt-1 rounded border-slate-300 dark:border-slate-700"
                    >

                    <span class="text-sm text-slate-700 dark:text-slate-300">
                        Ich habe die Übersicht geprüft und möchte die enthaltenen Daten
                        jetzt in mein FinanzView-Konto wiederherstellen.
                    </span>

                </label>


                <div class="flex flex-col sm:flex-row sm:justify-end gap-3">

                    <a
                        href="{{ route('settings.data-export') }}"
                        class="inline-flex justify-center items-center px-5 py-3 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 font-medium transition"
                    >
                        Abbrechen
                    </a>

                    <button
                        type="submit"
                        class="inline-flex justify-center items-center px-5 py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-medium transition"
                    >
                        Wiederherstellung starten
                    </button>

                </div>

            </form>

        </div>

    </section>

</div>

@endsection
