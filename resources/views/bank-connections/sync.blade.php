@extends('layouts.app')

@section('title', 'Umsätze abrufen – FinanzView')
@section('eyebrow', 'Bankverbindungen')
@section('page_title', 'Umsätze abrufen')

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-back-link :href="route('bank-connections.index')" label="Bankverbindungen" />

    <x-page-header title="Umsätze abrufen" :subtitle="$connection->name . ' · ' . $connection->linkedAccounts->map(fn ($link) => $link->account->name)->implode(', ')" />

    <x-flash />

    <form method="POST" action="{{ route('bank-connections.sync.start', $connection) }}" class="fv-card p-5 sm:p-6 space-y-4" data-busy-form>
        @csrf

        @include('bank-connections._pin')

        <x-field label="Zeitraum" for="period">
            <select id="period" name="period" class="fv-input">
                <option value="auto">{{ $connection->last_synced_at ? 'Seit dem letzten Abruf (' . $connection->last_synced_at->format('d.m.Y') . ')' : 'Letzte 90 Tage' }}</option>
                <option value="30">Letzte 30 Tage</option>
                <option value="90">Letzte 90 Tage</option>
                <option value="180">Letzte 180 Tage</option>
                <option value="365">Letztes Jahr</option>
            </select>
        </x-field>

        <p class="text-[13px] text-slate-500 dark:text-slate-400">
            Je nach Bank musst du den Abruf in der App freigeben – bei der Sparkasse meist nur alle 90 bis 180 Tage oder bei längeren Zeiträumen.
        </p>

        <div class="flex justify-end">
            <button type="submit" class="fv-btn fv-btn-primary">
                <x-icon name="download" class="w-4 h-4" />
                Jetzt abrufen
            </button>
        </div>
    </form>

</div>

@include('bank-connections._busy')

@endsection
