
@extends('layouts.app')

@section('title', 'Wiederkehrende Buchung bearbeiten – FinanzView')

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

        <div class="mt-6">

            <p class="text-sm text-slate-500 dark:text-slate-400">
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
                Wiederkehrende Buchung bearbeiten
            </h2>

            <p class="text-slate-500 dark:text-slate-400 mt-2">
                Passe die Einstellungen deiner regelmäßigen Buchung an.
            </p>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- ERFOLG --}}
    {{-- ========================================================= --}}

    @if(session('success'))

        <div
            class="
                mb-6
                rounded-2xl
                border
                border-emerald-200
                dark:border-emerald-900
                bg-emerald-50
                dark:bg-emerald-950/30
                px-5
                py-4
            "
        >

            <div class="flex items-center gap-3">

                <span class="text-lg text-emerald-600 dark:text-emerald-400">
                    ✓
                </span>

                <p class="text-sm font-medium text-emerald-700 dark:text-emerald-400">
                    {{ session('success') }}
                </p>

            </div>

        </div>

    @endif


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
        action="{{ route('recurring-transactions.update', $recurringTransaction) }}"
    >

        @csrf
        @method('PUT')


        {{-- ===================================================== --}}
        {{-- BUCHUNGSDETAILS --}}
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

            {{-- KARTENKOPF --}}

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

                    <div class="min-w-0">

                        <h3
                            class="
                                text-lg
                                font-semibold
                                text-slate-900
                                dark:text-white
                            "
                        >
                            Buchungsdetails
                        </h3>

                        <p
                            class="
                                text-sm
                                text-slate-500
                                dark:text-slate-400
                                mt-1
                            "
                        >
                            Lege fest, was regelmäßig gebucht werden soll.
                        </p>

                    </div>

                </div>

            </div>


            {{-- INHALT --}}

            <div class="p-6 sm:p-8 space-y-7">


                {{-- ================================================= --}}
                {{-- BUCHUNGSART --}}
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

                        {{-- AUSGABE --}}

                        <label class="cursor-pointer group">

                            <input
                                type="radio"
                                name="type"
                                value="expense"
                                class="peer sr-only"
                                @checked(old('type', $recurringTransaction->type) === 'expense')
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

                                    peer-checked:border-red-500
                                    peer-checked:bg-red-50
                                    dark:peer-checked:border-red-500
                                    dark:peer-checked:bg-red-950/20

                                    group-hover:border-slate-300
                                    dark:group-hover:border-slate-600
                                    peer-checked:group-hover:border-red-500
                                "
                            >

                                <div class="flex items-center gap-4">

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
                                            text-lg
                                        "
                                    >
                                        ↘
                                    </div>

                                    <div class="min-w-0">

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

                                {{-- AUSGEWÄHLT --}}

                                <div
                                    class="
                                        absolute
                                        top-4
                                        right-4
                                        hidden
                                        peer-checked:flex
                                        w-5
                                        h-5
                                        rounded-full
                                        bg-red-500
                                        items-center
                                        justify-center
                                        text-white
                                        text-xs
                                        font-bold
                                    "
                                >
                                    ✓
                                </div>

                            </div>

                        </label>


                        {{-- EINNAHME --}}

                        <label class="cursor-pointer group">

                            <input
                                type="radio"
                                name="type"
                                value="income"
                                class="peer sr-only"
                                @checked(old('type', $recurringTransaction->type) === 'income')
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

                                    peer-checked:border-emerald-500
                                    peer-checked:bg-emerald-50
                                    dark:peer-checked:border-emerald-500
                                    dark:peer-checked:bg-emerald-950/20

                                    group-hover:border-slate-300
                                    dark:group-hover:border-slate-600
                                    peer-checked:group-hover:border-emerald-500
                                "
                            >

                                <div class="flex items-center gap-4">

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
                                            text-lg
                                        "
                                    >
                                        ↗
                                    </div>

                                    <div class="min-w-0">

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

                                {{-- AUSGEWÄHLT --}}

                                <div
                                    class="
                                        absolute
                                        top-4
                                        right-4
                                        hidden
                                        peer-checked:flex
                                        w-5
                                        h-5
                                        rounded-full
                                        bg-emerald-500
                                        items-center
                                        justify-center
                                        text-white
                                        text-xs
                                        font-bold
                                    "
                                >
                                    ✓
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
                        value="{{ old('description', $recurringTransaction->description) }}"
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
                            value="{{ old('amount', $recurringTransaction->amount) }}"
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
                                dark:text-slate-500
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
                                    @selected(
                                        old(
                                            'account_id',
                                            $recurringTransaction->account_id
                                        ) == $account->id
                                    )
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
                                    @selected(
                                        old(
                                            'category_id',
                                            $recurringTransaction->category_id
                                        ) == $category->id
                                    )
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
                mt-6
                rounded-3xl
                bg-white
                dark:bg-slate-900
                border
                border-slate-200
                dark:border-slate-800
                overflow-hidden
            "
        >

            {{-- KARTENKOPF --}}

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
                            bg-slate-100
                            dark:bg-slate-800
                            flex
                            items-center
                            justify-center
                            text-xl
                        "
                    >
                        🗓️
                    </div>

                    <div>

                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                            Zeitplan
                        </h3>

                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Lege fest, wann die Buchung ausgeführt werden soll.
                        </p>

                    </div>

                </div>

            </div>


            <div class="p-6 sm:p-8 space-y-7">


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
                            @selected(
                                old(
                                    'frequency',
                                    $recurringTransaction->frequency
                                ) === 'weekly'
                            )
                        >
                            Wöchentlich
                        </option>

                        <option
                            value="monthly"
                            @selected(
                                old(
                                    'frequency',
                                    $recurringTransaction->frequency
                                ) === 'monthly'
                            )
                        >
                            Monatlich
                        </option>

                        <option
                            value="quarterly"
                            @selected(
                                old(
                                    'frequency',
                                    $recurringTransaction->frequency
                                ) === 'quarterly'
                            )
                        >
                            Vierteljährlich
                        </option>

                        <option
                            value="yearly"
                            @selected(
                                old(
                                    'frequency',
                                    $recurringTransaction->frequency
                                ) === 'yearly'
                            )
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
                            value="{{ old(
                                'next_date',
                                optional($recurringTransaction->next_date)->format('Y-m-d')
                            ) }}"
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
                            value="{{ old(
                                'end_date',
                                optional($recurringTransaction->end_date)->format('Y-m-d')
                            ) }}"
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
                            @checked(
                                old(
                                    'is_active',
                                    $recurringTransaction->is_active
                                )
                            )
                            class="
                                w-5
                                h-5
                                rounded
                                border-slate-300
                                text-emerald-600
                                focus:ring-emerald-500
                            "
                        >

                        <span class="min-w-0">

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
        {{-- AKTIONEN --}}
        {{-- ========================================================= --}}

        <div
            class="
                mt-6
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
                    gap-2
                    rounded-xl
                    bg-emerald-600
                    px-6
                    py-3
                    text-sm
                    font-medium
                    text-white
                    hover:bg-emerald-700
                    transition
                "
            >
                ✓ Änderungen speichern
            </button>

        </div>

    </form>


    {{-- ========================================================= --}}
    {{-- HINWEIS --}}
    {{-- ========================================================= --}}

    <div
        class="
            mt-6
            rounded-3xl
            border
            border-slate-200
            dark:border-slate-800
            bg-slate-50
            dark:bg-slate-900/50
            p-5
            sm:p-6
        "
    >

        <div class="flex items-start gap-4">

            <div
                class="
                    w-10
                    h-10
                    shrink-0
                    rounded-xl
                    bg-slate-100
                    dark:bg-slate-800
                    flex
                    items-center
                    justify-center
                    text-lg
                "
            >
                ℹ️
            </div>

            <div>

                <p class="text-sm font-medium text-slate-700 dark:text-slate-300">
                    Hinweis
                </p>

                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Änderungen werden sofort gespeichert und für zukünftige Ausführungen verwendet.
                </p>

            </div>

        </div>

    </div>

</div>

@endsection