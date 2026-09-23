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

                    $cardColor = is_string($creditCard->color) && preg_match('/^#[0-9a-fA-F]{6}$/', $creditCard->color)
                        ? $creditCard->color
                        : '#3f3f45';
                @endphp

                <a href="{{ route('credit-cards.show', $creditCard) }}" class="group block {{ $creditCard->is_active ? '' : 'opacity-60' }}">

                    {{-- Kartenoptik (Seitenverhältnis einer echten Karte) --}}
                    <div
                        class="relative aspect-[1.586] overflow-hidden rounded-2xl p-5 text-white shadow-lg shadow-slate-900/20 transition group-hover:-translate-y-0.5 group-hover:shadow-xl"
                        style="background: linear-gradient(135deg, {{ $cardColor }}, color-mix(in srgb, {{ $cardColor }} 55%, black));"
                    >
                        <div aria-hidden="true" class="absolute -right-12 -bottom-16 w-52 h-52 rounded-full bg-white/10"></div>
                        <div aria-hidden="true" class="absolute -right-2 -bottom-24 w-52 h-52 rounded-full bg-white/5"></div>

                        <div class="relative flex h-full flex-col justify-between">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-semibold truncate">{{ $creditCard->name }}</p>
                                    <p class="text-xs text-white/70 truncate">{{ $creditCard->issuer ?: $creditCard->provider?->name ?: 'Kreditkarte' }}</p>
                                </div>

                                @unless ($creditCard->is_active)
                                    <span class="rounded-full bg-white/20 px-2 py-0.5 text-[11px] font-medium">Inaktiv</span>
                                @endunless
                            </div>

                            <div>
                                <p class="text-xs text-white/70">Aktueller Saldo</p>
                                <p class="text-2xl font-semibold tracking-tight tabular-nums">{{ number_format($balance, 2, ',', '.') }} €</p>
                            </div>

                            <p class="font-mono text-sm tracking-[0.2em] text-white/80">
                                •••• {{ $creditCard->last_four ?: '····' }}
                            </p>
                        </div>
                    </div>

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
