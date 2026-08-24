@extends('layouts.app')

@section('title', 'Dashboard – Finanzblick')

@section('eyebrow', 'Finanzblick')

@section('page_title', 'Dashboard')


@section('content')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">


    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-5">

        <div>

            <p class="text-sm text-slate-500">
                Willkommen zurück,
                {{ auth()->user()->name }}
            </p>

            <h2 class="text-3xl font-semibold text-slate-900 mt-1">
                Deine Finanzen
            </h2>

            <p class="text-slate-500 mt-1">
                Alles Wichtige auf einen Blick.
            </p>

        </div>


        {{-- MONAT + BUCHUNG --}}

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
                    class="flex-1 sm:w-44 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-slate-200"
                >

                <button
                    type="submit"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium hover:bg-slate-50 whitespace-nowrap"
                >
                    Monat
                </button>

            </form>


            <a
                href="{{ route('transactions.create') }}"
                class="inline-flex items-center justify-center rounded-xl bg-slate-950 px-5 py-3 text-sm font-medium text-white hover:bg-slate-800 whitespace-nowrap"
            >
                + Buchung
            </a>

        </div>

    </div>



    {{-- ========================================================= --}}
    {{-- GESAMTVERMÖGEN --}}
    {{-- ========================================================= --}}

    <div
        class="
            bg-slate-950
            text-white
            rounded-3xl
            p-6
            sm:p-8
            mt-8
            relative
            overflow-hidden
        "
    >

        <div
            class="
                absolute
                -right-20
                -top-20
                w-64
                h-64
                rounded-full
                bg-white/5
            "
        ></div>


        <div class="relative">

            <div class="flex items-center justify-between">

                <div>

                    <p class="text-sm text-slate-400">
                        Gesamtvermögen
                    </p>

                    <p class="text-4xl sm:text-5xl font-semibold mt-2">
                        {{ number_format(
                            $totalBalance,
                            2,
                            ',',
                            '.'
                        ) }}
                        €
                    </p>

                    <p class="text-sm text-slate-400 mt-3">
                        über alle aktiven Konten
                    </p>

                </div>


                <div
                    class="
                        hidden sm:flex
                        w-14 h-14
                        rounded-2xl
                        bg-white/10
                        items-center justify-center
                        text-2xl
                    "
                >
                    💰
                </div>

            </div>

        </div>

    </div>



    {{-- ========================================================= --}}
    {{-- MONATS-KENNZAHLEN --}}
    {{-- ========================================================= --}}

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">


        {{-- EINNAHMEN --}}

        <div class="bg-white rounded-2xl shadow-sm p-5 border border-slate-100">

            <div class="flex items-center justify-between">

                <div>

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

                <div
                    class="w-11 h-11 rounded-xl bg-emerald-50 flex items-center justify-center"
                >
                    ↗️
                </div>

            </div>

            <p class="text-xs text-slate-400 mt-4">
                {{ $currentMonth }}
            </p>

        </div>



        {{-- AUSGABEN --}}

        <div class="bg-white rounded-2xl shadow-sm p-5 border border-slate-100">

            <div class="flex items-center justify-between">

                <div>

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

                <div
                    class="w-11 h-11 rounded-xl bg-red-50 flex items-center justify-center"
                >
                    ↘️
                </div>

            </div>

            <p class="text-xs text-slate-400 mt-4">
                {{ $currentMonth }}
            </p>

        </div>



        {{-- SALDO --}}

        <div class="bg-white rounded-2xl shadow-sm p-5 border border-slate-100">

            <div class="flex items-center justify-between">

                <div>

                    <p class="text-sm text-slate-500">
                        Monatssaldo
                    </p>

                    <p
                        class="
                            text-2xl
                            font-semibold
                            mt-2
                            {{ $monthlyBalance >= 0
                                ? 'text-emerald-600'
                                : 'text-red-600' }}
                        "
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

                </div>

                <div
                    class="w-11 h-11 rounded-xl bg-slate-100 flex items-center justify-center"
                >
                    💰
                </div>

            </div>

            <p class="text-xs text-slate-400 mt-4">
                {{ number_format(
                    $savingsRate,
                    1,
                    ',',
                    '.'
                ) }}
                % Sparquote
            </p>

        </div>

    </div>



    {{-- ========================================================= --}}
    {{-- MONATSVERLAUF --}}
    {{-- ========================================================= --}}

    <div class="bg-white rounded-2xl shadow-sm p-6 mt-6 border border-slate-100">

        <div class="flex items-center justify-between mb-6">

            <div>

                <h2 class="font-semibold text-slate-900">
                    Einnahmen & Ausgaben
                </h2>

                <p class="text-sm text-slate-500 mt-1">
                    Entwicklung der letzten 6 Monate
                </p>

            </div>

            <div class="hidden sm:flex items-center gap-4 text-xs">

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


        <div class="space-y-6">

            @foreach ($chartMonths as $chartMonth)

                <div>

                    <div class="flex items-center justify-between text-sm mb-2">

                        <span class="font-medium text-slate-700">
                            {{ $chartMonth['label'] }}
                        </span>

                        <span class="text-slate-500">

                            Saldo:

                            <span
                                class="
                                    font-medium
                                    {{ $chartMonth['balance'] >= 0
                                        ? 'text-emerald-600'
                                        : 'text-red-600' }}
                                "
                            >

                                {{ $chartMonth['balance'] >= 0 ? '+' : '' }}

                                {{ number_format(
                                    $chartMonth['balance'],
                                    2,
                                    ',',
                                    '.'
                                ) }}

                                €

                            </span>

                        </span>

                    </div>


                    {{-- EINNAHMEN --}}

                    <div class="flex items-center gap-3 mb-2">

                        <span class="hidden sm:block w-20 text-xs text-slate-400">
                            Einnahmen
                        </span>

                        <div class="flex-1 h-3 bg-slate-100 rounded-full overflow-hidden">

                            <div
                                class="h-full bg-emerald-500 rounded-full transition-all"
                                style="width: {{ ($chartMonth['income'] / $maxChartValue) * 100 }}%"
                            ></div>

                        </div>

                        <span class="w-20 text-right text-xs font-medium text-slate-600">

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

                    <div class="flex items-center gap-3">

                        <span class="hidden sm:block w-20 text-xs text-slate-400">
                            Ausgaben
                        </span>

                        <div class="flex-1 h-3 bg-slate-100 rounded-full overflow-hidden">

                            <div
                                class="h-full bg-red-500 rounded-full transition-all"
                                style="width: {{ ($chartMonth['expense'] / $maxChartValue) * 100 }}%"
                            ></div>

                        </div>

                        <span class="w-20 text-right text-xs font-medium text-slate-600">

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



    {{-- ========================================================= --}}
    {{-- KONTEN + KATEGORIEN --}}
    {{-- ========================================================= --}}

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mt-6">


        {{-- KONTEN --}}

        <div class="xl:col-span-2 bg-white rounded-2xl shadow-sm overflow-hidden border border-slate-100">

            <div class="p-5 border-b border-slate-100 flex items-center justify-between">

                <div>

                    <h2 class="font-semibold text-slate-900">
                        Deine Konten
                    </h2>

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

                    <div class="text-4xl mb-3">
                        🏦
                    </div>

                    <p class="font-medium text-slate-900">
                        Noch keine Konten
                    </p>

                    <a
                        href="{{ route('accounts.create') }}"
                        class="inline-block mt-4 rounded-xl bg-slate-950 px-4 py-2 text-sm font-medium text-white"
                    >
                        Konto erstellen
                    </a>

                </div>

            @else

                <div class="divide-y divide-slate-100">

                    @foreach ($accounts as $account)

                        <div class="p-5 flex items-center gap-4 hover:bg-slate-50 transition">


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

                                @if ($account->include_in_total)

                                    <p class="text-xs text-emerald-600 mt-1">
                                        Im Gesamtvermögen
                                    </p>

                                @else

                                    <p class="text-xs text-slate-400 mt-1">
                                        Nicht eingerechnet
                                    </p>

                                @endif

                            </div>

                        </div>

                    @endforeach

                </div>

            @endif

        </div>



        {{-- AUSGABEN NACH KATEGORIE --}}

        <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-slate-100">

            <div class="p-5 border-b border-slate-100">

                <h2 class="font-semibold text-slate-900">
                    Ausgaben nach Kategorie
                </h2>

                <p class="text-sm text-slate-500 mt-1">
                    {{ $currentMonth }}
                </p>

            </div>


            @if ($expensesByCategory->isEmpty())

                <div class="p-10 text-center">

                    <div class="text-4xl mb-3">
                        📊
                    </div>

                    <p class="text-sm text-slate-500">
                        Noch keine Ausgaben vorhanden.
                    </p>

                </div>

            @else

                <div class="p-5 space-y-5">

                    @foreach ($expensesByCategory as $item)

                        @php

                            $percentage = $monthlyExpense > 0
                                ? ($item['amount'] / $monthlyExpense) * 100
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


                            <p class="text-xs text-slate-400 mt-1">

                                {{ number_format(
                                    $percentage,
                                    1,
                                    ',',
                                    '.'
                                ) }}

                                %

                            </p>

                        </div>

                    @endforeach

                </div>

            @endif

        </div>

    </div>



    {{-- ========================================================= --}}
    {{-- LETZTE BUCHUNGEN --}}
    {{-- ========================================================= --}}

    <div class="bg-white rounded-2xl shadow-sm mt-6 overflow-hidden border border-slate-100">


        <div class="p-5 border-b border-slate-100 flex items-center justify-between">

            <div>

                <h2 class="font-semibold text-slate-900">
                    Letzte Buchungen
                </h2>

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

                <div class="text-4xl mb-3">
                    💸
                </div>

                <p class="font-medium text-slate-900">
                    Noch keine Buchungen
                </p>

                <a
                    href="{{ route('transactions.create') }}"
                    class="inline-block mt-4 rounded-xl bg-slate-950 px-4 py-2 text-sm font-medium text-white"
                >
                    Erste Buchung erstellen
                </a>

            </div>

        @else

            <div class="divide-y divide-slate-100">

                @foreach ($recentTransactions as $transaction)

                    <div class="p-5 flex items-center gap-4 hover:bg-slate-50 transition">


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


                        <div class="text-right">

                            <p
                                class="
                                    font-semibold
                                    whitespace-nowrap
                                    {{ $transaction->type === 'income'
                                        ? 'text-emerald-600'
                                        : 'text-red-600' }}
                                "
                            >

                                {{ $transaction->type === 'income' ? '+' : '-' }}

                                {{ number_format(
                                    $transaction->amount,
                                    2,
                                    ',',
                                    '.'
                                ) }}

                                €

                            </p>

                        </div>

                    </div>

                @endforeach

            </div>

        @endif

    </div>



    {{-- ========================================================= --}}
    {{-- JAHRESÜBERSICHT --}}
    {{-- ========================================================= --}}

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6">


        <div class="bg-white rounded-2xl shadow-sm p-5 border border-slate-100">

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


        <div class="bg-white rounded-2xl shadow-sm p-5 border border-slate-100">

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

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6 pb-8">


        <a
            href="{{ route('accounts.index') }}"
            class="
                bg-white
                rounded-2xl
                shadow-sm
                p-5
                border border-slate-100
                hover:bg-slate-50
                transition
            "
        >

            <span class="text-2xl">
                🏦
            </span>

            <p class="font-medium mt-3">
                Konten
            </p>

        </a>


        <a
            href="{{ route('transactions.index') }}"
            class="
                bg-white
                rounded-2xl
                shadow-sm
                p-5
                border border-slate-100
                hover:bg-slate-50
                transition
            "
        >

            <span class="text-2xl">
                💳
            </span>

            <p class="font-medium mt-3">
                Buchungen
            </p>

        </a>


        <a
            href="{{ route('categories.index') }}"
            class="
                bg-white
                rounded-2xl
                shadow-sm
                p-5
                border border-slate-100
                hover:bg-slate-50
                transition
            "
        >

            <span class="text-2xl">
                🗂️
            </span>

            <p class="font-medium mt-3">
                Kategorien
            </p>

        </a>


        <a
            href="{{ route('transactions.create') }}"
            class="
                bg-slate-950
                text-white
                rounded-2xl
                shadow-sm
                p-5
                hover:bg-slate-800
                transition
            "
        >

            <span class="text-2xl">
                ➕
            </span>

            <p class="font-medium mt-3">
                Buchung
            </p>

        </a>

    </div>


</div>

@endsection