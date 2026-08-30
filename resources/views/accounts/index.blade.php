@extends('layouts.app')

@section('title', 'Konten – Finanzblick')

@section('eyebrow', 'Finanzverwaltung')

@section('page_title', 'Konten')

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
                Deine Konten
            </h2>

            <p class="text-slate-500 dark:text-slate-400 mt-2">
                Verwalte Bankkonten, Bargeld und weitere Vermögenswerte.
            </p>

        </div>


        {{-- NEUES KONTO --}}

        <a
            href="{{ route('accounts.create') }}"
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

            Neues Konto

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
                dark:bg-emerald-950/40
                border
                border-emerald-100
                dark:border-emerald-900
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


    @if (session('error'))

        <div
            class="
                mt-6
                rounded-2xl
                bg-red-50
                dark:bg-red-950/40
                border
                border-red-100
                dark:border-red-900
                p-4
                text-sm
                text-red-700
                dark:text-red-300
            "
        >

            <div class="flex items-center gap-3">

                <span class="text-lg">
                    !
                </span>

                <span>
                    {{ session('error') }}
                </span>

            </div>

        </div>

    @endif


    {{-- ========================================================= --}}
    {{-- KENNZAHLEN --}}
    {{-- ========================================================= --}}

    @php

        $activeAccounts = $accounts->where(
            'is_active',
            true
        );

        $includedAccounts = $accounts->where(
            'include_in_total',
            true
        );

        $totalBalance = $includedAccounts->sum(
            'calculated_balance'
        );

    @endphp


    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 mt-8">


        {{-- GESAMTVERMÖGEN --}}

        <div
            class="
                relative
                overflow-hidden
                rounded-3xl
                bg-slate-950
                dark:bg-slate-800
                text-white
                p-6
            "
        >

            <div
                class="
                    absolute
                    -right-8
                    -top-8
                    w-32
                    h-32
                    rounded-full
                    bg-emerald-500/10
                "
            ></div>

            <div class="relative">

                <div class="flex items-center justify-between">

                    <p class="text-sm text-slate-400">
                        Gesamtvermögen
                    </p>

                    <div
                        class="
                            w-9
                            h-9
                            rounded-xl
                            bg-white/10
                            flex
                            items-center
                            justify-center
                        "
                    >
                        💰
                    </div>

                </div>

                <p class="text-3xl font-semibold tracking-tight mt-5">

                    {{ number_format(
                        $totalBalance,
                        2,
                        ',',
                        '.'
                    ) }} €

                </p>

                <p class="text-xs text-slate-500 mt-2">
                    Alle einbezogenen Konten
                </p>

            </div>

        </div>


        {{-- AKTIVE KONTEN --}}

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
                    Aktive Konten
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
                    🏦
                </div>

            </div>

            <p class="text-3xl font-semibold text-slate-900 dark:text-white mt-5">

                {{ $activeAccounts->count() }}

            </p>

            <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">

                Von {{ $accounts->count() }} Konten insgesamt

            </p>

        </div>


        {{-- IM GESAMTVERMÖGEN --}}

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
                    Im Gesamtvermögen
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
                        text-slate-600
                        dark:text-slate-300
                    "
                >
                    ✓
                </div>

            </div>

            <p class="text-3xl font-semibold text-emerald-600 dark:text-emerald-400 mt-5">

                {{ $includedAccounts->count() }}

            </p>

            <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">
                Konten werden berücksichtigt
            </p>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- KONTEN --}}
    {{-- ========================================================= --}}

    <div class="mt-8">

        <div
            class="
                flex
                flex-col
                sm:flex-row
                sm:items-center
                sm:justify-between
                gap-3
                mb-4
            "
        >

            <div>

                <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                    Vermögen
                </p>

                <h3 class="text-xl font-semibold text-slate-900 dark:text-white mt-1">
                    Deine Konten
                </h3>

                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Aktuelle Kontostände und Einstellungen
                </p>

            </div>

            @if ($accounts->isNotEmpty())

                <span
                    class="
                        text-sm
                        text-slate-400
                        dark:text-slate-500
                    "
                >
                    {{ $accounts->count() }}
                    {{ $accounts->count() === 1 ? 'Konto' : 'Konten' }}
                </span>

            @endif

        </div>


        @if ($accounts->isEmpty())

            {{-- ================================================= --}}
            {{-- KEINE KONTEN --}}
            {{-- ================================================= --}}

            <div
                class="
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
                        mx-auto
                        w-16
                        h-16
                        rounded-2xl
                        bg-emerald-50
                        dark:bg-emerald-950/50
                        flex
                        items-center
                        justify-center
                        text-3xl
                    "
                >
                    🏦
                </div>

                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mt-5">
                    Noch keine Konten
                </h3>

                <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 max-w-md mx-auto">
                    Erstelle dein erstes Konto, damit Finanzblick
                    deine Vermögensentwicklung berechnen kann.
                </p>

                <a
                    href="{{ route('accounts.create') }}"
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
                    + Erstes Konto erstellen
                </a>

            </div>

        @else

            {{-- ================================================= --}}
            {{-- KONTO-KARTEN --}}
            {{-- ================================================= --}}

            <div
                class="
                    grid
                    grid-cols-1
                    md:grid-cols-2
                    xl:grid-cols-3
                    gap-4
                "
            >

                @foreach ($accounts as $account)

                    <div
                        class="
                            group
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
                            dark:hover:border-slate-700
                            transition
                        "
                    >

                        {{-- ================================================= --}}
                        {{-- KARTENINHALT --}}
                        {{-- ================================================= --}}

                        <div class="p-6">

                            <div class="flex items-start justify-between gap-4">

                                {{-- ICON --}}

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
                                    style="
                                        background-color:
                                        {{ $account->color ?: '#ecfdf5' }};
                                    "
                                >
                                    {{ $account->icon ?: '🏦' }}
                                </div>


                                {{-- STATUS --}}

                                @if ($account->is_active)

                                    <span
                                        class="
                                            inline-flex
                                            items-center
                                            gap-1.5
                                            rounded-full
                                            bg-emerald-50
                                            dark:bg-emerald-950/50
                                            px-2.5
                                            py-1
                                            text-[11px]
                                            font-medium
                                            text-emerald-700
                                            dark:text-emerald-300
                                        "
                                    >

                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>

                                        Aktiv

                                    </span>

                                @else

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
                                            text-[11px]
                                            font-medium
                                            text-slate-500
                                            dark:text-slate-400
                                        "
                                    >

                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>

                                        Inaktiv

                                    </span>

                                @endif

                            </div>


                            {{-- NAME --}}

                            <h3
                                class="
                                    text-lg
                                    font-semibold
                                    text-slate-900
                                    dark:text-white
                                    mt-5
                                    truncate
                                "
                            >
                                {{ $account->name }}
                            </h3>


                            {{-- INSTITUT / TYP --}}

                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 truncate">

                                @if ($account->institution)

                                    {{ $account->institution }}

                                @else

                                    {{ match($account->type) {

                                        'checking' => 'Girokonto',

                                        'savings' => 'Sparkonto',

                                        'credit_card' => 'Kreditkarte',

                                        'paypal' => 'PayPal',

                                        'cash' => 'Bargeld',

                                        'investment' => 'Investment',

                                        'loan' => 'Kredit',

                                        default => 'Sonstiges'

                                    } }}

                                @endif

                            </p>


                            {{-- SALDO --}}

                            <div class="mt-7">

                                <p class="text-xs text-slate-400 dark:text-slate-500">
                                    Aktueller Kontostand
                                </p>

                                <p
                                    class="
                                        text-3xl
                                        font-semibold
                                        tracking-tight
                                        mt-1
                                        {{ $account->calculated_balance >= 0
                                            ? 'text-slate-900 dark:text-white'
                                            : 'text-red-600 dark:text-red-400' }}
                                    "
                                >

                                    {{ number_format(
                                        $account->calculated_balance,
                                        2,
                                        ',',
                                        '.'
                                    ) }}

                                    <span class="text-lg font-medium">
                                        {{ $account->currency }}
                                    </span>

                                </p>

                            </div>


                            {{-- STATUS GESAMTVERMÖGEN --}}

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

                                <div class="flex items-center gap-2">

                                    @if ($account->include_in_total)

                                        <span
                                            class="
                                                w-7
                                                h-7
                                                rounded-lg
                                                bg-emerald-100
                                                dark:bg-emerald-950/70
                                                flex
                                                items-center
                                                justify-center
                                                text-emerald-600
                                                dark:text-emerald-400
                                                text-sm
                                            "
                                        >
                                            ✓
                                        </span>

                                        <div>

                                            <p class="text-xs font-medium text-emerald-700 dark:text-emerald-300">
                                                Im Gesamtvermögen
                                            </p>

                                            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">
                                                Wird bei der Vermögensberechnung berücksichtigt
                                            </p>

                                        </div>

                                    @else

                                        <span
                                            class="
                                                w-7
                                                h-7
                                                rounded-lg
                                                bg-slate-200
                                                dark:bg-slate-700
                                                flex
                                                items-center
                                                justify-center
                                                text-slate-500
                                                dark:text-slate-400
                                                text-sm
                                            "
                                        >
                                            –
                                        </span>

                                        <div>

                                            <p class="text-xs font-medium text-slate-600 dark:text-slate-300">
                                                Nicht eingerechnet
                                            </p>

                                            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">
                                                Wird nicht zum Gesamtvermögen gezählt
                                            </p>

                                        </div>

                                    @endif

                                </div>

                            </div>

                        </div>


                        {{-- ================================================= --}}
                        {{-- AKTIONEN --}}
                        {{-- ================================================= --}}

                        <div
                            class="
                                px-6
                                py-4
                                bg-slate-50/80
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
                                    'accounts.edit',
                                    $account
                                ) }}"
                                class="
                                    inline-flex
                                    items-center
                                    gap-2
                                    text-sm
                                    font-medium
                                    text-slate-600
                                    dark:text-slate-300
                                    hover:text-emerald-600
                                    dark:hover:text-emerald-400
                                    transition
                                "
                            >

                                <span>
                                    ✎
                                </span>

                                Bearbeiten

                            </a>


                            <form
                                method="POST"
                                action="{{ route(
                                    'accounts.destroy',
                                    $account
                                ) }}"
                                onsubmit="
                                    return confirm(
                                        'Möchtest du dieses Konto wirklich löschen?'
                                    );
                                "
                            >

                                @csrf

                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="
                                        inline-flex
                                        items-center
                                        gap-2
                                        text-sm
                                        font-medium
                                        text-red-500
                                        dark:text-red-400
                                        hover:text-red-700
                                        dark:hover:text-red-300
                                        transition
                                    "
                                >

                                    <span>
                                        🗑
                                    </span>

                                    Löschen

                                </button>

                            </form>

                        </div>

                    </div>

                @endforeach

            </div>

        @endif

    </div>


    {{-- ========================================================= --}}
    {{-- HINWEIS --}}
    {{-- ========================================================= --}}

    @if ($accounts->isNotEmpty())

        <div
            class="
                mt-6
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
                        flex-shrink-0
                    "
                >
                    💡
                </div>

                <div>

                    <p class="text-sm font-medium text-slate-700 dark:text-slate-200">
                        Tipp
                    </p>

                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Du kannst in den Kontoeinstellungen festlegen,
                        welche Konten in dein Gesamtvermögen einbezogen werden.
                    </p>

                </div>

            </div>

        </div>

    @endif

</div>

@endsection