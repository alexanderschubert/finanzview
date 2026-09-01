@extends('layouts.app')

@section('title', 'Kredit hinzufügen – Finanzblick')

@section('eyebrow', 'Finanzplanung')

@section('page_title', 'Kredit hinzufügen')

@section('content')

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">


    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-5">

        <div class="min-w-0">

            <p class="text-sm text-slate-500 dark:text-slate-400">
                Finanzplanung
            </p>

            <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900 dark:text-white mt-1">
                Kredit hinzufügen
            </h2>

            <p class="text-slate-500 dark:text-slate-400 mt-2">
                Erstelle eine neue Finanzierung und behalte sie im Blick.
            </p>

        </div>


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
                flex-shrink-0
            "
        >
            ←
            <span class="ml-2">
                Kredite
            </span>
        </a>

    </div>


    {{-- ========================================================= --}}
    {{-- VALIDIERUNGSFEHLER --}}
    {{-- ========================================================= --}}

    @if ($errors->any())

        <div
            class="
                mt-6
                rounded-2xl
                border
                border-red-100
                dark:border-red-900
                bg-red-50
                dark:bg-red-950/40
                p-5
            "
        >

            <div class="flex items-start gap-3">

                <span class="text-lg">
                    ⚠️
                </span>

                <div class="min-w-0">

                    <p class="font-medium text-red-700 dark:text-red-300">
                        Bitte überprüfe deine Eingaben.
                    </p>

                    <ul class="mt-2 space-y-1 text-sm text-red-600 dark:text-red-400">

                        @foreach ($errors->all() as $error)

                            <li>
                                • {{ $error }}
                            </li>

                        @endforeach

                    </ul>

                </div>

            </div>

        </div>

    @endif


    {{-- ========================================================= --}}
    {{-- FORMULAR --}}
    {{-- ========================================================= --}}

    <form
        method="POST"
        action="{{ route('loans.store') }}"
        class="mt-8 space-y-6"
    >

        @csrf


        {{-- ===================================================== --}}
        {{-- GRUNDINFORMATIONEN --}}
        {{-- ===================================================== --}}

        <section
            class="
                rounded-3xl
                bg-white
                dark:bg-slate-900
                border
                border-slate-100
                dark:border-slate-800
                shadow-sm
                p-6
                sm:p-8
            "
        >

            <div class="mb-6">

                <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                    Basis
                </p>

                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mt-1">
                    Grundinformationen
                </h3>

                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Wer bekommt das Geld und wie heißt die Finanzierung?
                </p>

            </div>


            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">


                {{-- KREDITNAME --}}

                <div class="md:col-span-2">

                    <label
                        for="name"
                        class="
                            block
                            text-sm
                            font-medium
                            text-slate-700
                            dark:text-slate-300
                            mb-2
                        "
                    >
                        Bezeichnung
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        placeholder="z. B. Autofinanzierung"
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


                {{-- GLÄUBIGER --}}

                <div>

                    <label
                        for="creditor_name"
                        class="
                            block
                            text-sm
                            font-medium
                            text-slate-700
                            dark:text-slate-300
                            mb-2
                        "
                    >
                        Gläubiger
                    </label>

                    <input
                        type="text"
                        id="creditor_name"
                        name="creditor_name"
                        value="{{ old('creditor_name') }}"
                        placeholder="z. B. PayPal, Sparkasse, auxmoney"
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


                {{-- ANBIETER --}}

                <div>

                    <label
                        for="provider_id"
                        class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                    >
                        Anbieter
                        <span class="font-normal text-slate-400">
                            (optional)
                        </span>
                    </label>

                    <select
                        id="provider_id"
                        name="provider_id"
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

                        <option value="">
                            Kein Anbieter ausgewählt
                        </option>

                        @foreach ($providers as $provider)

                            <option
                                value="{{ $provider->id }}"
                                @selected(old('provider_id') == $provider->id)
                            >
                                {{ $provider->emoji }} {{ $provider->name }}
                            </option>

                        @endforeach

                    </select>

                    <p class="mt-2 text-xs text-slate-400 dark:text-slate-500">
                        Das hinterlegte Anbieterlogo wird automatisch beim Kredit angezeigt.
                    </p>

                </div>


                {{-- KREDITART --}}

                <div>

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
                        Kreditart
                    </label>

                    <select
                        id="type"
                        name="type"
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

                        <option value="loan" @selected(old('type', 'loan') === 'loan')>
                            Ratenkredit
                        </option>

                        <option value="installment" @selected(old('type') === 'installment')>
                            Finanzierung
                        </option>

                        <option value="paypal_installment" @selected(old('type') === 'paypal_installment')>
                            PayPal Ratenzahlung
                        </option>

                        <option value="other" @selected(old('type') === 'other')>
                            Sonstige
                        </option>

                    </select>

                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- DARSTELLUNG --}}
        {{-- ===================================================== --}}

        <section
            class="
                rounded-3xl
                bg-white
                dark:bg-slate-900
                border
                border-slate-100
                dark:border-slate-800
                shadow-sm
                p-6
                sm:p-8
            "
        >

            <div class="mb-6">

                <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                    Darstellung
                </p>

                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mt-1">
                    Icon & Farbe
                </h3>

                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Passe das Aussehen des Kredits in der Übersicht an.
                </p>

            </div>


            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">


                {{-- ICON --}}

                <div>

                    <label
                        for="creditor_icon"
                        class="
                            block
                            text-sm
                            font-medium
                            text-slate-700
                            dark:text-slate-300
                            mb-2
                        "
                    >
                        Icon
                    </label>

                    <div class="flex gap-3">

                        <div
                            id="icon-preview"
                            class="
                                w-14
                                h-14
                                shrink-0
                                rounded-2xl
                                bg-slate-100
                                dark:bg-slate-800
                                flex
                                items-center
                                justify-center
                                text-2xl
                            "
                        >
                            {{ old('creditor_icon', '💳') }}
                        </div>

                        <input
                            type="text"
                            id="creditor_icon"
                            name="creditor_icon"
                            value="{{ old('creditor_icon', '💳') }}"
                            maxlength="20"
                            class="
                                min-w-0
                                flex-1
                                rounded-xl
                                border
                                border-slate-200
                                dark:border-slate-700
                                bg-white
                                dark:bg-slate-800
                                px-4
                                py-3
                                text-xl
                                text-slate-900
                                dark:text-white
                                outline-none
                                focus:ring-2
                                focus:ring-emerald-500/20
                                focus:border-emerald-500
                            "
                        >

                    </div>

                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">
                        Zum Beispiel 💳 🏦 🚗 🏠 🛒
                    </p>

                </div>


                {{-- FARBE --}}

                <div>

                    <label
                        for="creditor_color"
                        class="
                            block
                            text-sm
                            font-medium
                            text-slate-700
                            dark:text-slate-300
                            mb-2
                        "
                    >
                        Farbe
                    </label>

                    <div class="flex gap-3">

                        <input
                            type="color"
                            id="creditor_color"
                            name="creditor_color"
                            value="{{ old('creditor_color', '#10b981') }}"
                            class="
                                w-14
                                h-14
                                rounded-xl
                                border
                                border-slate-200
                                dark:border-slate-700
                                cursor-pointer
                                bg-white
                                dark:bg-slate-800
                            "
                        >

                        <div class="flex-1 flex items-center">

                            <span class="text-sm text-slate-500 dark:text-slate-400">
                                Die Farbe wird für Icon und Fortschrittsbalken verwendet.
                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- KREDITDATEN --}}
        {{-- ===================================================== --}}

        <section
            class="
                rounded-3xl
                bg-white
                dark:bg-slate-900
                border
                border-slate-100
                dark:border-slate-800
                shadow-sm
                p-6
                sm:p-8
            "
        >

            <div class="mb-6">

                <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                    Finanzen
                </p>

                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mt-1">
                    Kreditdaten
                </h3>

                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Beträge, Zinsen und monatliche Rate.
                </p>

            </div>


            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">


                {{-- URSPRÜNGLICHER BETRAG --}}

                <div>

                    <label
                        for="principal_amount"
                        class="
                            block
                            text-sm
                            font-medium
                            text-slate-700
                            dark:text-slate-300
                            mb-2
                        "
                    >
                        Ursprünglicher Kreditbetrag
                    </label>

                    <div class="relative">

                        <input
                            type="number"
                            id="principal_amount"
                            name="principal_amount"
                            value="{{ old('principal_amount') }}"
                            required
                            min="0.01"
                            step="0.01"
                            placeholder="10000,00"
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
                                pr-12
                                text-slate-900
                                dark:text-white
                                outline-none
                                focus:ring-2
                                focus:ring-emerald-500/20
                                focus:border-emerald-500
                            "
                        >

                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">
                            €
                        </span>

                    </div>

                </div>


                {{-- BEREITS GETILGT --}}

                <div>

                    <label
                        for="paid_amount"
                        class="
                            block
                            text-sm
                            font-medium
                            text-slate-700
                            dark:text-slate-300
                            mb-2
                        "
                    >
                        Bereits getilgt
                    </label>

                    <div class="relative">

                        <input
                            type="number"
                            id="paid_amount"
                            name="paid_amount"
                            value="{{ old('paid_amount', 0) }}"
                            min="0"
                            step="0.01"
                            placeholder="0,00"
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
                                pr-12
                                text-slate-900
                                dark:text-white
                                outline-none
                                focus:ring-2
                                focus:ring-emerald-500/20
                                focus:border-emerald-500
                            "
                        >

                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">
                            €
                        </span>

                    </div>

                </div>


                {{-- ZINSSATZ --}}

                <div>

                    <label
                        for="interest_rate"
                        class="
                            block
                            text-sm
                            font-medium
                            text-slate-700
                            dark:text-slate-300
                            mb-2
                        "
                    >
                        Zinssatz
                    </label>

                    <div class="relative">

                        <input
                            type="number"
                            id="interest_rate"
                            name="interest_rate"
                            value="{{ old('interest_rate') }}"
                            min="0"
                            step="0.001"
                            placeholder="5,990"
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
                                pr-12
                                text-slate-900
                                dark:text-white
                                outline-none
                                focus:ring-2
                                focus:ring-emerald-500/20
                                focus:border-emerald-500
                            "
                        >

                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">
                            %
                        </span>

                    </div>

                </div>


                {{-- RATE --}}

                <div>

                    <label
                        for="installment_amount"
                        class="
                            block
                            text-sm
                            font-medium
                            text-slate-700
                            dark:text-slate-300
                            mb-2
                        "
                    >
                        Monatliche Rate
                    </label>

                    <div class="relative">

                        <input
                            type="number"
                            id="installment_amount"
                            name="installment_amount"
                            value="{{ old('installment_amount') }}"
                            required
                            min="0.01"
                            step="0.01"
                            placeholder="250,00"
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
                                pr-12
                                text-slate-900
                                dark:text-white
                                outline-none
                                focus:ring-2
                                focus:ring-emerald-500/20
                                focus:border-emerald-500
                            "
                        >

                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">
                            €
                        </span>

                    </div>

                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- LAUFZEIT --}}
        {{-- ===================================================== --}}

        <section
            class="
                rounded-3xl
                bg-white
                dark:bg-slate-900
                border
                border-slate-100
                dark:border-slate-800
                shadow-sm
                p-6
                sm:p-8
            "
        >

            <div class="mb-6">

                <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                    Laufzeit
                </p>

                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mt-1">
                    Laufzeit & Raten
                </h3>

                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Informationen zur Laufzeit des Kredits.
                </p>

            </div>


            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">


                {{-- GESAMTE RATEN --}}

                <div>

                    <label
                        for="total_installments"
                        class="
                            block
                            text-sm
                            font-medium
                            text-slate-700
                            dark:text-slate-300
                            mb-2
                        "
                    >
                        Anzahl der Raten
                    </label>

                    <input
                        type="number"
                        id="total_installments"
                        name="total_installments"
                        value="{{ old('total_installments') }}"
                        min="1"
                        placeholder="48"
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


                {{-- BEZAHLTE RATEN --}}

                <div>

                    <label
                        for="paid_installments"
                        class="
                            block
                            text-sm
                            font-medium
                            text-slate-700
                            dark:text-slate-300
                            mb-2
                        "
                    >
                        Bereits bezahlte Raten
                    </label>

                    <input
                        type="number"
                        id="paid_installments"
                        name="paid_installments"
                        value="{{ old('paid_installments', 0) }}"
                        min="0"
                        placeholder="0"
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


                {{-- STARTDATUM --}}

                <div>

                    <label
                        for="start_date"
                        class="
                            block
                            text-sm
                            font-medium
                            text-slate-700
                            dark:text-slate-300
                            mb-2
                        "
                    >
                        Startdatum
                    </label>

                    <input
                        type="date"
                        id="start_date"
                        name="start_date"
                        value="{{ old('start_date') }}"
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


                {{-- ENDDATUM --}}

                <div>

                    <label
                        for="end_date"
                        class="
                            block
                            text-sm
                            font-medium
                            text-slate-700
                            dark:text-slate-300
                            mb-2
                        "
                    >
                        Voraussichtliches Enddatum
                    </label>

                    <input
                        type="date"
                        id="end_date"
                        name="end_date"
                        value="{{ old('end_date') }}"
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

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- VERKNÜPFUNG --}}
        {{-- ===================================================== --}}

        <section
            class="
                rounded-3xl
                bg-white
                dark:bg-slate-900
                border
                border-slate-100
                dark:border-slate-800
                shadow-sm
                p-6
                sm:p-8
            "
        >

            <div class="mb-6">

                <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                    Verknüpfung
                </p>

                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mt-1">
                    Zahlungskonto
                </h3>

                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Optional kannst du den Kredit einem Konto zuordnen.
                </p>

            </div>


            <div>

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

                    <option value="">
                        Kein Konto ausgewählt
                    </option>

                    @foreach($accounts as $account)

                        <option
                            value="{{ $account->id }}"
                            @selected(old('account_id') == $account->id)
                        >
                            {{ $account->icon ?: '🏦' }}
                            {{ $account->name }}
                        </option>

                    @endforeach

                </select>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- NOTIZEN --}}
        {{-- ===================================================== --}}

        <section
            class="
                rounded-3xl
                bg-white
                dark:bg-slate-900
                border
                border-slate-100
                dark:border-slate-800
                shadow-sm
                p-6
                sm:p-8
            "
        >

            <div class="mb-5">

                <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                    Optional
                </p>

                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mt-1">
                    Notizen
                </h3>

            </div>

            <textarea
                id="notes"
                name="notes"
                rows="4"
                placeholder="Optionale Informationen zum Kredit..."
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
                    resize-y
                    focus:ring-2
                    focus:ring-emerald-500/20
                    focus:border-emerald-500
                "
            >{{ old('notes') }}</textarea>

        </section>


        {{-- ===================================================== --}}
        {{-- AKTIV --}}
        {{-- ===================================================== --}}

        <section
            class="
                rounded-2xl
                bg-white
                dark:bg-slate-900
                border
                border-slate-100
                dark:border-slate-800
                shadow-sm
                p-5
            "
        >

            <label class="flex items-start gap-3 cursor-pointer">

                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    @checked(old('is_active', true))
                    class="
                        mt-0.5
                        w-5
                        h-5
                        rounded
                        border-slate-300
                        text-emerald-500
                        focus:ring-emerald-500
                    "
                >

                <div>

                    <div class="font-medium text-slate-900 dark:text-white">
                        Kredit ist aktiv
                    </div>

                    <div class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Der Kredit wird in der aktiven Übersicht berücksichtigt.
                    </div>

                </div>

            </label>

        </section>


        {{-- ===================================================== --}}
        {{-- BUTTONS --}}
        {{-- ===================================================== --}}

        <div
            class="
                flex
                flex-col-reverse
                sm:flex-row
                sm:justify-end
                gap-3
                pb-8
            "
        >

            <a
                href="{{ route('loans.index') }}"
                class="
                    inline-flex
                    justify-center
                    items-center
                    rounded-xl
                    border
                    border-slate-200
                    dark:border-slate-700
                    bg-white
                    dark:bg-slate-900
                    px-5
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
                Abbrechen
            </a>


            <button
                type="submit"
                class="
                    inline-flex
                    justify-center
                    items-center
                    rounded-xl
                    bg-emerald-600
                    px-6
                    py-3
                    text-sm
                    font-semibold
                    text-white
                    hover:bg-emerald-700
                    transition
                "
            >
                Kredit speichern
            </button>

        </div>

    </form>

</div>


{{-- ========================================================= --}}
{{-- ICON LIVE-VORSCHAU --}}
{{-- ========================================================= --}}

<script>

document.addEventListener('DOMContentLoaded', function () {

    const iconInput = document.getElementById('creditor_icon');
    const iconPreview = document.getElementById('icon-preview');

    if (iconInput && iconPreview) {

        iconInput.addEventListener('input', function () {

            iconPreview.textContent = this.value || '💳';

        });

    }

});

</script>

@endsection