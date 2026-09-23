@extends('layouts.app')

@section('title', 'Wiederkehrende Buchungen – FinanzView')
@section('eyebrow', 'Finanzplanung')
@section('page_title', 'Wiederkehrend')

@php
    $frequencyLabels = [
        'weekly' => 'Wöchentlich',
        'monthly' => 'Monatlich',
        'quarterly' => 'Vierteljährlich',
        'yearly' => 'Jährlich',
    ];

    // Umrechnung auf einen Monat, um Verträge vergleichbar zu machen.
    $perMonth = [
        'weekly' => 52 / 12,
        'monthly' => 1,
        'quarterly' => 1 / 3,
        'yearly' => 1 / 12,
    ];

    $active = $recurringTransactions->where('is_active', true);

    $monthlyExpense = $active->where('type', 'expense')
        ->sum(fn ($item) => (float) $item->amount * ($perMonth[$item->frequency] ?? 1));

    $monthlyIncome = $active->where('type', 'income')
        ->sum(fn ($item) => (float) $item->amount * ($perMonth[$item->frequency] ?? 1));

    $groups = [
        'Aktiv' => $active,
        'Pausiert' => $recurringTransactions->where('is_active', false),
    ];
@endphp

@section('content')

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-page-header title="Wiederkehrend" subtitle="Daueraufträge, Abos und regelmäßige Einnahmen.">
        <a href="{{ route('recurring-transactions.create') }}" class="fv-btn fv-btn-primary text-sm py-2.5">
            <x-icon name="plus" class="w-4 h-4" />
            Neu
        </a>
    </x-page-header>

    <x-flash />

    @if ($recurringTransactions->isEmpty())

        <div class="fv-card">
            <x-empty-state icon="repeat" title="Noch nichts Wiederkehrendes" :href="route('recurring-transactions.create')" action="Wiederkehrende Buchung anlegen">
                Miete, Gehalt oder Streaming-Abo – FinanzView bucht sie automatisch zum Fälligkeitstag.
            </x-empty-state>
        </div>

    @else

        {{-- ÜBERSICHT PRO MONAT --}}

        <div class="grid grid-cols-2 gap-3">
            <x-stat label="Fixkosten pro Monat" icon="trending-down" tone="negative" hint="Aktive Ausgaben, umgerechnet">
                {{ number_format($monthlyExpense, 2, ',', '.') }} €
            </x-stat>

            <x-stat label="Regelmäßige Einnahmen" icon="trending-up" tone="positive" hint="Pro Monat, umgerechnet">
                {{ number_format($monthlyIncome, 2, ',', '.') }} €
            </x-stat>
        </div>


        {{-- LISTEN --}}

        @foreach ($groups as $group => $items)
            @continue($items->isEmpty())

            <section>
                <h3 class="px-1 pb-2 text-[13px] font-semibold text-slate-500 dark:text-slate-400">{{ $group }}</h3>

                <ul class="fv-card overflow-hidden divide-y divide-slate-100 dark:divide-white/5">
                    @foreach ($items as $recurring)
                        @php
                            $isIncome = $recurring->type === 'income';
                            $nextDate = $recurring->next_date;
                        @endphp

                        <li>
                            <a href="{{ route('recurring-transactions.show', $recurring) }}" class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-white/5 transition {{ $recurring->is_active ? '' : 'opacity-60' }}">
                                <x-emoji-tile :emoji="$recurring->category?->icon" fallback="repeat" />

                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-slate-900 dark:text-white truncate">{{ $recurring->description }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                        {{ $frequencyLabels[$recurring->frequency] ?? $recurring->frequency }}
                                        @if ($recurring->is_active && $nextDate)
                                            · nächste {{ $nextDate->isToday() ? 'heute' : ($nextDate->isTomorrow() ? 'morgen' : 'am ' . $nextDate->format('d.m.Y')) }}
                                        @endif
                                        @if ($recurring->account)
                                            · {{ $recurring->account->name }}
                                        @endif
                                    </p>
                                </div>

                                <p class="font-semibold tabular-nums whitespace-nowrap {{ $isIncome ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white' }}">
                                    {{ $isIncome ? '+' : '−' }}{{ number_format((float) $recurring->amount, 2, ',', '.') }} €
                                </p>

                                <x-icon name="chevron-right" class="w-4 h-4 text-slate-300 dark:text-slate-600" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach

    @endif

</div>

@endsection
