@extends('layouts.app')

@section('title', 'Backup prüfen – FinanzView')
@section('eyebrow', 'Einstellungen')
@section('page_title', 'Backup prüfen')

@php
    $labels = [
        'accounts' => 'Konten',
        'categories' => 'Kategorien',
        'category_rules' => 'Kategorie-Regeln',
        'tags' => 'Tags',
        'transactions' => 'Buchungen',
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

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-back-link :href="route('settings.data-export')" label="Daten & Export" />

    <x-page-header title="Backup prüfen" subtitle="Das wird wiederhergestellt – bitte kurz kontrollieren." />


    {{-- DATEI --}}

    <dl class="fv-card overflow-hidden divide-y divide-slate-100 dark:divide-white/5 text-sm">
        <div class="flex items-center justify-between gap-4 px-4 py-3">
            <dt class="text-slate-500 dark:text-slate-400">Anwendung</dt>
            <dd class="font-medium text-slate-900 dark:text-white">{{ $metadata['application'] ?? 'Unbekannt' }}</dd>
        </div>
        <div class="flex items-center justify-between gap-4 px-4 py-3">
            <dt class="text-slate-500 dark:text-slate-400">Format</dt>
            <dd class="font-medium text-slate-900 dark:text-white">{{ $metadata['format'] ?? 'Unbekannt' }} · Version {{ $metadata['version'] ?? '?' }}</dd>
        </div>
        @if (! empty($metadata['exported_at']))
            <div class="flex items-center justify-between gap-4 px-4 py-3">
                <dt class="text-slate-500 dark:text-slate-400">Erstellt am</dt>
                <dd class="font-medium text-slate-900 dark:text-white">{{ \Carbon\Carbon::parse($metadata['exported_at'])->format('d.m.Y H:i') }}</dd>
            </div>
        @endif
    </dl>


    {{-- INHALT --}}

    <section>
        <h3 class="px-1 pb-2 text-[13px] font-semibold text-slate-500 dark:text-slate-400">Inhalt</h3>

        <ul class="fv-card overflow-hidden divide-y divide-slate-100 dark:divide-white/5 text-sm">
            @foreach ($labels as $key => $label)
                <li class="flex items-center justify-between gap-4 px-4 py-2.5 {{ ($counts[$key] ?? 0) === 0 ? 'text-slate-400 dark:text-slate-500' : 'text-slate-900 dark:text-white' }}">
                    <span>{{ $label }}</span>
                    <span class="font-semibold tabular-nums">{{ $counts[$key] ?? 0 }}</span>
                </li>
            @endforeach
        </ul>
    </section>


    {{-- HINWEISE --}}

    <div class="flex gap-3 rounded-2xl bg-slate-100 dark:bg-white/5 p-4 text-sm text-slate-600 dark:text-slate-300">
        <x-icon name="shield" class="w-5 h-5 shrink-0" />
        <ul class="space-y-1">
            <li>Vorhandene Daten werden nicht gelöscht, bereits vorhandene nicht doppelt angelegt.</li>
            <li>Benutzer-IDs aus dem Backup werden nie übernommen.</li>
            <li>Bei einem Fehler wird alles zurückgerollt – es bleibt nichts halb importiert.</li>
        </ul>
    </div>


    {{-- BESTÄTIGEN --}}

    <form method="POST" action="{{ route('settings.data-export.import.restore') }}" class="fv-card p-5 sm:p-6 space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" name="confirm" value="1" required class="mt-0.5 w-5 h-5 rounded">
            <span class="text-sm text-slate-700 dark:text-slate-200">
                Ich habe die Übersicht geprüft und möchte die Daten jetzt in mein FinanzView-Konto übernehmen.
            </span>
        </label>

        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
            <a href="{{ route('settings.data-export') }}" class="fv-btn fv-btn-secondary">Abbrechen</a>
            <button type="submit" class="fv-btn fv-btn-primary">Wiederherstellen</button>
        </div>
    </form>

</div>

@endsection
