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

    @php
        $firstName = \Illuminate\Support\Str::before(trim(auth()->user()->name), ' ');
        $hour = (int) now()->format('G');
        $greeting = $hour < 11 ? 'Guten Morgen' : ($hour < 18 ? 'Hallo' : 'Guten Abend');
    @endphp

    <x-page-header
        :title="$greeting . ', ' . $firstName"
        subtitle="Deine Finanzen im Überblick."
    >
        <x-month-switcher route="dashboard" :month="$selectedMonth" />
    </x-page-header>


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

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-4 mt-6 items-stretch">

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
