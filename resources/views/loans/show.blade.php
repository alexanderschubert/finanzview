@extends('layouts.app')

@section('title', $loan->name . ' – Finanzblick')

@section('eyebrow', 'Finanzplanung')

@section('page_title', $loan->name)

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
                    Finanzplanung
                </p>

            </div>

            <div class="flex items-center gap-4 mt-3">

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
                        {{ $loan->creditor_color ?: '#10b981' }}20;
                    "
                >
                    {{ $loan->creditor_icon ?: '💳' }}
                </div>

                <div class="min-w-0">

                    <h2
                        class="
                            text-3xl
                            sm:text-4xl
                            font-semibold
                            tracking-tight
                            text-slate-900
                            dark:text-white
                            truncate
                        "
                    >
                        {{ $loan->name }}
                    </h2>

                    <p class="text-slate-500 dark:text-slate-400 mt-1">
                        {{ $loan->creditor_name ?: 'Kredit' }}
                    </p>

                </div>

            </div>

        </div>


        {{-- AKTIONEN --}}

        <div class="flex items-center gap-3">

            <a
                href="{{ route('loans.index') }}"
                class="
                    inline-flex
                    items-center
                    justify-center
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
                    text-slate-600
                    dark:text-slate-300
                    hover:bg-slate-50
                    dark:hover:bg-slate-800
                    transition
                "
            >
                ←
                <span class="hidden sm:inline ml-2">
                    Kredite
                </span>
            </a>

            <a
                href="{{ route('loans.edit', $loan) }}"
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
                "
            >
                <span class="mr-2">
                    ✏️
                </span>

                <span class="hidden sm:inline">
                    Bearbeiten
                </span>

            </a>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- ERFOLGSMELDUNG --}}
    {{-- ========================================================= --}}

    @if(session('success'))

        <div
            class="
                mt-6
                rounded-2xl
                border
                border-emerald-100
                dark:border-emerald-900
                bg-emerald-50
                dark:bg-emerald-950/40
                px-5
                py-4
                text-sm
                text-emerald-700
                dark:text-emerald-300
            "
        >
            {{ session('success') }}
        </div>

    @endif


    {{-- ========================================================= --}}
    {{-- HAUPTKARTE --}}
    {{-- ========================================================= --}}

    <section
        class="
            mt-8
            rounded-3xl
            bg-white
            dark:bg-slate-900
            border
            border-slate-100
            dark:border-slate-800
            shadow-sm
            overflow-hidden
        "
    >

        <div class="p-6 sm:p-8">

            <div
                class="
                    flex
                    flex-col
                    lg:flex-row
                    lg:items-start
                    lg:justify-between
                    gap-8
                "
            >

                {{-- INFO --}}

                <div>

                    <div class="flex flex-wrap items-center gap-2">

                        <span
                            class="
                                rounded-full
                                px-3
                                py-1
                                text-xs
                                font-medium
                                {{ $loan->is_active
                                    ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400'
                                    : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400' }}
                            "
                        >
                            {{ $loan->is_active ? 'Aktiv' : 'Inaktiv' }}
                        </span>

                    </div>

                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-4">
                        Aktuelle Restschuld
                    </p>

                    <p
                        class="
                            text-4xl
                            sm:text-5xl
                            font-semibold
                            tracking-tight
                            text-slate-900
                            dark:text-white
                            mt-1
                        "
                    >
                        {{ number_format(
                            $loan->remaining_amount,
                            2,
                            ',',
                            '.'
                        ) }} €
                    </p>

                </div>


                {{-- FORTSCHRITT --}}

                <div class="w-full lg:max-w-md">

                    <div class="flex items-center justify-between gap-4 mb-3">

                        <div>

                            <p class="text-sm font-medium text-slate-700 dark:text-slate-200">
                                Tilgungsfortschritt
                            </p>

                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">

                                {{ number_format(
                                    $loan->paid_amount,
                                    2,
                                    ',',
                                    '.'
                                ) }}

                                € von

                                {{ number_format(
                                    $loan->principal_amount,
                                    2,
                                    ',',
                                    '.'
                                ) }}

                                € getilgt

                            </p>

                        </div>

                        <span
                            class="text-lg font-semibold"
                            style="
                                color:
                                {{ $loan->creditor_color ?: '#10b981' }};
                            "
                        >
                            {{ number_format(
                                $loan->progress,
                                1,
                                ',',
                                '.'
                            ) }} %
                        </span>

                    </div>


                    <div
                        class="
                            h-4
                            rounded-full
                            bg-slate-100
                            dark:bg-slate-800
                            overflow-hidden
                        "
                    >

                        <div
                            class="h-full rounded-full transition-all duration-700"
                            style="
                                width:
                                {{ min(
                                    100,
                                    max(
                                        0,
                                        $loan->progress
                                    )
                                ) }}%;

                                background-color:
                                {{ $loan->creditor_color ?: '#10b981' }};
                            "
                        ></div>

                    </div>

                </div>

            </div>

        </div>


        {{-- KENNZAHLEN --}}

        <div
            class="
                grid
                grid-cols-2
                lg:grid-cols-4
                border-t
                border-slate-100
                dark:border-slate-800
            "
        >

            <div class="p-5 sm:p-6">

                <p class="text-xs text-slate-400 dark:text-slate-500">
                    Ursprünglicher Betrag
                </p>

                <p class="text-lg font-semibold text-slate-900 dark:text-white mt-1">
                    {{ number_format(
                        $loan->principal_amount,
                        2,
                        ',',
                        '.'
                    ) }} €
                </p>

            </div>


            <div
                class="
                    p-5
                    sm:p-6
                    border-l
                    border-slate-100
                    dark:border-slate-800
                "
            >

                <p class="text-xs text-slate-400 dark:text-slate-500">
                    Monatliche Rate
                </p>

                <p class="text-lg font-semibold text-slate-900 dark:text-white mt-1">
                    {{ number_format(
                        $loan->installment_amount,
                        2,
                        ',',
                        '.'
                    ) }} €
                </p>

            </div>


            <div
                class="
                    p-5
                    sm:p-6
                    border-t
                    lg:border-t-0
                    lg:border-l
                    border-slate-100
                    dark:border-slate-800
                "
            >

                <p class="text-xs text-slate-400 dark:text-slate-500">
                    Noch offen
                </p>

                <p class="text-lg font-semibold text-slate-900 dark:text-white mt-1">

                    @if($loan->remaining_installments !== null)

                        {{ $loan->remaining_installments }} Raten

                    @else

                        –

                    @endif

                </p>

            </div>


            <div
                class="
                    p-5
                    sm:p-6
                    border-l
                    border-t
                    lg:border-t-0
                    border-slate-100
                    dark:border-slate-800
                "
            >

                <p class="text-xs text-slate-400 dark:text-slate-500">
                    Zinssatz
                </p>

                <p class="text-lg font-semibold text-slate-900 dark:text-white mt-1">

                    @if($loan->interest_rate !== null)

                        {{ number_format(
                            $loan->interest_rate,
                            3,
                            ',',
                            '.'
                        ) }} %

                    @else

                        –

                    @endif

                </p>

            </div>

        </div>

    </section>


    {{-- ========================================================= --}}
    {{-- ZWEI SPALTEN --}}
    {{-- ========================================================= --}}

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mt-5">


        {{-- ===================================================== --}}
        {{-- TILGUNGSPLAN --}}
        {{-- ===================================================== --}}

        <section
            class="
                lg:col-span-2
                bg-white
                dark:bg-slate-900
                rounded-3xl
                border
                border-slate-100
                dark:border-slate-800
                shadow-sm
                overflow-hidden
            "
        >

            <details open>

                <summary
                    class="
                        list-none
                        cursor-pointer
                        p-6
                        sm:p-8
                    "
                >

                    <div class="flex items-center justify-between gap-4">

                        <div>

                            <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                                Tilgung
                            </p>

                            <h3 class="text-xl font-semibold text-slate-900 dark:text-white mt-1">
                                Tilgungsplan
                            </h3>

                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                                Raten und Zahlungsstatus
                            </p>

                        </div>

                        <span class="text-slate-400 dark:text-slate-500">
                            ▼
                        </span>

                    </div>

                </summary>


                <div class="px-6 sm:px-8 pb-8">

                    @if($payments->count() > 0)

                        <div class="overflow-x-auto">

                            <table class="w-full text-sm">

                                <thead>

                                    <tr
                                        class="
                                            border-b
                                            border-slate-100
                                            dark:border-slate-800
                                            text-left
                                        "
                                    >

                                        <th class="py-3 pr-4 font-medium text-slate-400 dark:text-slate-500">
                                            Rate
                                        </th>

                                        <th class="py-3 px-4 font-medium text-slate-400 dark:text-slate-500">
                                            Fälligkeit
                                        </th>

                                        <th class="py-3 px-4 font-medium text-slate-400 dark:text-slate-500">
                                            Betrag
                                        </th>

                                        <th class="py-3 px-4 font-medium text-slate-400 dark:text-slate-500">
                                            Typ
                                        </th>

                                        <th class="py-3 pl-4 text-right font-medium text-slate-400 dark:text-slate-500">
                                            Status
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>

                                    @foreach($payments as $payment)

                                        <tr
                                            class="
                                                border-b
                                                border-slate-100
                                                dark:border-slate-800/70
                                                last:border-0
                                            "
                                        >

                                            <td class="py-4 pr-4 font-medium text-slate-900 dark:text-white">
                                                {{ $payment->installment_number }}
                                            </td>

                                            <td class="py-4 px-4 whitespace-nowrap text-slate-600 dark:text-slate-300">
                                                {{ $payment->due_date->format('d.m.Y') }}
                                            </td>

                                            <td class="py-4 px-4 whitespace-nowrap font-medium text-slate-900 dark:text-white">
                                                {{ number_format(
                                                    $payment->amount,
                                                    2,
                                                    ',',
                                                    '.'
                                                ) }} €
                                            </td>

                                            <td class="py-4 px-4">

                                                @if($payment->payment_type === 'extra')

                                                    <span
                                                        class="
                                                            inline-flex
                                                            rounded-full
                                                            bg-violet-50
                                                            dark:bg-violet-950/40
                                                            px-2.5
                                                            py-1
                                                            text-xs
                                                            font-medium
                                                            text-violet-600
                                                            dark:text-violet-400
                                                        "
                                                    >
                                                        Sondertilgung
                                                    </span>

                                                @else

                                                    <span
                                                        class="
                                                            inline-flex
                                                            rounded-full
                                                            bg-slate-100
                                                            dark:bg-slate-800
                                                            px-2.5
                                                            py-1
                                                            text-xs
                                                            font-medium
                                                            text-slate-600
                                                            dark:text-slate-400
                                                        "
                                                    >
                                                        Rate
                                                    </span>

                                                @endif

                                            </td>

                                            <td class="py-4 pl-4 text-right">

                                                @if($payment->status === 'paid')

                                                    <span
                                                        class="
                                                            inline-flex
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
                                                        ✓ Bezahlt
                                                    </span>

                                                @elseif($payment->status === 'overdue')

                                                    <span
                                                        class="
                                                            inline-flex
                                                            rounded-full
                                                            bg-red-50
                                                            dark:bg-red-950/40
                                                            px-2.5
                                                            py-1
                                                            text-xs
                                                            font-medium
                                                            text-red-600
                                                            dark:text-red-400
                                                        "
                                                    >
                                                        Überfällig
                                                    </span>

                                                @elseif($payment->status === 'cancelled')

                                                    <span
                                                        class="
                                                            inline-flex
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
                                                        Storniert
                                                    </span>

                                                @else

                                                    <span
                                                        class="
                                                            inline-flex
                                                            rounded-full
                                                            bg-amber-50
                                                            dark:bg-amber-950/40
                                                            px-2.5
                                                            py-1
                                                            text-xs
                                                            font-medium
                                                            text-amber-600
                                                            dark:text-amber-400
                                                        "
                                                    >
                                                        Geplant
                                                    </span>

                                                @endif

                                            </td>

                                        </tr>

                                    @endforeach

                                </tbody>

                            </table>

                        </div>

                    @else

                        <div
                            class="
                                rounded-2xl
                                bg-slate-50
                                dark:bg-slate-800/50
                                p-8
                                text-center
                            "
                        >

                            <div class="text-3xl">
                                📅
                            </div>

                            <h3 class="font-medium text-slate-900 dark:text-white mt-3">
                                Noch kein Tilgungsplan vorhanden
                            </h3>

                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                                Die Raten werden hier angezeigt, sobald ein Tilgungsplan angelegt wurde.
                            </p>

                        </div>

                    @endif

                </div>

            </details>

        </section>


        {{-- ===================================================== --}}
        {{-- RECHTE SPALTE --}}
        {{-- ===================================================== --}}

        <div class="space-y-5">


            {{-- ================================================= --}}
            {{-- SONDETILGUNGEN --}}
            {{-- ================================================= --}}

            <section
                class="
                    bg-white
                    dark:bg-slate-900
                    rounded-3xl
                    border
                    border-slate-100
                    dark:border-slate-800
                    shadow-sm
                    overflow-hidden
                "
            >

                <details>

                    <summary
                        class="
                            list-none
                            cursor-pointer
                            p-6
                        "
                    >

                        <div class="flex items-center justify-between gap-3">

                            <div>

                                <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                                    Zusätzliche Zahlungen
                                </p>

                                <h3 class="font-semibold text-slate-900 dark:text-white mt-1">
                                    Sondertilgungen
                                </h3>

                            </div>

                            <span class="text-slate-400 dark:text-slate-500">
                                ▼
                            </span>

                        </div>

                    </summary>


                    <div class="px-6 pb-6">

                        @if($extraPayments->count() > 0)

                            <div class="space-y-3">

                                @foreach($extraPayments as $payment)

                                    <div
                                        class="
                                            flex
                                            items-center
                                            justify-between
                                            gap-3
                                            rounded-2xl
                                            bg-slate-50
                                            dark:bg-slate-800
                                            p-3
                                        "
                                    >

                                        <div>

                                            <p class="text-sm font-medium text-slate-900 dark:text-white">

                                                {{ $payment->paid_date?->format('d.m.Y')
                                                    ?? $payment->due_date->format('d.m.Y') }}

                                            </p>

                                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                                                Sondertilgung
                                            </p>

                                        </div>

                                        <p class="font-semibold text-slate-900 dark:text-white">
                                            {{ number_format(
                                                $payment->amount,
                                                2,
                                                ',',
                                                '.'
                                            ) }} €
                                        </p>

                                    </div>

                                @endforeach

                            </div>

                        @else

                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                Noch keine Sondertilgungen erfasst.
                            </p>

                        @endif


                        {{-- FORMULAR --}}

                        <form
                            method="POST"
                            action="{{ route('loans.extra-payment', $loan) }}"
                            class="
                                mt-5
                                pt-5
                                border-t
                                border-slate-100
                                dark:border-slate-800
                                space-y-4
                            "
                        >

                            @csrf

                            <div>

                                <label
                                    for="extra_amount"
                                    class="
                                        block
                                        text-sm
                                        font-medium
                                        text-slate-700
                                        dark:text-slate-300
                                        mb-2
                                    "
                                >
                                    Betrag
                                </label>

                                <div class="relative">

                                    <input
                                        type="number"
                                        id="extra_amount"
                                        name="amount"
                                        min="0.01"
                                        step="0.01"
                                        required
                                        placeholder="500,00"
                                        class="
                                            w-full
                                            rounded-xl
                                            border
                                            border-slate-200
                                            dark:border-slate-700
                                            bg-white
                                            dark:bg-slate-800
                                            px-4
                                            py-3
                                            pr-10
                                            text-slate-900
                                            dark:text-white
                                            outline-none
                                            focus:ring-2
                                            focus:ring-emerald-500/20
                                            focus:border-emerald-500
                                        "
                                    >

                                    <span
                                        class="
                                            absolute
                                            right-4
                                            top-1/2
                                            -translate-y-1/2
                                            text-slate-400
                                        "
                                    >
                                        €
                                    </span>

                                </div>

                            </div>


                            <div>

                                <label
                                    for="extra_paid_date"
                                    class="
                                        block
                                        text-sm
                                        font-medium
                                        text-slate-700
                                        dark:text-slate-300
                                        mb-2
                                    "
                                >
                                    Datum
                                </label>

                                <input
                                    type="date"
                                    id="extra_paid_date"
                                    name="paid_date"
                                    value="{{ now()->format('Y-m-d') }}"
                                    required
                                    class="
                                        w-full
                                        rounded-xl
                                        border
                                        border-slate-200
                                        dark:border-slate-700
                                        bg-white
                                        dark:bg-slate-800
                                        px-4
                                        py-3
                                        text-slate-900
                                        dark:text-white
                                        outline-none
                                        focus:ring-2
                                        focus:ring-emerald-500/20
                                        focus:border-emerald-500
                                    "
                                >

                            </div>


                            <button
                                type="submit"
                                class="
                                    w-full
                                    rounded-xl
                                    bg-emerald-600
                                    py-3
                                    text-sm
                                    font-medium
                                    text-white
                                    hover:bg-emerald-700
                                    transition
                                "
                            >
                                + Sondertilgung erfassen
                            </button>

                        </form>

                    </div>

                </details>

            </section>


            {{-- ================================================= --}}
            {{-- KREDITINFORMATIONEN --}}
            {{-- ================================================= --}}

            <section
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

                <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                    Übersicht
                </p>

                <h3 class="font-semibold text-slate-900 dark:text-white mt-1 mb-5">
                    Kreditinformationen
                </h3>


                <div class="space-y-4 text-sm">


                    <div class="flex items-center justify-between gap-4">

                        <span class="text-slate-500 dark:text-slate-400">
                            Kreditart
                        </span>

                        <span class="font-medium text-slate-900 dark:text-white text-right">

                            @switch($loan->type)

                                @case('loan')
                                    Ratenkredit
                                    @break

                                @case('installment')
                                    Finanzierung
                                    @break

                                @case('paypal_installment')
                                    PayPal Ratenzahlung
                                    @break

                                @default
                                    Sonstige

                            @endswitch

                        </span>

                    </div>


                    @if($loan->start_date)

                        <div class="flex items-center justify-between gap-4">

                            <span class="text-slate-500 dark:text-slate-400">
                                Startdatum
                            </span>

                            <span class="font-medium text-slate-900 dark:text-white">
                                {{ $loan->start_date->format('d.m.Y') }}
                            </span>

                        </div>

                    @endif


                    @if($loan->end_date)

                        <div class="flex items-center justify-between gap-4">

                            <span class="text-slate-500 dark:text-slate-400">
                                Enddatum
                            </span>

                            <span class="font-medium text-slate-900 dark:text-white">
                                {{ $loan->end_date->format('d.m.Y') }}
                            </span>

                        </div>

                    @endif


                    @if($loan->account)

                        <div class="flex items-center justify-between gap-4">

                            <span class="text-slate-500 dark:text-slate-400">
                                Zahlungskonto
                            </span>

                            <span class="font-medium text-slate-900 dark:text-white text-right">
                                {{ $loan->account->name }}
                            </span>

                        </div>

                    @endif

                </div>

            </section>


            {{-- ================================================= --}}
            {{-- NOTIZEN --}}
            {{-- ================================================= --}}

            @if($loan->notes)

                <section
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

                    <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                        Hinweise
                    </p>

                    <h3 class="font-semibold text-slate-900 dark:text-white mt-1 mb-3">
                        Notizen
                    </h3>

                    <p
                        class="
                            text-sm
                            text-slate-600
                            dark:text-slate-400
                            whitespace-pre-line
                        "
                    >
                        {{ $loan->notes }}
                    </p>

                </section>

            @endif

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- LÖSCHEN --}}
    {{-- ========================================================= --}}

    <div class="mt-8 text-center">

        <form
            method="POST"
            action="{{ route('loans.destroy', $loan) }}"
            onsubmit="return confirm('Möchtest du diesen Kredit wirklich löschen?');"
        >

            @csrf

            @method('DELETE')

            <button
                type="submit"
                class="
                    text-sm
                    text-red-500
                    dark:text-red-400
                    hover:text-red-700
                    dark:hover:text-red-300
                    transition
                "
            >
                Kredit löschen
            </button>

        </form>

    </div>

</div>

@endsection