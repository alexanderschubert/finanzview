@extends('layouts.app')

@section('title', 'Monatsplan – FinanzView')
@section('eyebrow', 'Planung')
@section('page_title', 'Monatsplan')

@php
    $money = fn ($value) => number_format((float) $value, 2, ',', '.') . ' €';
    $palette = ['#38bdf8', '#fb923c', '#a78bfa', '#34d399', '#fbbf24', '#f472b6', '#94a3b8', '#2dd4bf', '#f87171', '#c084fc', '#60a5fa'];
@endphp

@section('content')

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-page-header title="Monatsplan" subtitle="Deine festen Einnahmen und Ausgaben pro Monat. Der Plan bucht nichts.">
        @if ($plan)
            <a href="{{ route('plan.export', ['month' => $monthKey]) }}" class="fv-btn fv-btn-secondary text-sm py-2">
                <x-icon name="download" class="w-4 h-4" />
                CSV exportieren
            </a>
        @endif
        <x-month-switcher route="plan.index" :month="$monthKey" />
    </x-page-header>

    <x-flash />

    @if ($errors->any())
        <div class="flex gap-3 rounded-2xl bg-red-50 dark:bg-red-500/10 p-4 text-sm text-red-700 dark:text-red-300" role="alert">
            <x-icon name="alert" class="w-5 h-5" />
            <p>{{ $errors->first() }}</p>
        </div>
    @endif

    @if (! $plan)

        <div class="fv-card">
            <x-empty-state icon="layout" :title="'Noch kein Plan für ' . $month->translatedFormat('F Y')">
                Lege den Plan an, um feste Einnahmen und Ausgaben des Monats in Gruppen zu sammeln.
            </x-empty-state>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-3 px-6 pb-8">
                @if ($previous)
                    <form method="POST" action="{{ route('plan.store') }}">
                        @csrf
                        <input type="hidden" name="month" value="{{ $monthKey }}">
                        <input type="hidden" name="source" value="previous">
                        <button type="submit" class="fv-btn fv-btn-primary w-full">
                            Aus {{ $previous->month->translatedFormat('F Y') }} übernehmen
                        </button>
                    </form>
                @endif

                <form method="POST" action="{{ route('plan.store') }}">
                    @csrf
                    <input type="hidden" name="month" value="{{ $monthKey }}">
                    <button type="submit" class="fv-btn {{ $previous ? 'fv-btn-secondary' : 'fv-btn-primary' }} w-full">Leeren Plan anlegen</button>
                </form>

                <a href="{{ route('plan.import', ['month' => $monthKey]) }}" class="fv-btn fv-btn-secondary">
                    <x-icon name="upload" class="w-4 h-4" />
                    CSV importieren
                </a>
            </div>

            @if ($previous)
                <p class="px-6 pb-6 text-center text-xs text-slate-500 dark:text-slate-400">
                    Beim Übernehmen fallen Posten weg, deren letzte Rate vor diesem Monat liegt.
                </p>
            @endif
        </div>

    @else

        {{-- KENNZAHLEN --}}

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <x-stat label="Saldo im Monat" icon="wallet" :tone="$totals['balance'] < 0 ? 'negative' : 'positive'" class="col-span-2 lg:col-span-2">
                {{ $totals['balance'] < 0 ? '−' : '+' }}{{ $money(abs($totals['balance'])) }}
            </x-stat>
            <x-stat label="Einkünfte" icon="trending-up" tone="positive">{{ $money($totals['income']) }}</x-stat>
            <x-stat label="Ausgaben" icon="trending-down" tone="negative">{{ $money($totals['expense']) }}</x-stat>

            @if ($totals['has_rates'])
                <x-stat label="Raten" icon="repeat" class="col-span-1">{{ $money($totals['rates']) }}</x-stat>
            @endif

            @if ($totals['has_loans'])
                <x-stat label="Kredite" icon="banknote" class="col-span-1">{{ $money($totals['loans']) }}</x-stat>
            @endif
        </div>


        <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1.7fr)_minmax(0,1fr)] gap-4 items-start">

            {{-- AUSGABEN IN GRUPPEN --}}

            <div class="space-y-4">
                @foreach ($plan->groups->reject->isIncome() as $group)
                    @include('plan._group', ['group' => $group, 'color' => $palette[$loop->index % count($palette)]])
                @endforeach

                <form method="POST" action="{{ route('plan.groups.store', $plan) }}" class="fv-card p-4 flex flex-col sm:flex-row gap-3">
                    @csrf
                    <input type="text" name="name" required maxlength="255" placeholder="Neue Gruppe, z. B. Versicherung" aria-label="Name der neuen Gruppe" class="fv-input flex-1 min-w-0">
                    <select name="kind" aria-label="Art der Gruppe" class="fv-input sm:w-44">
                        @foreach ($kinds as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="fv-btn fv-btn-secondary">
                        <x-icon name="plus" class="w-4 h-4" />
                        Gruppe
                    </button>
                </form>
            </div>


            {{-- EINNAHMEN UND VERTEILUNG --}}

            <div class="space-y-4 lg:sticky lg:top-4">
                @foreach ($plan->groups->filter->isIncome() as $group)
                    @include('plan._group', ['group' => $group, 'color' => '#34d399'])
                @endforeach

                @if ($totals['groups']->where('sum', '>', 0)->isNotEmpty())
                    @php
                        $parts = $totals['groups']->where('sum', '>', 0)->sortByDesc('sum')->values();
                        $scale = max($totals['income'], $totals['expense'], 0.01);
                        $colorOf = fn ($group) => $palette[$plan->groups->reject->isIncome()->values()->search(fn ($g) => $g->id === $group->id) % count($palette)];
                    @endphp

                    <section class="fv-card p-5">
                        <h3 class="font-semibold text-slate-900 dark:text-white">Wohin das Geld geht</h3>

                        <div class="mt-3 flex h-3 overflow-hidden rounded-full bg-slate-200/70 dark:bg-white/10" role="img" aria-label="Anteil der Ausgaben an den Einkünften">
                            @foreach ($parts as $part)
                                <span title="{{ $part['group']->name }}" style="width: {{ round($part['sum'] / $scale * 100, 2) }}%; background-color: {{ $colorOf($part['group']) }}"></span>
                            @endforeach
                        </div>

                        @if ($totals['income'] > 0)
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ round($totals['expense'] / $totals['income'] * 100) }} % der Einkünfte sind verplant.</p>
                        @endif

                        <ul class="mt-4 space-y-3">
                            @foreach ($parts as $part)
                                <li>
                                    <div class="flex items-baseline justify-between gap-3 text-sm">
                                        <span class="truncate text-slate-700 dark:text-slate-200">{{ $part['group']->name }}</span>
                                        <span class="tabular-nums text-slate-500 dark:text-slate-400">{{ $money($part['sum']) }}</span>
                                    </div>
                                    <x-progress :value="$part['sum'] / $parts->first()['sum'] * 100" class="mt-1.5" />
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                <div class="flex flex-wrap justify-center gap-2 text-sm">
                    <a href="{{ route('plan.import', ['month' => $monthKey]) }}" class="fv-link">CSV importieren</a>
                    <span class="text-slate-300 dark:text-slate-600" aria-hidden="true">·</span>
                    <form method="POST" action="{{ route('plan.destroy', $plan) }}" onsubmit="return confirm('Plan für diesen Monat löschen? Buchungen sind davon nicht betroffen.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 dark:text-red-400 hover:underline">Plan löschen</button>
                    </form>
                </div>
            </div>

        </div>

    @endif

</div>

@endsection
