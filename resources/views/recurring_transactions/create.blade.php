@extends('layouts.app')

@section('title', 'Neue wiederkehrende Buchung – Finanzblick')

@section('eyebrow', 'Finanzen')

@section('page_title', 'Wiederkehrend')

@section('content')

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <div class="mb-8">

        <a
            href="{{ route('recurring-transactions.index') }}"
            class="
                inline-flex
                items-center
                gap-2
                text-sm
                text-slate-500
                dark:text-slate-400
                hover:text-slate-900
                dark:hover:text-white
                transition
            "
        >
            ← Wiederkehrend
        </a>

        <p class="text-sm text-slate-500 dark:text-slate-400 mt-6">
            Regelmäßige Buchung
        </p>

        <h2
            class="
                text-3xl
                sm:text-4xl
                font-semibold
                tracking-tight
                text-slate-900
                dark:text-white
                mt-1
            "
        >
            Neue wiederkehrende Buchung
        </h2>

        <p class="text-slate-500 dark:text-slate-400 mt-2">
            Lege eine regelmäßige Einnahme oder Ausgabe an.
        </p>

    </div>


    {{-- ========================================================= --}}
    {{-- FEHLER --}}
    {{-- ========================================================= --}}

    @if($errors->any())

        <div
            class="
                mb-6
                rounded-2xl
                border
                border-red-200
                dark:border-red-900
                bg-red-50
                dark:bg-red-950/30
                px-5
                py-4
            "
        >

            <p class="text-sm font-semibold text-red-700 dark:text-red-400">
                Bitte überprüfe deine Eingaben.
            </p>

            <ul class="mt-2 text-sm text-red-600 dark:text-red-400 space-y-1">

                @foreach($errors->all() as $error)

                    <li>
                        • {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif


    {{-- ========================================================= --}}
    {{-- FORMULAR --}}
    {{-- ========================================================= --}}

    <form
        method="POST"
        action="{{ route('recurring-transactions.store') }}"
    >

        @csrf


        {{-- ===================================================== --}}
        {{-- ART DER BUCHUNG --}}
        {{-- ===================================================== --}}

        <section
            class="
                rounded-3xl
                bg-white
                dark:bg-slate-900
                border
                border-slate-200
                dark:border-slate-800
                overflow-hidden
            "
        >

            {{-- HEADER --}}

            <div
                class="
                    p-6
                    sm:p-8
                    border-b
                    border-slate-200
                    dark:border-slate-800
                "
            >

                <div class="flex items-start gap-4">

                    <div
                        class="
                            w-12
                            h-12
                            shrink-0
                            rounded-2xl
                            bg-emerald-50
                            dark:bg-emerald-500/10
                            flex
                            items-center
                            justify-center
                            text-xl
                        "
                    >
                        🔄
                    </div>

                    <div>

                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                            Buchungsdetails
                        </h3>

                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Lege fest, was regelmäßig gebucht werden soll.
                        </p>

                    </div>

                </div>

            </div>


            <div class="p-6 sm:p-8 space-y-6">


                {{-- ================================================= --}}
                {{-- TYP --}}
                {{-- ================================================= --}}

                <div>

                    <label
                        class="
                            block
                            text-sm
                            font-medium
                            text-slate-700
                            dark:text-slate-300
                            mb-3
                        "
                    >
                        Buchungsart
                    </label>


                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">


                        {{-- ================================================= --}}
                        {{-- AUSGABE --}}
                        {{-- ================================================= --}}

                        <label class="relative block cursor-pointer">

                            <input
                                type="radio"
                                name="type"
                                value="expense"
                                class="peer sr-only"
                                @checked(old('type', 'expense') === 'expense')
                            >

                            <div
                                class="
                                    relative
                                    rounded-2xl
                                    border-2
                                    border-slate-200
                                    dark:border-slate-700
                                    bg-white
                                    dark:bg-slate-800
                                    p-5
                                    transition-all
                                    duration-150
                                    hover:border-slate-300
                                    dark:hover:border-slate-600
                                    peer-checked:border-red-500
                                    peer-checked:bg-red-50
                                    dark:peer-checked:border-red-500
                                    dark:peer-checked:bg-red-950/20
                                "
                            >

                                {{-- AUSWAHL --}}
                                <div
                                    class="
                                        absolute
                                        top-4
                                        right-4
                                        w-5
                                        h-5
                                        rounded-full
                                        border-2
                                        border-slate-300
                                        dark:border-slate-600
                                        flex
                                        items-center
                                        justify-center
                                        transition
                                        peer-checked:border-red-500
                                    "
                                >

                                    <div
                                        class="
                                            w-2.5
                                            h-2.5
                                            rounded-full
                                            bg-red-500
                                            opacity-0
                                            scale-50
                                            transition
                                            peer-checked:opacity-100
                                            peer-checked:scale-100
                                        "
                                    ></div>

                                </div>


                                <div class="flex items-center gap-4 pr-8">

                                    <div
                                        class="
                                            w-11
                                            h-11
                                            shrink-0
                                            rounded-xl
                                            bg-red-50
                                            dark:bg-red-500/10
                                            flex
                                            items-center
                                            justify-center
                                            text-red-600
                                            dark:text-red-400
                                            text-xl
                                        "
                                    >
                                        ↘
                                    </div>

                                    <div>

                                        <p
                                            class="
                                                font-semibold
                                                text-slate-900
                                                dark:text-white
                                            "
                                        >
                                            Ausgabe
                                        </p>

                                        <p
                                            class="
                                                text-xs
                                                text-slate-500
                                                dark:text-slate-400
                                                mt-1
                                            "
                                        >
                                            Geld wird vom Konto abgebucht.
                                        </p>

                                    </div>

                                </div>

                            </div>

                        </label>


                        {{-- ================================================= --}}
                        {{-- EINNAHME --}}
                        {{-- ================================================= --}}

                        <label class="relative block cursor-pointer">

                            <input
                                type="radio"
                                name="type"
                                value="income"
                                class="peer sr-only"
                                @checked(old('type') === 'income')
                            >

                            <div
                                class="
                                    relative
                                    rounded-2xl
                                    border-2
                                    border-slate-200
                                    dark:border-slate-700
                                    bg-white
                                    dark:bg-slate-800
                                    p-5
                                    transition-all
                                    duration-150
                                    hover:border-slate-300
                                    dark:hover:border-slate-600
                                    peer-checked:border-emerald-500
                                    peer-checked:bg-emerald-50
                                    dark:peer-checked:border-emerald-500
                                    dark:peer-checked:bg-emerald-950/20
                                "
                            >

                                {{-- AUSWAHL --}}
                                <div
                                    class="
                                        absolute
                                        top-4
                                        right-4
                                        w-5
                                        h-5
                                        rounded-full
                                        border-2
                                        border-slate-300
                                        dark:border-slate-600
                                        flex
                                        items-center
                                        justify-center
                                        transition
                                    "
                                >

                                    <div
                                        class="
                                            w-2.5
                                            h-2.5
                                            rounded-full
                                            bg-emerald-500
                                            opacity-0
                                            scale-50
                                            transition
                                        "
                                    ></div>

                                </div>


                                <div class="flex items-center gap-4 pr-8">

                                    <div
                                        class="
                                            w-11
                                            h-11
                                            shrink-0
                                            rounded-xl
                                            bg-emerald-50
                                            dark:bg-emerald-500/10
                                            flex
                                            items-center
                                            justify-center
                                            text-emerald-600
                                            dark:text-emerald-400
                                            text-xl
                                        "
                                    >
                                        ↗
                                    </div>

                                    <div>

                                        <p
                                            class="
                                                font-semibold
                                                text-slate-900
                                                dark:text-white
                                            "
                                        >
                                            Einnahme
                                        </p>

                                        <p
                                            class="
                                                text-xs
                                                text-slate-500
                                                dark:text-slate-400
                                                mt-1
                                            "
                                        >
                                            Geld wird dem Konto gutgeschrieben.
                                        </p>

                                    </div>

                                </div>

                            </div>

                        </label>

                    </div>


                    @error('type')

                        <p class="text-sm text-red-600 dark:text-red-400 mt-2">
                            {{ $message }}
                        </p>

                    @enderror

                </div>


                {{-- ================================================= --}}
                {{-- BEZEICHNUNG --}}
                {{-- ================================================= --}}

                <div>

                    <label
                        for="description"
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
                        id="description"
                        name="description"
                        type="text"
                        value="{{ old('description') }}"
                        required
                        maxlength="255"
                        placeholder="z. B. Miete, Gehalt oder Netflix"
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
                            text-sm
                            text-slate-900
                            dark:text-white
                            placeholder:text-slate-400
                            outline-none
                            transition
                            focus:border-emerald-500
                            focus:ring-2
                            focus:ring-emerald-500/20
                        "
                    >

                    @error('description')

                        <p class="text-sm text-red-600 dark:text-red-400 mt-2">
                            {{ $message }}
                        </p>

                    @enderror

                </div>


                {{-- ================================================= --}}
                {{-- BETRAG --}}
                {{-- ================================================= --}}

                <div>

                    <label
                        for="amount"
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
                            id="amount"
                            name="amount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            value="{{ old('amount') }}"
                            required
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
                                text-sm
                                text-slate-900
                                dark:text-white
                                placeholder:text-slate-400
                                outline-none
                                transition
                                focus:border-emerald-500
                                focus:ring-2
                                focus:ring-emerald-500/20
                            "
                        >

                        <span
                            class="
                                absolute
                                right-4
                                top-1/2
                                -translate-y-1/2
                                text-sm
                                font-medium
                                text-slate-400
                            "
                        >
                            €
                        </span>

                    </div>

                    @error('amount')

                        <p class="text-sm text-red-600 dark:text-red-400 mt-2">
                            {{ $message }}
                        </p>

                    @enderror

                </div>


                {{-- ================================================= --}}
                {{-- KONTO + KATEGORIE --}}
                {{-- ================================================= --}}

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">


                    {{-- KONTO --}}

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
                                text-sm
                                text-slate-900
                                dark:text-white
                                outline-none
                                transition
                                focus:border-emerald-500
                                focus:ring-2
                                focus:ring-emerald-500/20
                            "
                        >

                            <option value="">
                                Konto auswählen
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

                        @error('account_id')

                            <p class="text-sm text-red-600 dark:text-red-400 mt-2">
                                {{ $message }}
                            </p>

                        @enderror

                    </div>


                    {{-- KATEGORIE --}}

                    <div>

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
                                w-full
                                rounded-xl
                                border
                                border-slate-200
                                dark:border-slate-700
                                bg-white
                                dark:bg-slate-800
                                px-4
                                py-3
                                text-sm
                                text-slate-900
                                dark:text-white
                                outline-none
                                transition
                                focus:border-emerald-500
                                focus:ring-2
                                focus:ring-emerald-500/20
                            "
                        >

                            <option value="">
                                Keine Kategorie
                            </option>

                            @foreach($categories as $category)

                                <option
                                    value="{{ $category->id }}"
                                    @selected(old('category_id') == $category->id)
                                >
                                    {{ $category->icon ?: '📁' }}
                                    {{ $category->name }}
                                </option>

                            @endforeach

                        </select>

                        @error('category_id')

                            <p class="text-sm text-red-600 dark:text-red-400 mt-2">
                                {{ $message }}
                            </p>

                        @enderror

                    </div>

                </div>

            </div>

        </section>


        {{-- ========================================================= --}}
        {{-- ZEITPLAN --}}
        {{-- ========================================================= --}}

        <section
            class="
                mt-5
                rounded-3xl
                bg-white
                dark:bg-slate-900
                border
                border-slate-200
                dark:border-slate-800
                overflow-hidden
            "
        >

            <div
                class="
                    p-6
                    sm:p-8
                    border-b
                    border-slate-200
                    dark:border-slate-800
                "
            >

                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                    Zeitplan
                </h3>

                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Lege fest, wann die Buchung ausgeführt werden soll.
                </p>

            </div>


            <div class="p-6 sm:p-8 space-y-6">


                {{-- ================================================= --}}
                {{-- INTERVALL --}}
                {{-- ================================================= --}}

                <div>

                    <label
                        for="frequency"
                        class="
                            block
                            text-sm
                            font-medium
                            text-slate-700
                            dark:text-slate-300
                            mb-2
                        "
                    >
                        Intervall
                    </label>

                    <select
                        id="frequency"
                        name="frequency"
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
                            text-sm
                            text-slate-900
                            dark:text-white
                            outline-none
                            transition
                            focus:border-emerald-500
                            focus:ring-2
                            focus:ring-emerald-500/20
                        "
                    >

                        <option
                            value="weekly"
                            @selected(old('frequency', 'monthly') === 'weekly')
                        >
                            Wöchentlich
                        </option>

                        <option
                            value="monthly"
                            @selected(old('frequency', 'monthly') === 'monthly')
                        >
                            Monatlich
                        </option>

                        <option
                            value="quarterly"
                            @selected(old('frequency', 'monthly') === 'quarterly')
                        >
                            Vierteljährlich
                        </option>

                        <option
                            value="yearly"
                            @selected(old('frequency', 'monthly') === 'yearly')
                        >
                            Jährlich
                        </option>

                    </select>

                    @error('frequency')

                        <p class="text-sm text-red-600 dark:text-red-400 mt-2">
                            {{ $message }}
                        </p>

                    @enderror

                </div>


                {{-- ================================================= --}}
                {{-- DATEN --}}
                {{-- ================================================= --}}

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">


                    {{-- NÄCHSTE AUSFÜHRUNG --}}

                    <div>

                        <label
                            for="next_date"
                            class="
                                block
                                text-sm
                                font-medium
                                text-slate-700
                                dark:text-slate-300
                                mb-2
                            "
                        >
                            Nächste Ausführung
                        </label>

                        <input
                            id="next_date"
                            name="next_date"
                            type="date"
                            value="{{ old('next_date', now()->format('Y-m-d')) }}"
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
                                text-sm
                                text-slate-900
                                dark:text-white
                                outline-none
                                transition
                                focus:border-emerald-500
                                focus:ring-2
                                focus:ring-emerald-500/20
                            "
                        >

                        @error('next_date')

                            <p class="text-sm text-red-600 dark:text-red-400 mt-2">
                                {{ $message }}
                            </p>

                        @enderror

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
                            Enddatum

                            <span class="text-slate-400 font-normal">
                                (optional)
                            </span>

                        </label>

                        <input
                            id="end_date"
                            name="end_date"
                            type="date"
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
                                text-sm
                                text-slate-900
                                dark:text-white
                                outline-none
                                transition
                                focus:border-emerald-500
                                focus:ring-2
                                focus:ring-emerald-500/20
                            "
                        >

                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">
                            Leer lassen, wenn die Buchung unbegrenzt laufen soll.
                        </p>

                        @error('end_date')

                            <p class="text-sm text-red-600 dark:text-red-400 mt-2">
                                {{ $message }}
                            </p>

                        @enderror

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- AKTIV --}}
                {{-- ================================================= --}}

                <div
                    class="
                        rounded-2xl
                        bg-slate-50
                        dark:bg-slate-800/60
                        border
                        border-slate-100
                        dark:border-slate-700
                        p-5
                    "
                >

                    <label class="flex items-center gap-4 cursor-pointer">

                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            checked
                            class="
                                w-5
                                h-5
                                rounded
                                border-slate-300
                                text-emerald-600
                                focus:ring-emerald-500
                            "
                        >

                        <span>

                            <span
                                class="
                                    block
                                    text-sm
                                    font-medium
                                    text-slate-700
                                    dark:text-slate-200
                                "
                            >
                                Wiederkehrende Buchung aktivieren
                            </span>

                            <span
                                class="
                                    block
                                    text-xs
                                    text-slate-500
                                    dark:text-slate-400
                                    mt-1
                                "
                            >
                                Die Buchung wird automatisch für zukünftige Ausführungen berücksichtigt.
                            </span>

                        </span>

                    </label>

                </div>

            </div>

        </section>


        {{-- ========================================================= --}}
        {{-- BUTTONS --}}
        {{-- ========================================================= --}}

        <div
            class="
                mt-5
                flex
                flex-col-reverse
                sm:flex-row
                sm:items-center
                sm:justify-end
                gap-3
            "
        >

            <a
                href="{{ route('recurring-transactions.index') }}"
                class="
                    inline-flex
                    items-center
                    justify-center
                    rounded-xl
                    border
                    border-slate-200
                    dark:border-slate-700
                    bg-white
                    dark:bg-slate-800
                    px-5
                    py-3
                    text-sm
                    font-medium
                    text-slate-700
                    dark:text-slate-200
                    hover:bg-slate-100
                    dark:hover:bg-slate-700
                    transition
                "
            >
                Abbrechen
            </a>


            <button
                type="submit"
                class="
                    inline-flex
                    items-center
                    justify-center
                    rounded-xl
                    bg-slate-950
                    dark:bg-white
                    px-6
                    py-3
                    text-sm
                    font-medium
                    text-white
                    dark:text-slate-950
                    hover:bg-slate-800
                    dark:hover:bg-slate-200
                    transition
                "
            >
                Wiederkehrende Buchung erstellen
            </button>

        </div>

    </form>

</div>

@endsection