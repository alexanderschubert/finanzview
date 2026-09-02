@extends('layouts.app')

@section('title', 'Budgets – FinanzView')

@section('eyebrow', 'Finanzplanung')

@section('page_title', 'Budgets')

@section('content')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-6">

        <div class="min-w-0">

            <div class="flex items-center gap-2">

                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 flex-shrink-0"></span>

                <p class="text-sm font-medium text-emerald-600 dark:text-emerald-400">
                    Finanzplanung
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
                Deine Budgets
            </h2>

            <p class="text-slate-500 dark:text-slate-400 mt-2">
                Plane deine Ausgaben und behalte deine Ziele im Blick.
            </p>

        </div>


        {{-- AKTIONEN --}}

        <div class="flex flex-col sm:flex-row gap-3">

            <form
                method="GET"
                action="{{ route('budgets.index') }}"
                class="flex gap-2"
            >

                <input
                    type="month"
                    name="month"
                    value="{{ $selectedMonth }}"
                    class="
                        rounded-xl
                        border
                        border-slate-200
                        dark:border-slate-700
                        bg-white
                        dark:bg-slate-800
                        px-4
                        py-3
                        text-sm
                        text-slate-700
                        dark:text-slate-200
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
                        dark:bg-slate-800
                        px-4
                        py-3
                        text-sm
                        font-medium
                        text-slate-700
                        dark:text-slate-200
                        hover:bg-slate-50
                        dark:hover:bg-slate-700
                        transition
                    "
                >
                    Anzeigen
                </button>

            </form>


            <a
                href="{{ route('budgets.create') }}"
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
                    flex-shrink-0
                "
            >
                <span class="mr-2 text-emerald-200">
                    +
                </span>

                Budget erstellen

            </a>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- MONAT --}}
    {{-- ========================================================= --}}

    <div
        class="
            mt-8
            rounded-3xl
            bg-white
            dark:bg-slate-900
            border
            border-slate-100
            dark:border-slate-800
            shadow-sm
            p-6
        "
    >

        <div
            class="
                flex
                flex-col
                sm:flex-row
                sm:items-center
                sm:justify-between
                gap-4
            "
        >

            <div>

                <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                    Budgetübersicht
                </p>

                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mt-1">
                    {{ $referenceMonth->translatedFormat('F Y') }}
                </h3>

                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Budgetverbrauch für den ausgewählten Monat.
                </p>

            </div>


            <div
                class="
                    inline-flex
                    items-center
                    rounded-full
                    bg-slate-100
                    dark:bg-slate-800
                    px-3
                    py-1.5
                    text-xs
                    font-medium
                    text-slate-600
                    dark:text-slate-300
                    self-start
                    sm:self-auto
                "
            >

                {{ $budgets->count() }}

                {{ $budgets->count() === 1 ? 'Budget' : 'Budgets' }}

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- ERFOLGSMELDUNG --}}
    {{-- ========================================================= --}}

    @if (session('success'))

        <div
            class="
                mt-5
                rounded-2xl
                border
                border-emerald-100
                dark:border-emerald-900
                bg-emerald-50
                dark:bg-emerald-950/40
                p-4
                text-sm
                text-emerald-700
                dark:text-emerald-300
            "
        >

            <div class="flex items-center gap-3">

                <span class="text-lg">
                    ✓
                </span>

                <span>
                    {{ session('success') }}
                </span>

            </div>

        </div>

    @endif


    {{-- ========================================================= --}}
    {{-- KEINE BUDGETS --}}
    {{-- ========================================================= --}}

    @if ($budgets->isEmpty())

        <div
            class="
                mt-8
                bg-white
                dark:bg-slate-900
                rounded-3xl
                border
                border-slate-100
                dark:border-slate-800
                shadow-sm
                p-10
                sm:p-14
                text-center
            "
        >

            <div
                class="
                    w-16
                    h-16
                    mx-auto
                    rounded-2xl
                    bg-emerald-50
                    dark:bg-emerald-950/50
                    flex
                    items-center
                    justify-center
                    text-3xl
                "
            >
                🎯
            </div>

            <h3 class="font-semibold text-slate-900 dark:text-white mt-5">
                Noch keine Budgets
            </h3>

            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 max-w-md mx-auto">
                Erstelle dein erstes Budget, um deine Ausgaben zu planen
                und deine finanziellen Ziele besser im Blick zu behalten.
            </p>

            <a
                href="{{ route('budgets.create') }}"
                class="
                    inline-flex
                    items-center
                    justify-center
                    mt-6
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


        {{-- ===================================================== --}}
        {{-- BUDGETKARTEN --}}
        {{-- ===================================================== --}}

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 mt-8">

            @foreach ($budgets as $budget)

                @php

                    $isApplicable =
                        (bool) ($budget->calculated_applicable ?? false);

                    $percentage =
                        max(
                            0,
                            (float) ($budget->calculated_percentage ?? 0)
                        );

                    $remaining =
                        (float) ($budget->calculated_remaining ?? 0);

                    $spent =
                        (float) ($budget->calculated_spent ?? 0);

                    $budgetAmount =
                        (float) $budget->amount;

                @endphp


                <div
                    class="
                        bg-white
                        dark:bg-slate-900
                        rounded-3xl
                        border
                        border-slate-100
                        dark:border-slate-800
                        shadow-sm
                        overflow-hidden
                        transition
                        {{ !$budget->is_active || !$isApplicable
                            ? 'opacity-75'
                            : 'hover:shadow-md hover:-translate-y-0.5' }}
                    "
                >

                    {{-- ================================================= --}}
                    {{-- INHALT --}}
                    {{-- ================================================= --}}

                    <div class="p-6">


                        {{-- HEADER --}}

                        <div class="flex items-start justify-between gap-4">

                            <div class="flex items-center gap-4 min-w-0">

                                <div
                                    class="
                                        w-12
                                        h-12
                                        rounded-2xl
                                        flex
                                        items-center
                                        justify-center
                                        text-xl
                                        flex-shrink-0
                                    "
                                    style="
                                        background-color:
                                        {{ $budget->color ?: '#ecfdf5' }}
                                    "
                                >
                                    {{ $budget->icon ?: '🎯' }}
                                </div>


                                <div class="min-w-0">

                                    <h3
                                        class="
                                            font-semibold
                                            text-slate-900
                                            dark:text-white
                                            truncate
                                        "
                                    >
                                        {{ $budget->name }}
                                    </h3>

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

                                    </p>

                                </div>

                            </div>


                            {{-- STATUS --}}

                            @if (!$budget->is_active)

                                <span
                                    class="
                                        flex-shrink-0
                                        rounded-full
                                        bg-slate-100
                                        dark:bg-slate-800
                                        px-2.5
                                        py-1
                                        text-xs
                                        font-medium
                                        text-slate-500
                                        dark:text-slate-400
                                    "
                                >
                                    Inaktiv
                                </span>

                            @elseif (!$isApplicable)

                                <span
                                    class="
                                        flex-shrink-0
                                        rounded-full
                                        bg-slate-100
                                        dark:bg-slate-800
                                        px-2.5
                                        py-1
                                        text-xs
                                        font-medium
                                        text-slate-500
                                        dark:text-slate-400
                                    "
                                >
                                    Nicht gültig
                                </span>

                            @elseif ($budget->calculated_exceeded)

                                <span
                                    class="
                                        flex-shrink-0
                                        rounded-full
                                        bg-red-50
                                        dark:bg-red-950/40
                                        px-2.5
                                        py-1
                                        text-xs
                                        font-medium
                                        text-red-600
                                        dark:text-red-400
                                    "
                                >
                                    Überschritten
                                </span>

                            @elseif ($percentage >= 80)

                                <span
                                    class="
                                        flex-shrink-0
                                        rounded-full
                                        bg-amber-50
                                        dark:bg-amber-950/40
                                        px-2.5
                                        py-1
                                        text-xs
                                        font-medium
                                        text-amber-600
                                        dark:text-amber-400
                                    "
                                >
                                    Achtung
                                </span>

                            @else

                                <span
                                    class="
                                        flex-shrink-0
                                        rounded-full
                                        bg-emerald-50
                                        dark:bg-emerald-950/40
                                        px-2.5
                                        py-1
                                        text-xs
                                        font-medium
                                        text-emerald-600
                                        dark:text-emerald-400
                                    "
                                >
                                    OK
                                </span>

                            @endif

                        </div>


                        {{-- ZEITRAUM --}}

                        <div
                            class="
                                mt-6
                                rounded-2xl
                                bg-slate-50
                                dark:bg-slate-800
                                px-4
                                py-3
                            "
                        >

                            <p class="text-xs text-slate-400 dark:text-slate-500">
                                Berechnungszeitraum
                            </p>

                            <p class="text-sm font-medium text-slate-700 dark:text-slate-200 mt-1">

                                {{ $budget->calculated_start_date->format('d.m.Y') }}

                                –

                                {{ $budget->calculated_end_date->format('d.m.Y') }}

                            </p>

                        </div>


                        {{-- BUDGETBETRAG --}}

                        <div class="mt-6">

                            <p class="text-xs text-slate-400 dark:text-slate-500">
                                Budget
                            </p>

                            <p class="text-3xl font-semibold text-slate-900 dark:text-white mt-1">

                                {{ number_format(
                                    $budgetAmount,
                                    2,
                                    ',',
                                    '.'
                                ) }} €

                            </p>

                        </div>


                        {{-- ================================================= --}}
                        {{-- NICHT GÜLTIG --}}
                        {{-- ================================================= --}}

                        @if (!$isApplicable)

                            <div
                                class="
                                    mt-6
                                    rounded-2xl
                                    bg-slate-50
                                    dark:bg-slate-800
                                    p-4
                                "
                            >

                                <div class="flex items-center gap-3">

                                    <div
                                        class="
                                            w-9
                                            h-9
                                            rounded-xl
                                            bg-slate-200
                                            dark:bg-slate-700
                                            flex
                                            items-center
                                            justify-center
                                        "
                                    >
                                        🕐
                                    </div>

                                    <div>

                                        <p class="text-sm font-medium text-slate-700 dark:text-slate-200">
                                            Nicht gültig
                                        </p>

                                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                                            Für {{ $referenceMonth->translatedFormat('F Y') }}
                                            werden keine Ausgaben angerechnet.
                                        </p>

                                    </div>

                                </div>

                            </div>

                        @else


                            {{-- VERBRAUCH --}}

                            <div class="mt-6">

                                <div class="flex items-center justify-between">

                                    <span class="text-sm text-slate-500 dark:text-slate-400">
                                        Ausgegeben
                                    </span>

                                    <span
                                        class="
                                            text-sm
                                            font-semibold
                                            {{ $budget->calculated_exceeded
                                                ? 'text-red-600 dark:text-red-400'
                                                : 'text-slate-900 dark:text-white' }}
                                        "
                                    >

                                        {{ number_format(
                                            $spent,
                                            2,
                                            ',',
                                            '.'
                                        ) }} €

                                    </span>

                                </div>


                                {{-- PROGRESSBAR --}}

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
                                                    $percentage >= 80
                                                        ? 'bg-amber-500'
                                                        : 'bg-emerald-500'
                                                ) }}
                                        "
                                        style="
                                            width:
                                            {{ min($percentage, 100) }}%
                                        "
                                    ></div>

                                </div>


                                {{-- PROZENT / REST --}}

                                <div class="flex items-center justify-between mt-2">

                                    <span class="text-xs text-slate-400 dark:text-slate-500">

                                        {{ number_format(
                                            $percentage,
                                            1,
                                            ',',
                                            '.'
                                        ) }} %

                                    </span>


                                    @if ($remaining >= 0)

                                        <span class="text-xs text-emerald-600 dark:text-emerald-400">

                                            Noch

                                            {{ number_format(
                                                $remaining,
                                                2,
                                                ',',
                                                '.'
                                            ) }} €

                                        </span>

                                    @else

                                        <span class="text-xs font-medium text-red-600 dark:text-red-400">

                                            {{ number_format(
                                                abs($remaining),
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


                        {{-- ================================================= --}}
                        {{-- KATEGORIEN --}}
                        {{-- ================================================= --}}

                        <div class="mt-6">

                            <p class="text-xs text-slate-400 dark:text-slate-500 mb-2">
                                Zugeordnete Kategorien
                            </p>

                            @if ($budget->categories->isEmpty())

                                <p class="text-sm text-slate-400 dark:text-slate-500">
                                    Keine Kategorien zugeordnet.
                                </p>

                            @else

                                <div class="flex flex-wrap gap-2">

                                    @foreach ($budget->categories as $category)

                                        <span
                                            class="
                                                inline-flex
                                                items-center
                                                gap-1.5
                                                rounded-full
                                                bg-slate-100
                                                dark:bg-slate-800
                                                px-2.5
                                                py-1
                                                text-xs
                                                text-slate-600
                                                dark:text-slate-300
                                            "
                                        >

                                            {{ $category->icon ?: '📁' }}

                                            {{ $category->name }}

                                        </span>

                                    @endforeach

                                </div>

                            @endif

                        </div>

                    </div>


                    {{-- ================================================= --}}
                    {{-- AKTIONEN --}}
                    {{-- ================================================= --}}

                    <div
                        class="
                            px-6
                            py-4
                            bg-slate-50
                            dark:bg-slate-800/60
                            border-t
                            border-slate-100
                            dark:border-slate-800
                            flex
                            items-center
                            justify-between
                            gap-4
                        "
                    >

                        <a
                            href="{{ route(
                                'budgets.show',
                                [
                                    'budget' => $budget,
                                    'month' => $selectedMonth,
                                ]
                            ) }}"
                            class="
                                text-sm
                                font-medium
                                text-slate-700
                                dark:text-slate-200
                                hover:text-slate-950
                                dark:hover:text-white
                                transition
                            "
                        >
                            Details →
                        </a>


                        <div class="flex items-center gap-4">

                            <a
                                href="{{ route('budgets.edit', $budget) }}"
                                class="
                                    text-sm
                                    text-slate-500
                                    dark:text-slate-400
                                    hover:text-slate-900
                                    dark:hover:text-white
                                    transition
                                "
                            >
                                Bearbeiten
                            </a>


                            <form
                                method="POST"
                                action="{{ route('budgets.destroy', $budget) }}"
                                onsubmit="return confirm('Möchtest du dieses Budget wirklich löschen?');"
                            >

                                @csrf

                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="
                                        text-sm
                                        text-red-500
                                        dark:text-red-400
                                        hover:text-red-700
                                        dark:hover:text-red-300
                                        transition
                                    "
                                >
                                    Löschen
                                </button>

                            </form>

                        </div>

                    </div>

                </div>

            @endforeach

        </div>

    @endif

</div>

@endsection