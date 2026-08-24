@extends('layouts.app')

@section('title', 'Konten – Finanzblick')

@section('eyebrow', 'Finanzverwaltung')

@section('page_title', 'Konten')


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
                Deine Konten
            </h2>

            <p class="text-slate-500 mt-1">
                Verwalte Bankkonten, Bargeld und weitere Vermögenswerte.
            </p>

        </div>


        <a
            href="{{ route('accounts.create') }}"
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
            + Konto
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
    {{-- GESAMTÜBERSICHT --}}
    {{-- ========================================================= --}}

    @php

        $activeAccounts = $accounts->where('is_active', true);

        $includedAccounts = $accounts->where(
            'include_in_total',
            true
        );

        $totalBalance = $includedAccounts->sum(
            'calculated_balance'
        );

    @endphp


    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-8">


        {{-- GESAMTVERMÖGEN --}}

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
                Gesamtvermögen
            </p>

            <p
                class="
                    text-2xl
                    font-semibold
                    mt-2
                    {{ $totalBalance >= 0
                        ? 'text-slate-900'
                        : 'text-red-600' }}
                "
            >
                {{ number_format(
                    $totalBalance,
                    2,
                    ',',
                    '.'
                ) }}
                €
            </p>

            <p class="text-xs text-slate-400 mt-1">
                Einbezogene Konten
            </p>

        </div>



        {{-- KONTEN --}}

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
                Aktive Konten
            </p>

            <p class="text-2xl font-semibold text-slate-900 mt-2">
                {{ $activeAccounts->count() }}
            </p>

            <p class="text-xs text-slate-400 mt-1">
                Von {{ $accounts->count() }} Konten insgesamt
            </p>

        </div>



        {{-- EINBEZOGEN --}}

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
                Im Gesamtvermögen
            </p>

            <p class="text-2xl font-semibold text-emerald-600 mt-2">
                {{ $includedAccounts->count() }}
            </p>

            <p class="text-xs text-slate-400 mt-1">
                Konten werden berücksichtigt
            </p>

        </div>

    </div>



    {{-- ========================================================= --}}
    {{-- KONTEN --}}
    {{-- ========================================================= --}}

    <div class="mt-8">


        @if ($accounts->isEmpty())

            {{-- ================================================= --}}
            {{-- KEINE KONTEN --}}
            {{-- ================================================= --}}

            <div
                class="
                    bg-white
                    rounded-3xl
                    border
                    border-slate-100
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
                        bg-slate-100
                        flex
                        items-center
                        justify-center
                        text-3xl
                    "
                >
                    🏦
                </div>


                <h3 class="text-lg font-semibold text-slate-900 mt-5">
                    Noch keine Konten
                </h3>


                <p class="text-sm text-slate-500 mt-2 max-w-md mx-auto">
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
                        bg-slate-950
                        px-5
                        py-3
                        text-sm
                        font-medium
                        text-white
                        hover:bg-slate-800
                    "
                >
                    Erstes Konto erstellen
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
                    gap-5
                "
            >

                @foreach ($accounts as $account)

                    <div
                        class="
                            bg-white
                            rounded-3xl
                            border
                            border-slate-100
                            shadow-sm
                            overflow-hidden
                            hover:shadow-md
                            transition
                        "
                    >


                        {{-- KOPF --}}

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
                                    "
                                    style="
                                        background-color:
                                        {{ $account->color ?: '#f1f5f9' }};
                                    "
                                >
                                    {{ $account->icon ?: '🏦' }}
                                </div>


                                {{-- STATUS --}}

                                @if ($account->is_active)

                                    <span
                                        class="
                                            text-[11px]
                                            font-medium
                                            text-emerald-700
                                            bg-emerald-50
                                            px-2.5
                                            py-1
                                            rounded-full
                                        "
                                    >
                                        Aktiv
                                    </span>

                                @else

                                    <span
                                        class="
                                            text-[11px]
                                            font-medium
                                            text-slate-500
                                            bg-slate-100
                                            px-2.5
                                            py-1
                                            rounded-full
                                        "
                                    >
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
                                    mt-5
                                    truncate
                                "
                            >
                                {{ $account->name }}
                            </h3>


                            {{-- INSTITUT --}}

                            <p class="text-sm text-slate-500 mt-1">

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

                            <div class="mt-6">

                                <p class="text-xs text-slate-400">
                                    Aktueller Kontostand
                                </p>

                                <p
                                    class="
                                        text-2xl
                                        font-semibold
                                        mt-1
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

                            </div>



                            {{-- INFO --}}

                            <div
                                class="
                                    flex
                                    items-center
                                    justify-between
                                    mt-6
                                    pt-4
                                    border-t
                                    border-slate-100
                                "
                            >

                                <span class="text-xs text-slate-400">

                                    @if ($account->include_in_total)

                                        <span class="text-emerald-600">
                                            ● Im Gesamtvermögen
                                        </span>

                                    @else

                                        Nicht eingerechnet

                                    @endif

                                </span>


                                <span class="text-xs text-slate-400">
                                    {{ $account->currency }}
                                </span>

                            </div>

                        </div>



                        {{-- AKTIONEN --}}

                        <div
                            class="
                                px-6
                                py-4
                                bg-slate-50
                                border-t
                                border-slate-100
                                flex
                                items-center
                                justify-between
                            "
                        >

                            <a
                                href="{{ route(
                                    'accounts.edit',
                                    $account
                                ) }}"
                                class="
                                    text-sm
                                    font-medium
                                    text-slate-600
                                    hover:text-slate-900
                                "
                            >
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
                                        text-sm
                                        font-medium
                                        text-red-500
                                        hover:text-red-700
                                    "
                                >
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
                border
                border-slate-100
                p-5
            "
        >

            <div class="flex items-start gap-3">

                <span class="text-lg">
                    💡
                </span>

                <div>

                    <p class="text-sm font-medium text-slate-700">
                        Tipp
                    </p>

                    <p class="text-xs text-slate-500 mt-1">
                        Du kannst festlegen, welche Konten in dein
                        Gesamtvermögen einbezogen werden.
                    </p>

                </div>

            </div>

        </div>

    @endif


</div>

@endsection