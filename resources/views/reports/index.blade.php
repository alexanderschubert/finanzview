@extends('layouts.app')

@section('title', 'Analysen – FinanzView')

@section('eyebrow', 'Auswertungen')

@section('page_title', 'Analysen')

@php
    $money = fn ($value) => number_format((float) $value, 2, ',', '.') . ' €';

    $percent = fn ($value) => $value === null
        ? '–'
        : ($value > 0 ? '+' : '') . number_format((float) $value, 1, ',', '.') . ' %';

    /*
     * Farbe für Veränderungen. Bei Ausgaben ist ein Anstieg
     * schlecht, bei Einnahmen/Saldo gut.
     */
    $changeClass = function ($value, bool $higherIsBetter = true) {
        if ($value === null || abs($value) < 0.05) {
            return 'text-slate-500 dark:text-slate-400';
        }

        $good = $higherIsBetter ? $value > 0 : $value < 0;

        return $good
            ? 'text-emerald-600 dark:text-emerald-400'
            : 'text-rose-600 dark:text-rose-400';
    };

    $isHex = fn ($color) => is_string($color) && preg_match('/^#[0-9a-fA-F]{3,8}$/', $color);

    $totals = $report['totals'];
    $previousTotals = $report['previous_totals'];
    $changes = $report['changes'];

    $monthlyMax = max(
        1,
        $report['monthly']->max('income'),
        $report['monthly']->max('expense')
    );

    $periodLabel = $report['start']->format('d.m.Y') . ' – ' . $report['end']->format('d.m.Y');
    $previousLabel = $report['previous_start']->format('d.m.Y') . ' – ' . $report['previous_end']->format('d.m.Y');

    $card = 'bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm break-inside-avoid';
    $field = 'rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-3 text-sm text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500';
@endphp

