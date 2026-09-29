@extends('layouts.app')

@section('title', 'Kontoauszug importieren – FinanzView')
@section('eyebrow', 'Buchungen')
@section('page_title', 'Import')

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-back-link :href="route('transactions.index')" label="Buchungen" />

    <x-page-header title="Kontoauszug importieren" subtitle="Buchungen aus der CSV-Datei deiner Bank übernehmen." />

    <x-flash />

    @if (\App\Services\Fints\FintsConfig::enabled())
        <a href="{{ route('bank-connections.index') }}" class="flex items-center gap-3 rounded-2xl bg-teal-50 dark:bg-teal-500/10 p-4 text-sm text-teal-800 dark:text-teal-300">
            <x-icon name="landmark" class="w-5 h-5" />
            <span class="flex-1">Tipp: Umsätze ohne CSV-Datei direkt von der Bank abrufen</span>
            <x-icon name="chevron-right" class="w-4 h-4" />
        </a>
    @endif

    @if ($accounts->isEmpty())
        <div class="fv-card">
            <x-empty-state icon="landmark" title="Noch kein Konto" :href="route('accounts.create')" action="Konto anlegen">
                Lege zuerst ein Konto an, in das importiert werden soll.
            </x-empty-state>
        </div>
    @else
        <form method="POST" action="{{ route('transactions.import.upload') }}" enctype="multipart/form-data" class="fv-card p-5 sm:p-6 space-y-5">
            @csrf

            <x-field label="In Konto importieren" for="account_id" error="account_id">
                <select id="account_id" name="account_id" required class="fv-input">
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}" @selected((string) old('account_id', $accounts->count() === 1 ? $account->id : null) === (string) $account->id)>
                            {{ $account->name }}
                        </option>
                    @endforeach
                </select>
            </x-field>

            <div>
                <label for="file" class="flex flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-slate-200 dark:border-white/10 px-4 py-10 text-center cursor-pointer hover:border-emerald-400 hover:bg-emerald-50/40 dark:hover:bg-emerald-500/5 transition">
                    <x-icon name="upload" class="w-8 h-8 text-slate-400" />
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200" data-file-name>CSV-Datei auswählen</span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">Umsätze aus dem Online-Banking, max. 5 MB</span>
                    <input id="file" name="file" type="file" accept=".csv,.txt,.tsv,text/csv" required class="sr-only"
                        onchange="this.form.querySelector('[data-file-name]').textContent = this.files[0]?.name || 'CSV-Datei auswählen'">
                </label>

                @error('file')
                    <p class="mt-1.5 text-[13px] text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <a href="{{ route('transactions.index') }}" class="fv-btn fv-btn-secondary">Abbrechen</a>
                <button type="submit" class="fv-btn fv-btn-primary">Weiter zur Vorschau</button>
            </div>
        </form>

        <section class="space-y-3">
            <h3 class="px-1 text-[13px] font-semibold text-slate-500 dark:text-slate-400">So funktioniert’s</h3>

            <ol class="fv-card divide-y divide-slate-100 dark:divide-white/5 text-sm">
                @foreach ([
                    ['Umsätze exportieren', 'Im Online-Banking die Umsätze als CSV herunterladen – z. B. Sparkasse, DKB, ING, comdirect, N26 oder PayPal.'],
                    ['Vorschau prüfen', 'Spalten werden automatisch erkannt. Du siehst alle Buchungen, bevor etwas gespeichert wird.'],
                    ['Kategorien übernehmen', 'FinanzView schlägt Kategorien anhand deiner bisherigen Buchungen vor.'],
                    ['Keine Dubletten', 'Bereits importierte Zeilen werden erkannt und übersprungen – du kannst überlappende Zeiträume gefahrlos erneut importieren.'],
                ] as $i => [$title, $text])
                    <li class="flex gap-3 px-4 py-3">
                        <span class="w-6 h-6 shrink-0 rounded-full bg-emerald-600 text-white text-xs font-semibold flex items-center justify-center">{{ $i + 1 }}</span>
                        <div>
                            <p class="font-medium text-slate-900 dark:text-white">{{ $title }}</p>
                            <p class="text-[13px] text-slate-500 dark:text-slate-400">{{ $text }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif

</div>

@endsection
