@extends('layouts.app')

@section('title', $budget->name . ' – Budget – Finanzblick')
@section('eyebrow', 'Budget')
@section('page_title', $budget->name)

@section('content')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">

        <div class="min-w-0">

            <a
                href="{{ route('budgets.index', ['month' => $selectedMonth]) }}"
                class="
                    inline-flex
                    items-center
                    gap-2
                    text-sm
                    text-slate-500
                    dark:text-slate-400
                    hover:text-slate-900
                    dark:hover:text-white
                    transition
                "
            >
                ← Zurück zu den Budgets
            </a>

            <div class="flex items-center gap-4 mt-5">

                <div
                    class="
                        w-14
                        h-14
                        rounded-2xl
                        flex
                        items-center
                        justify-center
                        text-2xl
                        flex-shrink-0
                    "
                    style="background-color: {{ $budget->color ?: '#ecfdf5' }}"
                >
                    {{ $budget->icon ?: '🎯' }}
                </div>

                <div class="min-w-0">

                    <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                        Budget
                    </p>

                    <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900 dark:text-white mt-1 truncate">
                        {{ $budget->name }}
                    </h2>

                    <p class="text-slate-500 dark:text-slate-400 mt-2">
                        Übersicht für {{ $referenceMonth->translatedFormat('F Y') }}.
                    </p>

                </div>

            </div>

        </div>


        {{-- AKTIONEN --}}

        <div class="flex flex-col sm:flex-row gap-3">

            <form
                method="GET"
                action="{{ route('budgets.show', $budget) }}"
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
                href="{{ route('budgets.edit', $budget) }}"
                class="
                    inline-flex
                    items-center
                    justify-center
                    rounded-xl
                    bg-slate-900
                    dark:bg-white
                    px-5
                    py-3
                    text-sm
                    font-medium
                    text-white
                    dark:text-slate-900
                    hover:bg-slate-800
                    dark:hover:bg-slate-100
                    transition
                "
            >
                Budget bearbeiten
            </a>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- NICHT AKTIV --}}
    {{-- ========================================================= --}}

    @if (!$calculation['applicable'])

        <div
            class="
                mt-8
                rounded-3xl
                border
                border-amber-200
                dark:border-amber-900
                bg-amber-50
                dark:bg-amber-950/30
                p-6
            "
        >

            <div class="flex items-start gap-4">

                <div
                    class="
                        w-11
                        h-11
                        rounded-2xl
                        bg-amber-100
                        dark:bg-amber-900/50
                        flex
                        items-center
                        justify-center
                        text-xl
                        flex-shrink-0
                    "
                >
                    🕐
                </div>

                <div>

                    <h3 class="font-semibold text-amber-900 dark:text-amber-200">
                        Budget ist in diesem Monat nicht aktiv
                    </h3>

                    <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">
                        Das Budget ist für
                        {{ $referenceMonth->translatedFormat('F Y') }}
                        noch nicht bzw. nicht mehr gültig.
                    </p>

                    <p class="text-xs text-amber-600 dark:text-amber-400 mt-2">
                        Budgetbeginn:
                        {{ $budget->start_date->format('d.m.Y') }}

                        @if ($budget->end_date)
                            · Ende:
                            {{ $budget->end_date->format('d.m.Y') }}
                        @endif
                    </p>

                </div>

            </div>

        </div>

    @endif


    {{-- ========================================================= --}}
    {{-- KENNZAHLEN --}}
    {{-- ========================================================= --}}

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-8">

        {{-- BUDGET --}}

        <div
            class="
                bg-white
                dark:bg-slate-900
                rounded-3xl
                border
                border-slate-100
                dark:border-slate-800
                shadow-sm
                p-6
            "
        >

            <div class="flex items-center justify-between">

                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Budget
                </p>

                <div
                    class="
                        w-9
                        h-9
                        rounded-xl
                        bg-emerald-50
                        dark:bg-emerald-950/50
                        flex
                        items-center
                        justify-center
                    "
                >
                    🎯
                </div>

            </div>

            <p class="text-3xl font-semibold text-slate-900 dark:text-white mt-5">

                {{ number_format(
                    $budget->amount,
                    2,
                    ',',
                    '.'
                ) }} €

            </p>

            <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">
                {{ ucfirst($budget->period === 'monthly'
                    ? 'monatlich'
                    : ($budget->period === 'yearly'
                        ? 'jährlich'
                        : 'benutzerdefiniert')) }}
            </p>

        </div>


        {{-- VERBRAUCH --}}

        <div
            class="
                bg-white
                dark:bg-slate-900
                rounded-3xl
                border
                border-slate-100
                dark:border-slate-800
                shadow-sm
                p-6
            "
        >

            <div class="flex items-center justify-between">

                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Verbrauch
                </p>

                <div
                    class="
                        w-9
                        h-9
                        rounded-xl
                        {{ $calculation['exceeded']
                            ? 'bg-red-50 dark:bg-red-950/50'
                            : 'bg-amber-50 dark:bg-amber-950/50' }}
                        flex
                        items-center
                        justify-center
                    "
                >
                    {{ $calculation['exceeded'] ? '⚠️' : '📊' }}
                </div>

            </div>

            <p
                class="
                    text-3xl
                    font-semibold
                    mt-5
                    {{ $calculation['exceeded']
                        ? 'text-red-600 dark:text-red-400'
                        : 'text-slate-900 dark:text-white' }}
                "
            >

                {{ number_format(
                    $calculation['spent'],
                    2,
                    ',',
                    '.'
                ) }} €

            </p>

            <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">

                {{ number_format(
                    $calculation['percentage'],
                    1,
                    ',',
                    '.'
                ) }} % verbraucht

            </p>

        </div>


        {{-- VERBLEIBEND --}}

        <div
            class="
                bg-white
                dark:bg-slate-900
                rounded-3xl
                border
                border-slate-100
                dark:border-slate-800
                shadow-sm
                p-6
            "
        >

            <div class="flex items-center justify-between">

                <p class="text-sm text-slate-500 dark:text-slate-400">

                    {{ $calculation['remaining'] >= 0
                        ? 'Verbleibend'
                        : 'Über Budget' }}

                </p>

                <div
                    class="
                        w-9
                        h-9
                        rounded-xl
                        {{ $calculation['remaining'] >= 0
                            ? 'bg-emerald-50 dark:bg-emerald-950/50'
                            : 'bg-red-50 dark:bg-red-950/50' }}
                        flex
                        items-center
                        justify-center
                    "
                >
                    {{ $calculation['remaining'] >= 0 ? '✓' : '!' }}
                </div>

            </div>

            <p
                class="
                    text-3xl
                    font-semibold
                    mt-5
                    {{ $calculation['remaining'] >= 0
                        ? 'text-emerald-600 dark:text-emerald-400'
                        : 'text-red-600 dark:text-red-400' }}
                "
            >

                {{ number_format(
                    abs($calculation['remaining']),
                    2,
                    ',',
                    '.'
                ) }} €

            </p>

            <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">

                @if ($calculation['remaining'] >= 0)
                    stehen noch zur Verfügung
                @else
                    über dem Budget
                @endif

            </p>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- BUDGETFORTSCHRITT --}}
    {{-- ========================================================= --}}

    <div
        class="
            bg-white
            dark:bg-slate-900
            rounded-3xl
            border
            border-slate-100
            dark:border-slate-800
            shadow-sm
            p-6
            sm:p-8
            mt-5
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
                    Fortschritt
                </p>

                <h3 class="text-xl font-semibold text-slate-900 dark:text-white mt-1">
                    Budgetverbrauch
                </h3>

            </div>

            <div class="text-left sm:text-right">

                <p
                    class="
                        text-2xl
                        font-semibold
                        {{ $calculation['exceeded']
                            ? 'text-red-600 dark:text-red-400'
                            : 'text-slate-900 dark:text-white' }}
                    "
                >
                    {{ number_format(
                        $calculation['percentage'],
                        1,
                        ',',
                        '.'
                    ) }} %
                </p>

            </div>

        </div>


        <div
            class="
                h-5
                bg-slate-100
                dark:bg-slate-800
                rounded-full
                overflow-hidden
                mt-6
            "
        >

            <div
                class="
                    h-full
                    rounded-full
                    transition-all
                    {{ $calculation['exceeded']
                        ? 'bg-red-500'
                        : (
                            $calculation['percentage'] >= 80
                                ? 'bg-amber-500'
                                : 'bg-emerald-500'
                        ) }}
                "
                style="
                    width:
                    {{ min(
                        max(
                            $calculation['percentage'],
                            0
                        ),
                        100
                    ) }}%
                "
            ></div>

        </div>


        <div class="flex items-center justify-between mt-3">

            <span class="text-sm text-slate-500 dark:text-slate-400">
                {{ number_format($calculation['spent'], 2, ',', '.') }} €
            </span>

            <span class="text-sm text-slate-500 dark:text-slate-400">
                {{ number_format($budget->amount, 2, ',', '.') }} €
            </span>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- ZEITRAUM + KATEGORIEN --}}
    {{-- ========================================================= --}}

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mt-5">

        {{-- ZEITRAUM --}}

        <div
            class="
                bg-white
                dark:bg-slate-900
                rounded-3xl
                border
                border-slate-100
                dark:border-slate-800
                shadow-sm
                p-6
            "
        >

            <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                Zeitraum
            </p>

            <h3 class="font-semibold text-slate-900 dark:text-white mt-1">
                Gültigkeit
            </h3>

            <div class="mt-5 space-y-4">

                <div class="flex items-center justify-between gap-4">

                    <span class="text-sm text-slate-500 dark:text-slate-400">
                        Von
                    </span>

                    <span class="font-medium text-slate-900 dark:text-white">
                        {{ $calculation['start_date']->format('d.m.Y') }}
                    </span>

                </div>

                <div class="flex items-center justify-between gap-4">

                    <span class="text-sm text-slate-500 dark:text-slate-400">
                        Bis
                    </span>

                    <span class="font-medium text-slate-900 dark:text-white">
                        {{ $calculation['end_date']->format('d.m.Y') }}
                    </span>

                </div>

                <div
                    class="
                        pt-4
                        border-t
                        border-slate-100
                        dark:border-slate-800
                    "
                >

                    <p class="text-xs text-slate-400 dark:text-slate-500">
                        Bezugsmonat
                    </p>

                    <p class="font-medium text-slate-900 dark:text-white mt-1">
                        {{ $referenceMonth->translatedFormat('F Y') }}
                    </p>

                </div>

            </div>

        </div>


        {{-- KATEGORIEN --}}

        <div
            class="
                bg-white
                dark:bg-slate-900
                rounded-3xl
                border
                border-slate-100
                dark:border-slate-800
                shadow-sm
                p-6
            "
        >

            <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                Kategorien
            </p>

            <h3 class="font-semibold text-slate-900 dark:text-white mt-1">
                Berücksichtigte Kategorien
            </h3>

            @if ($budget->categories->isEmpty())

                <div
                    class="
                        mt-5
                        rounded-2xl
                        bg-amber-50
                        dark:bg-amber-950/30
                        p-4
                    "
                >

                    <p class="text-sm text-amber-700 dark:text-amber-300">
                        Diesem Budget sind keine Kategorien zugeordnet.
                    </p>

                </div>

            @else

                <div class="flex flex-wrap gap-2 mt-5">

                    @foreach ($budget->categories as $category)

                        <span
                            class="
                                inline-flex
                                items-center
                                gap-2
                                rounded-full
                                bg-slate-100
                                dark:bg-slate-800
                                px-3
                                py-2
                                text-sm
                                text-slate-700
                                dark:text-slate-300
                            "
                        >

                            <span>
                                {{ $category->icon ?: '📁' }}
                            </span>

                            {{ $category->name }}

                        </span>

                    @endforeach

                </div>

            @endif

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- BUCHUNGEN --}}
    {{-- ========================================================= --}}

    <div
        class="
            bg-white
            dark:bg-slate-900
            rounded-3xl
            border
            border-slate-100
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
                flex-col
                sm:flex-row
                sm:items-center
                sm:justify-between
                gap-3
            "
        >

            <div>

                <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                    Aktivitäten
                </p>

                <h3 class="font-semibold text-slate-900 dark:text-white mt-1">
                    Zugehörige Buchungen
                </h3>

                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Ausgaben der ausgewählten Budgetkategorien
                </p>

            </div>

            <span
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
                "
            >
                {{ $transactions->count() }}
                {{ $transactions->count() === 1 ? 'Buchung' : 'Buchungen' }}
            </span>

        </div>


        @if ($transactions->isEmpty())

            <div class="p-10 text-center">

                <div
                    class="
                        w-14
                        h-14
                        mx-auto
                        rounded-2xl
                        bg-slate-100
                        dark:bg-slate-800
                        flex
                        items-center
                        justify-center
                        text-2xl
                    "
                >
                    💳
                </div>

                <p class="font-medium text-slate-900 dark:text-white mt-4">
                    Keine Buchungen gefunden
                </p>

                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Für diesen Budgetzeitraum wurden keine passenden Ausgaben gefunden.
                </p>

            </div>

        @else

            <div class="divide-y divide-slate-100 dark:divide-slate-800">

                @foreach ($transactions as $transaction)

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
                                bg-red-50
                                dark:bg-red-950/50
                                flex
                                items-center
                                justify-center
                                flex-shrink-0
                                text-lg
                            "
                        >
                            {{ $transaction->category?->icon ?: '💳' }}
                        </div>


                        <div class="flex-1 min-w-0">

                            <p class="font-medium text-slate-900 dark:text-white truncate">
                                {{ $transaction->description ?: 'Ohne Beschreibung' }}
                            </p>

                            <p
                                class="
                                    text-sm
                                    text-slate-500
                                    dark:text-slate-400
                                    mt-1
                                    truncate
                                "
                            >

                                {{ $transaction->transaction_date?->format('d.m.Y') }}

                                @if ($transaction->category)
                                    · {{ $transaction->category->name }}
                                @endif

                                @if ($transaction->account)
                                    · {{ $transaction->account->name }}
                                @endif

                            </p>

                        </div>


                        <div class="text-right flex-shrink-0">

                            <p class="font-semibold text-red-600 dark:text-red-400">

                                -{{ number_format(
                                    $transaction->amount,
                                    2,
                                    ',',
                                    '.'
                                ) }} €

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


    {{-- ========================================================= --}}
    {{-- FOOTER AKTIONEN --}}
    {{-- ========================================================= --}}

    <div class="flex flex-col sm:flex-row gap-3 mt-5">

        <a
            href="{{ route('budgets.edit', $budget) }}"
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
            "
        >
            Budget bearbeiten
        </a>

        <a
            href="{{ route('budgets.index', ['month' => $selectedMonth]) }}"
            class="
                inline-flex
                items-center
                justify-center
                rounded-xl
                border
                border-slate-200
                dark:border-slate-700
                bg-white
                dark:bg-slate-800
                px-5
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
            ← Alle Budgets
        </a>

    </div>

</div>

@endsection