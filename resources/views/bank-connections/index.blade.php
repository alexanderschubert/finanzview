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
                                @if ($connection->iban) · {{ $connection->maskedIban() }} @endif
                                @if ($connection->account) → {{ $connection->account->name }} @endif
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

    <p class="px-1 text-[13px] text-slate-500 dark:text-slate-400">
        Deine Online-Banking-PIN wird nie gespeichert, sondern bei jedem Abruf abgefragt. Abgerufene Umsätze laufen durch deine
        <a href="{{ route('category-rules.index') }}" class="fv-link">Kategorie-Regeln</a>; bereits vorhandene Buchungen werden erkannt und übersprungen.
    </p>

</div>

@endsection
