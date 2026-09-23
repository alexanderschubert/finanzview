@extends('layouts.app')

@section('title', 'Kredite – FinanzView')
@section('eyebrow', 'Finanzplanung')
@section('page_title', 'Kredite')

@php
    $groups = [
        'Laufende Kredite' => $loans->where('is_active', true),
        'Abgeschlossen oder pausiert' => $loans->where('is_active', false),
    ];
@endphp

@section('content')

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-page-header title="Kredite" subtitle="Restschuld, Raten und Tilgungsfortschritt.">
        <a href="{{ route('loans.create') }}" class="fv-btn fv-btn-primary text-sm py-2.5">
            <x-icon name="plus" class="w-4 h-4" />
            Neuer Kredit
        </a>
    </x-page-header>

    <x-flash />

    @if ($loans->isEmpty())

        <div class="fv-card">
            <x-empty-state icon="banknote" title="Keine Kredite" :href="route('loans.create')" action="Kredit anlegen">
                Lege Kredite oder Finanzierungen an, um Restschuld und Tilgung im Blick zu behalten.
            </x-empty-state>
        </div>

    @else

        {{-- ÜBERSICHT --}}

        <div class="fv-card p-5 sm:p-6">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Offene Restschuld</p>
            <p class="mt-1 text-[34px] leading-tight font-semibold tracking-tight tabular-nums text-slate-900 dark:text-white">
                {{ number_format($totalRemaining, 2, ',', '.') }} €
            </p>

            <x-progress :value="$overallProgress" class="mt-4" />

            <div class="mt-2 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 tabular-nums">
                <span>{{ number_format($totalPaid, 2, ',', '.') }} € getilgt</span>
                <span>{{ number_format($overallProgress, 0, ',', '.') }} % von {{ number_format($totalPrincipal, 2, ',', '.') }} €</span>
            </div>

            <div class="mt-5 grid grid-cols-2 gap-4 border-t border-slate-100 dark:border-white/5 pt-4">
                <div>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Monatliche Raten</p>
                    <p class="mt-0.5 font-semibold tabular-nums text-slate-900 dark:text-white">{{ number_format($monthlyInstallments, 2, ',', '.') }} €</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Raten bezahlt</p>
                    <p class="mt-0.5 font-semibold tabular-nums text-slate-900 dark:text-white">
                        {{ $paidInstallments }}@if ($totalInstallments > 0) <span class="font-normal text-slate-400">von {{ $totalInstallments }}</span>@endif
                    </p>
                </div>
            </div>
        </div>


        {{-- KREDITE --}}

        @foreach ($groups as $group => $groupLoans)
            @continue($groupLoans->isEmpty())

            <section>
                <h3 class="px-1 pb-2 text-[13px] font-semibold text-slate-500 dark:text-slate-400">{{ $group }}</h3>

                <ul class="fv-card overflow-hidden divide-y divide-slate-100 dark:divide-white/5">
                    @foreach ($groupLoans as $loan)
                        @php
                            $progress = (float) ($loan->progress ?? 0);
                            $remaining = (float) ($loan->remaining_amount ?? 0);
                        @endphp

                        <li>
                            <a href="{{ route('loans.show', $loan) }}" class="block px-4 py-4 hover:bg-slate-50 dark:hover:bg-white/5 transition {{ $loan->is_active ? '' : 'opacity-70' }}">
                                <div class="flex items-center gap-3">
                                    <x-financial-provider :provider="$loan->provider" :fallback-icon="$loan->creditor_icon ?: '🏦'" size="sm" />

                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-slate-900 dark:text-white truncate">{{ $loan->name }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                            {{ $loan->creditor_name ?: 'Kredit' }}
                                            @if ((float) $loan->installment_amount > 0)
                                                · {{ number_format((float) $loan->installment_amount, 2, ',', '.') }} € / Monat
                                            @endif
                                            @if ($loan->end_date)
                                                · bis {{ $loan->end_date->format('m/Y') }}
                                            @endif
                                        </p>
                                    </div>

                                    <div class="text-right shrink-0">
                                        <p class="font-semibold tabular-nums text-slate-900 dark:text-white">{{ number_format($remaining, 2, ',', '.') }} €</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">
                                            @if ($loan->remaining_installments !== null)
                                                noch {{ $loan->remaining_installments }} {{ $loan->remaining_installments === 1 ? 'Rate' : 'Raten' }}
                                            @else
                                                offen
                                            @endif
                                        </p>
                                    </div>

                                    <x-icon name="chevron-right" class="w-4 h-4 text-slate-300 dark:text-slate-600" />
                                </div>

                                <div class="mt-3 flex items-center gap-3 pl-[52px]">
                                    <x-progress :value="$progress" class="flex-1 h-1.5" />
                                    <span class="w-10 text-right text-xs font-medium tabular-nums text-slate-500 dark:text-slate-400">{{ number_format($progress, 0, ',', '.') }} %</span>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach

    @endif

</div>

@endsection
