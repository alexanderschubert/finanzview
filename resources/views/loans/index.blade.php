@extends('layouts.app')

@section('title', 'Kredite – Finanzblick')

@section('eyebrow', 'Finanzplanung')

@section('page_title', 'Kredite')

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
                Deine Kredite
            </h2>

            <p class="text-slate-500 dark:text-slate-400 mt-2">
                Behalte Restschuld, Raten und Tilgungsfortschritt im Blick.
            </p>

        </div>


        <a
            href="{{ route('loans.create') }}"
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

            Kredit hinzufügen

        </a>

    </div>


    {{-- ========================================================= --}}
    {{-- ÜBERSICHT --}}
    {{-- ========================================================= --}}

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mt-8">


        {{-- RESTSCHULD --}}

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
                    Restschuld
                </p>

                <div
                    class="
                        w-10
                        h-10
                        rounded-xl
                        bg-red-50
                        dark:bg-red-950/40
                        flex
                        items-center
                        justify-center
                        text-lg
                    "
                >
                    💳
                </div>

            </div>

            <p class="text-2xl font-semibold text-red-600 dark:text-red-400 mt-5">
                {{ number_format($totalRemaining, 2, ',', '.') }} €
            </p>

            <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">
                Noch offene Kreditsumme
            </p>

        </div>


        {{-- GETILGT --}}

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
                    Bereits getilgt
                </p>

                <div
                    class="
                        w-10
                        h-10
                        rounded-xl
                        bg-emerald-50
                        dark:bg-emerald-950/40
                        flex
                        items-center
                        justify-center
                        text-lg
                        text-emerald-600
                        dark:text-emerald-400
                    "
                >
                    ✓
                </div>

            </div>

            <p class="text-2xl font-semibold text-emerald-600 dark:text-emerald-400 mt-5">
                {{ number_format($totalPaid, 2, ',', '.') }} €
            </p>

            <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">
                {{ number_format($overallProgress, 1, ',', '.') }} % getilgt
            </p>

        </div>


        {{-- MONATLICHE RATEN --}}

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
                    Monatliche Raten
                </p>

                <div
                    class="
                        w-10
                        h-10
                        rounded-xl
                        bg-blue-50
                        dark:bg-blue-950/40
                        flex
                        items-center
                        justify-center
                        text-lg
                    "
                >
                    📅
                </div>

            </div>

            <p class="text-2xl font-semibold text-slate-900 dark:text-white mt-5">
                {{ number_format($monthlyInstallments, 2, ',', '.') }} €
            </p>

            <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">
                Monatliche Belastung
            </p>

        </div>


        {{-- KREDITE --}}

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
                    Kredite
                </p>

                <div
                    class="
                        w-10
                        h-10
                        rounded-xl
                        bg-slate-100
                        dark:bg-slate-800
                        flex
                        items-center
                        justify-center
                        text-lg
                    "
                >
                    📊
                </div>

            </div>

            <p class="text-2xl font-semibold text-slate-900 dark:text-white mt-5">
                {{ $loans->count() }}
            </p>

            <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">
                {{ $loans->where('is_active', true)->count() }} aktiv
            </p>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- GESAMTFORTSCHRITT --}}
    {{-- ========================================================= --}}

    @if ($loans->count() > 0)

        <div
            class="
                mt-5
                rounded-3xl
                border
                border-emerald-100
                dark:border-emerald-900
                bg-emerald-50
                dark:bg-emerald-950/30
                p-6
                sm:p-8
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
                        Tilgung
                    </p>

                    <h3 class="text-xl font-semibold text-slate-900 dark:text-white mt-1">
                        Gesamtfortschritt
                    </h3>

                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Fortschritt über alle deine Kredite.
                    </p>

                </div>

                <p class="text-3xl font-semibold text-emerald-700 dark:text-emerald-400">
                    {{ number_format($overallProgress, 1, ',', '.') }} %
                </p>

            </div>


            <div
                class="
                    h-3
                    rounded-full
                    bg-white/80
                    dark:bg-slate-800
                    overflow-hidden
                    mt-6
                "
            >

                <div
                    class="
                        h-full
                        rounded-full
                        bg-emerald-500
                        transition-all
                        duration-500
                    "
                    style="
                        width:
                        {{ min(100, max(0, $overallProgress)) }}%
                    "
                ></div>

            </div>


            <div
                class="
                    flex
                    flex-col
                    sm:flex-row
                    sm:justify-between
                    gap-2
                    mt-3
                    text-xs
                    text-slate-500
                    dark:text-slate-400
                "
            >

                <span>
                    {{ number_format($totalPaid, 2, ',', '.') }} €
                    bereits getilgt
                </span>

                <span>
                    {{ number_format($totalPrincipal, 2, ',', '.') }} €
                    ursprüngliche Kreditsumme
                </span>

            </div>

        </div>

    @endif


    {{-- ========================================================= --}}
    {{-- KREDITLISTE --}}
    {{-- ========================================================= --}}

    @if ($loans->count() > 0)

        <div class="mt-8">

            <div class="mb-4">

                <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                    Finanzierungen
                </p>

                <h3 class="text-xl font-semibold text-slate-900 dark:text-white mt-1">
                    Deine Kredite
                </h3>

                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Alle Finanzierungen und laufenden Raten.
                </p>

            </div>


            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">

                @foreach ($loans as $loan)

                    @php

                        $progress =
                            (float) ($loan->progress ?? 0);

                        $remaining =
                            (float) ($loan->remaining_amount ?? 0);

                        $icon =
                            $loan->creditor_icon ?: '💳';

                        $creditor =
                            $loan->creditor_name ?: 'Kredit';

                        $color =
                            $loan->creditor_color ?: '#10b981';

                    @endphp


                    <a
                        href="{{ route('loans.show', $loan) }}"
                        class="
                            group
                            block
                            bg-white
                            dark:bg-slate-900
                            rounded-3xl
                            border
                            border-slate-100
                            dark:border-slate-800
                            shadow-sm
                            overflow-hidden
                            hover:shadow-md
                            hover:-translate-y-0.5
                            transition
                        "
                    >

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
                                            {{ $color }}20;
                                        "
                                    >
                                        {{ $icon }}
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
                                            {{ $loan->name }}
                                        </h3>

                                        <p
                                            class="
                                                text-sm
                                                text-slate-500
                                                dark:text-slate-400
                                                mt-1
                                                truncate
                                            "
                                        >
                                            {{ $creditor }}
                                        </p>

                                    </div>

                                </div>


                                @if ($loan->is_active)

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
                                        Aktiv
                                    </span>

                                @else

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

                                @endif

                            </div>


                            {{-- RESTSCHULD --}}

                            <div class="mt-7">

                                <div class="flex items-end justify-between gap-4">

                                    <div>

                                        <p class="text-xs text-slate-400 dark:text-slate-500">
                                            Restschuld
                                        </p>

                                        <p
                                            class="
                                                text-2xl
                                                font-semibold
                                                text-slate-900
                                                dark:text-white
                                                mt-1
                                            "
                                        >
                                            {{ number_format(
                                                $remaining,
                                                2,
                                                ',',
                                                '.'
                                            ) }} €
                                        </p>

                                    </div>


                                    <span
                                        class="
                                            text-sm
                                            font-medium
                                            text-slate-500
                                            dark:text-slate-400
                                        "
                                    >
                                        {{ number_format(
                                            $progress,
                                            1,
                                            ',',
                                            '.'
                                        ) }} %
                                    </span>

                                </div>


                                {{-- PROGRESS --}}

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
                                        class="h-full rounded-full transition-all duration-500"
                                        style="
                                            width:
                                            {{ min(
                                                100,
                                                max(
                                                    0,
                                                    $progress
                                                )
                                            ) }}%;

                                            background-color:
                                            {{ $color }};
                                        "
                                    ></div>

                                </div>

                            </div>


                            {{-- INFORMATIONEN --}}

                            <div
                                class="
                                    grid
                                    grid-cols-2
                                    gap-4
                                    mt-6
                                    pt-5
                                    border-t
                                    border-slate-100
                                    dark:border-slate-800
                                "
                            >

                                <div>

                                    <p class="text-xs text-slate-400 dark:text-slate-500">
                                        Monatliche Rate
                                    </p>

                                    <p class="font-semibold text-slate-900 dark:text-white mt-1">
                                        {{ number_format(
                                            $loan->installment_amount,
                                            2,
                                            ',',
                                            '.'
                                        ) }} €
                                    </p>

                                </div>


                                <div>

                                    <p class="text-xs text-slate-400 dark:text-slate-500">
                                        Noch offen
                                    </p>

                                    <p class="font-semibold text-slate-900 dark:text-white mt-1">

                                        @if ($loan->remaining_installments !== null)

                                            {{ $loan->remaining_installments }}
                                            Raten

                                        @else

                                            –

                                        @endif

                                    </p>

                                </div>

                            </div>


                            {{-- ENDE --}}

                            @if ($loan->end_date)

                                <div
                                    class="
                                        flex
                                        items-center
                                        justify-between
                                        mt-5
                                        text-sm
                                    "
                                >

                                    <span class="text-slate-400 dark:text-slate-500">
                                        Voraussichtliches Ende
                                    </span>

                                    <span class="font-medium text-slate-700 dark:text-slate-200">
                                        {{ $loan->end_date->format('m/Y') }}
                                    </span>

                                </div>

                            @endif

                        </div>


                        {{-- FOOTER --}}

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
                            "
                        >

                            <span class="text-sm text-slate-500 dark:text-slate-400">
                                Kreditdetails
                            </span>

                            <span
                                class="
                                    text-slate-400
                                    dark:text-slate-500
                                    group-hover:translate-x-1
                                    transition-transform
                                "
                            >
                                →
                            </span>

                        </div>

                    </a>

                @endforeach

            </div>

        </div>

    @else


        {{-- ========================================================= --}}
        {{-- KEINE KREDITE --}}
        {{-- ========================================================= --}}

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
                    bg-slate-100
                    dark:bg-slate-800
                    flex
                    items-center
                    justify-center
                    text-3xl
                "
            >
                💳
            </div>

            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mt-5">
                Noch keine Kredite
            </h3>

            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 max-w-md mx-auto">
                Lege deinen ersten Kredit oder eine Finanzierung an,
                um Restschuld, Raten und Tilgungsfortschritt im Blick zu behalten.
            </p>

            <a
                href="{{ route('loans.create') }}"
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
                + Ersten Kredit anlegen
            </a>

        </div>

    @endif

</div>

@endsection