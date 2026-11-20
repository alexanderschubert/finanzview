@extends('layouts.app')

@section('title', 'Dashboard – FinanzView')
@section('eyebrow', 'Übersicht')
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

    <x-flash class="mt-4" />

    @if ($showLayoutHint)
        <div class="fv-card mt-4 flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:p-5">
            <span class="w-11 h-11 shrink-0 rounded-2xl bg-linear-to-br from-emerald-400 to-emerald-700 text-white flex items-center justify-center shadow-lg shadow-emerald-900/20">
                <x-icon name="target" class="w-5 h-5" />
            </span>

            <div class="min-w-0 flex-1">
                <p class="font-semibold text-slate-900 dark:text-white">Neu: das Cockpit-Layout</p>
                <p class="text-[13px] text-slate-500 dark:text-slate-400">Oben steht, was bis Monatsende noch frei ist, daneben die nächsten Zahlungen und das, was noch zu prüfen ist. Alles andere bleibt unter Einstellungen → Dashboard wählbar.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <form method="POST" action="{{ route('settings.dashboard.preset') }}">
                    @csrf
                    <input type="hidden" name="preset" value="cockpit">
                    <button type="submit" class="fv-btn fv-btn-primary text-sm py-2">Cockpit nutzen</button>
                </form>

                <form method="POST" action="{{ route('settings.dashboard.preset') }}">
                    @csrf
                    <input type="hidden" name="preset" value="classic">
                    <button type="submit" class="fv-btn fv-btn-secondary text-sm py-2">Beim bisherigen bleiben</button>
                </form>
            </div>
        </div>
    @endif


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
         * Tablet:  2 Spalten
         * Desktop: 12 Spalten
         */
        $dashboardWidgetWidths = [
            // Hauptkachel: zwei Zeilen hoch, daneben die Kennzahlen
            'cockpit' => 'md:col-span-2 lg:col-span-7',
            'upcoming' => 'md:col-span-2 lg:col-span-5',
            'summary' => 'md:col-span-2 lg:col-span-6 lg:row-span-2',
            'income' => 'lg:col-span-3',
            'expenses' => 'lg:col-span-3',
            'savings_rate' => 'lg:col-span-3',
            'review' => 'lg:col-span-6',
            'weekly' => 'lg:col-span-3',
            'plan' => 'lg:col-span-6',

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

    {{-- „dense“: Lücken neben der Hauptkachel füllen sich mit den nächsten kleinen Kacheln. --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-4 mt-6 items-stretch [grid-auto-flow:dense]">

        @foreach($dashboardWidgetOrder as $widget)

            @if($dashboardWidgets[$widget] ?? false)

                @php
                    $widgetWidth = $dashboardWidgetWidths[$widget] ?? 'lg:col-span-12';

                    // „Zu prüfen“ ist nur im Cockpit-Layout breit (mit direkter Zuordnung).
                    if ($widget === 'review' && ! ($dashboardWidgets['cockpit'] ?? false)) {
                        $widgetWidth = 'lg:col-span-3';
                    }
                @endphp

                <div class="{{ $widgetWidth }}">
                    @include('dashboard.widgets.' . $widget)
                </div>

            @endif

        @endforeach

    </div>

</div>

@endsection
