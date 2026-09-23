@extends('layouts.app')

@section('title', 'Kreditkarten – FinanzView')
@section('eyebrow', 'Finanzplanung')
@section('page_title', 'Kreditkarten')

@php
    $totalLimit = $creditCards->sum(fn ($card) => (float) ($card->credit_limit ?? 0));
    $totalBalance = $creditCards->sum(fn ($card) => (float) ($card->current_balance ?? 0));
    $totalAvailable = max(0, $totalLimit - $totalBalance);
    $overallUtilization = $totalLimit > 0 ? min(100, max(0, ($totalBalance / $totalLimit) * 100)) : 0;
@endphp

@section('content')

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-page-header title="Kreditkarten" subtitle="Saldo, Limit und Abrechnungen deiner Karten.">
        <a href="{{ route('credit-cards.create') }}" class="fv-btn fv-btn-primary text-sm py-2.5">
            <x-icon name="plus" class="w-4 h-4" />
            Neue Karte
        </a>
    </x-page-header>

    <x-flash />

    @if ($creditCards->isEmpty())

        <div class="fv-card">
            <x-empty-state icon="card" title="Noch keine Kreditkarten" :href="route('credit-cards.create')" action="Kreditkarte anlegen">
                Verwalte Limit, Saldo und monatliche Abrechnungen deiner Kreditkarten.
            </x-empty-state>
        </div>

    @else

        {{-- ÜBERSICHT --}}

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <x-stat label="Saldo">
                {{ number_format($totalBalance, 2, ',', '.') }} €
            </x-stat>

            <x-stat label="Verfügbar" tone="positive">
                {{ number_format($totalAvailable, 2, ',', '.') }} €
            </x-stat>

            <x-stat label="Auslastung" :tone="$overallUtilization >= 70 ? 'negative' : 'neutral'" :hint="'Limit ' . number_format($totalLimit, 0, ',', '.') . ' €'">
                {{ number_format($overallUtilization, 0, ',', '.') }} %
            </x-stat>
        </div>


        {{-- KARTEN --}}

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            @foreach ($creditCards as $creditCard)
                @php
                    $limit = (float) ($creditCard->credit_limit ?? 0);
                    $balance = (float) ($creditCard->current_balance ?? 0);
                    $available = max(0, $limit - $balance);
                    $utilization = $limit > 0 ? min(100, max(0, ($balance / $limit) * 100)) : 0;

                @endphp

                <a href="{{ route('credit-cards.show', $creditCard) }}" class="group block {{ $creditCard->is_active ? '' : 'opacity-60' }}">

                    <x-wallet-card
                        :color="$creditCard->color"
                        :fallback-color="$creditCard->provider?->color ?: '#3f3f45'"
                        :title="$creditCard->name"
                        :subtitle="$creditCard->issuer ?: ($creditCard->provider?->name ?: 'Kreditkarte')"
                        amount-label="Aktueller Saldo"
                        :amount="number_format($balance, 2, ',', '.') . ' €'"
                        :number="'•••• ' . ($creditCard->last_four ?: '····')"
                        :badge="$creditCard->is_active ? null : 'Inaktiv'"
                        :provider="$creditCard->provider"
                        class="transition group-hover:-translate-y-0.5 group-hover:shadow-xl"
                    />

                    {{-- Auslastung --}}
                    <div class="mt-3 px-1">
                        @if ($limit > 0)
                            <x-progress :value="$utilization" :tone="$utilization >= 90 ? 'negative' : ($utilization >= 70 ? 'warning' : 'positive')" class="h-1.5" />
                            <div class="mt-1.5 flex justify-between text-xs text-slate-500 dark:text-slate-400 tabular-nums">
                                <span>{{ number_format($available, 2, ',', '.') }} € verfügbar</span>
                                <span>{{ number_format($utilization, 0, ',', '.') }} % von {{ number_format($limit, 0, ',', '.') }} €</span>
                            </div>
                        @else
                            <p class="text-xs text-slate-500 dark:text-slate-400">Kein Limit hinterlegt</p>
                        @endif
                    </div>

                </a>
            @endforeach
        </div>

    @endif

</div>

@endsection
