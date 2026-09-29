@extends('layouts.app')

@section('title', 'Monatsbericht – FinanzView')
@section('eyebrow', 'Auswertung')
@section('page_title', 'Monatsbericht')

@php
    $money = fn ($value) => number_format((float) $value, 2, ',', '.') . ' €';
    $totals = $report['totals'];
    $previous = $report['previous_totals'];
    $average = $report['average_totals'];

    $compare = function (string $key) use ($previous, $average, $money) {
        $parts = ['Vormonat ' . $money($previous[$key])];

        if ($average !== null) {
            $parts[] = 'Ø ' . $money($average[$key]);
        }

        return implode(' · ', $parts);
    };

    $tones = [
        'positive' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400',
        'negative' => 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400',
        'neutral' => 'bg-slate-100 text-slate-500 dark:bg-white/5 dark:text-slate-400',
    ];
@endphp

@section('content')

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-page-header title="Monatsbericht" :subtitle="$report['average_months'] > 0 ? 'Verglichen mit dem Vormonat und deinem üblichen Monat (Ø der letzten ' . $report['average_months'] . ' Monate).' : 'Dein Monat auf einen Blick.'">
        <x-month-switcher route="reports.month" :month="$monthKey" />
    </x-page-header>

    @unless ($report['has_data'])

        <div class="fv-card">
            <x-empty-state icon="calendar" title="Keine Buchungen in diesem Monat" :href="route('transactions.import.create')" action="Kontoauszug importieren">
                Sobald Einnahmen oder Ausgaben erfasst sind, erscheint hier dein Monatsbericht.
            </x-empty-state>
        </div>

    @else

        {{-- KENNZAHLEN --}}

        <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
            <x-stat label="Einnahmen" icon="trending-up" tone="positive" :hint="$compare('income')">{{ $money($totals['income']) }}</x-stat>
            <x-stat label="Ausgaben" icon="trending-down" tone="negative" :hint="$compare('expense')">{{ $money($totals['expense']) }}</x-stat>
            <x-stat label="Übrig" icon="wallet" :tone="$totals['balance'] < 0 ? 'negative' : 'neutral'" class="col-span-2 lg:col-span-1"
                :hint="$totals['savings_rate'] !== null ? 'Sparquote ' . number_format($totals['savings_rate'], 1, ',', '.') . ' %' : null">
                {{ $totals['balance'] < 0 ? '−' : '' }}{{ $money(abs($totals['balance'])) }}
            </x-stat>
        </div>


        {{-- AUFFÄLLIGKEITEN --}}

        @if ($report['insights'] !== [])
            <section>
                <h3 class="px-1 pb-2 text-[13px] font-semibold text-slate-500 dark:text-slate-400">Auffällig</h3>
                <ul class="fv-card divide-y divide-slate-100 dark:divide-white/5">
                    @foreach ($report['insights'] as $insight)
                        <li class="flex items-start gap-3 px-4 py-3">
                            <span class="w-8 h-8 shrink-0 rounded-lg flex items-center justify-center {{ $tones[$insight['tone']] ?? $tones['neutral'] }}">
                                <x-icon :name="$insight['icon']" class="w-4 h-4" />
                            </span>
                            <p class="pt-1 text-sm text-slate-700 dark:text-slate-200">{{ $insight['text'] }}</p>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif


        {{-- KATEGORIEN --}}

        @if ($report['categories']->isNotEmpty())
            <section>
                <h3 class="px-1 pb-2 text-[13px] font-semibold text-slate-500 dark:text-slate-400">Ausgaben nach Kategorie</h3>
                <ul class="fv-card divide-y divide-slate-100 dark:divide-white/5">
                    @foreach ($report['categories'] as $category)
                        @php
                            $ratio = $category['average'] ? $category['difference'] / $category['average'] : null;
                            $notable = $ratio !== null && abs($category['difference']) >= 20 && abs($ratio) >= 0.25;
                        @endphp
                        <li class="px-4 py-3">
                            <a href="{{ route('transactions.index', ['month' => $monthKey, 'category_id' => $category['id'], 'type' => 'expense']) }}" class="flex items-center gap-3">
                                <x-emoji-tile :emoji="$category['icon']" fallback="tag" :color="$category['color']" size="sm" />

                                <div class="flex-1 min-w-0">
                                    <div class="flex items-baseline justify-between gap-3">
                                        <p class="font-medium text-slate-900 dark:text-white truncate">{{ $category['name'] }}</p>
                                        <p class="shrink-0 font-semibold tabular-nums text-slate-900 dark:text-white">{{ $money($category['amount']) }}</p>
                                    </div>

                                    <div class="mt-1.5 h-1.5 rounded-full bg-slate-100 dark:bg-white/5 overflow-hidden">
                                        <div class="h-full rounded-full bg-emerald-500" style="width: {{ min(100, $category['share']) }}%"></div>
                                    </div>

                                    <div class="mt-1 flex items-center justify-between gap-3 text-xs text-slate-500 dark:text-slate-400">
                                        <span>{{ number_format($category['share'], 0, ',', '.') }} % · {{ $category['count'] === 1 ? '1 Buchung' : $category['count'] . ' Buchungen' }}</span>
                                        @if ($category['average'] !== null)
                                            <span class="tabular-nums {{ $notable ? ($ratio > 0 ? 'text-red-600 dark:text-red-400 font-medium' : 'text-emerald-600 dark:text-emerald-400 font-medium') : '' }}">
                                                Ø {{ $money($category['average']) }}
                                                @if ($category['average'] > 0 && $ratio !== null && abs($ratio) >= 0.05)
                                                    ({{ $ratio > 0 ? '+' : '−' }}{{ (int) round(abs($ratio) * 100) }} %)
                                                @elseif ($category['average'] == 0 && $category['amount'] > 0)
                                                    (neu)
                                                @endif
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif


        {{-- BUDGETS --}}

        @if ($report['budgets']->isNotEmpty())
            <section>
                <h3 class="px-1 pb-2 text-[13px] font-semibold text-slate-500 dark:text-slate-400">Budgets</h3>
                <ul class="fv-card divide-y divide-slate-100 dark:divide-white/5">
                    @foreach ($report['budgets'] as $item)
                        @php
                            [$budget, $result] = [$item['budget'], $item['result']];
                            $percent = (float) $result['percentage'];
                        @endphp
                        <li class="px-4 py-3">
                            <a href="{{ route('budgets.show', $budget) }}" class="block">
                                <div class="flex items-baseline justify-between gap-3">
                                    <p class="font-medium text-slate-900 dark:text-white truncate">{{ $budget->icon }} {{ $budget->name }}</p>
                                    <p class="shrink-0 text-sm tabular-nums {{ $result['exceeded'] ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-slate-600 dark:text-slate-300' }}">
                                        {{ $money($result['spent']) }} von {{ $money($budget->amount) }}
                                    </p>
                                </div>
                                <x-progress :value="min(100, $percent)" :tone="$result['exceeded'] ? 'negative' : ($percent >= 80 ? 'warning' : 'positive')" class="mt-2 h-1.5" />
                                <p class="mt-1 text-xs {{ $result['exceeded'] ? 'text-red-600 dark:text-red-400' : 'text-slate-500 dark:text-slate-400' }}">
                                    {{ $result['exceeded'] ? 'Überschritten um ' . $money(abs($result['remaining'])) : 'Noch ' . $money($result['remaining']) . ' übrig' }}
                                </p>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif


        <div class="grid gap-6 sm:grid-cols-2">

            {{-- GRÖSSTE AUSGABEN --}}

            @if ($report['largest']->isNotEmpty())
                <section>
                    <h3 class="px-1 pb-2 text-[13px] font-semibold text-slate-500 dark:text-slate-400">Größte Ausgaben</h3>
                    <ul class="fv-card divide-y divide-slate-100 dark:divide-white/5">
                        @foreach ($report['largest'] as $row)
                            <li>
                                <a href="{{ route('transactions.edit', $row['id']) }}" class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 dark:hover:bg-white/5 transition">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ $row['title'] }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $row['date']->format('d.m.') }} · {{ $row['category_name'] }}</p>
                                    </div>
                                    <p class="shrink-0 text-sm font-semibold tabular-nums text-slate-900 dark:text-white">−{{ $money($row['amount']) }}</p>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif


            {{-- NEUE HÄNDLER --}}

            @if ($report['new_merchants']->isNotEmpty())
                <section>
                    <h3 class="px-1 pb-2 text-[13px] font-semibold text-slate-500 dark:text-slate-400">
                        Zum ersten Mal
                        <span class="font-normal text-slate-400 dark:text-slate-500">· {{ $report['new_merchants']->count() }}</span>
                    </h3>
                    <ul class="fv-card divide-y divide-slate-100 dark:divide-white/5">
                        @foreach ($report['new_merchants']->take(5) as $merchant)
                            <li class="flex items-center gap-3 px-4 py-2.5">
                                <p class="flex-1 min-w-0 text-sm font-medium text-slate-900 dark:text-white truncate">{{ $merchant['title'] }}</p>
                                <p class="shrink-0 text-sm tabular-nums text-slate-600 dark:text-slate-300">−{{ $money($merchant['amount']) }}</p>
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-2 px-1 text-[13px] text-slate-500 dark:text-slate-400">Händler, bei denen du in den 12 Monaten davor nichts ausgegeben hast – praktisch, um neue Abos zu entdecken.</p>
                </section>
            @endif

        </div>

        <p class="px-1 text-[13px] text-slate-500 dark:text-slate-400">
            {{ $report['transaction_count'] }} Buchungen · Umbuchungen zählen nicht mit ·
            <a href="{{ route('reports.index', ['period' => 'custom', 'from' => $report['month']->copy()->startOfMonth()->toDateString(), 'to' => $report['month']->copy()->endOfMonth()->toDateString()]) }}" class="fv-link">Ausführliche Analyse</a>
        </p>

    @endunless

</div>

@endsection
