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

        @php
            $planned = $totals['income'] > 0 ? round($totals['expense'] / $totals['income'] * 100) : null;
            $barWidth = $planned === null ? ($totals['expense'] > 0 ? 100 : 0) : min($planned, 100);
            $over = $totals['balance'] < 0;
        @endphp

        <section class="fv-card relative overflow-hidden p-5 sm:p-6">
            <div aria-hidden="true" class="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full blur-3xl {{ $over ? 'bg-red-500/15' : 'bg-emerald-500/15' }}"></div>

            <div class="relative grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)] lg:items-center">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $over ? 'Fehlbetrag im Monat' : 'Bleibt im Monat übrig' }}</p>
                    <p class="mt-1 fv-display text-[38px] leading-none font-extrabold tabular-nums {{ $over ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                        {{ $over ? '−' : '+' }}{{ $money(abs($totals['balance'])) }}
                    </p>
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Plan für {{ $month->translatedFormat('F Y') }} – bucht nichts.</p>
                </div>

                <div>
                    <div class="flex items-baseline justify-between text-sm">
                        <span class="font-medium text-slate-700 dark:text-slate-200">Verplant</span>
                        <span class="tabular-nums text-slate-500 dark:text-slate-400">{{ $planned !== null ? $planned . ' % der Einkünfte' : '–' }}</span>
                    </div>
                    <div class="mt-2 h-3 overflow-hidden rounded-full bg-slate-200/70 dark:bg-white/10" role="img" aria-label="Anteil der Ausgaben an den Einkünften">
                        <div class="h-full rounded-full {{ $over ? 'bg-linear-to-r from-red-400 to-red-600' : 'bg-linear-to-r from-emerald-400 to-emerald-600' }}" style="width: {{ $barWidth }}%"></div>
                    </div>

                    <dl class="mt-4 grid grid-cols-2 gap-3 {{ ($totals['has_rates'] || $totals['has_loans']) ? 'sm:grid-cols-4' : 'sm:grid-cols-2' }}">
                        <div class="rounded-2xl bg-emerald-500/10 px-3 py-2.5">
                            <dt class="flex items-center gap-1.5 text-[11px] font-medium text-emerald-700 dark:text-emerald-300"><x-icon name="trending-up" class="w-3.5 h-3.5" /> Einkünfte</dt>
                            <dd class="mt-0.5 font-semibold tabular-nums text-slate-900 dark:text-white">{{ $money($totals['income']) }}</dd>
                        </div>
                        <div class="rounded-2xl bg-red-500/10 px-3 py-2.5">
                            <dt class="flex items-center gap-1.5 text-[11px] font-medium text-red-700 dark:text-red-300"><x-icon name="trending-down" class="w-3.5 h-3.5" /> Ausgaben</dt>
                            <dd class="mt-0.5 font-semibold tabular-nums text-slate-900 dark:text-white">{{ $money($totals['expense']) }}</dd>
                        </div>
                        @if ($totals['has_rates'])
                            <div class="rounded-2xl bg-slate-900/5 dark:bg-white/5 px-3 py-2.5">
                                <dt class="flex items-center gap-1.5 text-[11px] font-medium text-slate-500 dark:text-slate-400"><x-icon name="repeat" class="w-3.5 h-3.5" /> davon Raten</dt>
                                <dd class="mt-0.5 font-semibold tabular-nums text-slate-900 dark:text-white">{{ $money($totals['rates']) }}</dd>
                            </div>
                        @endif
                        @if ($totals['has_loans'])
                            <div class="rounded-2xl bg-slate-900/5 dark:bg-white/5 px-3 py-2.5">
                                <dt class="flex items-center gap-1.5 text-[11px] font-medium text-slate-500 dark:text-slate-400"><x-icon name="banknote" class="w-3.5 h-3.5" /> davon Kredite</dt>
                                <dd class="mt-0.5 font-semibold tabular-nums text-slate-900 dark:text-white">{{ $money($totals['loans']) }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>
        </section>


        <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1.7fr)_minmax(0,1fr)] gap-4 items-start">

            {{-- AUSGABEN IN GRUPPEN --}}

            <div class="space-y-4">
                @foreach ($plan->groups->reject->isIncome() as $group)
                    @include('plan._group', ['group' => $group, 'color' => $palette[$loop->index % count($palette)], 'incomeTotal' => $totals['income']])
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
                    @include('plan._group', ['group' => $group, 'color' => '#34d399', 'incomeTotal' => $totals['income']])
                @endforeach

                @if ($totals['groups']->where('sum', '>', 0)->isNotEmpty())
                    @php
                        $parts = $totals['groups']->where('sum', '>', 0)->sortByDesc('sum')->values();
                        $scale = max($totals['income'], $totals['expense'], 0.01);
                        $colorOf = fn ($group) => (preg_match('/^#[0-9a-fA-F]{6}$/', (string) $group->color) ? $group->color : null) ?? $palette[$plan->groups->reject->isIncome()->values()->search(fn ($g) => $g->id === $group->id) % count($palette)];
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
