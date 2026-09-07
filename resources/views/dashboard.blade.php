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
    {{-- ========================================================= --}}
    {{-- DYNAMISCHE DASHBOARD-WIDGETS --}}
    {{-- ========================================================= --}}

    @php
        /*
         * Großzügiges 12-Spalten-Dashboard.
         *
         * Die Widget-Reihenfolge bleibt vollständig dynamisch.
         * Die Breite eines Widgets wird zentral hier definiert.
         *
         * Mobile: 1 Spalte
         * Tablet:  6 Spalten
         * Desktop: 12 Spalten
         */
        $dashboardWidgetWidths = [
            // Kennzahlen
            'summary' => 'lg:col-span-3',
            'income' => 'lg:col-span-3',
            'expenses' => 'lg:col-span-3',
            'savings_rate' => 'lg:col-span-3',

            // Große Auswertungen
            'monthly_balance' => 'lg:col-span-8',
            'yearly' => 'lg:col-span-4',

            'income_expense_chart' => 'lg:col-span-12',
            'wealth_chart' => 'lg:col-span-12',

            // Planung
            'budgets' => 'lg:col-span-12',

            // Karten / Kredite
            'credit_cards' => 'lg:col-span-6',
            'loans' => 'lg:col-span-6',

            // Übersichten
            'accounts' => 'lg:col-span-6',
            'categories' => 'lg:col-span-6',
            'recent_transactions' => 'lg:col-span-12',

            // Schnellzugriff
            'quick_actions' => 'lg:col-span-12',
        ];
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-5 mt-8 items-start">

        @foreach($dashboardWidgetOrder as $widget)

            @if($dashboardWidgets[$widget] ?? false)

                <div class="{{ $dashboardWidgetWidths[$widget] ?? 'lg:col-span-12' }}">
                    @include('dashboard.widgets.' . $widget)
                </div>

            @endif

        @endforeach

    </div>

</div>

@endsection
