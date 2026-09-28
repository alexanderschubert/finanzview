@extends('layouts.app')

@section('title', 'Daten & Export – FinanzView')
@section('eyebrow', 'Einstellungen')
@section('page_title', 'Daten & Export')

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-back-link :href="route('settings.index')" label="Einstellungen" />

    <x-page-header title="Daten & Export" subtitle="Deine Daten gehören dir – exportieren, sichern und wiederherstellen." />

    <x-flash />

    @if ($errors->any())
        <div class="flex gap-3 rounded-2xl bg-red-50 dark:bg-red-500/10 p-4 text-sm text-red-700 dark:text-red-300" role="alert">
            <x-icon name="alert" class="w-5 h-5" />
            <div>
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        </div>
    @endif


    {{-- CSV --}}

    <section>
        <h3 class="px-1 pb-2 text-[13px] font-semibold text-slate-500 dark:text-slate-400">Buchungen als Tabelle</h3>

        <form method="POST" action="{{ route('settings.data-export.transactions') }}" class="fv-card p-5 sm:p-6 space-y-4">
            @csrf

            <p class="text-sm text-slate-600 dark:text-slate-300">
                CSV-Datei für Excel, Numbers oder LibreOffice. Ohne Angaben werden alle Buchungen exportiert.
            </p>

            <div class="grid grid-cols-2 gap-4">
                <x-field label="Von" for="date_from" error="date_from">
                    <input id="date_from" name="date_from" type="date" value="{{ old('date_from') }}" class="fv-input">
                </x-field>

                <x-field label="Bis" for="date_to" error="date_to">
                    <input id="date_to" name="date_to" type="date" value="{{ old('date_to') }}" class="fv-input">
                </x-field>
            </div>

            <x-field label="Konto" for="account_id" error="account_id">
                <select id="account_id" name="account_id" class="fv-input">
                    <option value="">Alle Konten</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}" @selected((string) old('account_id') === (string) $account->id)>{{ $account->name }}</option>
                    @endforeach
                </select>
            </x-field>

            <div class="flex justify-end">
                <button type="submit" class="fv-btn fv-btn-primary">
                    <x-icon name="download" class="w-4 h-4" />
                    CSV herunterladen
                </button>
            </div>
        </form>
    </section>


    {{-- BACKUP --}}

    <section>
        <h3 class="px-1 pb-2 text-[13px] font-semibold text-slate-500 dark:text-slate-400">Vollständiges Backup</h3>

        <div class="fv-card p-5 sm:p-6 space-y-4">
            <p class="text-sm text-slate-600 dark:text-slate-300">
                JSON-Datei mit allen Konten, Buchungen, Kategorien, Budgets, Krediten, Kreditkarten, wiederkehrenden Buchungen und Dashboard-Einstellungen.
            </p>

            <form method="POST" action="{{ route('settings.data-export.json') }}" class="flex justify-end">
                @csrf
                <button type="submit" class="fv-btn fv-btn-secondary">
                    <x-icon name="download" class="w-4 h-4" />
                    Backup herunterladen
                </button>
            </form>
        </div>
    </section>


    {{-- WIEDERHERSTELLEN --}}

    <section>
        <h3 class="px-1 pb-2 text-[13px] font-semibold text-slate-500 dark:text-slate-400">Backup wiederherstellen</h3>

        <form method="POST" action="{{ route('settings.data-export.import') }}" enctype="multipart/form-data" class="fv-card p-5 sm:p-6 space-y-4">
            @csrf

            <p class="text-sm text-slate-600 dark:text-slate-300">
                Lade ein FinanzView-Backup hoch. Du siehst zuerst eine Vorschau – erst danach wird etwas übernommen.
                Vorhandene Daten werden <span class="font-medium">nicht gelöscht</span>, fehlende werden ergänzt.
            </p>

            <label for="backup" class="flex flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-slate-200 dark:border-white/10 px-4 py-8 text-center cursor-pointer hover:border-emerald-400 hover:bg-emerald-50/40 dark:hover:bg-emerald-500/5 transition">
                <x-icon name="download" class="w-7 h-7 text-slate-400 rotate-180" />
                <span class="text-sm font-medium text-slate-700 dark:text-slate-200" data-file-name>JSON-Datei auswählen</span>
                <span class="text-xs text-slate-500 dark:text-slate-400">finanzview-backup-….json</span>
                <input id="backup" name="backup" type="file" accept=".json,application/json" required class="sr-only"
                    onchange="this.form.querySelector('[data-file-name]').textContent = this.files[0]?.name || 'JSON-Datei auswählen'">
            </label>

            <div class="flex justify-end">
                <button type="submit" class="fv-btn fv-btn-primary">Vorschau anzeigen</button>
            </div>
        </form>
    </section>

</div>

@endsection
