@extends('layouts.app')

@section('title', 'Dashboard – Finanzblick')

@section('eyebrow', 'Übersicht')

@section('page_title', 'Dashboard')

@section('content')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">


    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-6">

        <div>

            <p class="text-sm text-slate-500">
                Guten Tag, {{ auth()->user()->name }}
            </p>

            <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900 mt-1">
                Deine Finanzen
            </h2>

            <p class="text-slate-500 mt-2">
                Alles Wichtige auf einen Blick.
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
                    class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm"
                >

                <button
                    type="submit"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50"
                >
                    Anzeigen
                </button>

            </form>


            <a
                href="{{ route('transactions.create') }}"
                class="inline-flex items-center justify-center rounded-xl bg-slate-950 px-5 py-3 text-sm font-medium text-white hover:bg-slate-800 transition"
            >
                + Buchung
            </a>

        </div>

    </div>



    {{-- ========================================================= --}}
    {{-- VERMÖGEN --}}
    {{-- ========================================================= --}}

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mt-8">

        <div
            class="lg:col-span-2 rounded-3xl bg-slate-950 text-white p-6 sm:p-8 overflow-hidden relative"
        >

            <div class="absolute -right-20 -top-20 w-64 h-64 rounded-full bg-white/5"></div>

            <div class="absolute -right-10 -bottom-32 w-72 h-72 rounded-full bg-white/5"></div>

            <div class="relative">

                <p class="text-sm text-slate-400">
                    Gesamtvermögen
                </p>

                <p class="text-4xl sm:text-5xl font-semibold tracking-tight mt-2">

                    {{ number_format(
                        $totalBalance,
                        2,
                        ',',
                        '.'
                    ) }}

                    €

                </p>


                <div class="flex flex-wrap gap-6 mt-8">

                    <div>

                        <p class="text-xs text-slate-400">
                            Einnahmen
                        </p>

                        <p class="font-medium text-emerald-400 mt-1">

                            +{{ number_format(
                                $monthlyIncome,
                                2,
                                ',',
                                '.'
                            ) }}

                            €

                        </p>

                    </div>


                    <div>

                        <p class="text-xs text-slate-400">
                            Ausgaben
                        </p>

                        <p class="font-medium text-red-400 mt-1">

                            -{{ number_format(
                                $monthlyExpense,
                                2,
                                ',',
                                '.'
                            ) }}

                            €

                        </p>

                    </div>


                    <div>

                        <p class="text-xs text-slate-400">
                            Sparquote
                        </p>

                        <p class="font-medium text-white mt-1">

                            {{ number_format(
                                $savingsRate,
                                1,
                                ',',
                                '.'
                            ) }}

                            %

                        </p>

                    </div>

                </div>

            </div>

        </div>


        {{-- MONATSSALDO --}}

        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 sm:p-8">

            <p class="text-sm text-slate-500">
                Monatssaldo
            </p>

            <p
                class="text-3xl font-semibold mt-4
                {{ $monthlyBalance >= 0
                    ? 'text-emerald-600'
                    : 'text-red-600' }}"
            >

                {{ $monthlyBalance >= 0 ? '+' : '' }}

                {{ number_format(
                    $monthlyBalance,
                    2,
                    ',',
                    '.'
                ) }}

                €

            </p>

            <p class="text-sm text-slate-500 mt-4">
                {{ $currentMonth }}
            </p>

        </div>

    </div>



    {{-- ========================================================= --}}
    {{-- MONATS-KENNZAHLEN --}}
    {{-- ========================================================= --}}

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mt-5">


        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6">

            <p class="text-sm text-slate-500">
                Einnahmen
            </p>

            <p class="text-2xl font-semibold text-emerald-600 mt-2">

                +{{ number_format(
                    $monthlyIncome,
                    2,
                    ',',
                    '.'
                ) }}

                €

            </p>

        </div>


        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6">

            <p class="text-sm text-slate-500">
                Ausgaben
            </p>

            <p class="text-2xl font-semibold text-red-600 mt-2">

                -{{ number_format(
                    $monthlyExpense,
                    2,
                    ',',
                    '.'
                ) }}

                €

            </p>

        </div>


        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6">

            <p class="text-sm text-slate-500">
                Sparquote
            </p>

            <p class="text-2xl font-semibold text-slate-900 mt-2">

                {{ number_format(
                    $savingsRate,
                    1,
                    ',',
                    '.'
                ) }}

                %

            </p>

        </div>

    </div>



    {{-- ========================================================= --}}
    {{-- MONATSVERLAUF --}}
    {{-- ========================================================= --}}

    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm mt-5 overflow-hidden">

        <div class="p-6 sm:p-8">

            <h3 class="text-lg font-semibold text-slate-900">
                Einnahmen & Ausgaben
            </h3>

            <p class="text-sm text-slate-500 mt-1">
                Entwicklung der letzten 6 Monate
            </p>


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


            <div class="space-y-6 mt-8">

                @foreach ($chartMonths as $chartMonth)

                    <div>

                        <div class="flex items-center justify-between mb-2">

                            <span class="text-sm font-medium text-slate-700">
                                {{ $chartMonth['label'] }}
                            </span>

                            <span class="text-xs text-slate-400">

                                Saldo:

                                {{ $chartMonth['balance'] >= 0 ? '+' : '' }}

                                {{ number_format(
                                    $chartMonth['balance'],
                                    2,
                                    ',',
                                    '.'
                                ) }}

                                €

                            </span>

                        </div>


                        {{-- EINNAHMEN --}}

                        <div class="flex items-center gap-3">

                            <span class="hidden sm:block w-20 text-xs text-slate-400">
                                Einnahmen
                            </span>

                            <div class="flex-1 h-2.5 bg-slate-100 rounded-full overflow-hidden">

                                <div
                                    class="h-full bg-emerald-500 rounded-full"
                                    style="
                                        width:
                                        {{ ($chartMonth['income'] / $maxChartValue) * 100 }}%
                                    "
                                ></div>

                            </div>

                            <span class="w-20 text-right text-xs font-medium">

                                {{ number_format(
                                    $chartMonth['income'],
                                    0,
                                    ',',
                                    '.'
                                ) }}

                                €

                            </span>

                        </div>


                        {{-- AUSGABEN --}}

                        <div class="flex items-center gap-3 mt-2">

                            <span class="hidden sm:block w-20 text-xs text-slate-400">
                                Ausgaben
                            </span>

                            <div class="flex-1 h-2.5 bg-slate-100 rounded-full overflow-hidden">

                                <div
                                    class="h-full bg-red-500 rounded-full"
                                    style="
                                        width:
                                        {{ ($chartMonth['expense'] / $maxChartValue) * 100 }}%
                                    "
                                ></div>

                            </div>

                            <span class="w-20 text-right text-xs font-medium">

                                {{ number_format(
                                    $chartMonth['expense'],
                                    0,
                                    ',',
                                    '.'
                                ) }}

                                €

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
            $svgWidth
            - $paddingLeft
            - $paddingRight;

        $innerHeight =
            $svgHeight
            - $paddingTop
            - $paddingBottom;

        $wealthPoints = [];

        foreach ($wealthMonths as $index => $wealthMonth) {

            $count = max(
                $wealthMonths->count() - 1,
                1
            );

            $x =
                $paddingLeft
                +
                (
                    $index / $count
                )
                *
                $innerWidth;

            $normalized =
                (
                    $wealthMonth['balance']
                    - $wealthMin
                )
                /
                $wealthRange;

            $y =
                $paddingTop
                +
                (
                    1 - $normalized
                )
                *
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


            {{-- HEADER --}}

            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">

                <div>

                    <p class="text-xs font-medium uppercase tracking-wider text-slate-400">
                        Vermögen
                    </p>

                    <h3 class="text-xl font-semibold text-slate-900 mt-1">
                        Vermögensentwicklung
                    </h3>

                    <p class="text-sm text-slate-500 mt-1">
                        Entwicklung deines Gesamtvermögens über die letzten 6 Monate.
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
                        ) }}

                        €

                    </p>

                </div>

            </div>


            {{-- CHART --}}

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
                                        $paddingTop
                                        +
                                        (
                                            $i / 4
                                        )
                                        *
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
                                fill="#0f172a"
                                opacity="0.05"
                            />


                            {{-- LINIE --}}

                            <polyline
                                points="{{ $wealthLinePoints }}"
                                fill="none"
                                stroke="#0f172a"
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
                                    stroke="#0f172a"
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


                {{-- WERTE --}}

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mt-6">

                    @foreach ($wealthMonths as $wealthMonth)

                        <div class="rounded-2xl bg-slate-50 px-3 py-3">

                            <p class="text-xs text-slate-400">
                                {{ $wealthMonth['full_label'] }}
                            </p>

                            <p
                                class="
                                    text-sm
                                    font-semibold
                                    mt-1
                                    {{ $wealthMonth['balance'] >= 0
                                        ? 'text-slate-900'
                                        : 'text-red-600' }}
                                "
                            >

                                {{ number_format(
                                    $wealthMonth['balance'],
                                    2,
                                    ',',
                                    '.'
                                ) }}

                                €

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

                <h3 class="text-lg font-semibold text-slate-900">
                    Deine Budgets
                </h3>

                <p class="text-sm text-slate-500 mt-1">
                    Budgetverbrauch für {{ $currentMonth }}
                </p>

            </div>


            <a
                href="{{ route('budgets.index') }}"
                class="text-sm font-medium text-slate-500 hover:text-slate-900"
            >
                Alle Budgets →
            </a>

        </div>


        @if ($budgets->isEmpty())

            <div
                class="
                    bg-white
                    rounded-3xl
                    border
                    border-slate-100
                    shadow-sm
                    p-8
                    text-center
                "
            >

                <div class="text-4xl">
                    🎯
                </div>

                <h4 class="font-semibold text-slate-900 mt-3">
                    Noch keine Budgets
                </h4>

                <p class="text-sm text-slate-500 mt-1">
                    Lege dein erstes Budget an.
                </p>

                <a
                    href="{{ route('budgets.create') }}"
                    class="
                        inline-flex
                        mt-5
                        rounded-xl
                        bg-slate-950
                        px-5
                        py-3
                        text-sm
                        font-medium
                        text-white
                        hover:bg-slate-800
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
                            border
                            border-slate-100
                            shadow-sm
                            p-5
                            hover:shadow-md
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
                                style="
                                    background-color:
                                    {{ $budget->color ?: '#f1f5f9' }}
                                "
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
                                    ) }}

                                    €

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
                                    ) }}

                                    €

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
                                    style="
                                        width:
                                        {{ min(
                                            $budget->calculated_percentage,
                                            100
                                        ) }}%
                                    "
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
                                        ) }}

                                        €

                                    </span>

                                @else

                                    <span class="text-xs font-medium text-red-600">

                                        {{ number_format(
                                            abs(
                                                $budget->calculated_remaining
                                            ),
                                            2,
                                            ',',
                                            '.'
                                        ) }}

                                        € über Budget

                                    </span>

                                @endif

                            </div>

                        </div>


                        @if ($budget->categories->isNotEmpty())

                            <div class="flex flex-wrap gap-2 mt-5">

                                @foreach ($budget->categories->take(3) as $category)

                                    <span
                                        class="
                                            rounded-full
                                            bg-slate-100
                                            px-2.5
                                            py-1
                                            text-xs
                                            text-slate-600
                                        "
                                    >

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

                    <h3 class="font-semibold text-slate-900">
                        Deine Konten
                    </h3>

                    <p class="text-sm text-slate-500 mt-1">
                        Aktuelle Kontostände
                    </p>

                </div>

                <a
                    href="{{ route('accounts.index') }}"
                    class="text-sm text-slate-500 hover:text-slate-900"
                >
                    Alle anzeigen →
                </a>

            </div>


            @if ($accounts->isEmpty())

                <div class="p-10 text-center">

                    <div class="text-4xl">
                        🏦
                    </div>

                    <p class="font-medium text-slate-900 mt-3">
                        Noch keine Konten
                    </p>

                    <a
                        href="{{ route('accounts.create') }}"
                        class="inline-flex mt-4 rounded-xl bg-slate-950 px-4 py-2 text-sm font-medium text-white"
                    >
                        Konto erstellen
                    </a>

                </div>

            @else

                <div class="divide-y divide-slate-100">

                    @foreach ($accounts as $account)

                        <a
                            href="{{ route('accounts.edit', $account) }}"
                            class="flex items-center gap-4 p-5 hover:bg-slate-50 transition"
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

                                <p
                                    class="
                                        font-semibold
                                        {{ $account->calculated_balance >= 0
                                            ? 'text-slate-900'
                                            : 'text-red-600' }}
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

                            </div>

                        </a>

                    @endforeach

                </div>

            @endif

        </div>


        {{-- AUSGABEN --}}

        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">

            <div class="p-6 border-b border-slate-100">

                <h3 class="font-semibold text-slate-900">
                    Ausgaben
                </h3>

                <p class="text-sm text-slate-500 mt-1">
                    {{ $currentMonth }}
                </p>

            </div>


            @if ($expensesByCategory->isEmpty())

                <div class="p-8 text-center">

                    <div class="text-3xl">
                        📊
                    </div>

                    <p class="text-sm text-slate-500 mt-3">
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
                                    ) }}

                                    €

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

                <h3 class="font-semibold text-slate-900">
                    Letzte Buchungen
                </h3>

                <p class="text-sm text-slate-500 mt-1">
                    Deine zuletzt erfassten Transaktionen
                </p>

            </div>


            <a
                href="{{ route('transactions.index') }}"
                class="text-sm text-slate-500 hover:text-slate-900"
            >
                Alle anzeigen →
            </a>

        </div>


        @if ($recentTransactions->isEmpty())

            <div class="p-10 text-center">

                <div class="text-4xl">
                    💸
                </div>

                <p class="font-medium text-slate-900 mt-3">
                    Noch keine Buchungen
                </p>

            </div>

        @else

            <div class="divide-y divide-slate-100">

                @foreach ($recentTransactions as $transaction)

                    <a
                        href="{{ route(
                            'transactions.edit',
                            $transaction
                        ) }}"
                        class="flex items-center gap-4 p-5 hover:bg-slate-50 transition"
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


                        <p
                            class="
                                font-semibold
                                whitespace-nowrap
                                {{ $transaction->type === 'income'
                                    ? 'text-emerald-600'
                                    : 'text-red-600' }}
                            "
                        >

                            {{ $transaction->type === 'income'
                                ? '+'
                                : '-' }}

                            {{ number_format(
                                $transaction->amount,
                                2,
                                ',',
                                '.'
                            ) }}

                            €

                        </p>

                    </a>

                @endforeach

            </div>

        @endif

    </div>



    {{-- ========================================================= --}}
    {{-- JAHRESÜBERSICHT --}}
    {{-- ========================================================= --}}

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-5">

        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">

            <p class="text-sm text-slate-500">
                Einnahmen dieses Jahr
            </p>

            <p class="text-2xl font-semibold text-emerald-600 mt-2">

                +{{ number_format(
                    $yearlyIncome,
                    2,
                    ',',
                    '.'
                ) }}

                €

            </p>

        </div>


        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">

            <p class="text-sm text-slate-500">
                Ausgaben dieses Jahr
            </p>

            <p class="text-2xl font-semibold text-red-600 mt-2">

                -{{ number_format(
                    $yearlyExpense,
                    2,
                    ',',
                    '.'
                ) }}

                €

            </p>

        </div>

    </div>



    {{-- ========================================================= --}}
    {{-- SCHNELLZUGRIFF --}}
    {{-- ========================================================= --}}

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-5">

        <a
            href="{{ route('accounts.index') }}"
            class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 hover:bg-slate-50 transition"
        >

            <span class="text-2xl">
                🏦
            </span>

            <p class="font-medium text-slate-900 mt-3">
                Konten
            </p>

        </a>


        <a
            href="{{ route('transactions.index') }}"
            class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 hover:bg-slate-50 transition"
        >

            <span class="text-2xl">
                💳
            </span>

            <p class="font-medium text-slate-900 mt-3">
                Buchungen
            </p>

        </a>


        <a
            href="{{ route('categories.index') }}"
            class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 hover:bg-slate-50 transition"
        >

            <span class="text-2xl">
                🗂️
            </span>

            <p class="font-medium text-slate-900 mt-3">
                Kategorien
            </p>

        </a>


        <a
            href="{{ route('budgets.index') }}"
            class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 hover:bg-slate-50 transition"
        >

            <span class="text-2xl">
                🎯
            </span>

            <p class="font-medium text-slate-900 mt-3">
                Budgets
            </p>

        </a>

    </div>

</div>

@endsection