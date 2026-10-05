@extends('layouts.app')

@section('title', 'Bankverbindungen – FinanzView')
@section('eyebrow', 'Einstellungen')
@section('page_title', 'Bankverbindungen')

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-back-link :href="route('settings.index')" label="Einstellungen" />

    <x-page-header title="Bankverbindungen" subtitle="Umsätze per FinTS direkt von deiner Bank abrufen.">
        @if ($enabled)
            <a href="{{ route('bank-connections.create') }}" class="fv-btn fv-btn-primary text-sm py-2.5">
                <x-icon name="plus" class="w-4 h-4" />
                Bank verbinden
            </a>
        @endif
    </x-page-header>

    <x-flash />

    @unless ($enabled)
        {{-- Ohne Produktregistrierung ist der Abruf ausgeblendet. --}}
        <div class="fv-card p-5 sm:p-6 space-y-3">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300 flex items-center justify-center">
                    <x-icon name="alert" class="w-5 h-5" />
                </span>
                <p class="font-medium text-slate-900 dark:text-white">Wartet auf Produktregistrierung</p>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-300">
                Für den Abruf per FinTS braucht jede FinanzView-Installation eine kostenlose Produktregistrierungsnummer
                der Deutschen Kreditwirtschaft (hbci-zka.de). Trage sie danach im Container als
                <code class="rounded bg-slate-100 dark:bg-white/10 px-1.5 py-0.5 text-[13px]">FINTS_PRODUCT_ID</code>
                ein und starte FinanzView neu.
            </p>
            <p class="text-sm text-slate-600 dark:text-slate-300">
                Bis dahin kannst du Umsätze per <a href="{{ route('transactions.import.create') }}" class="fv-link">CSV-Import</a> übernehmen.
            </p>
        </div>
    @endunless

    @if ($pending && ($pending['stage'] ?? null) === 'challenge')
        <a href="{{ route('bank-connections.challenge') }}" class="flex gap-3 rounded-2xl bg-sky-50 dark:bg-sky-500/10 p-4 text-sm text-sky-800 dark:text-sky-300">
            <x-icon name="alert" class="w-5 h-5" />
            <span>Ein Bankvorgang wartet auf deine Freigabe. <span class="underline">Fortsetzen</span></span>
        </a>
    @endif

    @if ($connections->isNotEmpty())
        <div class="space-y-3">
            @foreach ($connections as $connection)
                <div class="fv-card p-5 space-y-4">
                    <div class="flex items-start gap-3">
                        <span class="w-10 h-10 rounded-xl bg-teal-600 text-white flex items-center justify-center shrink-0">
                            <x-icon name="landmark" class="w-5 h-5" />
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-slate-900 dark:text-white truncate">{{ $connection->name }}</p>
                            <p class="text-[13px] text-slate-500 dark:text-slate-400 truncate">
                                BLZ {{ $connection->bank_code }}
                                @if ($connection->tan_mode_name) · {{ $connection->tan_mode_name }} @endif
                            </p>
                            <p class="text-[13px] text-slate-500 dark:text-slate-400">
                                @if (! $connection->isReady())
                                    <span class="text-amber-600 dark:text-amber-400">Einrichtung nicht abgeschlossen</span>
                                @elseif ($connection->last_synced_at)
                                    Zuletzt abgerufen {{ $connection->last_synced_at->diffForHumans() }}
                                @else
                                    Noch nicht abgerufen
                                @endif
                            </p>
                        </div>
                        <a href="{{ route('bank-connections.edit', $connection) }}" class="w-9 h-9 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-white/10 transition"
                            title="Bearbeiten" aria-label="{{ $connection->name }} bearbeiten">
                            <x-icon name="pencil" class="w-4 h-4" />
                        </a>
                    </div>

                    @if ($connection->linkedAccounts->isNotEmpty())
                        <ul class="rounded-xl bg-slate-50 dark:bg-white/5 divide-y divide-slate-200/70 dark:divide-white/5">
                            @foreach ($connection->linkedAccounts as $link)
                                @php $difference = $link->balanceDifference(); @endphp
                                <li class="px-4 py-3 space-y-1">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ $link->account->name }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 font-mono">{{ $link->maskedIban() }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-semibold tabular-nums text-slate-900 dark:text-white">{{ number_format($link->account->current_balance, 2, ',', '.') }} €</p>
                                            @if ($link->bank_balance !== null)
                                                <p class="text-xs text-slate-500 dark:text-slate-400">Bank {{ $link->balance_date?->format('d.m.') }}: {{ number_format((float) $link->bank_balance, 2, ',', '.') }} €</p>
                                            @endif
                                        </div>
                                    </div>

                                    @if ($difference !== null && abs($difference) >= 0.005)
                                        <form method="POST" action="{{ route('bank-connections.reconcile', $link) }}" class="flex items-center justify-between gap-3 pt-1"
                                            onsubmit="return confirm('Startsaldo von „{{ addslashes($link->account->name) }}“ um {{ number_format($difference, 2, ',', '.') }} € anpassen, damit der Kontostand zur Bank passt?')">
                                            @csrf
                                            <span class="text-xs text-amber-700 dark:text-amber-400">Abweichung {{ $difference > 0 ? '+' : '−' }}{{ number_format(abs($difference), 2, ',', '.') }} €</span>
                                            <button type="submit" class="fv-link text-xs">Angleichen</button>
                                        </form>
                                    @endif

                                    @if ($link->last_error)
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $link->last_error }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($enabled)
                        @if ($connection->isReady())
                            <a href="{{ route('bank-connections.sync', $connection) }}" class="fv-btn fv-btn-primary w-full">
                                <x-icon name="download" class="w-4 h-4" />
                                Umsätze abrufen
                            </a>
                        @else
                            <a href="{{ route('bank-connections.setup', $connection) }}" class="fv-btn fv-btn-secondary w-full">Einrichtung fortsetzen</a>
                        @endif
                    @endif

                    @if ($connection->last_result)
                        <p class="text-[13px] text-slate-500 dark:text-slate-400">{{ $connection->last_result }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    @elseif ($enabled)
        <div class="fv-card">
            <x-empty-state icon="landmark" title="Noch keine Bank verbunden" :href="route('bank-connections.create')" action="Bank verbinden">
                Verbinde dein Girokonto, um Umsätze mit einem Tipp abzurufen – ohne CSV-Datei.
            </x-empty-state>
        </div>
    @endif

    {{-- BANKENLISTE --}}

    @php
        $instituteCount = \App\Models\FintsInstitute::query()->count();
        $importedAt = \App\Models\ApplicationSetting::get('fints_institutes_imported_at');
    @endphp

    @if ($enabled || auth()->user()->isAdmin())
        <section>
            <h3 class="px-1 pb-2 text-[13px] font-semibold text-slate-500 dark:text-slate-400">Bankenliste</h3>

            <div class="fv-card p-5 space-y-3">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-slate-100 text-slate-500 dark:bg-white/5 dark:text-slate-400 flex items-center justify-center">
                        <x-icon name="landmark" class="w-5 h-5" />
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-slate-900 dark:text-white">
                            {{ $instituteCount > 0 ? number_format($instituteCount, 0, ',', '.') . ' Banken' : 'Noch keine Bankenliste' }}
                        </p>
                        <p class="text-[13px] text-slate-500 dark:text-slate-400">
                            @if ($instituteCount > 0 && $importedAt)
                                Importiert am {{ \Carbon\Carbon::parse($importedAt)->format('d.m.Y') }} – beim Verbinden genügt der Banknamen.
                            @else
                                Mit der FinTS-Bankenliste der Deutschen Kreditwirtschaft genügt beim Verbinden der Banknamen.
                            @endif
                        </p>
                    </div>
                </div>

                @if (auth()->user()->isAdmin())
                    <form method="POST" action="{{ route('bank-connections.institutes.import') }}" enctype="multipart/form-data" class="flex flex-col sm:flex-row gap-2 sm:items-center">
                        @csrf
                        <label class="sr-only" for="list">Bankenliste (CSV)</label>
                        <input id="list" name="list" type="file" accept=".csv,.txt,text/csv" required class="fv-input text-sm flex-1 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 dark:file:bg-white/10 file:px-3 file:py-1.5 file:text-sm">
                        <button type="submit" class="fv-btn fv-btn-secondary text-sm py-2.5">{{ $instituteCount > 0 ? 'Liste ersetzen' : 'Liste importieren' }}</button>
                    </form>
                    @error('list')
                        <p class="text-[13px] text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                @elseif ($instituteCount === 0)
                    <p class="text-[13px] text-slate-500 dark:text-slate-400">Ein Administrator kann sie hier importieren.</p>
                @endif
            </div>
        </section>
    @endif

    <p class="px-1 text-[13px] text-slate-500 dark:text-slate-400">
        Deine Online-Banking-PIN wird nie gespeichert, sondern bei jedem Abruf abgefragt. Abgerufene Umsätze laufen durch deine
        <a href="{{ route('category-rules.index') }}" class="fv-link">Kategorie-Regeln</a>; bereits vorhandene Buchungen werden erkannt und übersprungen.
    </p>

</div>

@endsection
