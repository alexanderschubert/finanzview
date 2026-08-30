@extends('layouts.app')

@section('title', 'Budget erstellen – Finanzblick')

@section('eyebrow', 'Finanzplanung')

@section('page_title', 'Budget erstellen')


@section('content')

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">


    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <div class="mb-6">

        <a
            href="{{ route('budgets.index') }}"
            class="
                inline-flex
                items-center
                text-sm
                text-slate-500
                dark:text-slate-400
                hover:text-slate-900
                dark:hover:text-white
                transition
            "
        >
            ← Budgets
        </a>


        <div class="mt-4">

            <h2
                class="
                    text-2xl
                    sm:text-3xl
                    font-semibold
                    tracking-tight
                    text-slate-900
                    dark:text-white
                "
            >
                Neues Budget
            </h2>

            <p class="text-slate-500 dark:text-slate-400 mt-1">
                Lege fest, wie viel du für bestimmte Kategorien ausgeben möchtest.
            </p>

        </div>

    </div>



    {{-- ========================================================= --}}
    {{-- FEHLER --}}
    {{-- ========================================================= --}}

    @if ($errors->any())

        <div
            class="
                mb-6
                rounded-2xl
                bg-red-50
                dark:bg-red-950/40
                border
                border-red-100
                dark:border-red-900
                p-5
                text-sm
                text-red-700
                dark:text-red-300
            "
        >

            <p class="font-medium mb-2">
                Bitte überprüfe deine Eingaben.
            </p>

            <ul class="list-disc list-inside space-y-1">

                @foreach ($errors->all() as $error)

                    <li>
                        {{ $error }}
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
        action="{{ route('budgets.store') }}"
        class="space-y-5"
    >

        @csrf



        {{-- ===================================================== --}}
        {{-- GRUNDINFORMATIONEN --}}
        {{-- ===================================================== --}}

        <div
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

            <div class="p-6 sm:p-8">

                <div class="mb-6">

                    <h3 class="font-semibold text-slate-900 dark:text-white">
                        Budgetinformationen
                    </h3>

                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Grundlegende Angaben zu deinem Budget.
                    </p>

                </div>


                <div class="space-y-6">


                    {{-- NAME --}}

                    <div>

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
                            Name des Budgets
                        </label>

                        <input
                            id="name"
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            required
                            maxlength="255"
                            autofocus
                            placeholder="z. B. Lebensmittel"
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
                                px-4
                                py-3
                                text-slate-900
                                dark:text-white
                                placeholder-slate-400
                                focus:outline-none
                                focus:ring-2
                                focus:ring-emerald-500/20
                                focus:border-emerald-500
                            "
                        >

                    </div>



                    {{-- BETRAG --}}

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
                            Budgetbetrag
                        </label>

                        <div class="relative">

                            <input
                                id="amount"
                                type="number"
                                name="amount"
                                value="{{ old('amount') }}"
                                required
                                min="0.01"
                                step="0.01"
                                placeholder="400,00"
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
                                    px-4
                                    py-3
                                    pr-12
                                    text-slate-900
                                    dark:text-white
                                    placeholder-slate-400
                                    focus:outline-none
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
                                    dark:text-slate-500
                                    font-medium
                                "
                            >
                                €
                            </span>

                        </div>

                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">
                            Der Betrag gilt für den ausgewählten Zeitraum.
                        </p>

                    </div>

                </div>

            </div>

        </div>



        {{-- ========================================================= --}}
        {{-- ZEITRAUM --}}
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
                overflow-hidden
            "
        >

            <div class="p-6 sm:p-8">

                <div class="mb-6">

                    <h3 class="font-semibold text-slate-900 dark:text-white">
                        Zeitraum
                    </h3>

                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Lege fest, für welchen Zeitraum das Budget gilt.
                    </p>

                </div>


                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">


                    {{-- START --}}

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
                            Von
                        </label>

                        <input
                            id="start_date"
                            type="date"
                            name="start_date"
                            value="{{ old(
                                'start_date',
                                now()->startOfMonth()->format('Y-m-d')
                            ) }}"
                            required
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
                                px-4
                                py-3
                                text-slate-900
                                dark:text-white
                                focus:outline-none
                                focus:ring-2
                                focus:ring-emerald-500/20
                                focus:border-emerald-500
                            "
                        >

                    </div>



                    {{-- ENDE --}}

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
                            Bis
                        </label>

                        <input
                            id="end_date"
                            type="date"
                            name="end_date"
                            value="{{ old(
                                'end_date',
                                now()->endOfMonth()->format('Y-m-d')
                            ) }}"
                            required
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
                                px-4
                                py-3
                                text-slate-900
                                dark:text-white
                                focus:outline-none
                                focus:ring-2
                                focus:ring-emerald-500/20
                                focus:border-emerald-500
                            "
                        >

                    </div>



                    {{-- PERIODE --}}

                    <div>

                        <label
                            for="period"
                            class="
                                block
                                text-sm
                                font-medium
                                text-slate-700
                                dark:text-slate-300
                                mb-2
                            "
                        >
                            Wiederholung
                        </label>

                        <select
                            id="period"
                            name="period"
                            required
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
                                px-4
                                py-3
                                text-slate-900
                                dark:text-white
                                focus:outline-none
                                focus:ring-2
                                focus:ring-emerald-500/20
                                focus:border-emerald-500
                            "
                        >

                            <option
                                value="monthly"
                                @selected(old('period', 'monthly') === 'monthly')
                            >
                                Monatlich
                            </option>

                            <option
                                value="yearly"
                                @selected(old('period') === 'yearly')
                            >
                                Jährlich
                            </option>

                            <option
                                value="custom"
                                @selected(old('period') === 'custom')
                            >
                                Benutzerdefiniert
                            </option>

                        </select>

                    </div>

                </div>

            </div>

        </div>



        {{-- ========================================================= --}}
        {{-- KATEGORIEN --}}
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
                overflow-hidden
            "
        >

            <div class="p-6 sm:p-8">

                <div class="mb-6">

                    <h3 class="font-semibold text-slate-900 dark:text-white">
                        Kategorien
                    </h3>

                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Wähle die Kategorien aus, die zu diesem Budget gehören.
                    </p>

                </div>


                @php

                    $expenseCategories = $categories
                        ->whereIn('type', ['expense', 'both']);

                @endphp


                @if ($expenseCategories->isEmpty())

                    <div
                        class="
                            rounded-2xl
                            bg-amber-50
                            dark:bg-amber-950/40
                            border
                            border-amber-100
                            dark:border-amber-900
                            p-4
                            text-sm
                            text-amber-800
                            dark:text-amber-300
                        "
                    >
                        Du hast noch keine Ausgabenkategorien.
                    </div>

                @else

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                        @foreach ($expenseCategories as $category)

                            <label
                                class="
                                    flex
                                    items-center
                                    gap-3
                                    rounded-2xl
                                    border
                                    border-slate-200
                                    dark:border-slate-700
                                    bg-white
                                    dark:bg-slate-800
                                    p-4
                                    cursor-pointer
                                    hover:bg-slate-50
                                    dark:hover:bg-slate-700
                                    transition
                                "
                            >

                                <input
                                    type="checkbox"
                                    name="categories[]"
                                    value="{{ $category->id }}"
                                    @checked(
                                        in_array(
                                            $category->id,
                                            old('categories', [])
                                        )
                                    )
                                    class="
                                        w-5
                                        h-5
                                        rounded
                                        border-slate-300
                                        dark:border-slate-600
                                        text-emerald-600
                                        focus:ring-emerald-500
                                        flex-shrink-0
                                    "
                                >


                                <span
                                    class="
                                        w-9
                                        h-9
                                        rounded-xl
                                        bg-slate-100
                                        dark:bg-slate-700
                                        flex
                                        items-center
                                        justify-center
                                        text-lg
                                        flex-shrink-0
                                    "
                                >
                                    {{ $category->icon ?: '📁' }}
                                </span>


                                <span class="text-sm font-medium text-slate-700 dark:text-slate-200 min-w-0 truncate">
                                    {{ $category->name }}
                                </span>

                            </label>

                        @endforeach

                    </div>

                @endif

            </div>

        </div>



        {{-- ========================================================= --}}
        {{-- DARSTELLUNG --}}
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
                overflow-hidden
            "
        >

            <div class="p-6 sm:p-8">

                <div class="mb-6">

                    <h3 class="font-semibold text-slate-900 dark:text-white">
                        Darstellung
                    </h3>

                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Passe das Erscheinungsbild des Budgets an.
                    </p>

                </div>


                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">


                    {{-- ICON --}}

                    <div>

                        <label
                            for="icon"
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

                        <input
                            id="icon"
                            type="text"
                            name="icon"
                            value="{{ old('icon', '🎯') }}"
                            maxlength="255"
                            placeholder="🎯"
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
                                px-4
                                py-3
                                text-slate-900
                                dark:text-white
                                text-xl
                                placeholder-slate-400
                                focus:outline-none
                                focus:ring-2
                                focus:ring-emerald-500/20
                                focus:border-emerald-500
                            "
                        >

                    </div>



                    {{-- FARBE --}}

                    <div>

                        <label
                            for="color"
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
                                id="color"
                                type="color"
                                value="{{ old('color', '#f1f5f9') }}"
                                class="
                                    w-14
                                    h-12
                                    rounded-xl
                                    border
                                    border-slate-200
                                    dark:border-slate-700
                                    bg-white
                                    dark:bg-slate-800
                                    p-1
                                    cursor-pointer
                                    flex-shrink-0
                                "
                                oninput="document.getElementById('color-value').value = this.value"
                            >

                            <input
                                id="color-value"
                                type="text"
                                name="color"
                                value="{{ old('color', '#f1f5f9') }}"
                                maxlength="255"
                                placeholder="#f1f5f9"
                                class="
                                    box-border
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
                                    text-slate-900
                                    dark:text-white
                                    placeholder-slate-400
                                    focus:outline-none
                                    focus:ring-2
                                    focus:ring-emerald-500/20
                                    focus:border-emerald-500
                                "
                                oninput="
                                    const value = this.value.trim();
                                    if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
                                        document.getElementById('color').value = value;
                                    }
                                "
                            >

                        </div>

                    </div>

                </div>

            </div>

        </div>



        {{-- ========================================================= --}}
        {{-- STATUS --}}
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
            "
        >

            <label
                class="
                    flex
                    items-start
                    gap-3
                    rounded-2xl
                    bg-slate-50
                    dark:bg-slate-800/60
                    border
                    border-slate-100
                    dark:border-slate-700
                    p-4
                    cursor-pointer
                    transition
                    hover:bg-slate-100
                    dark:hover:bg-slate-800
                "
            >

                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    @checked(old('is_active', true))
                    class="
                        w-5
                        h-5
                        mt-0.5
                        rounded
                        border-slate-300
                        dark:border-slate-600
                        bg-white
                        dark:bg-slate-800
                        text-emerald-600
                        focus:ring-emerald-500
                        flex-shrink-0
                    "
                >

                <span class="min-w-0">

                    <span
                        class="
                            block
                            text-sm
                            font-medium
                            text-slate-900
                            dark:text-white
                        "
                    >
                        Budget aktiv
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
                        Aktive Budgets werden in der Übersicht berücksichtigt.
                    </span>

                </span>

            </label>

        </div>



        {{-- ========================================================= --}}
        {{-- BUTTONS --}}
        {{-- ========================================================= --}}

        <div
            class="
                flex
                flex-col-reverse
                sm:flex-row
                sm:justify-end
                gap-3
                pt-2
            "
        >

            <a
                href="{{ route('budgets.index') }}"
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
                    text-slate-600
                    dark:text-slate-300
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
                    px-5
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
                Budget erstellen
            </button>

        </div>

    </form>

</div>

@endsection