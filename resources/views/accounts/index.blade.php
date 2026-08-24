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
                Verwalte deine Bankkonten, Bargeldkonten und weitere Vermögenswerte.
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
            + Konto hinzufügen
        </a>

    </div>



    {{-- ========================================================= --}}
    {{-- ERFOLGSMELDUNG --}}
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



    {{-- ========================================================= --}}
    {{-- FEHLER --}}
    {{-- ========================================================= --}}

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
    {{-- ÜBERSICHT --}}
    {{-- ========================================================= --}}

    @php

        $activeAccounts = $accounts->where('is_active', true);

        $totalAccounts = $accounts->count();

        $includedAccounts = $accounts
            ->where('include_in_total', true)
            ->count();

    @endphp


    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-8">


        {{-- GESAMT KONTEN --}}

        <div
            class="
                bg-white
                rounded-2xl
                shadow-sm
                border
                border-slate-100
                p-5
            "
        >

            <div class="flex items-center justify-between">

                <div>

                    <p class="text-sm text-slate-500">
                        Konten
                    </p>

                    <p class="text-3xl font-semibold text-slate-900 mt-2">
                        {{ $totalAccounts }}
                    </p>

                </div>


                <div
                    class="
                        w-11
                        h-11
                        rounded-xl
                        bg-slate-100
                        flex
                        items-center
                        justify-center
                        text-xl
                    "
                >
                    🏦
                </div>

            </div>

        </div>



        {{-- AKTIVE KONTEN --}}

        <div
            class="
                bg-white
                rounded-2xl
                shadow-sm
                border
                border-slate-100
                p-5
            "
        >

            <div class="flex items-center justify-between">

                <div>

                    <p class="text-sm text-slate-500">
                        Aktive Konten
                    </p>

                    <p class="text-3xl font-semibold text-emerald-600 mt-2">
                        {{ $activeAccounts->count() }}
                    </p>

                </div>


                <div
                    class="
                        w-11
                        h-11
                        rounded-xl
                        bg-emerald-50
                        flex
                        items-center
                        justify-center
                        text-xl
                    "
                >
                    ✓
                </div>

            </div>

        </div>



        {{-- GESAMTVERMÖGEN --}}

        <div
            class="
                bg-white
                rounded-2xl
                shadow-sm
                border
                border-slate-100
                p-5
            "
        >

            <div class="flex items-center justify-between">

                <div>

                    <p class="text-sm text-slate-500">
                        Im Gesamtvermögen
                    </p>

                    <p class="text-3xl font-semibold text-slate-900 mt-2">
                        {{ $includedAccounts }}
                    </p>

                </div>


                <div
                    class="
                        w-11
                        h-11
                        rounded-xl
                        bg-slate-100
                        flex
                        items-center
                        justify-center
                        text-xl
                    "
                >
                    💰
                </div>

            </div>

        </div>

    </div>



    {{-- ========================================================= --}}
    {{-- KONTEN --}}
    {{-- ========================================================= --}}

    @if ($accounts->isEmpty())

        <div
            class="
                bg-white
                rounded-3xl
                shadow-sm
                border
                border-slate-100
                mt-6
                p-10
                sm:p-16
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


            <h2 class="font-semibold text-slate-900 text-lg mt-5">
                Noch keine Konten
            </h2>


            <p class="text-sm text-slate-500 mt-2 max-w-md mx-auto">
                Lege dein erstes Konto an, damit Finanzblick deine
                Kontostände und Buchungen verwalten kann.
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


        {{-- ===================================================== --}}
        {{-- DESKTOP / TABLET KONTEN --}}
        {{-- ===================================================== --}}

        <div
            class="
                grid
                grid-cols-1
                md:grid-cols-2
                xl:grid-cols-3
                gap-5
                mt-6
            "
        >


            @foreach ($accounts as $account)

                <div
                    class="
                        bg-white
                        rounded-3xl
                        shadow-sm
                        border
                        border-slate-100
                        overflow-hidden
                        hover:shadow-md
                        transition
                    "
                >


                    {{-- KARTEN HEADER --}}

                    <div class="p-5">


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
                                style="background-color: {{ $account->color ?: '#f1f5f9' }}"
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
                                        text-xs
                                        font-medium
                                        text-emerald-700
                                        bg-emerald-50
                                        px-2.5
                                        py-1.5
                                        rounded-full
                                    "
                                >

                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>

                                    Aktiv

                                </span>

                            @else

                                <span
                                    class="
                                        text-xs
                                        font-medium
                                        text-slate-500
                                        bg-slate-100
                                        px-2.5
                                        py-1.5
                                        rounded-full
                                    "
                                >
                                    Inaktiv
                                </span>

                            @endif

                        </div>



                        {{-- KONTO NAME --}}

                        <div class="mt-5">

                            <h2 class="font-semibold text-lg text-slate-900 truncate">
                                {{ $account->name }}
                            </h2>


                            <p class="text-sm text-slate-500 mt-1 truncate">

                                @if ($account->institution)

                                    {{ $account->institution }}

                                @else

                                    @switch($account->type)

                                        @case('checking')
                                            Girokonto
                                            @break

                                        @case('savings')
                                            Sparkonto
                                            @break

                                        @case('credit_card')
                                            Kreditkarte
                                            @break

                                        @case('paypal')
                                            PayPal
                                            @break

                                        @case('cash')
                                            Bargeld
                                            @break

                                        @case('investment')
                                            Investment
                                            @break

                                        @case('loan')
                                            Kredit
                                            @break

                                        @default
                                            Sonstiges

                                    @endswitch

                                @endif

                            </p>

                        </div>



                        {{-- SALDO --}}

                        <div class="mt-6">

                            <p class="text-xs text-slate-400">
                                Aktueller Kontostand
                            </p>


                            <p
                                class="
                                    text-3xl
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

                                <span class="text-lg">
                                    {{ $account->currency }}
                                </span>

                            </p>

                        </div>

                    </div>



                    {{-- TRENNER --}}

                    <div class="border-t border-slate-100"></div>



                    {{-- KARTEN FOOTER --}}

                    <div
                        class="
                            px-5
                            py-4
                            flex
                            items-center
                            justify-between
                            gap-3
                            bg-slate-50/50
                        "
                    >


                        <div>

                            @if ($account->include_in_total)

                                <p class="text-xs text-emerald-600 font-medium">
                                    ✓ Im Gesamtvermögen
                                </p>

                            @else

                                <p class="text-xs text-slate-400">
                                    Nicht eingerechnet
                                </p>

                            @endif

                        </div>


                        <div class="flex items-center gap-3">

                            <a
                                href="{{ route('accounts.edit', $account) }}"
                                class="
                                    text-sm
                                    font-medium
                                    text-slate-500
                                    hover:text-slate-900
                                "
                            >
                                Bearbeiten
                            </a>


                            <form
                                method="POST"
                                action="{{ route('accounts.destroy', $account) }}"
                                onsubmit="return confirm('Möchtest du dieses Konto wirklich löschen?');"
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

                </div>

            @endforeach

        </div>

    @endif



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
                border-slate-200
                p-5
                flex
                items-start
                gap-4
            "
        >

            <div class="text-xl">
                💡
            </div>

            <div>

                <p class="font-medium text-slate-900">
                    Tipp
                </p>

                <p class="text-sm text-slate-500 mt-1">
                    Konten, die nicht in das Gesamtvermögen einbezogen werden,
                    bleiben trotzdem für Buchungen verfügbar.
                </p>

            </div>

        </div>

    @endif


</div>

@endsection