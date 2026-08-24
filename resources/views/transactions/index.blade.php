@extends('layouts.app')

@section('title', 'Buchungen – Finanzblick')

@section('eyebrow', 'Finanzverwaltung')

@section('page_title', 'Buchungen')


@section('content')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">


    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-5">

        <div>

            <p class="text-sm text-slate-500">
                Finanzverwaltung
            </p>

            <h2 class="text-3xl font-semibold text-slate-900 mt-1">
                Buchungen
            </h2>

            <p class="text-slate-500 mt-1">
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
                bg-slate-950
                px-5
                py-3
                text-sm
                font-medium
                text-white
                hover:bg-slate-800
                transition
            "
        >
            + Buchung
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
                bg-emerald-50
                border
                border-emerald-100
                p-4
                text-sm
                text-emerald-700
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
                bg-red-50
                border
                border-red-100
                p-4
                text-sm
                text-red-700
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
            rounded-3xl
            shadow-sm
            border
            border-slate-100
            p-5
            sm:p-6
            mt-8
        "
    >

        <div class="flex items-center justify-between mb-5">

            <div>

                <h3 class="font-semibold text-slate-900">
                    Filter
                </h3>

                <p class="text-sm text-slate-500 mt-1">
                    Grenzen deine Buchungen ein.
                </p>

            </div>


            <a
                href="{{ route('transactions.index') }}"
                class="text-sm text-slate-500 hover:text-slate-900"
            >
                Zurücksetzen
            </a>

        </div>


        <form
            method="GET"
            action="{{ route('transactions.index') }}"
            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4"
        >


            {{-- MONAT --}}

            <div>

                <label
                    for="month"
                    class="block text-xs font-medium text-slate-500 mb-2"
                >
                    Monat
                </label>

                <input
                    type="month"
                    id="month"
                    name="month"
                    value="{{ request('month') }}"
                    class="
                        w-full
                        rounded-xl
                        border
                        border-slate-200
                        px-3
                        py-2.5
                        text-sm
                        focus:outline-none
                        focus:ring-2
                        focus:ring-slate-200
                    "
                >

            </div>



            {{-- TYP --}}

            <div>

                <label
                    for="type"
                    class="block text-xs font-medium text-slate-500 mb-2"
                >
                    Art
                </label>

                <select
                    id="type"
                    name="type"
                    class="
                        w-full
                        rounded-xl
                        border
                        border-slate-200
                        bg-white
                        px-3
                        py-2.5
                        text-sm
                        focus:outline-none
                        focus:ring-2
                        focus:ring-slate-200
                    "
                >

                    <option value="">
                        Alle
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

                </select>

            </div>



            {{-- KONTO --}}

            <div>

                <label
                    for="account_id"
                    class="block text-xs font-medium text-slate-500 mb-2"
                >
                    Konto
                </label>

                <select
                    id="account_id"
                    name="account_id"
                    class="
                        w-full
                        rounded-xl
                        border
                        border-slate-200
                        bg-white
                        px-3
                        py-2.5
                        text-sm
                        focus:outline-none
                        focus:ring-2
                        focus:ring-slate-200
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

            <div>

                <label
                    for="category_id"
                    class="block text-xs font-medium text-slate-500 mb-2"
                >
                    Kategorie
                </label>

                <select
                    id="category_id"
                    name="category_id"
                    class="
                        w-full
                        rounded-xl
                        border
                        border-slate-200
                        bg-white
                        px-3
                        py-2.5
                        text-sm
                        focus:outline-none
                        focus:ring-2
                        focus:ring-slate-200
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



            {{-- SUCHEN --}}

            <div class="flex items-end">

                <button
                    type="submit"
                    class="
                        w-full
                        rounded-xl
                        bg-slate-950
                        px-4
                        py-2.5
                        text-sm
                        font-medium
                        text-white
                        hover:bg-slate-800
                        transition
                    "
                >
                    Filtern
                </button>

            </div>

        </form>

    </div>



    {{-- ========================================================= --}}
    {{-- ÜBERSICHT --}}
    {{-- ========================================================= --}}

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-6">


        {{-- ANZAHL --}}

        <div
            class="
                bg-white
                rounded-2xl
                border
                border-slate-100
                shadow-sm
                p-5
            "
        >

            <p class="text-sm text-slate-500">
                Buchungen
            </p>

            <p class="text-2xl font-semibold text-slate-900 mt-2">
                {{ $transactions->total() }}
            </p>

        </div>



        {{-- EINNAHMEN --}}

        <div
            class="
                bg-white
                rounded-2xl
                border
                border-slate-100
                shadow-sm
                p-5
            "
        >

            <p class="text-sm text-slate-500">
                Einnahmen
            </p>

            <p class="text-2xl font-semibold text-emerald-600 mt-2">
                +{{ number_format($totalIncome ?? 0, 2, ',', '.') }} €
            </p>

        </div>



        {{-- AUSGABEN --}}

        <div
            class="
                bg-white
                rounded-2xl
                border
                border-slate-100
                shadow-sm
                p-5
            "
        >

            <p class="text-sm text-slate-500">
                Ausgaben
            </p>

            <p class="text-2xl font-semibold text-red-600 mt-2">
                -{{ number_format($totalExpense ?? 0, 2, ',', '.') }} €
            </p>

        </div>

    </div>



    {{-- ========================================================= --}}
    {{-- BUCHUNGEN --}}
    {{-- ========================================================= --}}

    <div
        class="
            bg-white
            rounded-3xl
            shadow-sm
            border
            border-slate-100
            overflow-hidden
            mt-6
        "
    >


        {{-- DESKTOP HEADER --}}

        <div
            class="
                hidden
                md:grid
                md:grid-cols-[1fr_160px_160px_120px]
                gap-4
                px-6
                py-4
                border-b
                border-slate-100
                bg-slate-50
                text-xs
                font-medium
                text-slate-500
            "
        >

            <div>
                Buchung
            </div>

            <div>
                Konto
            </div>

            <div>
                Datum
            </div>

            <div class="text-right">
                Betrag
            </div>

        </div>



        @if ($transactions->isEmpty())

            {{-- ================================================= --}}
            {{-- LEER --}}
            {{-- ================================================= --}}

            <div class="p-10 sm:p-14 text-center">

                <div
                    class="
                        mx-auto
                        w-16
                        h-16
                        rounded-2xl
                        bg-slate-100
                        flex
                        items-center
                        justify-center
                        text-3xl
                    "
                >
                    💸
                </div>


                <h3 class="font-semibold text-slate-900 mt-5">
                    Keine Buchungen gefunden
                </h3>


                <p class="text-sm text-slate-500 mt-2">
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
                        bg-slate-950
                        px-5
                        py-3
                        text-sm
                        font-medium
                        text-white
                        hover:bg-slate-800
                    "
                >
                    + Erste Buchung
                </a>

            </div>


        @else


            {{-- ================================================= --}}
            {{-- LISTE --}}
            {{-- ================================================= --}}

            <div class="divide-y divide-slate-100">

                @foreach ($transactions as $transaction)

                    <a
                        href="{{ route('transactions.edit', $transaction) }}"
                        class="
                            block
                            p-5
                            sm:px-6
                            hover:bg-slate-50
                            transition
                        "
                    >

                        {{-- MOBILE --}}

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
                                            ? 'bg-emerald-50'
                                            : 'bg-red-50' }}
                                    "
                                >
                                    {{ $transaction->category?->icon ?: '💳' }}
                                </div>


                                <div class="flex-1 min-w-0">

                                    <div class="flex items-start justify-between gap-3">

                                        <div class="min-w-0">

                                            <p class="font-medium text-slate-900 truncate">
                                                {{ $transaction->description }}
                                            </p>

                                            <p class="text-xs text-slate-500 mt-1">

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

                                    </div>


                                    <div class="flex items-center gap-2 mt-3">

                                        <span class="text-xs text-slate-500">

                                            {{ $transaction->account?->icon ?: '🏦' }}

                                            {{ $transaction->account?->name ?: 'Kein Konto' }}

                                        </span>


                                        @if ($transaction->is_pending)

                                            <span
                                                class="
                                                    text-[10px]
                                                    font-medium
                                                    text-amber-700
                                                    bg-amber-50
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



                        {{-- DESKTOP --}}

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
                                            ? 'bg-emerald-50'
                                            : 'bg-red-50' }}
                                    "
                                >
                                    {{ $transaction->category?->icon ?: '💳' }}
                                </div>


                                <div class="min-w-0">

                                    <p class="font-medium text-slate-900 truncate">
                                        {{ $transaction->description }}
                                    </p>


                                    <div class="flex items-center gap-2 mt-1">

                                        @if ($transaction->category)

                                            <span class="text-xs text-slate-500">
                                                {{ $transaction->category->name }}
                                            </span>

                                        @endif


                                        @if ($transaction->merchant)

                                            <span class="text-xs text-slate-400">
                                                · {{ $transaction->merchant }}
                                            </span>

                                        @endif


                                        @if ($transaction->is_pending)

                                            <span
                                                class="
                                                    text-[10px]
                                                    font-medium
                                                    text-amber-700
                                                    bg-amber-50
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

                            <div class="text-sm text-slate-600 truncate">

                                {{ $transaction->account?->icon ?: '🏦' }}

                                {{ $transaction->account?->name ?: 'Kein Konto' }}

                            </div>



                            {{-- DATUM --}}

                            <div class="text-sm text-slate-500">

                                {{ $transaction->transaction_date?->format('d.m.Y') }}

                            </div>



                            {{-- BETRAG --}}

                            <div
                                class="
                                    text-right
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
                    "
                >

                    {{ $transactions->withQueryString()->links() }}

                </div>

            @endif


        @endif

    </div>

</div>

@endsection