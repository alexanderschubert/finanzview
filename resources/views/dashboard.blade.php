@extends('layouts.app')

@section('title', 'Dashboard – FinanzView')
@section('eyebrow', 'Finanzübersicht')
@section('page_title', 'Dashboard')

@section('content')

<div
    class="
        dashboard-container
        {{ $dashboardCompact ? 'dashboard-compact' : '' }}
        max-w-7xl
        mx-auto
        px-4
        sm:px-6
        lg:px-8
        py-6
        sm:py-8
    "
>

    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-6">

        <div class="min-w-0">

            <div class="flex items-center gap-2">

                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>

                <p class="text-xs sm:text-sm font-medium text-emerald-600 dark:text-emerald-400">
                    Finanzübersicht
                </p>

            </div>

            <h2
                class="
                    text-3xl
                    sm:text-4xl
                    font-semibold
                    tracking-tight
                    text-slate-900
                    dark:text-white
                    mt-2
                "
            >
                Hallo, {{ auth()->user()->name }}
            </h2>

            <p class="text-sm sm:text-base text-slate-500 dark:text-slate-400 mt-2">
                Deine finanzielle Übersicht für {{ $currentMonth }}.
            </p>

        </div>


        <div class="flex flex-col sm:flex-row gap-3">

            {{-- MONAT --}}

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
                        min-w-0
                        rounded-xl
                        border
                        border-slate-200
                        dark:border-slate-700
                        bg-white
                        dark:bg-slate-900
                        px-4
                        py-3
                        text-sm
                        text-slate-700
                        dark:text-slate-200
                        shadow-sm
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
                        border
                        border-slate-200
                        dark:border-slate-700
                        bg-white
                        dark:bg-slate-900
                        px-4
                        py-3
                        text-sm
                        font-medium
                        text-slate-700
                        dark:text-slate-200
                        hover:bg-slate-50
                        dark:hover:bg-slate-800
                        transition
                        shadow-sm
                    "
                >
                    Anzeigen
                </button>

            </form>


            {{-- NEUE BUCHUNG --}}

            <a
                href="{{ route('transactions.create') }}"
                class="
                    inline-flex
                    items-center
                    justify-center
                    rounded-xl
                    bg-emerald-600
                    px-5
                    py-3
                    text-sm
                    font-medium
                    text-white
                    hover:bg-emerald-700
                    transition
                    shadow-sm
                "
            >
                <span class="mr-2 text-emerald-200 text-lg leading-none">
                    +
                </span>

                Neue Buchung
            </a>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- KENNZAHLEN --}}
    {{-- ========================================================= --}}

    <div
        class="
            grid
            grid-cols-1
            sm:grid-cols-2
            {{ collect([
                $dashboardWidgets['summary'],
                $dashboardWidgets['income'],
                $dashboardWidgets['expenses'],
            ])->filter()->count() === 1 ? 'xl:grid-cols-1' : '' }}
            {{ collect([
                $dashboardWidgets['summary'],
                $dashboardWidgets['income'],
                $dashboardWidgets['expenses'],
            ])->filter()->count() === 2 ? 'xl:grid-cols-2' : '' }}
            {{ collect([
                $dashboardWidgets['summary'],
                $dashboardWidgets['income'],
                $dashboardWidgets['expenses'],
            ])->filter()->count() >= 3 ? 'xl:grid-cols-3' : '' }}
            gap-4
            mt-8
        "
    >


        @if(
        $dashboardWidgets['summary']
        || $dashboardWidgets['income']
        || $dashboardWidgets['expenses']
    )

    @if($dashboardWidgets['summary'])

    {{-- GESAMTVERMÖGEN --}}

        <div
            class="
                relative
                overflow-hidden
                rounded-3xl
                bg-slate-950
                dark:bg-slate-900
                text-white
                p-6
                shadow-sm
            "
        >

            <div
                class="
                    absolute
                    -right-10
                    -top-10
                    w-36
                    h-36
                    rounded-full
                    bg-emerald-500/10
                "
            ></div>

            <div class="relative">

                <div class="flex items-center justify-between">

                    <p class="text-sm text-slate-400">
                        Gesamtvermögen
                    </p>

                    <div
                        class="
                            w-10
                            h-10
                            rounded-2xl
                            bg-white/10
                            flex
                            items-center
                            justify-center
                            text-lg
                        "
                    >
                        💰
                    </div>

                </div>

                <p class="text-3xl font-semibold tracking-tight mt-5">

                    {{ number_format(
                        $totalBalance,
                        2,
                        ',',
                        '.'
                    ) }} €

                </p>

                <p class="text-xs text-slate-500 mt-2">
                    Alle berücksichtigten Konten
                </p>

            </div>

        </div>


        @endif

    @if($dashboardWidgets['income'])

    {{-- EINNAHMEN --}}

        <div
            class="
                bg-white
                dark:bg-slate-900
                rounded-3xl
                border
                border-slate-200
                dark:border-slate-800
                shadow-sm
                p-6
            "
        >

            <div class="flex items-center justify-between">

                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Einnahmen
                </p>

                <div
                    class="
                        w-10
                        h-10
                        rounded-2xl
                        bg-emerald-50
                        dark:bg-emerald-950/50
                        flex
                        items-center
                        justify-center
                        text-emerald-600
                        dark:text-emerald-400
                    "
                >
                    ↗
                </div>

            </div>

            <p class="text-3xl font-semibold text-emerald-600 dark:text-emerald-400 mt-5">

                +{{ number_format(
                    $monthlyIncome,
                    2,
                    ',',
                    '.'
                ) }} €

            </p>

            <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">
                {{ $currentMonth }}
            </p>

        </div>


        @endif

    @if($dashboardWidgets['expenses'])

    {{-- AUSGABEN --}}

        <div
            class="
                bg-white
                dark:bg-slate-900
                rounded-3xl
                border
                border-slate-200
                dark:border-slate-800
                shadow-sm
                p-6
            "
        >

            <div class="flex items-center justify-between">

                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Ausgaben
                </p>

                <div
                    class="
                        w-10
                        h-10
                        rounded-2xl
                        bg-red-50
                        dark:bg-red-950/50
                        flex
                        items-center
                        justify-center
                        text-red-600
                        dark:text-red-400
                    "
                >
                    ↘
                </div>

            </div>

            <p class="text-3xl font-semibold text-red-600 dark:text-red-400 mt-5">

                -{{ number_format(
                    $monthlyExpense,
                    2,
                    ',',
                    '.'
                ) }} €

            </p>

            <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">
                {{ $currentMonth }}
            </p>

        </div>


        @if($dashboardWidgets['savings_rate'])

        @endif

    @endif

    {{-- SPARQUOTE --}}

        <div
            class="
                bg-white
                dark:bg-slate-900
                rounded-3xl
                border
                border-slate-200
                dark:border-slate-800
                shadow-sm
                p-6
            "
        >

            <div class="flex items-center justify-between">

                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Sparquote
                </p>

                <div
                    class="
                        w-10
                        h-10
                        rounded-2xl
                        bg-slate-100
                        dark:bg-slate-800
                        flex
                        items-center
                        justify-center
                        text-slate-600
                        dark:text-slate-300
                    "
                >
                    %
                </div>

            </div>

            <p
                class="
                    text-3xl
                    font-semibold
                    mt-5
                    {{ $savingsRate >= 0
                        ? 'text-slate-900 dark:text-white'
                        : 'text-red-600 dark:text-red-400' }}
                "
            >

                {{ number_format(
                    $savingsRate,
                    1,
                    ',',
                    '.'
                ) }} %

            </p>

            <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">
                Einnahmen minus Ausgaben
            </p>

        </div>

    </div>


        @endif

    {{-- ========================================================= --}}
    @if(
        $dashboardWidgets['monthly_balance']
        ||
        $dashboardWidgets['yearly']
    )

    {{-- MONATSSALDO + JAHRESWERTE --}}
    {{-- ========================================================= --}}

    <div
        class="
            grid
            grid-cols-1
            {{ $dashboardWidgets['monthly_balance'] && $dashboardWidgets['yearly']
                ? 'lg:grid-cols-3'
                : '' }}
            gap-4
            mt-4
        "
    >


        @if($dashboardWidgets['monthly_balance'])

        {{-- MONATSSALDO --}}

        <div
            class="
                {{ $dashboardWidgets['monthly_balance'] && $dashboardWidgets['yearly']
                    ? 'lg:col-span-2'
                    : '' }}
                rounded-3xl
                border
                p-6
                shadow-sm
                {{ $monthlyBalance >= 0
                    ? 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-100 dark:border-emerald-900'
                    : 'bg-red-50 dark:bg-red-950/30 border-red-100 dark:border-red-900' }}
            "
        >

            <div
                class="
                    flex
                    flex-col
                    sm:flex-row
                    sm:items-center
                    sm:justify-between
                    gap-5
                "
            >

                <div>

                    <p class="text-sm font-medium text-slate-600 dark:text-slate-300">
                        Monatssaldo
                    </p>

                    <p
                        class="
                            text-3xl
                            font-semibold
                            tracking-tight
                            mt-2
                            {{ $monthlyBalance >= 0
                                ? 'text-emerald-700 dark:text-emerald-400'
                                : 'text-red-700 dark:text-red-400' }}
                        "
                    >

                        {{ $monthlyBalance >= 0 ? '+' : '' }}

                        {{ number_format(
                            $monthlyBalance,
                            2,
                            ',',
                            '.'
                        ) }} €

                    </p>

                </div>


                <div
                    class="
                        inline-flex
                        items-center
                        rounded-full
                        px-4
                        py-2
                        text-sm
                        font-medium
                        self-start
                        {{ $monthlyBalance >= 0
                            ? 'bg-white dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300'
                            : 'bg-white dark:bg-red-900/60 text-red-700 dark:text-red-300' }}
                    "
                >

                    {{ $monthlyBalance >= 0
                        ? 'Positiver Monat'
                        : 'Mehr Ausgaben als Einnahmen' }}

                </div>

            </div>

        </div>


        @endif

        @if($dashboardWidgets['yearly'])

        {{-- JAHRESWERTE --}}

        <div
            class="
                bg-white
                dark:bg-slate-900
                rounded-3xl
                border
                border-slate-200
                dark:border-slate-800
                shadow-sm
                p-6
            "
        >

            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">
                Dieses Jahr
            </p>

            <div class="mt-4 space-y-4">

                <div class="flex items-center justify-between gap-4">

                    <span class="text-sm text-slate-500 dark:text-slate-400">
                        Einnahmen
                    </span>

                    <span class="font-semibold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                        +{{ number_format($yearlyIncome, 2, ',', '.') }} €
                    </span>

                </div>

                <div class="flex items-center justify-between gap-4">

                    <span class="text-sm text-slate-500 dark:text-slate-400">
                        Ausgaben
                    </span>

                    <span class="font-semibold text-red-600 dark:text-red-400 whitespace-nowrap">
                        -{{ number_format($yearlyExpense, 2, ',', '.') }} €
                    </span>

                </div>

                <div
                    class="
                        pt-3
                        border-t
                        border-slate-100
                        dark:border-slate-800
                        flex
                        items-center
                        justify-between
                        gap-4
                    "
                >

                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                        Jahressaldo
                    </span>

                    <span
                        class="
                            font-semibold
                            whitespace-nowrap
                            {{ ($yearlyIncome - $yearlyExpense) >= 0
                                ? 'text-slate-900 dark:text-white'
                                : 'text-red-600 dark:text-red-400' }}
                        "
                    >

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

        @endif

    </div>


    {{-- ========================================================= --}}
    @if($dashboardWidgets['income_expense_chart'])

    @endif

    {{-- EINNAHMEN / AUSGABEN --}}
    {{-- ========================================================= --}}

    <div
        class="
            bg-white
            dark:bg-slate-900
            rounded-3xl
            border
            border-slate-200
            dark:border-slate-800
            shadow-sm
            mt-5
            overflow-hidden
        "
    >

        <div class="p-6 sm:p-8">

            <div
                class="
                    flex
                    flex-col
                    sm:flex-row
                    sm:items-start
                    sm:justify-between
                    gap-4
                "
            >

                <div>

                    <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                        Entwicklung
                    </p>

                    <h3 class="text-xl font-semibold text-slate-900 dark:text-white mt-1">
                        Einnahmen & Ausgaben
                    </h3>

                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Vergleich der letzten sechs Monate.
                    </p>

                </div>


                <div
                    class="
                        flex
                        items-center
                        gap-4
                        text-xs
                        text-slate-500
                        dark:text-slate-400
                    "
                >

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

                        <div class="flex items-center justify-between gap-4 mb-2">

                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                {{ $chartMonth['label'] }}
                            </span>

                            <span
                                class="
                                    text-xs
                                    font-medium
                                    whitespace-nowrap
                                    {{ $chartMonth['balance'] >= 0
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : 'text-red-600 dark:text-red-400' }}
                                "
                            >

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

                            <span class="hidden sm:block w-20 text-xs text-slate-400 dark:text-slate-500">
                                Einnahmen
                            </span>

                            <div
                                class="
                                    flex-1
                                    h-3
                                    bg-slate-100
                                    dark:bg-slate-800
                                    rounded-full
                                    overflow-hidden
                                "
                            >

                                <div
                                    class="h-full bg-emerald-500 rounded-full transition-all"
                                    style="width: {{ ($chartMonth['income'] / $maxChartValue) * 100 }}%"
                                ></div>

                            </div>

                            <span
                                class="
                                    w-24
                                    text-right
                                    text-xs
                                    font-medium
                                    text-slate-700
                                    dark:text-slate-300
                                "
                            >
                                {{ number_format($chartMonth['income'], 0, ',', '.') }} €
                            </span>

                        </div>


                        {{-- AUSGABEN --}}

                        <div class="flex items-center gap-3 mt-2">

                            <span class="hidden sm:block w-20 text-xs text-slate-400 dark:text-slate-500">
                                Ausgaben
                            </span>

                            <div
                                class="
                                    flex-1
                                    h-3
                                    bg-slate-100
                                    dark:bg-slate-800
                                    rounded-full
                                    overflow-hidden
                                "
                            >

                                <div
                                    class="h-full bg-red-500 rounded-full transition-all"
                                    style="width: {{ ($chartMonth['expense'] / $maxChartValue) * 100 }}%"
                                ></div>

                            </div>

                            <span
                                class="
                                    w-24
                                    text-right
                                    text-xs
                                    font-medium
                                    text-slate-700
                                    dark:text-slate-300
                                "
                            >
                                {{ number_format($chartMonth['expense'], 0, ',', '.') }} €
                            </span>

                        </div>

                    </div>

                @endforeach

            </div>

        </div>

    </div>


    @endif

    {{-- ========================================================= --}}
    @if($dashboardWidgets['wealth_chart'])

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


    <div
        class="
            bg-white
            dark:bg-slate-900
            rounded-3xl
            border
            border-slate-200
            dark:border-slate-800
            shadow-sm
            mt-5
            overflow-hidden
        "
    >

        <div class="p-6 sm:p-8">

            <div
                class="
                    flex
                    flex-col
                    sm:flex-row
                    sm:items-start
                    sm:justify-between
                    gap-4
                "
            >

                <div>

                    <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                        Vermögen
                    </p>

                    <h3 class="text-xl font-semibold text-slate-900 dark:text-white mt-1">
                        Vermögensentwicklung
                    </h3>

                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Entwicklung deines Gesamtvermögens über die letzten sechs Monate.
                    </p>

                </div>


                <div class="sm:text-right">

                    <p class="text-xs text-slate-400 dark:text-slate-500">
                        Aktuell
                    </p>

                    <p class="text-xl font-semibold text-slate-900 dark:text-white mt-1">
                        {{ number_format($totalBalance, 2, ',', '.') }} €
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
                                    stroke="currentColor"
                                    class="text-slate-200 dark:text-slate-700"
                                    stroke-width="1"
                                />

                            @endfor


                            <polygon
                                points="{{ $wealthAreaPoints }}"
                                fill="#10b981"
                                opacity="0.08"
                            />


                            <polyline
                                points="{{ $wealthLinePoints }}"
                                fill="none"
                                stroke="#10b981"
                                stroke-width="4"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />


                            @foreach ($wealthPoints as $point)

                                <circle
                                    cx="{{ $point['x'] }}"
                                    cy="{{ $point['y'] }}"
                                    r="6"
                                    class="fill-white dark:fill-slate-900"
                                    stroke="#10b981"
                                    stroke-width="3"
                                />

                            @endforeach


                            @foreach ($wealthPoints as $point)

                                <text
                                    x="{{ $point['x'] }}"
                                    y="{{ $svgHeight - 15 }}"
                                    text-anchor="middle"
                                    font-size="13"
                                    fill="currentColor"
                                    class="text-slate-400 dark:text-slate-500"
                                >
                                    {{ $point['label'] }}
                                </text>

                            @endforeach

                        </svg>

                    </div>

                </div>


                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mt-6">

                    @foreach ($wealthMonths as $wealthMonth)

                        <div
                            class="
                                rounded-2xl
                                bg-slate-50
                                dark:bg-slate-800
                                px-3
                                py-3
                            "
                        >

                            <p class="text-xs text-slate-400 dark:text-slate-500">
                                {{ $wealthMonth['full_label'] }}
                            </p>

                            <p
                                class="
                                    text-sm
                                    font-semibold
                                    mt-1
                                    {{ $wealthMonth['balance'] >= 0
                                        ? 'text-slate-900 dark:text-white'
                                        : 'text-red-600 dark:text-red-400' }}
                                "
                            >
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

                <div
                    class="
                        mt-8
                        rounded-2xl
                        bg-slate-50
                        dark:bg-slate-800
                        p-8
                        text-center
                    "
                >

                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Noch keine Vermögensdaten vorhanden.
                    </p>

                </div>

            @endif

        </div>

    </div>


    @endif

    {{-- ========================================================= --}}
    @if($dashboardWidgets['budgets'])

    {{-- BUDGETS --}}
    {{-- ========================================================= --}}

    <div class="mt-5">

        <div
            class="
                flex
                flex-col
                sm:flex-row
                sm:items-center
                sm:justify-between
                gap-3
                mb-4
            "
        >

            <div>

                <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                    Planung
                </p>

                <h3 class="text-xl font-semibold text-slate-900 dark:text-white mt-1">
                    Deine Budgets
                </h3>

                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Budgetverbrauch für {{ $currentMonth }}
                </p>

            </div>


            <a
                href="{{ route('budgets.index', ['month' => $selectedMonth]) }}"
                class="
                    text-sm
                    font-medium
                    text-slate-500
                    dark:text-slate-400
                    hover:text-emerald-600
                    dark:hover:text-emerald-400
                    transition
                "
            >
                Alle Budgets →
            </a>

        </div>


        @if ($budgets->isEmpty())

            <div
                class="
                    bg-white
                    dark:bg-slate-900
                    rounded-3xl
                    border
                    border-slate-200
                    dark:border-slate-800
                    shadow-sm
                    p-10
                    text-center
                "
            >

                <div
                    class="
                        w-14
                        h-14
                        mx-auto
                        rounded-2xl
                        bg-emerald-50
                        dark:bg-emerald-950/50
                        flex
                        items-center
                        justify-center
                        text-2xl
                    "
                >
                    🎯
                </div>

                <h4 class="font-semibold text-slate-900 dark:text-white mt-4">
                    Noch keine Budgets
                </h4>

                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Lege dein erstes Budget an, um deine Ausgaben besser zu planen.
                </p>

                <a
                    href="{{ route('budgets.create') }}"
                    class="
                        inline-flex
                        mt-5
                        rounded-xl
                        bg-emerald-600
                        px-5
                        py-3
                        text-sm
                        font-medium
                        text-white
                        hover:bg-emerald-700
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
                        href="{{ route('budgets.show', [
                            'budget' => $budget,
                            'month' => $selectedMonth,
                        ]) }}"
                        class="
                            block
                            bg-white
                            dark:bg-slate-900
                            rounded-3xl
                            border
                            border-slate-200
                            dark:border-slate-800
                            shadow-sm
                            p-5
                            hover:shadow-md
                            hover:-translate-y-0.5
                            hover:bg-slate-50
                            dark:hover:bg-slate-800
                            transition
                        "
                    >

                        <div class="flex items-center gap-3">

                            <div
                                class="
                                    w-11
                                    h-11
                                    rounded-2xl
                                    flex
                                    items-center
                                    justify-center
                                    text-xl
                                    flex-shrink-0
                                "
                                style="background-color: {{ $budget->color ?: '#ecfdf5' }}"
                            >
                                {{ $budget->icon ?: '🎯' }}
                            </div>

                            <div class="flex-1 min-w-0">

                                <p class="font-semibold text-slate-900 dark:text-white truncate">
                                    {{ $budget->name }}
                                </p>

                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">

                                    @switch($budget->period)

                                        @case('monthly')
                                            Monatlich
                                            @break

                                        @case('yearly')
                                            Jährlich
                                            @break

                                        @default
                                            Benutzerdefiniert

                                    @endswitch

                                    ·

                                    {{ number_format(
                                        $budget->amount,
                                        2,
                                        ',',
                                        '.'
                                    ) }} €

                                </p>

                            </div>


                            @if (!$budget->calculated_applicable)

                                <span class="rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-xs font-medium text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                    Nicht aktiv
                                </span>

                            @elseif ($budget->calculated_exceeded)

                                <span class="rounded-full bg-red-50 dark:bg-red-950/50 px-2.5 py-1 text-xs font-medium text-red-600 dark:text-red-400 whitespace-nowrap">
                                    Überschritten
                                </span>

                            @elseif ($budget->calculated_percentage >= 80)

                                <span class="rounded-full bg-amber-50 dark:bg-amber-950/50 px-2.5 py-1 text-xs font-medium text-amber-600 dark:text-amber-400 whitespace-nowrap">
                                    Achtung
                                </span>

                            @else

                                <span class="rounded-full bg-emerald-50 dark:bg-emerald-950/50 px-2.5 py-1 text-xs font-medium text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                    OK
                                </span>

                            @endif

                        </div>


                        @if (!$budget->calculated_applicable)

                            <div class="mt-6">

                                <div class="rounded-2xl bg-slate-50 dark:bg-slate-800 p-4">

                                    <div class="flex items-center gap-3">

                                        <div class="w-9 h-9 rounded-xl bg-slate-200 dark:bg-slate-700 flex items-center justify-center">
                                            🕐
                                        </div>

                                        <div>

                                            <p class="text-sm font-medium text-slate-700 dark:text-slate-200">
                                                Noch nicht gültig
                                            </p>

                                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                                                Das Budget beginnt am
                                                {{ $budget->start_date->format('d.m.Y') }}.
                                            </p>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        @else

                            <div class="mt-6">

                                <div class="flex items-center justify-between">

                                    <span class="text-sm text-slate-500 dark:text-slate-400">
                                        Verbrauch
                                    </span>

                                    <span class="
                                        text-sm
                                        font-semibold
                                        {{ $budget->calculated_exceeded
                                            ? 'text-red-600 dark:text-red-400'
                                            : 'text-slate-900 dark:text-white' }}
                                    ">
                                        {{ number_format(
                                            $budget->calculated_spent,
                                            2,
                                            ',',
                                            '.'
                                        ) }} €
                                    </span>

                                </div>


                                <div
                                    class="
                                        h-3
                                        bg-slate-100
                                        dark:bg-slate-800
                                        rounded-full
                                        overflow-hidden
                                        mt-3
                                    "
                                >

                                    <div
                                        class="
                                            h-full
                                            rounded-full
                                            transition-all
                                            {{ $budget->calculated_exceeded
                                                ? 'bg-red-500'
                                                : (
                                                    $budget->calculated_percentage >= 80
                                                        ? 'bg-amber-500'
                                                        : 'bg-emerald-500'
                                                ) }}
                                        "
                                        style="width: {{ min(max($budget->calculated_percentage, 0), 100) }}%"
                                    ></div>

                                </div>


                                <div class="flex items-center justify-between mt-2">

                                    <span class="text-xs text-slate-400 dark:text-slate-500">
                                        {{ number_format(
                                            $budget->calculated_percentage,
                                            1,
                                            ',',
                                            '.'
                                        ) }} %
                                    </span>


                                    @if ($budget->calculated_remaining >= 0)

                                        <span class="text-xs text-emerald-600 dark:text-emerald-400">

                                            Noch

                                            {{ number_format(
                                                $budget->calculated_remaining,
                                                2,
                                                ',',
                                                '.'
                                            ) }} €

                                        </span>

                                    @else

                                        <span class="text-xs font-medium text-red-600 dark:text-red-400">

                                            {{ number_format(
                                                abs($budget->calculated_remaining),
                                                2,
                                                ',',
                                                '.'
                                            ) }} €

                                            über Budget

                                        </span>

                                    @endif

                                </div>

                            </div>

                        @endif


                        @if ($budget->categories->isNotEmpty())

                            <div class="flex flex-wrap gap-2 mt-5">

                                @foreach ($budget->categories->take(3) as $category)

                                    <span class="rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-xs text-slate-600 dark:text-slate-300">

                                        {{ $category->icon ?: '📁' }}

                                        {{ $category->name }}

                                    </span>

                                @endforeach


                                @if ($budget->categories->count() > 3)

                                    <span class="rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-xs text-slate-500 dark:text-slate-400">
                                        +{{ $budget->categories->count() - 3 }}
                                    </span>

                                @endif

                            </div>

                        @endif

                    </a>

                @endforeach

            </div>

        @endif

    </div>


    {{-- ========================================================= --}}
    {{-- ========================================================= --}}
    @endif

    {{-- KREDITKARTEN & KREDITE --}}
    {{-- ========================================================= --}}

    @if(
        ($dashboardWidgets['credit_cards'] && $creditCards->isNotEmpty())
        ||
        ($dashboardWidgets['loans'] && $loans->isNotEmpty())
    )

        <div
            class="
                grid
                grid-cols-1
                {{ $dashboardWidgets['credit_cards'] && $creditCards->isNotEmpty()
                    && $dashboardWidgets['loans'] && $loans->isNotEmpty()
                    ? 'xl:grid-cols-2'
                    : '' }}
                gap-4
                mt-5
            "
        >

            {{-- ===================================================== --}}
            {{-- KREDITKARTEN --}}
            {{-- ===================================================== --}}

            @if($dashboardWidgets['credit_cards'] && $creditCards->isNotEmpty())

                <div
                    class="
                        bg-white
                        dark:bg-slate-900
                        rounded-3xl
                        border
                        border-slate-200
                        dark:border-slate-800
                        shadow-sm
                        overflow-hidden
                    "
                >

                    <div class="p-6 sm:p-8">

                        <div class="flex items-center justify-between gap-4">

                            <div>

                                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">
                                    Kreditkarten
                                </p>

                                <h3 class="text-xl font-semibold text-slate-900 dark:text-white mt-1">
                                    Aktive Kreditkarten
                                </h3>

                            </div>

                            <div
                                class="
                                    w-11
                                    h-11
                                    rounded-2xl
                                    bg-violet-50
                                    dark:bg-violet-950/40
                                    flex
                                    items-center
                                    justify-center
                                    text-lg
                                "
                            >
                                💳
                            </div>

                        </div>


                        <div class="mt-6 space-y-4">

                            @foreach($creditCards as $creditCard)

                                @php
                                    $creditLimit = (float) $creditCard->credit_limit;
                                    $currentBalance = (float) $creditCard->current_balance;

                                    $usage = $creditLimit > 0
                                        ? min(100, max(0, ($currentBalance / $creditLimit) * 100))
                                        : 0;
                                @endphp

                                <div
                                    class="
                                        rounded-2xl
                                        border
                                        border-slate-100
                                        dark:border-slate-800
                                        bg-slate-50
                                        dark:bg-slate-950/40
                                        p-4
                                    "
                                >

                                    <div class="flex items-center gap-3">

                                        <x-financial-provider
                                            :provider="$creditCard->provider"
                                            fallback-icon="💳"
                                            size="sm"
                                        />

                                        <div class="min-w-0 flex-1">

                                            <p class="font-medium text-slate-900 dark:text-white truncate">
                                                {{ $creditCard->name }}
                                            </p>

                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">

                                                @if($creditCard->last_four)
                                                    •••• {{ $creditCard->last_four }}
                                                @elseif($creditCard->issuer)
                                                    {{ $creditCard->issuer }}
                                                @else
                                                    Kreditkarte
                                                @endif

                                            </p>

                                        </div>

                                        <div class="text-right shrink-0">

                                            <p class="font-semibold text-slate-900 dark:text-white">
                                                {{ number_format($currentBalance, 2, ',', '.') }} €
                                            </p>

                                            @if($creditLimit > 0)

                                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                                    von {{ number_format($creditLimit, 2, ',', '.') }} €
                                                </p>

                                            @endif

                                        </div>

                                    </div>


                                    @if($creditLimit > 0)

                                        <div class="mt-4">

                                            <div class="flex items-center justify-between mb-2">

                                                <span class="text-xs text-slate-500 dark:text-slate-400">
                                                    Auslastung
                                                </span>

                                                <span
                                                    class="
                                                        text-xs
                                                        font-medium
                                                        {{ $usage >= 80
                                                            ? 'text-red-600 dark:text-red-400'
                                                            : ($usage >= 50
                                                                ? 'text-amber-600 dark:text-amber-400'
                                                                : 'text-slate-600 dark:text-slate-300') }}
                                                    "
                                                >
                                                    {{ number_format($usage, 0, ',', '.') }} %
                                                </span>

                                            </div>

                                            <div
                                                class="
                                                    h-2
                                                    rounded-full
                                                    bg-slate-200
                                                    dark:bg-slate-800
                                                    overflow-hidden
                                                "
                                            >

                                                <div
                                                    class="
                                                        h-full
                                                        rounded-full
                                                        {{ $usage >= 80
                                                            ? 'bg-red-500'
                                                            : ($usage >= 50
                                                                ? 'bg-amber-500'
                                                                : 'bg-violet-500') }}
                                                    "
                                                    style="width: {{ $usage }}%"
                                                ></div>

                                            </div>

                                        </div>

                                    @endif

                                </div>

                            @endforeach

                        </div>

                    </div>

                </div>

            @endif


            {{-- ===================================================== --}}
            {{-- KREDITE --}}
            {{-- ===================================================== --}}

            @if($dashboardWidgets['loans'] && $loans->isNotEmpty())

                <div
                    class="
                        bg-white
                        dark:bg-slate-900
                        rounded-3xl
                        border
                        border-slate-200
                        dark:border-slate-800
                        shadow-sm
                        overflow-hidden
                    "
                >

                    <div class="p-6 sm:p-8">

                        <div class="flex items-center justify-between gap-4">

                            <div>

                                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">
                                    Kredite
                                </p>

                                <h3 class="text-xl font-semibold text-slate-900 dark:text-white mt-1">
                                    Aktive Kredite
                                </h3>

                            </div>

                            <div
                                class="
                                    w-11
                                    h-11
                                    rounded-2xl
                                    bg-amber-50
                                    dark:bg-amber-950/40
                                    flex
                                    items-center
                                    justify-center
                                    text-lg
                                "
                            >
                                🏦
                            </div>

                        </div>


                        <div class="mt-6 space-y-4">

                            @foreach($loans as $loan)

                                @php
                                    $principal = (float) $loan->principal_amount;
                                    $remaining = max(0, (float) $loan->remainingAmount);
                                    $paid = max(0, $principal - $remaining);

                                    $progress = $principal > 0
                                        ? min(100, max(0, ($paid / $principal) * 100))
                                        : 0;
                                @endphp

                                <div
                                    class="
                                        rounded-2xl
                                        border
                                        border-slate-100
                                        dark:border-slate-800
                                        bg-slate-50
                                        dark:bg-slate-950/40
                                        p-4
                                    "
                                >

                                    <div class="flex items-center gap-3">

                                        <x-financial-provider
                                            :provider="$loan->provider"
                                            fallback-icon="🏦"
                                            size="sm"
                                        />

                                        <div class="min-w-0 flex-1">

                                            <p class="font-medium text-slate-900 dark:text-white truncate">
                                                {{ $loan->name }}
                                            </p>

                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                                {{ $loan->creditor_name ?: 'Kredit' }}
                                            </p>

                                        </div>

                                        <div class="text-right shrink-0">

                                            <p class="font-semibold text-slate-900 dark:text-white">
                                                {{ number_format($remaining, 2, ',', '.') }} €
                                            </p>

                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                                Restbetrag
                                            </p>

                                        </div>

                                    </div>


                                    <div class="mt-4">

                                        <div class="flex items-center justify-between mb-2">

                                            <span class="text-xs text-slate-500 dark:text-slate-400">
                                                Tilgungsfortschritt
                                            </span>

                                            <span class="text-xs font-medium text-slate-600 dark:text-slate-300">
                                                {{ number_format($progress, 0, ',', '.') }} %
                                            </span>

                                        </div>

                                        <div
                                            class="
                                                h-2
                                                rounded-full
                                                bg-slate-200
                                                dark:bg-slate-800
                                                overflow-hidden
                                            "
                                        >

                                            <div
                                                class="
                                                    h-full
                                                    rounded-full
                                                    bg-emerald-500
                                                "
                                                style="width: {{ $progress }}%"
                                            ></div>

                                        </div>

                                    </div>


                                    <div
                                        class="
                                            flex
                                            items-center
                                            justify-between
                                            gap-4
                                            mt-4
                                            pt-3
                                            border-t
                                            border-slate-200
                                            dark:border-slate-800
                                        "
                                    >

                                        <span class="text-xs text-slate-500 dark:text-slate-400">
                                            Ursprünglich
                                        </span>

                                        <span class="text-xs font-medium text-slate-700 dark:text-slate-300">
                                            {{ number_format($principal, 2, ',', '.') }} €
                                        </span>

                                    </div>

                                </div>

                            @endforeach

                        </div>

                    </div>

                </div>

            @endif

        </div>

    @endif


    @if(
        ($dashboardWidgets['accounts'] && $accounts->isNotEmpty())
        ||
        ($dashboardWidgets['categories'] && $expensesByCategory->isNotEmpty())
    )

    {{-- KONTEN + AUSGABEN --}}
    {{-- ========================================================= --}}

    <div
        class="
            grid
            grid-cols-1
            {{ $dashboardWidgets['accounts'] && $accounts->isNotEmpty()
                && $dashboardWidgets['categories'] && $expensesByCategory->isNotEmpty()
                ? 'lg:grid-cols-3'
                : '' }}
            gap-5
            mt-5
        "
    >


        @if($dashboardWidgets['accounts'] && $accounts->isNotEmpty())

        {{-- KONTEN --}}

        <div
            class="
                {{ $dashboardWidgets['accounts'] && $dashboardWidgets['categories'] && $expensesByCategory->isNotEmpty()
                    ? 'lg:col-span-2'
                    : '' }}
                bg-white
                dark:bg-slate-900
                rounded-3xl
                border
                border-slate-200
                dark:border-slate-800
                shadow-sm
                overflow-hidden
            "
        >

            <div
                class="
                    p-6
                    border-b
                    border-slate-100
                    dark:border-slate-800
                    flex
                    items-center
                    justify-between
                    gap-4
                "
            >

                <div class="min-w-0">

                    <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                        Konten
                    </p>

                    <h3 class="font-semibold text-slate-900 dark:text-white mt-1">
                        Deine Konten
                    </h3>

                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Aktuelle Kontostände
                    </p>

                </div>

                <a
                    href="{{ route('accounts.index') }}"
                    class="
                        text-sm
                        text-slate-500
                        dark:text-slate-400
                        hover:text-emerald-600
                        dark:hover:text-emerald-400
                        transition
                        whitespace-nowrap
                    "
                >
                    Alle anzeigen →
                </a>

            </div>


            @if ($accounts->isEmpty())

                <div class="p-10 text-center">

                    <div class="w-14 h-14 mx-auto rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-2xl">
                        🏦
                    </div>

                    <p class="font-medium text-slate-900 dark:text-white mt-4">
                        Noch keine Konten
                    </p>

                    <a
                        href="{{ route('accounts.create') }}"
                        class="
                            inline-flex
                            mt-4
                            rounded-xl
                            bg-emerald-600
                            px-4
                            py-2
                            text-sm
                            font-medium
                            text-white
                            hover:bg-emerald-700
                            transition
                        "
                    >
                        Konto erstellen
                    </a>

                </div>

            @else

                <div class="divide-y divide-slate-100 dark:divide-slate-800">

                    @foreach ($accounts as $account)

                        <a
                            href="{{ route('accounts.edit', $account) }}"
                            class="
                                flex
                                items-center
                                gap-4
                                p-5
                                hover:bg-slate-50
                                dark:hover:bg-slate-800
                                transition
                            "
                        >

                            <x-financial-provider
                                :provider="$account->provider"
                                :fallback-icon="$account->icon ?: '🏦'"
                                size="sm"
                            />

                            <div class="flex-1 min-w-0">

                                <p class="font-medium text-slate-900 dark:text-white truncate">
                                    {{ $account->name }}
                                </p>

                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 truncate">

                                    @if ($account->institution)
                                        {{ $account->institution }}
                                    @else
                                        {{ ucfirst($account->type) }}
                                    @endif

                                </p>

                            </div>

                            <div class="text-right flex-shrink-0">

                                <p
                                    class="
                                        font-semibold
                                        {{ $account->calculated_balance >= 0
                                            ? 'text-slate-900 dark:text-white'
                                            : 'text-red-600 dark:text-red-400' }}
                                    "
                                >

                                    {{ number_format(
                                        $account->calculated_balance,
                                        2,
                                        ',',
                                        '.'
                                    ) }}

                                    {{ $account->currency }}

                                </p>

                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                                    Kontostand
                                </p>

                            </div>

                            <span class="hidden sm:block text-slate-300 dark:text-slate-600">
                                →
                            </span>

                        </a>

                    @endforeach

                </div>

            @endif

        </div>


        @endif

        @if($dashboardWidgets['categories'] && $expensesByCategory->isNotEmpty())

        {{-- AUSGABEN NACH KATEGORIE --}}

        <div
            class="
                bg-white
                dark:bg-slate-900
                rounded-3xl
                border
                border-slate-200
                dark:border-slate-800
                shadow-sm
                overflow-hidden
            "
        >

            <div
                class="
                    p-6
                    border-b
                    border-slate-100
                    dark:border-slate-800
                "
            >

                <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                    Ausgaben
                </p>

                <h3 class="font-semibold text-slate-900 dark:text-white mt-1">
                    Nach Kategorie
                </h3>

                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    {{ $currentMonth }}
                </p>

            </div>


            @if ($expensesByCategory->isEmpty())

                <div class="p-8 text-center">

                    <div class="w-12 h-12 mx-auto rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-xl">
                        📊
                    </div>

                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-4">
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

                            <div class="flex items-center justify-between gap-3 text-sm">

                                <div class="flex items-center gap-2 min-w-0">

                                    <span class="flex-shrink-0">
                                        {{ $item['category']?->icon ?: '📁' }}
                                    </span>

                                    <span class="text-slate-700 dark:text-slate-300 truncate">
                                        {{ $item['category']?->name ?: 'Ohne Kategorie' }}
                                    </span>

                                </div>

                                <span class="font-medium text-slate-900 dark:text-white whitespace-nowrap">
                                    {{ number_format(
                                        $item['amount'],
                                        2,
                                        ',',
                                        '.'
                                    ) }} €
                                </span>

                            </div>


                            <div
                                class="
                                    h-2
                                    bg-slate-100
                                    dark:bg-slate-800
                                    rounded-full
                                    mt-2
                                    overflow-hidden
                                "
                            >

                                <div
                                    class="
                                        h-full
                                        bg-slate-900
                                        dark:bg-emerald-500
                                        rounded-full
                                    "
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
        @endif
    @endif

    @if($dashboardWidgets['recent_transactions'])

    {{-- LETZTE BUCHUNGEN --}}
    {{-- ========================================================= --}}

    <div
        class="
            bg-white
            dark:bg-slate-900
            rounded-3xl
            border
            border-slate-200
            dark:border-slate-800
            shadow-sm
            mt-5
            overflow-hidden
        "
    >

        <div
            class="
                p-6
                border-b
                border-slate-100
                dark:border-slate-800
                flex
                items-center
                justify-between
                gap-4
            "
        >

            <div class="min-w-0">

                <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                    Aktivitäten
                </p>

                <h3 class="font-semibold text-slate-900 dark:text-white mt-1">
                    Letzte Buchungen
                </h3>

                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Deine zuletzt erfassten Transaktionen
                </p>

            </div>

            <a
                href="{{ route('transactions.index') }}"
                class="
                    text-sm
                    text-slate-500
                    dark:text-slate-400
                    hover:text-emerald-600
                    dark:hover:text-emerald-400
                    transition
                    whitespace-nowrap
                "
            >
                Alle anzeigen →
            </a>

        </div>


        @if ($recentTransactions->isEmpty())

            <div class="p-10 text-center">

                <div class="w-14 h-14 mx-auto rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-2xl">
                    💳
                </div>

                <p class="font-medium text-slate-900 dark:text-white mt-4">
                    Noch keine Buchungen
                </p>

                <a
                    href="{{ route('transactions.create') }}"
                    class="
                        inline-flex
                        mt-4
                        rounded-xl
                        bg-emerald-600
                        px-4
                        py-2
                        text-sm
                        font-medium
                        text-white
                        hover:bg-emerald-700
                        transition
                    "
                >
                    Erste Buchung erstellen
                </a>

            </div>

        @else

            <div class="divide-y divide-slate-100 dark:divide-slate-800">

                @foreach ($recentTransactions as $transaction)

                    <a
                        href="{{ route('transactions.edit', $transaction) }}"
                        class="
                            flex
                            items-center
                            gap-4
                            p-5
                            hover:bg-slate-50
                            dark:hover:bg-slate-800
                            transition
                        "
                    >

                        <div
                            class="
                                w-11
                                h-11
                                rounded-2xl
                                flex
                                items-center
                                justify-center
                                flex-shrink-0
                                {{ $transaction->type === 'income'
                                    ? 'bg-emerald-50 dark:bg-emerald-950/50'
                                    : 'bg-red-50 dark:bg-red-950/50' }}
                            "
                        >
                            {{ $transaction->category?->icon ?: '💳' }}
                        </div>

                        <div class="flex-1 min-w-0">

                            <p class="font-medium text-slate-900 dark:text-white truncate">
                                {{ $transaction->description }}
                            </p>

                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 truncate">

                                {{ $transaction->transaction_date?->format('d.m.Y') }}

                                @if ($transaction->category)
                                    · {{ $transaction->category->name }}
                                @endif

                                @if ($transaction->account)
                                    · {{ $transaction->account->name }}
                                @endif

                            </p>

                        </div>

                        <p
                            class="
                                font-semibold
                                whitespace-nowrap
                                {{ $transaction->type === 'income'
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-red-600 dark:text-red-400' }}
                            "
                        >

                            {{ $transaction->type === 'income' ? '+' : '-' }}

                            {{ number_format(
                                $transaction->amount,
                                2,
                                ',',
                                '.'
                            ) }} €

                        </p>

                        <span class="hidden sm:block text-slate-300 dark:text-slate-600">
                            →
                        </span>

                    </a>

                @endforeach

            </div>

        @endif

    </div>


    {{-- ========================================================= --}}
    @endif

    @if($dashboardWidgets['quick_actions'])

    {{-- SCHNELLZUGRIFF --}}
    {{-- ========================================================= --}}

    <div class="mt-5">

        <div class="mb-4">

            <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                Schnellzugriff
            </p>

            <h3 class="font-semibold text-slate-900 dark:text-white mt-1">
                Was möchtest du tun?
            </h3>

        </div>


        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">

            @foreach ([
                [
                    'route' => 'accounts.index',
                    'icon' => '🏦',
                    'title' => 'Konten',
                ],
                [
                    'route' => 'transactions.index',
                    'icon' => '💳',
                    'title' => 'Buchungen',
                ],
                [
                    'route' => 'categories.index',
                    'icon' => '🗂️',
                    'title' => 'Kategorien',
                ],
                [
                    'route' => 'budgets.index',
                    'icon' => '🎯',
                    'title' => 'Budgets',
                ],
            ] as $quickAction)

                <a
                    href="{{ route($quickAction['route']) }}"
                    class="
                        bg-white
                        dark:bg-slate-900
                        rounded-2xl
                        border
                        border-slate-200
                        dark:border-slate-800
                        shadow-sm
                        p-5
                        hover:shadow-md
                        hover:-translate-y-0.5
                        hover:bg-slate-50
                        dark:hover:bg-slate-800
                        transition
                    "
                >

                    <div
                        class="
                            w-10
                            h-10
                            rounded-xl
                            bg-emerald-50
                            dark:bg-emerald-950/50
                            flex
                            items-center
                            justify-center
                            text-xl
                        "
                    >
                        {{ $quickAction['icon'] }}
                    </div>

                    <p class="font-medium text-slate-900 dark:text-white mt-4">
                        {{ $quickAction['title'] }}
                    </p>

                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                        Verwalten
                    </p>

                </a>

            @endforeach

        </div>

    </div>

</div>
    @endif


@endsection