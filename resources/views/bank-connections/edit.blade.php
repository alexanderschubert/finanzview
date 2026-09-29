@extends('layouts.app')

@section('title', 'Bankverbindung bearbeiten – FinanzView')
@section('eyebrow', 'Bankverbindungen')
@section('page_title', 'Bearbeiten')

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-back-link :href="route('bank-connections.index')" label="Bankverbindungen" />

    <x-page-header :title="$connection->name" subtitle="Ändern sich Bank, Adresse oder Anmeldename, wird die Verbindung neu eingerichtet." />

    <x-flash />

    @include('bank-connections._form')

    @if ($connection->linkedAccounts->isNotEmpty())
        <p class="px-1 text-[13px] text-slate-500 dark:text-slate-400">
            Verknüpft: {{ $connection->linkedAccounts->map(fn ($link) => $link->account->name . ' (' . $link->maskedIban() . ')')->implode(', ') }}
        </p>
    @endif

    @if ($connection->tan_mode_name)
        <p class="px-1 text-[13px] text-slate-500 dark:text-slate-400">
            TAN-Verfahren: {{ $connection->tan_mode_name }}@if ($connection->tan_medium) ({{ $connection->tan_medium }})@endif ·
            <a href="{{ route('bank-connections.setup', $connection) }}" class="fv-link">Verfahren oder Konten ändern</a>
        </p>
    @endif

    <form method="POST" action="{{ route('bank-connections.destroy', $connection) }}" onsubmit="return confirm('Bankverbindung entfernen? Abgerufene Buchungen bleiben erhalten.')" class="flex justify-center">
        @csrf
        @method('DELETE')
        <button type="submit" class="fv-btn text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10">
            <x-icon name="trash" class="w-4 h-4" />
            Bankverbindung entfernen
        </button>
    </form>

</div>

@endsection
