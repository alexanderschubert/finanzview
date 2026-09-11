@extends('layouts.app')

@section('title', 'Buchungen – FinanzView')

@section('eyebrow', 'Finanzverwaltung')

@section('page_title', 'Buchungen')

@section('content')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-5">

        <div class="min-w-0">

            <div class="flex items-center gap-2">

                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 flex-shrink-0"></span>

                <p class="text-sm font-medium text-emerald-600 dark:text-emerald-400">
                    Finanzverwaltung
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
                Buchungen
            </h2>

            <p class="text-slate-500 dark:text-slate-400 mt-2">
                Verwalte deine Einnahmen und Ausgaben.
            </p>

        </div>

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
                flex-shrink-0
            "
        >
            <span class="mr-2 text-emerald-200">
                +
            </span>

            Neue Buchung
        </a>

    </div>


    {{-- ========================================================= --}}
    {{-- MELDUNGEN --}}
    {{-- ========================================================= --}}

    @if (session('success'))

        <div
            class="
                mt-6
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
            {{ session('success') }}
        </div>

    @endif


    @if (session('error'))

        <div
            class="
                mt-6
                rounded-2xl
                border
                border-red-100
                dark:border-red-900
                bg-red-50
                dark:bg-red-950/40
                p-4
                text-sm
                text-red-700
                dark:text-red-300
            "
        >
            {{ session('error') }}
        </div>

    @endif


    {{-- ========================================================= --}}
    {{-- FILTER --}}
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
            mt-8
        "
    >

        <div
            class="
                flex
                flex-col
                sm:flex-row
                sm:items-center
                sm:justify-between
                gap-3
                mb-6
            "
        >

            <div>

                <div class="flex items-center gap-2">

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
                            text-lg
                        "
                    >
                        🔎
                    </div>

                    <div>

                        <h3 class="font-semibold text-slate-900 dark:text-white">
                            Buchungen filtern
                        </h3>

                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Grenze die Anzeige nach Zeitraum, Art, Konto oder Kategorie ein.
                        </p>

                    </div>

                </div>

            </div>

            <a
                href="{{ route('transactions.index') }}"
                class="
                    inline-flex
                    items-center
                    text-sm
                    font-medium
                    text-slate-500
                    dark:text-slate-400
                    hover:text-emerald-600
                    dark:hover:text-emerald-400
                    transition
                "
            >
                Filter zurücksetzen
            </a>

        </div>


        <form
            method="GET"
            action="{{ route('transactions.index') }}"
            class="
                grid
                grid-cols-1
                sm:grid-cols-2
                lg:grid-cols-5
                gap-4
            "
        >

            {{-- MONAT --}}

            <div class="min-w-0">

                <label
                    for="month"
                    class="
                        block
                        text-sm
                        font-medium
                        text-slate-700
                        dark:text-slate-300
                        mb-2
                    "
                >
                    Monat
                </label>

                <input
                    type="month"
                    id="month"
                    name="month"
                    value="{{ request('month') }}"
                    class="
                        box-border
                        w-full
                        min-w-0
                        rounded-xl
                        border
                        border-slate-200
                        dark:border-slate-700
                        bg-white
                        dark:bg-slate-800
                        text-slate-900
                        dark:text-white
                        px-4
                        py-3
                        text-sm
                        focus:outline-none
                        focus:ring-2
                        focus:ring-emerald-500/20
                        focus:border-emerald-500
                    "
                >

            </div>


            {{-- TYP --}}

            <div class="min-w-0">

                <label
                    for="type"
                    class="
                        block
                        text-sm
                        font-medium
                        text-slate-700
                        dark:text-slate-300
                        mb-2
                    "
                >
                    Art
                </label>

                <select
                    id="type"
                    name="type"
                    class="
                        box-border
                        w-full
                        min-w-0
                        rounded-xl
                        border
                        border-slate-200
                        dark:border-slate-700
                        bg-white
                        dark:bg-slate-800
                        text-slate-900
                        dark:text-white
                        px-4
                        py-3
                        text-sm
                        focus:outline-none
                        focus:ring-2
                        focus:ring-emerald-500/20
                        focus:border-emerald-500
                    "
                >

                    <option value="">
                        Alle Buchungen
                    </option>

                    <option
                        value="expense"
                        @selected(request('type') === 'expense')
                    >
                        Ausgaben
                    </option>

                    <option
                        value="income"
                        @selected(request('type') === 'income')
                    >
                        Einnahmen
                    </option>

                    <option
                        value="transfer"
                        @selected(request('type') === 'transfer')
                    >
                        Überweisungen
                    </option>

                </select>

            </div>


            {{-- KONTO --}}

            <div class="min-w-0">

                <label
                    for="account_id"
                    class="
                        block
                        text-sm
                        font-medium
                        text-slate-700
                        dark:text-slate-300
                        mb-2
                    "
                >
                    Konto
                </label>

                <select
                    id="account_id"
                    name="account_id"
                    class="
                        box-border
                        w-full
                        min-w-0
                        rounded-xl
                        border
                        border-slate-200
                        dark:border-slate-700
                        bg-white
                        dark:bg-slate-800
                        text-slate-900
                        dark:text-white
                        px-4
                        py-3
                        text-sm
                        focus:outline-none
                        focus:ring-2
                        focus:ring-emerald-500/20
                        focus:border-emerald-500
                    "
                >

                    <option value="">
                        Alle Konten
                    </option>

                    @foreach ($accounts as $account)

                        <option
                            value="{{ $account->id }}"
                            @selected(
                                (string) request('account_id')
                                ===
                                (string) $account->id
                            )
                        >
                            {{ $account->icon ?: '🏦' }}
                            {{ $account->name }}
                        </option>

                    @endforeach

                </select>

            </div>


            {{-- KATEGORIE --}}

            <div class="min-w-0">

                <label
                    for="category_id"
                    class="
                        block
                        text-sm
                        font-medium
                        text-slate-700
                        dark:text-slate-300
                        mb-2
                    "
                >
                    Kategorie
                </label>

                <select
                    id="category_id"
                    name="category_id"
                    class="
                        box-border
                        w-full
                        min-w-0
                        rounded-xl
                        border
                        border-slate-200
                        dark:border-slate-700
                        bg-white
                        dark:bg-slate-800
                        text-slate-900
                        dark:text-white
                        px-4
                        py-3
                        text-sm
                        focus:outline-none
                        focus:ring-2
                        focus:ring-emerald-500/20
                        focus:border-emerald-500
                    "
                >

                    <option value="">
                        Alle Kategorien
                    </option>

                    @foreach ($categories as $category)

                        <option
                            value="{{ $category->id }}"
                            @selected(
                                (string) request('category_id')
                                ===
                                (string) $category->id
                            )
                        >
                            {{ $category->icon ?: '📁' }}
                            {{ $category->name }}
                        </option>

                    @endforeach

                </select>

            </div>


            {{-- FILTERN --}}

            <div class="flex items-end">

                <button
                    type="submit"
                    class="
                        w-full
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
                    Filtern
                </button>

            </div>

        </form>

    </div>


    {{-- ========================================================= --}}
    {{-- KENNZAHLEN --}}
    {{-- ========================================================= --}}

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-6">

        {{-- ANZAHL --}}

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
                    Buchungen
                </p>

                <div
                    class="
                        w-9
                        h-9
                        rounded-xl
                        bg-slate-100
                        dark:bg-slate-800
                        flex
                        items-center
                        justify-center
                    "
                >
                    💳
                </div>

            </div>

            <p class="text-3xl font-semibold text-slate-900 dark:text-white mt-5">
                {{ $transactions->total() }}
            </p>

            <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">
                Gefundene Buchungen
            </p>

        </div>


        {{-- EINNAHMEN --}}

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
                    Einnahmen
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
                        text-emerald-600
                        dark:text-emerald-400
                    "
                >
                    ↗
                </div>

            </div>

            <p class="text-3xl font-semibold text-emerald-600 dark:text-emerald-400 mt-5">
                +{{ number_format($totalIncome ?? 0, 2, ',', '.') }} €
            </p>

            <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">
                Gesamtbetrag
            </p>

        </div>


        {{-- AUSGABEN --}}

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
                    Ausgaben
                </p>

                <div
                    class="
                        w-9
                        h-9
                        rounded-xl
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
                -{{ number_format($totalExpense ?? 0, 2, ',', '.') }} €
            </p>

            <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">
                Gesamtbetrag
            </p>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- BUCHUNGSLISTE --}}
    {{-- ========================================================= --}}

    <div
        class="
            bg-white
            dark:bg-slate-900
            rounded-3xl
            shadow-sm
            border
            border-slate-100
            dark:border-slate-800
            overflow-hidden
            mt-6
        "
    >

        {{-- HEADER --}}

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
                    Alle Buchungen
                </h3>

                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Deine erfassten Einnahmen, Ausgaben und Überweisungen.
                </p>

            </div>

            <div
                class="
                    hidden
                    sm:flex
                    items-center
                    gap-2
                    text-xs
                    text-slate-400
                    dark:text-slate-500
                "
            >

                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                Einnahmen

                <span class="w-2.5 h-2.5 rounded-full bg-red-500 ml-3"></span>
                Ausgaben

            </div>

        </div>


        @if ($transactions->isEmpty())

            <div class="p-10 sm:p-14 text-center">

                <div
                    class="
                        mx-auto
                        w-16
                        h-16
                        rounded-2xl
                        bg-slate-100
                        dark:bg-slate-800
                        flex
                        items-center
                        justify-center
                        text-3xl
                    "
                >
                    💸
                </div>

                <h3 class="font-semibold text-slate-900 dark:text-white mt-5">
                    Keine Buchungen gefunden
                </h3>

                <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">
                    Für die aktuellen Filter wurden keine Buchungen gefunden.
                </p>

                <a
                    href="{{ route('transactions.create') }}"
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
                    + Erste Buchung
                </a>

            </div>

        @else

            {{-- ================================================= --}}
            {{-- LISTE --}}
            {{-- ================================================= --}}

            <div class="divide-y divide-slate-100 dark:divide-slate-800">

                @foreach ($transactions as $transaction)

                    <a
                        href="{{ route('transactions.edit', $transaction) }}"
                        class="
                            block
                            p-5
                            sm:px-6
                            bg-white
                            dark:bg-slate-900
                            hover:bg-slate-50
                            dark:hover:bg-slate-800
                            transition
                        "
                    >

                        {{-- ================================================= --}}
                        {{-- MOBILE --}}
                        {{-- ================================================= --}}

                        <div class="md:hidden">

                            <div class="flex items-start gap-4">

                                <div
                                    class="
                                        w-11
                                        h-11
                                        rounded-2xl
                                        flex
                                        items-center
                                        justify-center
                                        text-lg
                                        flex-shrink-0
                                        {{ $transaction->type === 'income'
                                            ? 'bg-emerald-50 dark:bg-emerald-950/40'
                                            : ($transaction->type === 'transfer'
                                                ? 'bg-blue-50 dark:bg-blue-950/40'
                                                : 'bg-red-50 dark:bg-red-950/40') }}
                                    "
                                >
                                    {{ $transaction->type === 'transfer'
                                        ? '⇄'
                                        : ($transaction->category?->icon ?: '💳') }}
                                </div>


                                <div class="flex-1 min-w-0">

                                    <div class="flex items-start justify-between gap-3">

                                        <div class="min-w-0">

                                            <div class="flex items-center gap-2 flex-wrap">

                                                <p class="font-medium text-slate-900 dark:text-white truncate">
                                                    {{ $transaction->description }}
                                                </p>


                                                {{-- ================================================= --}}
                                                {{-- WIEDERKEHREND MOBILE --}}
                                                {{-- ================================================= --}}

                                                @if ($transaction->recurring_transaction_id)

                                                    <span
                                                        class="
                                                            inline-flex
                                                            items-center
                                                            gap-1
                                                            rounded-full
                                                            px-2
                                                            py-0.5
                                                            text-[10px]
                                                            font-medium
                                                            whitespace-nowrap
                                                            bg-violet-100
                                                            text-violet-700
                                                            dark:bg-violet-900
                                                            dark:text-violet-200
                                                        "
                                                    >
                                                        <span>↻</span>
                                                        <span>Wiederkehrend</span>
                                                    </span>

                                                @endif

                                            </div>


                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">

                                                {{ $transaction->transaction_date?->format('d.m.Y') }}

                                                @if ($transaction->category)
                                                    · {{ $transaction->category->name }}
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

                                            {{ $transaction->type === 'income'
                                                ? '+'
                                                : ($transaction->type === 'transfer'
                                                    ? ''
                                                    : '-') }}

                                            {{ number_format(
                                                $transaction->amount,
                                                2,
                                                ',',
                                                '.'
                                            ) }}
                                            €

                                        </p>

                                    </div>


                                    <div class="flex items-center gap-2 mt-3 flex-wrap">

                                        <span class="text-xs text-slate-500 dark:text-slate-400">

                                            @if ($transaction->type === 'transfer')

                                                {{ $transaction->account?->icon ?: '🏦' }}
                                                {{ $transaction->account?->name ?: 'Kein Konto' }}

                                                <span class="mx-1 text-blue-500">→</span>

                                                {{ $transaction->transferAccount?->icon ?: '🏦' }}
                                                {{ $transaction->transferAccount?->name ?: 'Kein Konto' }}

                                            @else

                                                {{ $transaction->account?->icon ?: '🏦' }}
                                                {{ $transaction->account?->name ?: 'Kein Konto' }}

                                            @endif

                                        </span>


                                        @if ($transaction->is_pending)

                                            <span
                                                class="
                                                    text-[10px]
                                                    font-medium
                                                    text-amber-700
                                                    dark:text-amber-300
                                                    bg-amber-50
                                                    dark:bg-amber-950/40
                                                    px-2
                                                    py-1
                                                    rounded-full
                                                "
                                            >
                                                Ausstehend
                                            </span>

                                        @endif

                                    </div>

                                </div>

                            </div>

                        </div>


                        {{-- ================================================= --}}
                        {{-- DESKTOP --}}
                        {{-- ================================================= --}}

                        <div
                            class="
                                hidden
                                md:grid
                                md:grid-cols-[1fr_160px_160px_120px]
                                gap-4
                                items-center
                            "
                        >

                            {{-- BUCHUNG --}}

                            <div class="flex items-center gap-4 min-w-0">

                                <div
                                    class="
                                        w-11
                                        h-11
                                        rounded-2xl
                                        flex
                                        items-center
                                        justify-center
                                        text-lg
                                        flex-shrink-0
                                        {{ $transaction->type === 'income'
                                            ? 'bg-emerald-50 dark:bg-emerald-950/40'
                                            : ($transaction->type === 'transfer'
                                                ? 'bg-blue-50 dark:bg-blue-950/40'
                                                : 'bg-red-50 dark:bg-red-950/40') }}
                                    "
                                >
                                    {{ $transaction->type === 'transfer'
                                        ? '⇄'
                                        : ($transaction->category?->icon ?: '💳') }}
                                </div>


                                <div class="min-w-0">

                                    <div class="flex items-center gap-2 flex-wrap">

                                        <p class="font-medium text-slate-900 dark:text-white truncate">
                                            {{ $transaction->description }}
                                        </p>


                                        {{-- ================================================= --}}
                                        {{-- WIEDERKEHREND DESKTOP --}}
                                        {{-- ================================================= --}}

                                        @if ($transaction->recurring_transaction_id)

                                            <span
                                                class="
                                                    inline-flex
                                                    items-center
                                                    gap-1
                                                    rounded-full
                                                    px-2
                                                    py-0.5
                                                    text-[10px]
                                                    font-medium
                                                    whitespace-nowrap
                                                    bg-violet-100
                                                    text-violet-700
                                                    dark:bg-violet-900
                                                    dark:text-violet-200
                                                "
                                            >
                                                <span>↻</span>
                                                <span>Wiederkehrend</span>
                                            </span>

                                        @endif

                                    </div>


                                    <div class="flex items-center gap-2 mt-1 flex-wrap">

                                        @if ($transaction->category)

                                            <span class="text-xs text-slate-500 dark:text-slate-400">
                                                {{ $transaction->category->name }}
                                            </span>

                                        @endif


                                        @if ($transaction->merchant)

                                            <span class="text-xs text-slate-400 dark:text-slate-500">
                                                · {{ $transaction->merchant }}
                                            </span>

                                        @endif


                                        @if ($transaction->is_pending)

                                            <span
                                                class="
                                                    text-[10px]
                                                    font-medium
                                                    text-amber-700
                                                    dark:text-amber-300
                                                    bg-amber-50
                                                    dark:bg-amber-950/40
                                                    px-2
                                                    py-1
                                                    rounded-full
                                                "
                                            >
                                                Ausstehend
                                            </span>

                                        @endif

                                    </div>

                                </div>

                            </div>


                            {{-- KONTO --}}

                            <div class="text-sm text-slate-600 dark:text-slate-300 truncate">

                                @if ($transaction->type === 'transfer')

                                    {{ $transaction->account?->icon ?: '🏦' }}
                                    {{ $transaction->account?->name ?: 'Kein Konto' }}

                                    <span class="text-blue-500 mx-1">→</span>

                                    {{ $transaction->transferAccount?->icon ?: '🏦' }}
                                    {{ $transaction->transferAccount?->name ?: 'Kein Konto' }}

                                @else

                                    {{ $transaction->account?->icon ?: '🏦' }}
                                    {{ $transaction->account?->name ?: 'Kein Konto' }}

                                @endif

                            </div>


                            {{-- DATUM --}}

                            <div class="text-sm text-slate-500 dark:text-slate-400">

                                {{ $transaction->transaction_date?->format('d.m.Y') }}

                            </div>


                            {{-- BETRAG --}}

                            <div
                                class="
                                    text-right
                                    font-semibold
                                    whitespace-nowrap
                                    {{ $transaction->type === 'income'
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : ($transaction->type === 'transfer'
                                            ? 'text-blue-600 dark:text-blue-400'
                                            : 'text-red-600 dark:text-red-400') }}
                                "
                            >

                                {{ $transaction->type === 'income'
                                    ? '+'
                                    : ($transaction->type === 'transfer'
                                        ? ''
                                        : '-') }}

                                {{ number_format(
                                    $transaction->amount,
                                    2,
                                    ',',
                                    '.'
                                ) }}

                                €

                            </div>

                        </div>

                    </a>

                @endforeach

            </div>


            {{-- ================================================= --}}
            {{-- PAGINATION --}}
            {{-- ================================================= --}}

            @if ($transactions->hasPages())

                <div
                    class="
                        px-5
                        sm:px-6
                        py-5
                        border-t
                        border-slate-100
                        dark:border-slate-800
                        bg-white
                        dark:bg-slate-900
                    "
                >

                    {{ $transactions->withQueryString()->links() }}

                </div>

            @endif

        @endif

    </div>


    {{-- ========================================================= --}}
    {{-- HINWEIS --}}
    {{-- ========================================================= --}}

    <div
        class="
            mt-5
            rounded-2xl
            bg-slate-50
            dark:bg-slate-900
            border
            border-slate-100
            dark:border-slate-800
            p-5
        "
    >

        <div class="flex items-start gap-3">

            <span class="text-lg">
                💡
            </span>

            <div>

                <p class="text-sm font-medium text-slate-700 dark:text-slate-200">
                    Tipp
                </p>

                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Klicke auf eine Buchung, um sie zu bearbeiten oder weitere Details anzuzeigen.
                </p>

            </div>

        </div>

    </div>

</div>

@endsection