@section('content')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 print:py-0 print:px-0">

    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-6">

        <div class="min-w-0">

            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 flex-shrink-0"></span>
                <p class="text-sm font-medium text-emerald-600 dark:text-emerald-400">
                    Auswertungen
                </p>
            </div>

            <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900 dark:text-white mt-2">
                Analysen
            </h2>

            <p class="text-slate-500 dark:text-slate-400 mt-2">
                {{ $periodLabel }}
                <span class="text-slate-400 dark:text-slate-500">· verglichen mit {{ $previousLabel }}</span>
            </p>

        </div>

        <div class="flex flex-wrap gap-3 print:hidden">

            <a
                href="{{ route('reports.export', array_filter($filters)) }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-3 text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition"
            >
                ⬇️ CSV exportieren
            </a>

            <button
                type="button"
                onclick="window.print()"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-medium text-white hover:bg-emerald-700 transition"
            >
                🖨️ Drucken / PDF
            </button>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- FILTER --}}
    {{-- ========================================================= --}}

    <form
        method="GET"
        action="{{ route('reports.index') }}"
        class="{{ $card }} mt-6 p-4 sm:p-5 flex flex-col lg:flex-row lg:items-end gap-4 print:hidden"
        data-report-filter
    >

        <div class="flex-1 min-w-0">
            <label for="period" class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1.5">Zeitraum</label>
            <select id="period" name="period" class="w-full {{ $field }}" data-period-select>
                @foreach ($periods as $value => $label)
                    <option value="{{ $value }}" @selected($filters['period'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex gap-4 {{ $filters['period'] === 'custom' ? '' : 'hidden' }}" data-custom-range>
            <div>
                <label for="from" class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1.5">Von</label>
                <input id="from" type="date" name="from" value="{{ $filters['from'] }}" class="{{ $field }}">
            </div>
            <div>
                <label for="to" class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1.5">Bis</label>
                <input id="to" type="date" name="to" value="{{ $filters['to'] }}" class="{{ $field }}">
            </div>
        </div>

        <div class="flex-1 min-w-0">
            <label for="account_id" class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1.5">Konto</label>
            <select id="account_id" name="account_id" class="w-full {{ $field }}">
                <option value="">Alle Konten</option>
                @foreach ($accounts as $account)
                    <option value="{{ $account->id }}" @selected($filters['account_id'] === $account->id)>{{ $account->name }}</option>
                @endforeach
            </select>
        </div>

        <button
            type="submit"
            class="rounded-xl bg-slate-900 dark:bg-white px-5 py-3 text-sm font-medium text-white dark:text-slate-900 hover:bg-slate-800 dark:hover:bg-slate-100 transition"
        >
            Anzeigen
        </button>

    </form>

    <script>
        (function () {
            const form = document.querySelector('[data-report-filter]');
            if (!form) return;

            const select = form.querySelector('[data-period-select]');
            const range = form.querySelector('[data-custom-range]');

            select.addEventListener('change', function () {
                range.classList.toggle('hidden', select.value !== 'custom');

                if (select.value !== 'custom') {
                    form.submit();
                }
            });
        })();
    </script>


    @if ($report['transaction_count'] === 0)

        {{-- ========================================================= --}}
        {{-- LEERZUSTAND --}}
        {{-- ========================================================= --}}

        <div class="{{ $card }} mt-6 p-10 text-center">
            <div class="text-4xl mb-3">📊</div>
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Keine Buchungen in diesem Zeitraum</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Wähle einen anderen Zeitraum oder ein anderes Konto. Umbuchungen zwischen Konten zählen nicht als Einnahme oder Ausgabe.
            </p>
        </div>

    @else

    {{-- ========================================================= --}}
    {{-- KENNZAHLEN --}}
    {{-- ========================================================= --}}

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mt-6 print:grid-cols-4">

        @foreach ([
            ['label' => 'Einnahmen', 'value' => $money($totals['income']), 'previous' => $money($previousTotals['income']), 'change' => $changes['income'], 'better' => true, 'accent' => 'text-emerald-600 dark:text-emerald-400'],
            ['label' => 'Ausgaben', 'value' => $money($totals['expense']), 'previous' => $money($previousTotals['expense']), 'change' => $changes['expense'], 'better' => false, 'accent' => 'text-rose-600 dark:text-rose-400'],
            ['label' => 'Saldo', 'value' => $money($totals['balance']), 'previous' => $money($previousTotals['balance']), 'change' => $changes['balance'], 'better' => true, 'accent' => $totals['balance'] >= 0 ? 'text-slate-900 dark:text-white' : 'text-rose-600 dark:text-rose-400'],
        ] as $kpi)
            <div class="{{ $card }} p-5">
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ $kpi['label'] }}</p>
                <p class="text-2xl font-semibold tabular-nums mt-1 {{ $kpi['accent'] }}">{{ $kpi['value'] }}</p>
                <p class="text-xs mt-2 tabular-nums">
                    <span class="font-medium {{ $changeClass($kpi['change'], $kpi['better']) }}">{{ $percent($kpi['change']) }}</span>
                    <span class="text-slate-400 dark:text-slate-500">ggü. {{ $kpi['previous'] }}</span>
                </p>
            </div>
        @endforeach

        <div class="{{ $card }} p-5">
            <p class="text-sm text-slate-500 dark:text-slate-400">Sparquote</p>
            <p class="text-2xl font-semibold tabular-nums mt-1 text-slate-900 dark:text-white">
                {{ $totals['savings_rate'] === null ? '–' : number_format($totals['savings_rate'], 1, ',', '.') . ' %' }}
            </p>
            <p class="text-xs mt-2 text-slate-400 dark:text-slate-500 tabular-nums">
                Ø Ausgaben pro Monat: {{ $money($report['average_monthly_expense']) }}
            </p>
        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- MONATSVERLAUF --}}
    {{-- ========================================================= --}}

    <div class="{{ $card }} mt-5 p-6 sm:p-8">

        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Verlauf</p>
                <h3 class="text-xl font-semibold text-slate-900 dark:text-white mt-1">Einnahmen & Ausgaben pro Monat</h3>
            </div>
            <div class="flex items-center gap-4 text-xs text-slate-500 dark:text-slate-400">
                <div class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>Einnahmen</div>
                <div class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>Ausgaben</div>
            </div>
        </div>

        <div class="mt-8 overflow-x-auto">
            <div class="flex items-end gap-3 h-56 min-w-full" style="min-width: {{ max(1, $report['monthly']->count()) * 56 }}px">
                @foreach ($report['monthly'] as $month)
                    <div class="flex-1 min-w-[44px] h-full flex flex-col justify-end items-center group">
                        <div class="flex items-end gap-1 w-full h-full justify-center">
                            <div
                                class="w-1/2 max-w-[22px] rounded-t-md bg-emerald-500"
                                style="height: {{ round($month['income'] / $monthlyMax * 100, 2) }}%"
                                title="Einnahmen {{ $month['label'] }}: {{ $money($month['income']) }}"
                            ></div>
                            <div
                                class="w-1/2 max-w-[22px] rounded-t-md bg-rose-500"
                                style="height: {{ round($month['expense'] / $monthlyMax * 100, 2) }}%"
                                title="Ausgaben {{ $month['label'] }}: {{ $money($month['expense']) }}"
                            ></div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="flex gap-3 mt-2 border-t border-slate-100 dark:border-slate-800 pt-2" style="min-width: {{ max(1, $report['monthly']->count()) * 56 }}px">
                @foreach ($report['monthly'] as $month)
                    <div class="flex-1 min-w-[44px] text-center">
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $month['short_label'] }}</p>
                        <p class="text-[11px] tabular-nums font-medium {{ $month['balance'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                            {{ $month['balance'] >= 0 ? '+' : '−' }}{{ number_format(abs($month['balance']), 0, ',', '.') }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>

        <p class="text-xs text-slate-400 dark:text-slate-500 mt-3">Die Zahl unter jedem Monat ist der Saldo in €.</p>

    </div>


    {{-- ========================================================= --}}
    {{-- KATEGORIEN --}}
    {{-- ========================================================= --}}

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5 mt-5 print:grid-cols-1">

        @foreach ([
            ['title' => 'Ausgaben nach Kategorie', 'eyebrow' => 'Wofür', 'items' => $report['expense_categories'], 'bar' => 'bg-rose-500', 'better' => false],
            ['title' => 'Einnahmen nach Kategorie', 'eyebrow' => 'Woher', 'items' => $report['income_categories'], 'bar' => 'bg-emerald-500', 'better' => true],
        ] as $section)

            <div class="{{ $card }} p-6 sm:p-8">

                <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">{{ $section['eyebrow'] }}</p>
                <h3 class="text-xl font-semibold text-slate-900 dark:text-white mt-1">{{ $section['title'] }}</h3>

                @if ($section['items']->isEmpty())
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-6">Keine Buchungen.</p>
                @else
                    <ul class="mt-6 space-y-4">
                        @foreach ($section['items'] as $category)
                            <li>
                                <div class="flex items-center justify-between gap-3 text-sm">
                                    <span class="flex items-center gap-2 min-w-0">
                                        <span>{{ $category['icon'] }}</span>
                                        <span class="truncate font-medium text-slate-800 dark:text-slate-100">{{ $category['name'] }}</span>
                                        <span class="text-xs text-slate-400 dark:text-slate-500 flex-shrink-0">{{ $category['count'] }}×</span>
                                    </span>
                                    <span class="flex items-baseline gap-3 flex-shrink-0 tabular-nums">
                                        <span class="text-xs {{ $changeClass($category['change'], $section['better']) }}" title="Vorperiode: {{ $money($category['previous_amount']) }}">{{ $percent($category['change']) }}</span>
                                        <span class="font-semibold text-slate-900 dark:text-white">{{ $money($category['amount']) }}</span>
                                    </span>
                                </div>
                                <div class="mt-2 flex items-center gap-3">
                                    <div class="flex-1 h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                        <div
                                            class="h-full rounded-full {{ $isHex($category['color']) ? '' : $section['bar'] }}"
                                            style="width: {{ max(1, $category['share']) }}%; {{ $isHex($category['color']) ? 'background-color: ' . $category['color'] . ';' : '' }}"
                                        ></div>
                                    </div>
                                    <span class="w-12 text-right text-xs text-slate-500 dark:text-slate-400 tabular-nums">{{ number_format($category['share'], 1, ',', '.') }} %</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif

            </div>

        @endforeach

    </div>


    {{-- ========================================================= --}}
    {{-- KATEGORIEN × MONATE --}}
    {{-- ========================================================= --}}

    @if ($report['month_count'] > 1 && $report['category_matrix']['rows']->isNotEmpty())

        @php $matrixMax = max(0.01, $report['category_matrix']['max']); @endphp

        <div class="{{ $card }} mt-5 p-6 sm:p-8">

            <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Muster</p>
            <h3 class="text-xl font-semibold text-slate-900 dark:text-white mt-1">Ausgaben je Kategorie und Monat</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Je dunkler das Feld, desto höher die Ausgabe.</p>

            <div class="mt-6 overflow-x-auto">
                <table class="w-full text-sm border-separate border-spacing-1">
                    <thead>
                        <tr>
                            <th class="text-left font-medium text-slate-500 dark:text-slate-400 pr-3 sticky left-0 bg-white dark:bg-slate-900">Kategorie</th>
                            @foreach ($report['monthly'] as $month)
                                <th class="font-medium text-slate-500 dark:text-slate-400 text-xs px-1 whitespace-nowrap">{{ $month['short_label'] }}</th>
                            @endforeach
                            <th class="font-medium text-slate-500 dark:text-slate-400 text-right pl-3">Summe</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report['category_matrix']['rows'] as $row)
                            <tr>
                                <td class="pr-3 whitespace-nowrap text-slate-800 dark:text-slate-100 sticky left-0 bg-white dark:bg-slate-900">
                                    {{ $row['icon'] }} {{ $row['name'] }}
                                </td>
                                @foreach ($row['months'] as $key => $value)
                                    @php $intensity = $value > 0 ? 0.12 + 0.78 * ($value / $matrixMax) : 0; @endphp
                                    <td
                                        class="rounded-md text-center text-[11px] tabular-nums px-1.5 py-2 min-w-[52px] {{ $intensity > 0.55 ? 'text-white' : 'text-slate-600 dark:text-slate-300' }} {{ $value > 0 ? '' : 'bg-slate-50 dark:bg-slate-800/50' }}"
                                        @if ($value > 0) style="background-color: rgba(225, 29, 72, {{ round($intensity, 2) }})" @endif
                                        title="{{ $row['name'] }}: {{ $money($value) }}"
                                    >
                                        {{ $value > 0 ? number_format($value, 0, ',', '.') : '·' }}
                                    </td>
                                @endforeach
                                <td class="text-right pl-3 font-semibold tabular-nums text-slate-900 dark:text-white whitespace-nowrap">{{ $money($row['total']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>

    @endif


    {{-- ========================================================= --}}
    {{-- HÄNDLER & GRÖSSTE AUSGABEN --}}
    {{-- ========================================================= --}}

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5 mt-5 print:grid-cols-1">

        <div class="{{ $card }} p-6 sm:p-8">

            <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Wo</p>
            <h3 class="text-xl font-semibold text-slate-900 dark:text-white mt-1">Top-Händler</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Nach Händler, sonst nach Beschreibung gruppiert.</p>

            @if ($report['top_merchants']->isEmpty())
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-6">Keine Ausgaben.</p>
            @else
                <table class="w-full text-sm mt-6">
                    <thead>
                        <tr class="text-xs text-slate-500 dark:text-slate-400">
                            <th class="text-left font-medium pb-2">Händler</th>
                            <th class="text-right font-medium pb-2">Anzahl</th>
                            <th class="text-right font-medium pb-2 hidden sm:table-cell">Ø</th>
                            <th class="text-right font-medium pb-2">Summe</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($report['top_merchants'] as $merchant)
                            <tr>
                                <td class="py-2.5 pr-3 text-slate-800 dark:text-slate-100 truncate max-w-[180px]">{{ $merchant['name'] }}</td>
                                <td class="py-2.5 text-right tabular-nums text-slate-500 dark:text-slate-400">{{ $merchant['count'] }}</td>
                                <td class="py-2.5 text-right tabular-nums text-slate-500 dark:text-slate-400 hidden sm:table-cell">{{ $money($merchant['average']) }}</td>
                                <td class="py-2.5 text-right tabular-nums font-semibold text-slate-900 dark:text-white">{{ $money($merchant['amount']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

        </div>

        <div class="{{ $card }} p-6 sm:p-8">

            <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Einzelposten</p>
            <h3 class="text-xl font-semibold text-slate-900 dark:text-white mt-1">Größte Ausgaben</h3>

            @if ($report['largest_expenses']->isEmpty())
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-6">Keine Ausgaben.</p>
            @else
                <ul class="mt-6 divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($report['largest_expenses'] as $expense)
                        <li class="py-2.5 flex items-center justify-between gap-3 text-sm">
                            <div class="min-w-0">
                                <p class="truncate text-slate-800 dark:text-slate-100">{{ $expense['description'] ?: ($expense['merchant'] ?: 'Ausgabe') }}</p>
                                <p class="text-xs text-slate-400 dark:text-slate-500">{{ $expense['date']->format('d.m.Y') }} · {{ $expense['category'] }}</p>
                            </div>
                            <span class="font-semibold tabular-nums text-rose-600 dark:text-rose-400 flex-shrink-0">{{ $money($expense['amount']) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif

        </div>

    </div>

    <p class="text-xs text-slate-400 dark:text-slate-500 mt-6">
        {{ $report['transaction_count'] }} Buchungen ausgewertet. Umbuchungen zwischen eigenen Konten sind nicht enthalten.
    </p>

    @endif

</div>

@endsection
