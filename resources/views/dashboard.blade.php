@extends('layouts.app')

@section('title', 'Dashboard – Finanzblick')
@section('eyebrow', 'Finanzübersicht')
@section('page_title', 'Dashboard')

@section('content')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-6">

        <div>

            <div class="flex items-center gap-2">

                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>

                <p class="text-sm font-medium text-emerald-600">
                    Finanzübersicht
                </p>

            </div>

            <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900 mt-2">
                Hallo, {{ auth()->user()->name }}
            </h2>

            <p class="text-slate-500 mt-2">
                Hier ist deine finanzielle Übersicht für {{ $currentMonth }}.
            </p>

        </div>


        <div class="flex flex-col sm:flex-row gap-3">

            <form
                method="GET"
                action="{{ route('dashboard') }}"
                class="flex gap-2"
            >

                <input
                    type="month"
                    name="month"
                    value="{{ $selectedMonth }}"
                    class="
                        rounded-xl
                        border border-slate-200
                        bg-white
                        px-4 py-3
                        text-sm
                        text-slate-700
                        focus:outline-none
                        focus:ring-2
                        focus:ring-emerald-500/20
                        focus:border-emerald-500
                    "
                >

                <button
                    type="submit"
                    class="
                        rounded-xl
                        border border-slate-200
                        bg-white
                        px-4 py-3
                        text-sm
                        font-medium
                        text-slate-700
                        hover:bg-slate-50
                        transition
                    "
                >
                    Anzeigen
                </button>

            </form>


            <a
                href="{{ route('transactions.create') }}"
                class="
                    inline-flex
                    items-center
                    justify-center
                    rounded-xl
                    bg-slate-950
                    px-5 py-3
                    text-sm
                    font-medium
                    text-white
                    hover:bg-slate-800
                    transition
                "
            >
                <span class="mr-2 text-emerald-400">+</span>
                Neue Buchung
            </a>

        </div>

    </div>



    {{-- ========================================================= --}}
    {{-- KENNZAHLEN --}}
    {{-- ========================================================= --}}

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mt-8">


        {{-- VERMÖGEN --}}

        <div class="bg-slate-950 text-white rounded-3xl p-6 relative overflow-hidden">

            <div class="absolute -right-8 -top-8 w-32 h-32 rounded-full bg-emerald-500/10"></div>

            <div class="relative">

                <div class="flex items-center justify-between">

                    <p class="text-sm text-slate-400">
                        Gesamtvermögen
                    </p>

                    <div class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center">
                        💰
                    </div>

                </div>

                <p class="text-3xl font-semibold tracking-tight mt-5">

                    {{ number_format(
                        $totalBalance,
                        2,
                        ',',
                        '.'
                    ) }}

                    €

                </p>

                <p class="text-xs text-slate-500 mt-2">
                    Alle berücksichtigten Konten
                </p>

            </div>

        </div>


        {{-- EINNAHMEN --}}

        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6">

            <div class="flex items-center justify-between">

                <p class="text-sm text-slate-500">
                    Einnahmen
                </p>

                <div class="w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center">
                    ↗
                </div>

            </div>

            <p class="text-3xl font-semibold text-emerald-600 mt-5">

                +{{ number_format(
                    $monthlyIncome,
                    2,
                    ',',
                    '.'
                ) }}

                €

            </p>

            <p class="text-xs text-slate-400 mt-2">
                {{ $currentMonth }}
            </p>

        </div>


        {{-- AUSGABEN --}}

        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6">

            <div class="flex items-center justify-between">

                <p class="text-sm text-slate-500">
                    Ausgaben
                </p>

                <div class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center">
                    ↘
                </div>

            </div>

            <p class="text-3xl font-semibold text-red-600 mt-5">

                -{{ number_format(
                    $monthlyExpense,
                    2,
                    ',',
                    '.'
                ) }}

                €

            </p>

            <p class="text-xs text-slate-400 mt-2">
                {{ $currentMonth }}
            </p>

        </div>


        {{-- SPARQUOTE --}}

        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6">

            <div class="flex items-center justify-between">

                <p class="text-sm text-slate-500">
                    Sparquote
                </p>

                <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center">
                    %
                </div>

            </div>

            <p class="
                text-3xl
                font-semibold
                mt-5
                {{ $savingsRate >= 0
                    ? 'text-slate-900'
                    : 'text-red-600' }}
            ">

                {{ number_format(
                    $savingsRate,
                    1,
                    ',',
                    '.'
                ) }}

                %

            </p>

            <p class="text-xs text-slate-400 mt-2">
                Einnahmen minus Ausgaben
            </p>

        </div>

    </div>



    {{-- ========================================================= --}}
    {{-- MONATSSALDO + JAHRESWERTE --}}
    {{-- ========================================================= --}}

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-4">


        {{-- MONATSSALDO --}}

        <div class="
            lg:col-span-2
            rounded-3xl
            border
            p-6
            {{ $monthlyBalance >= 0
                ? 'bg-emerald-50 border-emerald-100'
                : 'bg-red-50 border-red-100' }}
        ">

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

                <div>

                    <p class="text-sm font-medium text-slate-600">
                        Monatssaldo
                    </p>

                    <p class="
                        text-3xl
                        font-semibold
                        mt-2
                        {{ $monthlyBalance >= 0
                            ? 'text-emerald-700'
                            : 'text-red-700' }}
                    ">

                        {{ $monthlyBalance >= 0 ? '+' : '' }}

                        {{ number_format(
                            $monthlyBalance,
                            2,
                            ',',
                            '.'
                        ) }}

                        €

                    </p>

                </div>


                <div class="
                    inline-flex
                    items-center
                    rounded-full
                    px-4 py-2
                    text-sm
                    font-medium
                    self-start
                    {{ $monthlyBalance >= 0
                        ? 'bg-white text-emerald-700'
                        : 'bg-white text-red-700' }}
                ">

                    {{ $monthlyBalance >= 0
                        ? 'Positiver Monat'
                        : 'Mehr Ausgaben als Einnahmen' }}

                </div>

            </div>

        </div>


        {{-- JAHRESWERTE --}}

        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6">

            <p class="text-sm font-medium text-slate-500">
                Dieses Jahr
            </p>

            <div class="mt-4 space-y-4">

                <div class="flex items-center justify-between">

                    <span class="text-sm text-slate-500">
                        Einnahmen
                    </span>

                    <span class="font-semibold text-emerald-600">
                        +{{ number_format(
                            $yearlyIncome,
                            2,
                            ',',
                            '.'
                        ) }} €
                    </span>

                </div>


                <div class="flex items-center justify-between">

                    <span class="text-sm text-slate-500">
                        Ausgaben
                    </span>

                    <span class="font-semibold text-red-600">
                        -{{ number_format(
                            $yearlyExpense,
                            2,
                            ',',
                            '.'
                        ) }} €
                    </span>

                </div>


                <div class="pt-3 border-t border-slate-100 flex items-center justify-between">

                    <span class="text-sm font-medium text-slate-700">
                        Jahressaldo
                    </span>

                    <span class="
                        font-semibold
                        {{ ($yearlyIncome - $yearlyExpense) >= 0
                            ? 'text-slate-900'
                            : 'text-red-600' }}
                    ">

                        {{ ($yearlyIncome - $yearlyExpense) >= 0 ? '+' : '' }}

                        {{ number_format(
                            $yearlyIncome - $yearlyExpense,
                            2,
                            ',',
                            '.'
                        ) }} €

                    </span>

                </div>

            </div>

        </div>

    </div>



    {{-- ========================================================= --}}
    {{-- EINNAHMEN / AUSGABEN --}}
    {{-- ========================================================= --}}

    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm mt-5 overflow-hidden">

        <div class="p-6 sm:p-8">

            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">

                <div>

                    <p class="text-xs font-medium uppercase tracking-wider text-emerald-600">
                        Entwicklung
                    </p>

                    <h3 class="text-xl font-semibold text-slate-900 mt-1">
                        Einnahmen & Ausgaben
                    </h3>

                    <p class="text-sm text-slate-500 mt-1">
                        Vergleich der letzten sechs Monate.
                    </p>

                </div>


                <div class="flex items-center gap-4 text-xs text-slate-500">

                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        Einnahmen
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                        Ausgaben
                    </div>

                </div>

            </div>


            @php

                $maxChartValue = max(
                    1,
                    $chartMonths->max(function ($month) {
                        return max(
                            $month['income'],
                            $month['expense']
                        );
                    })
                );

            @endphp


            <div class="space-y-7 mt-8">

                @foreach ($chartMonths as $chartMonth)

                    <div>

                        <div class="flex items-center justify-between mb-2">

                            <span class="text-sm font-medium text-slate-700">
                                {{ $chartMonth['label'] }}
                            </span>

                            <span class="
                                text-xs
                                font-medium
                                {{ $chartMonth['balance'] >= 0
                                    ? 'text-emerald-600'
                                    : 'text-red-600' }}
                            ">

                                Saldo

                                {{ $chartMonth['balance'] >= 0 ? '+' : '' }}

                                {{ number_format(
                                    $chartMonth['balance'],
                                    2,
                                    ',',
                                    '.'
                                ) }} €

                            </span>

                        </div>


                        {{-- EINNAHMEN --}}

                        <div class="flex items-center gap-3">

                            <span class="hidden sm:block w-20 text-xs text-slate-400">
                                Einnahmen
                            </span>

                            <div class="flex-1 h-3 bg-slate-100 rounded-full overflow-hidden">

                                <div
                                    class="h-full bg-emerald-500 rounded-full transition-all"
                                    style="width: {{ ($chartMonth['income'] / $maxChartValue) * 100 }}%"
                                ></div>

                            </div>

                            <span class="w-24 text-right text-xs font-medium text-slate-700">

                                {{ number_format(
                                    $chartMonth['income'],
                                    0,
                                    ',',
                                    '.'
                                ) }} €

                            </span>

                        </div>


                        {{-- AUSGABEN --}}

                        <div class="flex items-center gap-3 mt-2">

                            <span class="hidden sm:block w-20 text-xs text-slate-400">
                                Ausgaben
                            </span>

                            <div class="flex-1 h-3 bg-slate-100 rounded-full overflow-hidden">

                                <div
                                    class="h-full bg-red-500 rounded-full transition-all"
                                    style="width: {{ ($chartMonth['expense'] / $maxChartValue) * 100 }}%"
                                ></div>

                            </div>

                            <span class="w-24 text-right text-xs font-medium text-slate-700">

                                {{ number_format(
                                    $chartMonth['expense'],
                                    0,
                                    ',',
                                    '.'
                                ) }} €

                            </span>

                        </div>

                    </div>

                @endforeach

            </div>

        </div>

    </div>



    {{-- ========================================================= --}}
    {{-- VERMÖGENSENTWICKLUNG --}}
    {{-- ========================================================= --}}

    @php

        $wealthValues = $wealthMonths
            ->pluck('balance')
            ->map(fn ($value) => (float) $value);

        $wealthMax = max(
            1,
            $wealthValues->max()
        );

        $wealthMin = min(
            0,
            $wealthValues->min()
        );

        $wealthRange = $wealthMax - $wealthMin;

        if ($wealthRange <= 0) {
            $wealthRange = 1;
        }

        $svgWidth = 900;
        $svgHeight = 320;

        $paddingLeft = 10;
        $paddingRight = 10;
        $paddingTop = 25;
        $paddingBottom = 50;

        $innerWidth =
            $svgWidth -
            $paddingLeft -
            $paddingRight;

        $innerHeight =
            $svgHeight -
            $paddingTop -
            $paddingBottom;

        $wealthPoints = [];

        foreach ($wealthMonths as $index => $wealthMonth) {

            $count = max(
                $wealthMonths->count() - 1,
                1
            );

            $x =
                $paddingLeft +
                ($index / $count) *
                $innerWidth;

            $normalized =
                (
                    $wealthMonth['balance'] -
                    $wealthMin
                ) /
                $wealthRange;

            $y =
                $paddingTop +
                (1 - $normalized) *
                $innerHeight;

            $wealthPoints[] = [
                'x' => $x,
                'y' => $y,
                'balance' => $wealthMonth['balance'],
                'label' => $wealthMonth['label'],
                'full_label' => $wealthMonth['full_label'],
            ];
        }

        $wealthLinePoints = collect($wealthPoints)
            ->map(
                fn ($point) =>
                    $point['x'] . ',' . $point['y']
            )
            ->implode(' ');

        $wealthBottom =
            $paddingTop + $innerHeight;

        if (count($wealthPoints) > 0) {

            $wealthAreaPoints =
                $wealthLinePoints
                . ' '
                . $wealthPoints[count($wealthPoints) - 1]['x']
                . ','
                . $wealthBottom
                . ' '
                . $wealthPoints[0]['x']
                . ','
                . $wealthBottom;

        } else {

            $wealthAreaPoints = '';

        }

    @endphp


    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm mt-5 overflow-hidden">

        <div class="p-6 sm:p-8">

            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">

                <div>

                    <p class="text-xs font-medium uppercase tracking-wider text-emerald-600">
                        Vermögen
                    </p>

                    <h3 class="text-xl font-semibold text-slate-900 mt-1">
                        Vermögensentwicklung
                    </h3>

                    <p class="text-sm text-slate-500 mt-1">
                        Entwicklung deines Gesamtvermögens über die letzten sechs Monate.
                    </p>

                </div>


                <div class="sm:text-right">

                    <p class="text-xs text-slate-400">
                        Aktuell
                    </p>

                    <p class="text-xl font-semibold text-slate-900 mt-1">

                        {{ number_format(
                            $totalBalance,
                            2,
                            ',',
                            '.'
                        ) }} €

                    </p>

                </div>

            </div>


            @if ($wealthMonths->isNotEmpty())

                <div class="mt-8 overflow-x-auto">

                    <div class="min-w-[650px]">

                        <svg
                            viewBox="0 0 {{ $svgWidth }} {{ $svgHeight }}"
                            class="w-full h-auto"
                            preserveAspectRatio="none"
                        >

                            {{-- HILFSLINIEN --}}

                            @for ($i = 0; $i <= 4; $i++)

                                @php

                                    $lineY =
                                        $paddingTop +
                                        ($i / 4) *
                                        $innerHeight;

                                @endphp

                                <line
                                    x1="{{ $paddingLeft }}"
                                    y1="{{ $lineY }}"
                                    x2="{{ $svgWidth - $paddingRight }}"
                                    y2="{{ $lineY }}"
                                    stroke="#e2e8f0"
                                    stroke-width="1"
                                />

                            @endfor


                            {{-- FLÄCHE --}}

                            <polygon
                                points="{{ $wealthAreaPoints }}"
                                fill="#10b981"
                                opacity="0.08"
                            />


                            {{-- LINIE --}}

                            <polyline
                                points="{{ $wealthLinePoints }}"
                                fill="none"
                                stroke="#10b981"
                                stroke-width="4"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />


                            {{-- PUNKTE --}}

                            @foreach ($wealthPoints as $point)

                                <circle
                                    cx="{{ $point['x'] }}"
                                    cy="{{ $point['y'] }}"
                                    r="6"
                                    fill="white"
                                    stroke="#10b981"
                                    stroke-width="3"
                                />

                            @endforeach


                            {{-- MONATE --}}

                            @foreach ($wealthPoints as $point)

                                <text
                                    x="{{ $point['x'] }}"
                                    y="{{ $svgHeight - 15 }}"
                                    text-anchor="middle"
                                    font-size="13"
                                    fill="#94a3b8"
                                >
                                    {{ $point['label'] }}
                                </text>

                            @endforeach

                        </svg>

                    </div>

                </div>


                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mt-6">

                    @foreach ($wealthMonths as $wealthMonth)

                        <div class="rounded-2xl bg-slate-50 px-3 py-3">

                            <p class="text-xs text-slate-400">
                                {{ $wealthMonth['full_label'] }}
                            </p>

                            <p class="
                                text-sm
                                font-semibold
                                mt-1
                                {{ $wealthMonth['balance'] >= 0
                                    ? 'text-slate-900'
                                    : 'text-red-600' }}
                            ">

                                {{ number_format(
                                    $wealthMonth['balance'],
                                    2,
                                    ',',
                                    '.'
                                ) }} €

                            </p>

                        </div>

                    @endforeach

                </div>

            @else

                <div class="mt-8 rounded-2xl bg-slate-50 p-8 text-center">

                    <p class="text-sm text-slate-500">
                        Noch keine Vermögensdaten vorhanden.
                    </p>

                </div>

            @endif

        </div>

    </div>



    {{-- ========================================================= --}}
    {{-- BUDGETS --}}
    {{-- ========================================================= --}}

    <div class="mt-5">

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">

            <div>

                <p class="text-xs font-medium uppercase tracking-wider text-emerald-600">
                    Planung
                </p>

                <h3 class="text-xl font-semibold text-slate-900 mt-1">
                    Deine Budgets
                </h3>

                <p class="text-sm text-slate-500 mt-1">
                    Budgetverbrauch für {{ $currentMonth }}
                </p>

            </div>


            <a
                href="{{ route('budgets.index') }}"
                class="text-sm font-medium text-slate-500 hover:text-slate-900 transition"
            >
                Alle Budgets →
            </a>

        </div>


        @if ($budgets->isEmpty())

            <div class="
                bg-white
                rounded-3xl
                border border-slate-100
                shadow-sm
                p-10
                text-center
            ">

                <div class="w-14 h-14 mx-auto rounded-2xl bg-emerald-50 flex items-center justify-center text-2xl">
                    🎯
                </div>

                <h4 class="font-semibold text-slate-900 mt-4">
                    Noch keine Budgets
                </h4>

                <p class="text-sm text-slate-500 mt-1">
                    Lege dein erstes Budget an, um deine Ausgaben besser zu planen.
                </p>

                <a
                    href="{{ route('budgets.create') }}"
                    class="
                        inline-flex
                        mt-5
                        rounded-xl
                        bg-slate-950
                        px-5 py-3
                        text-sm
                        font-medium
                        text-white
                        hover:bg-slate-800
                        transition
                    "
                >
                    + Budget erstellen
                </a>

            </div>

        @else

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">

                @foreach ($budgets->take(3) as $budget)

                    <a
                        href="{{ route('budgets.show', $budget) }}"
                        class="
                            block
                            bg-white
                            rounded-3xl
                            border border-slate-100
                            shadow-sm
                            p-5
                            hover:shadow-md
                            hover:-translate-y-0.5
                            transition
                        "
                    >

                        <div class="flex items-center gap-3">

                            <div
                                class="
                                    w-11 h-11
                                    rounded-2xl
                                    flex items-center justify-center
                                    text-xl
                                    flex-shrink-0
                                "
                                style="background-color: {{ $budget->color ?: '#ecfdf5' }}"
                            >
                                {{ $budget->icon ?: '🎯' }}
                            </div>


                            <div class="flex-1 min-w-0">

                                <p class="font-semibold text-slate-900 truncate">
                                    {{ $budget->name }}
                                </p>

                                <p class="text-xs text-slate-400 mt-1">

                                    Budget:

                                    {{ number_format(
                                        $budget->amount,
                                        2,
                                        ',',
                                        '.'
                                    ) }} €

                                </p>

                            </div>


                            @if ($budget->calculated_exceeded)

                                <span class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-600">
                                    Überschritten
                                </span>

                            @elseif ($budget->calculated_percentage >= 80)

                                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-600">
                                    Achtung
                                </span>

                            @else

                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-600">
                                    OK
                                </span>

                            @endif

                        </div>


                        <div class="mt-6">

                            <div class="flex items-center justify-between">

                                <span class="text-sm text-slate-500">
                                    Verbrauch
                                </span>

                                <span class="text-sm font-semibold text-slate-900">

                                    {{ number_format(
                                        $budget->calculated_spent,
                                        2,
                                        ',',
                                        '.'
                                    ) }} €

                                </span>

                            </div>


                            <div class="h-3 bg-slate-100 rounded-full overflow-hidden mt-3">

                                <div
                                    class="
                                        h-full
                                        rounded-full
                                        {{ $budget->calculated_exceeded
                                            ? 'bg-red-500'
                                            : (
                                                $budget->calculated_percentage >= 80
                                                    ? 'bg-amber-500'
                                                    : 'bg-emerald-500'
                                            ) }}
                                    "
                                    style="width: {{ min($budget->calculated_percentage, 100) }}%"
                                ></div>

                            </div>


                            <div class="flex items-center justify-between mt-2">

                                <span class="text-xs text-slate-400">

                                    {{ number_format(
                                        $budget->calculated_percentage,
                                        1,
                                        ',',
                                        '.'
                                    ) }} %

                                </span>


                                @if ($budget->calculated_remaining >= 0)

                                    <span class="text-xs text-emerald-600">

                                        Noch

                                        {{ number_format(
                                            $budget->calculated_remaining,
                                            2,
                                            ',',
                                            '.'
                                        ) }} €

                                    </span>

                                @else

                                    <span class="text-xs font-medium text-red-600">

                                        {{ number_format(
                                            abs($budget->calculated_remaining),
                                            2,
                                            ',',
                                            '.'
                                        ) }} € über Budget

                                    </span>

                                @endif

                            </div>

                        </div>


                        @if ($budget->categories->isNotEmpty())

                            <div class="flex flex-wrap gap-2 mt-5">

                                @foreach ($budget->categories->take(3) as $category)

                                    <span class="
                                        rounded-full
                                        bg-slate-100
                                        px-2.5 py-1
                                        text-xs
                                        text-slate-600
                                    ">

                                        {{ $category->icon ?: '📁' }}

                                        {{ $category->name }}

                                    </span>

                                @endforeach

                            </div>

                        @endif

                    </a>

                @endforeach

            </div>

        @endif

    </div>



    {{-- ========================================================= --}}
    {{-- KONTEN + AUSGABEN --}}
    {{-- ========================================================= --}}

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mt-5">


        {{-- KONTEN --}}

        <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">

            <div class="p-6 border-b border-slate-100 flex items-center justify-between">

                <div>

                    <p class="text-xs font-medium uppercase tracking-wider text-emerald-600">
                        Konten
                    </p>

                    <h3 class="font-semibold text-slate-900 mt-1">
                        Deine Konten
                    </h3>

                    <p class="text-sm text-slate-500 mt-1">
                        Aktuelle Kontostände
                    </p>

                </div>

                <a
                    href="{{ route('accounts.index') }}"
                    class="text-sm text-slate-500 hover:text-slate-900 transition"
                >
                    Alle anzeigen →
                </a>

            </div>


            @if ($accounts->isEmpty())

                <div class="p-10 text-center">

                    <div class="w-14 h-14 mx-auto rounded-2xl bg-slate-100 flex items-center justify-center text-2xl">
                        🏦
                    </div>

                    <p class="font-medium text-slate-900 mt-4">
                        Noch keine Konten
                    </p>

                    <a
                        href="{{ route('accounts.create') }}"
                        class="
                            inline-flex
                            mt-4
                            rounded-xl
                            bg-slate-950
                            px-4 py-2
                            text-sm
                            font-medium
                            text-white
                        "
                    >
                        Konto erstellen
                    </a>

                </div>

            @else

                <div class="divide-y divide-slate-100">

                    @foreach ($accounts as $account)

                        <a
                            href="{{ route('accounts.edit', $account) }}"
                            class="
                                flex items-center gap-4
                                p-5
                                hover:bg-slate-50
                                transition
                            "
                        >

                            <div
                                class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl flex-shrink-0"
                                style="background-color: {{ $account->color ?: '#f1f5f9' }}"
                            >
                                {{ $account->icon ?: '🏦' }}
                            </div>


                            <div class="flex-1 min-w-0">

                                <p class="font-medium text-slate-900 truncate">
                                    {{ $account->name }}
                                </p>

                                <p class="text-sm text-slate-500 mt-1 truncate">

                                    @if ($account->institution)

                                        {{ $account->institution }}

                                    @else

                                        {{ ucfirst($account->type) }}

                                    @endif

                                </p>

                            </div>


                            <div class="text-right">

                                <p class="
                                    font-semibold
                                    {{ $account->calculated_balance >= 0
                                        ? 'text-slate-900'
                                        : 'text-red-600' }}
                                ">

                                    {{ number_format(
                                        $account->calculated_balance,
                                        2,
                                        ',',
                                        '.'
                                    ) }}

                                    {{ $account->currency }}

                                </p>

                                <p class="text-xs text-slate-400 mt-1">
                                    Kontostand
                                </p>

                            </div>

                            <span class="text-slate-300">
                                →
                            </span>

                        </a>

                    @endforeach

                </div>

            @endif

        </div>


        {{-- AUSGABEN --}}

        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">

            <div class="p-6 border-b border-slate-100">

                <p class="text-xs font-medium uppercase tracking-wider text-emerald-600">
                    Ausgaben
                </p>

                <h3 class="font-semibold text-slate-900 mt-1">
                    Nach Kategorie
                </h3>

                <p class="text-sm text-slate-500 mt-1">
                    {{ $currentMonth }}
                </p>

            </div>


            @if ($expensesByCategory->isEmpty())

                <div class="p-8 text-center">

                    <div class="w-12 h-12 mx-auto rounded-2xl bg-slate-100 flex items-center justify-center text-xl">
                        📊
                    </div>

                    <p class="text-sm text-slate-500 mt-4">
                        Noch keine Ausgaben.
                    </p>

                </div>

            @else

                <div class="p-5 space-y-5">

                    @foreach ($expensesByCategory as $item)

                        @php

                            $percentage =
                                $monthlyExpense > 0
                                    ? (
                                        $item['amount'] /
                                        $monthlyExpense
                                    ) * 100
                                    : 0;

                        @endphp


                        <div>

                            <div class="flex items-center justify-between text-sm">

                                <div class="flex items-center gap-2 min-w-0">

                                    <span>
                                        {{ $item['category']?->icon ?: '📁' }}
                                    </span>

                                    <span class="text-slate-700 truncate">
                                        {{ $item['category']?->name ?: 'Ohne Kategorie' }}
                                    </span>

                                </div>


                                <span class="font-medium text-slate-900 whitespace-nowrap ml-3">

                                    {{ number_format(
                                        $item['amount'],
                                        2,
                                        ',',
                                        '.'
                                    ) }} €

                                </span>

                            </div>


                            <div class="h-2 bg-slate-100 rounded-full mt-2 overflow-hidden">

                                <div
                                    class="h-full bg-slate-900 rounded-full"
                                    style="width: {{ min($percentage, 100) }}%"
                                ></div>

                            </div>

                        </div>

                    @endforeach

                </div>

            @endif

        </div>

    </div>



    {{-- ========================================================= --}}
    {{-- LETZTE BUCHUNGEN --}}
    {{-- ========================================================= --}}

    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm mt-5 overflow-hidden">

        <div class="p-6 border-b border-slate-100 flex items-center justify-between">

            <div>

                <p class="text-xs font-medium uppercase tracking-wider text-emerald-600">
                    Aktivitäten
                </p>

                <h3 class="font-semibold text-slate-900 mt-1">
                    Letzte Buchungen
                </h3>

                <p class="text-sm text-slate-500 mt-1">
                    Deine zuletzt erfassten Transaktionen
                </p>

            </div>


            <a
                href="{{ route('transactions.index') }}"
                class="text-sm text-slate-500 hover:text-slate-900 transition"
            >
                Alle anzeigen →
            </a>

        </div>


        @if ($recentTransactions->isEmpty())

            <div class="p-10 text-center">

                <div class="w-14 h-14 mx-auto rounded-2xl bg-slate-100 flex items-center justify-center text-2xl">
                    💳
                </div>

                <p class="font-medium text-slate-900 mt-4">
                    Noch keine Buchungen
                </p>

                <a
                    href="{{ route('transactions.create') }}"
                    class="
                        inline-flex
                        mt-4
                        rounded-xl
                        bg-slate-950
                        px-4 py-2
                        text-sm
                        font-medium
                        text-white
                    "
                >
                    Erste Buchung erstellen
                </a>

            </div>

        @else

            <div class="divide-y divide-slate-100">

                @foreach ($recentTransactions as $transaction)

                    <a
                        href="{{ route('transactions.edit', $transaction) }}"
                        class="
                            flex items-center gap-4
                            p-5
                            hover:bg-slate-50
                            transition
                        "
                    >

                        <div
                            class="
                                w-11 h-11
                                rounded-2xl
                                flex items-center justify-center
                                flex-shrink-0
                                {{ $transaction->type === 'income'
                                    ? 'bg-emerald-50'
                                    : 'bg-red-50' }}
                            "
                        >

                            {{ $transaction->category?->icon ?: '💳' }}

                        </div>


                        <div class="flex-1 min-w-0">

                            <p class="font-medium text-slate-900 truncate">
                                {{ $transaction->description }}
                            </p>

                            <p class="text-sm text-slate-500 mt-1 truncate">

                                {{ $transaction->transaction_date?->format('d.m.Y') }}

                                @if ($transaction->category)

                                    · {{ $transaction->category->name }}

                                @endif

                                @if ($transaction->account)

                                    · {{ $transaction->account->name }}

                                @endif

                            </p>

                        </div>


                        <p class="
                            font-semibold
                            whitespace-nowrap
                            {{ $transaction->type === 'income'
                                ? 'text-emerald-600'
                                : 'text-red-600' }}
                        ">

                            {{ $transaction->type === 'income' ? '+' : '-' }}

                            {{ number_format(
                                $transaction->amount,
                                2,
                                ',',
                                '.'
                            ) }} €

                        </p>


                        <span class="hidden sm:block text-slate-300">
                            →
                        </span>

                    </a>

                @endforeach

            </div>

        @endif

    </div>



    {{-- ========================================================= --}}
    {{-- SCHNELLZUGRIFF --}}
    {{-- ========================================================= --}}

    <div class="mt-5">

        <div class="flex items-center justify-between mb-4">

            <div>

                <p class="text-xs font-medium uppercase tracking-wider text-emerald-600">
                    Schnellzugriff
                </p>

                <h3 class="font-semibold text-slate-900 mt-1">
                    Was möchtest du tun?
                </h3>

            </div>

        </div>


        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">


            <a
                href="{{ route('accounts.index') }}"
                class="
                    bg-white
                    rounded-2xl
                    border border-slate-100
                    shadow-sm
                    p-5
                    hover:shadow-md
                    hover:-translate-y-0.5
                    transition
                "
            >

                <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center text-xl">
                    🏦
                </div>

                <p class="font-medium text-slate-900 mt-4">
                    Konten
                </p>

                <p class="text-xs text-slate-400 mt-1">
                    Verwalten
                </p>

            </a>


            <a
                href="{{ route('transactions.index') }}"
                class="
                    bg-white
                    rounded-2xl
                    border border-slate-100
                    shadow-sm
                    p-5
                    hover:shadow-md
                    hover:-translate-y-0.5
                    transition
                "
            >

                <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center text-xl">
                    💳
                </div>

                <p class="font-medium text-slate-900 mt-4">
                    Buchungen
                </p>

                <p class="text-xs text-slate-400 mt-1">
                    Verwalten
                </p>

            </a>


            <a
                href="{{ route('categories.index') }}"
                class="
                    bg-white
                    rounded-2xl
                    border border-slate-100
                    shadow-sm
                    p-5
                    hover:shadow-md
                    hover:-translate-y-0.5
                    transition
                "
            >

                <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center text-xl">
                    🗂️
                </div>

                <p class="font-medium text-slate-900 mt-4">
                    Kategorien
                </p>

                <p class="text-xs text-slate-400 mt-1">
                    Verwalten
                </p>

            </a>


            <a
                href="{{ route('budgets.index') }}"
                class="
                    bg-white
                    rounded-2xl
                    border border-slate-100
                    shadow-sm
                    p-5
                    hover:shadow-md
                    hover:-translate-y-0.5
                    transition
                "
            >

                <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center text-xl">
                    🎯
                </div>

                <p class="font-medium text-slate-900 mt-4">
                    Budgets
                </p>

                <p class="text-xs text-slate-400 mt-1">
                    Verwalten
                </p>

            </a>

        </div>

    </div>

</div>

@endsection