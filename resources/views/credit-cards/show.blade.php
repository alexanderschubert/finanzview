@extends('layouts.app')

@section('title', $creditCard->name . ' – Kreditkarte – FinanzView')
@section('eyebrow', 'Kreditkarten')
@section('page_title', $creditCard->name)

@php
    $limit = (float) $creditCard->credit_limit;
    $balance = (float) $creditCard->current_balance;
    $available = $limit > 0 ? max(0, $limit - $balance) : null;
    $utilization = $limit > 0 ? min(100, max(0, ($balance / $limit) * 100)) : null;

    $statusLabels = [
        'open' => ['Offen', 'bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-slate-300'],
        'issued' => ['Abgerechnet', 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300'],
        'paid' => ['Bezahlt', 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300'],
        'overdue' => ['Überfällig', 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300'],
    ];

    $details = [
        'Herausgeber' => $creditCard->issuer,
        'Anbieter' => $creditCard->provider?->name,
        'Abbuchung vom Konto' => $creditCard->account?->name,
        'Abrechnungstag' => $creditCard->billing_day ? $creditCard->billing_day . '. des Monats' : null,
        'Fällig am' => $creditCard->payment_due_day ? $creditCard->payment_due_day . '. des Monats' : null,
    ];
@endphp

@section('content')

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-page-header :title="$creditCard->name" :subtitle="$creditCard->is_active ? 'Kreditkarte' : 'Kreditkarte · inaktiv'">
        <a href="{{ route('credit-cards.edit', $creditCard) }}" class="fv-btn fv-btn-secondary text-sm py-2.5">
            <x-icon name="pencil" class="w-4 h-4" />
            Bearbeiten
        </a>
    </x-page-header>

    <x-flash />


    {{-- KARTE UND AUSLASTUNG --}}

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 items-center">
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
        />

        @if ($utilization !== null)
            <div class="flex flex-col items-center gap-4">
                <x-progress-ring :value="$utilization" :tone="$utilization >= 90 ? 'negative' : ($utilization >= 70 ? 'warning' : 'positive')" :size="160" :stroke="14">
                    <p class="text-2xl font-semibold tabular-nums text-slate-900 dark:text-white">{{ number_format($utilization, 0, ',', '.') }} %</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">ausgelastet</p>
                </x-progress-ring>

                <p class="text-sm text-slate-500 dark:text-slate-400 tabular-nums">
                    <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ number_format($available, 2, ',', '.') }} €</span>
                    von {{ number_format($limit, 2, ',', '.') }} € verfügbar
                </p>
            </div>
        @else
            <div class="fv-card p-5 text-sm text-slate-500 dark:text-slate-400">
                Kein Kreditlimit hinterlegt. <a href="{{ route('credit-cards.edit', $creditCard) }}" class="fv-link">Limit eintragen</a>
            </div>
        @endif
    </div>


    {{-- DETAILS --}}

    <section>
        <h3 class="px-1 pb-2 text-[13px] font-semibold text-slate-500 dark:text-slate-400">Details</h3>

        <dl class="fv-card overflow-hidden divide-y divide-slate-100 dark:divide-white/5 text-sm">
            @foreach ($details as $label => $detail)
                <div class="flex items-center justify-between gap-4 px-4 py-3">
                    <dt class="text-slate-500 dark:text-slate-400">{{ $label }}</dt>
                    <dd class="font-medium text-right text-slate-900 dark:text-white">{{ $detail ?: '–' }}</dd>
                </div>
            @endforeach
        </dl>
    </section>


    {{-- ABRECHNUNGEN --}}

    <section>
        <h3 class="px-1 pb-2 text-[13px] font-semibold text-slate-500 dark:text-slate-400">Abrechnungen</h3>

        @if ($creditCard->statements->isEmpty())
            <div class="fv-card">
                <x-empty-state icon="calendar" title="Noch keine Abrechnungen">
                    FinanzView erstellt die monatliche Abrechnung automatisch am Abrechnungstag aus den Buchungen dieser Karte.
                </x-empty-state>
            </div>
        @else
            <ul class="fv-card overflow-hidden divide-y divide-slate-100 dark:divide-white/5">
                @foreach ($creditCard->statements->take(12) as $statement)
                    @php
                        [$statusLabel, $statusClass] = $statusLabels[$statement->status] ?? [ucfirst($statement->status), $statusLabels['open'][1]];
                    @endphp

                    <li class="flex items-center gap-3 px-4 py-3">
                        <x-emoji-tile fallback="calendar" />

                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-slate-900 dark:text-white">
                                {{ $statement->period_end?->translatedFormat('F Y') }}
                            </p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 tabular-nums">
                                {{ $statement->period_start?->format('d.m.') }} – {{ $statement->period_end?->format('d.m.Y') }}
                                @if ($statement->due_date)
                                    · fällig {{ $statement->due_date->format('d.m.Y') }}
                                @endif
                            </p>
                        </div>

                        <div class="text-right shrink-0">
                            <p class="font-semibold tabular-nums text-slate-900 dark:text-white">{{ number_format((float) $statement->amount, 2, ',', '.') }} €</p>
                            <span class="inline-block mt-0.5 rounded-full px-2 py-0.5 text-[11px] font-medium {{ $statusClass }}">{{ $statusLabel }}</span>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

</div>

@endsection
