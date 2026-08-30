@extends('layouts.app')

@section('title', 'Budget erstellen – Finanzblick')

@section('eyebrow', 'Finanzen')

@section('page_title', 'Budget erstellen')

@section('content')

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- ZURÜCK --}}

    <a
        href="{{ route('budgets.index') }}"
        class="inline-flex items-center text-sm text-slate-500 hover:text-slate-900"
    >
        ← Budgets
    </a>


    {{-- HEADER --}}

    <div class="mt-5">

        <h2 class="text-3xl font-semibold tracking-tight text-slate-900">
            Neues Budget
        </h2>

        <p class="text-slate-500 mt-2">
            Lege fest, wie viel du für bestimmte Kategorien ausgeben möchtest.
        </p>

    </div>


    {{-- FEHLER --}}

    @if ($errors->any())

        <div
            class="
                mt-6
                rounded-2xl
                border
                border-red-100
                bg-red-50
                p-5
                text-red-700
            "
        >

            <p class="font-medium mb-2">
                Bitte überprüfe deine Eingaben.
            </p>

            <ul class="list-disc list-inside text-sm space-y-1">

                @foreach ($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif


    {{-- FORMULAR --}}

    <form
        method="POST"
        action="{{ route('budgets.store') }}"
        class="mt-8 space-y-5"
    >

        @csrf


        {{-- ===================================================== --}}
        {{-- NAME --}}
        {{-- ===================================================== --}}

        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6">

            <label
                for="name"
                class="block text-sm font-medium text-slate-700"
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
                placeholder="z. B. Lebensmittel"
                class="
                    mt-2
                    w-full
                    rounded-xl
                    border
                    border-slate-200
                    px-4
                    py-3
                    text-slate-900
                    placeholder:text-slate-400
                    focus:outline-none
                    focus:ring-2
                    focus:ring-slate-200
                "
            >

        </div>


        {{-- ===================================================== --}}
        {{-- BETRAG --}}
        {{-- ===================================================== --}}

        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6">

            <label
                for="amount"
                class="block text-sm font-medium text-slate-700"
            >
                Budgetbetrag
            </label>

            <div class="relative mt-2">

                <input
                    id="amount"
                    type="number"
                    name="amount"
                    value="{{ old('amount') }}"
                    required
                    min="0.01"
                    step="0.01"
                    placeholder="400.00"
                    class="
                        w-full
                        rounded-xl
                        border
                        border-slate-200
                        px-4
                        py-3
                        pr-12
                        text-slate-900
                        focus:outline-none
                        focus:ring-2
                        focus:ring-slate-200
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


        {{-- ===================================================== --}}
        {{-- ZEITRAUM --}}
        {{-- ===================================================== --}}

        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6">

            <h3 class="font-semibold text-slate-900">
                Zeitraum
            </h3>

            <p class="text-sm text-slate-500 mt-1">
                Für welchen Zeitraum soll das Budget gelten?
            </p>


            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-5">

                {{-- START --}}

                <div>

                    <label
                        for="start_date"
                        class="block text-sm font-medium text-slate-700"
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
                            mt-2
                            w-full
                            rounded-xl
                            border
                            border-slate-200
                            px-4
                            py-3
                            focus:outline-none
                            focus:ring-2
                            focus:ring-slate-200
                        "
                    >

                </div>


                {{-- ENDE --}}

                <div>

                    <label
                        for="end_date"
                        class="block text-sm font-medium text-slate-700"
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
                            mt-2
                            w-full
                            rounded-xl
                            border
                            border-slate-200
                            px-4
                            py-3
                            focus:outline-none
                            focus:ring-2
                            focus:ring-slate-200
                        "
                    >

                </div>


                {{-- PERIODE --}}

                <div>

                    <label
                        for="period"
                        class="block text-sm font-medium text-slate-700"
                    >
                        Wiederholung
                    </label>

                    <select
                        id="period"
                        name="period"
                        required
                        class="
                            mt-2
                            w-full
                            rounded-xl
                            border
                            border-slate-200
                            px-4
                            py-3
                            bg-white
                            focus:outline-none
                            focus:ring-2
                            focus:ring-slate-200
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


        {{-- ===================================================== --}}
        {{-- KATEGORIEN --}}
        {{-- ===================================================== --}}

        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6">

            <h3 class="font-semibold text-slate-900">
                Kategorien
            </h3>

            <p class="text-sm text-slate-500 mt-1">
                Wähle die Kategorien aus, die zu diesem Budget gehören.
            </p>


            @php
                $expenseCategories = $categories
                    ->whereIn('type', ['expense', 'both']);
            @endphp


            @if ($expenseCategories->isEmpty())

                <div
                    class="
                        mt-5
                        rounded-2xl
                        bg-amber-50
                        p-4
                        text-sm
                        text-amber-800
                    "
                >
                    Du hast noch keine Ausgabenkategorien.
                </div>

            @else

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-5">

                    @foreach ($expenseCategories as $category)

                        <label
                            class="
                                flex
                                items-center
                                gap-3
                                rounded-2xl
                                border
                                border-slate-200
                                p-4
                                cursor-pointer
                                hover:bg-slate-50
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
                                    w-4
                                    h-4
                                    rounded
                                    border-slate-300
                                "
                            >


                            <span class="text-xl">
                                {{ $category->icon ?: '📁' }}
                            </span>


                            <span class="text-sm font-medium text-slate-700">
                                {{ $category->name }}
                            </span>

                        </label>

                    @endforeach

                </div>

            @endif

        </div>


        {{-- ===================================================== --}}
        {{-- DARSTELLUNG --}}
        {{-- ===================================================== --}}

        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6">

            <h3 class="font-semibold text-slate-900">
                Darstellung
            </h3>


            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mt-5">

                {{-- ICON --}}

                <div>

                    <label
                        for="icon"
                        class="block text-sm font-medium text-slate-700"
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
                            mt-2
                            w-full
                            rounded-xl
                            border
                            border-slate-200
                            px-4
                            py-3
                            focus:outline-none
                            focus:ring-2
                            focus:ring-slate-200
                        "
                    >

                </div>


                {{-- FARBE --}}

                <div>

                    <label
                        for="color"
                        class="block text-sm font-medium text-slate-700"
                    >
                        Farbe
                    </label>

                    <input
                        id="color"
                        type="text"
                        name="color"
                        value="{{ old('color', '#f1f5f9') }}"
                        maxlength="255"
                        placeholder="#f1f5f9"
                        class="
                            mt-2
                            w-full
                            rounded-xl
                            border
                            border-slate-200
                            px-4
                            py-3
                            focus:outline-none
                            focus:ring-2
                            focus:ring-slate-200
                        "
                    >

                </div>

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- AKTIV --}}
        {{-- ===================================================== --}}

        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6">

            <label class="flex items-center gap-3 cursor-pointer">

                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    @checked(old('is_active', true))
                    class="w-4 h-4 rounded border-slate-300"
                >

                <div>

                    <p class="text-sm font-medium text-slate-700">
                        Budget aktiv
                    </p>

                    <p class="text-xs text-slate-400 mt-1">
                        Aktive Budgets werden in der Übersicht berücksichtigt.
                    </p>

                </div>

            </label>

        </div>


        {{-- ===================================================== --}}
        {{-- BUTTONS --}}
        {{-- ===================================================== --}}

        <div class="flex flex-col-reverse sm:flex-row gap-3 pt-2">

            <a
                href="{{ route('budgets.index') }}"
                class="
                    flex-1
                    rounded-xl
                    border
                    border-slate-200
                    bg-white
                    px-5
                    py-3
                    text-center
                    text-sm
                    font-medium
                    text-slate-700
                    hover:bg-slate-50
                    transition
                "
            >
                Abbrechen
            </a>


            <button
                type="submit"
                class="
                    flex-1
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
                Budget speichern
            </button>

        </div>

    </form>

</div>

@endsection