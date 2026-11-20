@extends('layouts.app')

@section('title', 'Plan importieren – FinanzView')
@section('eyebrow', 'Planung')
@section('page_title', 'Plan importieren')

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-back-link :href="route('plan.index', ['month' => $monthKey])" label="Monatsplan" />

    <x-page-header title="Plan importieren" :subtitle="'Posten aus einer CSV-Datei in den Plan für ' . $month->translatedFormat('F Y') . ' übernehmen.'" />

    <form method="POST" action="{{ route('plan.import.store') }}" enctype="multipart/form-data" class="fv-card p-5 sm:p-6 space-y-5">
        @csrf
        <input type="hidden" name="month" value="{{ $monthKey }}">

        <x-field label="CSV-Datei" for="file" error="file" hint="Trennzeichen Semikolon oder Komma. Bestehende Posten bleiben erhalten.">
            <input id="file" name="file" type="file" accept=".csv,.txt,.tsv,text/csv" required class="fv-input">
        </x-field>

        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
            <a href="{{ route('plan.index', ['month' => $monthKey]) }}" class="fv-btn fv-btn-secondary">Abbrechen</a>
            <button type="submit" class="fv-btn fv-btn-primary">Importieren</button>
        </div>
    </form>

    <div class="fv-card p-5 sm:p-6 text-sm text-slate-600 dark:text-slate-300 space-y-3">
        <p class="font-medium text-slate-900 dark:text-white">So muss die Datei aussehen</p>
        <p>Die erste Zeile enthält die Spaltennamen. Pflicht sind <strong>Gruppe</strong>, <strong>Beschreibung</strong> und <strong>Betrag</strong>. Optional: <strong>Typ</strong> (Ausgabe, Einnahme, Rate oder Kredit), <strong>Bemerkung</strong>, <strong>Rhythmus</strong> (Monatlich, Vierteljährlich, Halbjährlich, Jährlich), <strong>Beginn</strong> und <strong>Letzte Rate</strong>.</p>
        <pre class="overflow-x-auto rounded-xl bg-slate-900/5 dark:bg-white/5 p-3 text-xs leading-relaxed">Gruppe;Typ;Beschreibung;Betrag;Bemerkung;Rhythmus;Beginn;Letzte Rate
Kredite;Kredit;Sparkassen Kredit;407,00;;Monatlich;20.01.2023;17.01.2033
Raten PayPal;Rate;Couch;74,15;;;;26.06.2026
Auto;Ausgabe;KFZ-Steuer;98,00;;Jährlich;08.05.2026;
Einnahmen;Einnahme;Gehalt;2400,00;;;;</pre>
        <p>Der Typ gilt für die ganze Gruppe und wird beim ersten Eintrag der Gruppe gelesen. Ohne Rhythmus gilt „monatlich“. Wer nicht monatlich zahlt, braucht einen Beginn, sonst wird der Posten monatlich geplant.</p>
    </div>

</div>

@endsection
