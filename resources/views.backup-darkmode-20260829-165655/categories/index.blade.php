@extends('layouts.app')

@section('title', 'Kategorien – Finanzblick')

@section('eyebrow', 'Finanzverwaltung')

@section('page_title', 'Kategorien')


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
                Kategorien
            </h2>

            <p class="text-slate-500 mt-1">
                Organisiere deine Einnahmen und Ausgaben.
            </p>

        </div>


        <a
            href="{{ route('categories.create') }}"
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
            + Kategorie
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
    {{-- ÜBERSICHT --}}
    {{-- ========================================================= --}}

    @php

        $incomeCategories = $categories->where('type', 'income');

        $expenseCategories = $categories->where('type', 'expense');

        $bothCategories = $categories->where('type', 'both');

        $activeCategories = $categories->where('is_active', true);

    @endphp


    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-8">


        {{-- AUSGABEN --}}

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
                        Ausgaben
                    </p>

                    <p class="text-3xl font-semibold text-red-600 mt-2">
                        {{ $expenseCategories->count() }}
                    </p>

                </div>

                <div
                    class="
                        w-11
                        h-11
                        rounded-xl
                        bg-red-50
                        flex
                        items-center
                        justify-center
                        text-xl
                    "
                >
                    ↘️
                </div>

            </div>

        </div>



        {{-- EINNAHMEN --}}

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
                        Einnahmen
                    </p>

                    <p class="text-3xl font-semibold text-emerald-600 mt-2">
                        {{ $incomeCategories->count() }}
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
                    ↗️
                </div>

            </div>

        </div>



        {{-- AKTIV --}}

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
                        Aktive Kategorien
                    </p>

                    <p class="text-3xl font-semibold text-slate-900 mt-2">
                        {{ $activeCategories->count() }}
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
                    🗂️
                </div>

            </div>

        </div>

    </div>



    {{-- ========================================================= --}}
    {{-- AUSGABEN --}}
    {{-- ========================================================= --}}

    <div class="mt-8">

        <div class="flex items-center justify-between mb-4">

            <div>

                <h2 class="text-lg font-semibold text-slate-900">
                    Ausgaben
                </h2>

                <p class="text-sm text-slate-500 mt-1">
                    Kategorien für deine Ausgaben
                </p>

            </div>

        </div>


        @if ($expenseCategories->isEmpty())

            <div
                class="
                    bg-white
                    rounded-2xl
                    border
                    border-slate-100
                    p-8
                    text-center
                "
            >

                <p class="text-sm text-slate-500">
                    Keine Ausgabenkategorien vorhanden.
                </p>

            </div>

        @else

            <div
                class="
                    grid
                    grid-cols-1
                    sm:grid-cols-2
                    lg:grid-cols-3
                    xl:grid-cols-4
                    gap-4
                "
            >

                @foreach ($expenseCategories as $category)

                    <div
                        class="
                            bg-white
                            rounded-2xl
                            border
                            border-slate-100
                            shadow-sm
                            p-5
                            hover:shadow-md
                            transition
                        "
                    >

                        <div class="flex items-start justify-between gap-3">

                            <div
                                class="
                                    w-12
                                    h-12
                                    rounded-2xl
                                    bg-red-50
                                    flex
                                    items-center
                                    justify-center
                                    text-2xl
                                    flex-shrink-0
                                "
                            >
                                {{ $category->icon ?: '📁' }}
                            </div>


                            @if ($category->is_active)

                                <span
                                    class="
                                        text-[11px]
                                        font-medium
                                        text-emerald-700
                                        bg-emerald-50
                                        px-2
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
                                        px-2
                                        py-1
                                        rounded-full
                                    "
                                >
                                    Inaktiv
                                </span>

                            @endif

                        </div>


                        <h3 class="font-medium text-slate-900 mt-4 truncate">
                            {{ $category->name }}
                        </h3>


                        @if ($category->description)

                            <p class="text-xs text-slate-400 mt-1 line-clamp-2">
                                {{ $category->description }}
                            </p>

                        @else

                            <p class="text-xs text-slate-400 mt-1">
                                Ausgabenkategorie
                            </p>

                        @endif


                        <div
                            class="
                                flex
                                items-center
                                gap-3
                                mt-5
                                pt-4
                                border-t
                                border-slate-100
                            "
                        >

                            <a
                                href="{{ route('categories.edit', $category) }}"
                                class="
                                    text-xs
                                    font-medium
                                    text-slate-500
                                    hover:text-slate-900
                                "
                            >
                                Bearbeiten
                            </a>


                            <form
                                method="POST"
                                action="{{ route('categories.destroy', $category) }}"
                                onsubmit="return confirm('Möchtest du diese Kategorie wirklich löschen?');"
                            >

                                @csrf

                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="
                                        text-xs
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
    {{-- EINNAHMEN --}}
    {{-- ========================================================= --}}

    <div class="mt-10">

        <div class="mb-4">

            <h2 class="text-lg font-semibold text-slate-900">
                Einnahmen
            </h2>

            <p class="text-sm text-slate-500 mt-1">
                Kategorien für deine Einnahmen
            </p>

        </div>


        @if ($incomeCategories->isEmpty())

            <div
                class="
                    bg-white
                    rounded-2xl
                    border
                    border-slate-100
                    p-8
                    text-center
                "
            >

                <p class="text-sm text-slate-500">
                    Keine Einnahmenkategorien vorhanden.
                </p>

            </div>

        @else

            <div
                class="
                    grid
                    grid-cols-1
                    sm:grid-cols-2
                    lg:grid-cols-3
                    xl:grid-cols-4
                    gap-4
                "
            >

                @foreach ($incomeCategories as $category)

                    <div
                        class="
                            bg-white
                            rounded-2xl
                            border
                            border-slate-100
                            shadow-sm
                            p-5
                            hover:shadow-md
                            transition
                        "
                    >

                        <div class="flex items-start justify-between gap-3">

                            <div
                                class="
                                    w-12
                                    h-12
                                    rounded-2xl
                                    bg-emerald-50
                                    flex
                                    items-center
                                    justify-center
                                    text-2xl
                                "
                            >
                                {{ $category->icon ?: '📁' }}
                            </div>


                            @if ($category->is_active)

                                <span
                                    class="
                                        text-[11px]
                                        font-medium
                                        text-emerald-700
                                        bg-emerald-50
                                        px-2
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
                                        px-2
                                        py-1
                                        rounded-full
                                    "
                                >
                                    Inaktiv
                                </span>

                            @endif

                        </div>


                        <h3 class="font-medium text-slate-900 mt-4 truncate">
                            {{ $category->name }}
                        </h3>


                        @if ($category->description)

                            <p class="text-xs text-slate-400 mt-1 line-clamp-2">
                                {{ $category->description }}
                            </p>

                        @else

                            <p class="text-xs text-slate-400 mt-1">
                                Einnahmenkategorie
                            </p>

                        @endif


                        <div
                            class="
                                flex
                                items-center
                                gap-3
                                mt-5
                                pt-4
                                border-t
                                border-slate-100
                            "
                        >

                            <a
                                href="{{ route('categories.edit', $category) }}"
                                class="
                                    text-xs
                                    font-medium
                                    text-slate-500
                                    hover:text-slate-900
                                "
                            >
                                Bearbeiten
                            </a>


                            <form
                                method="POST"
                                action="{{ route('categories.destroy', $category) }}"
                                onsubmit="return confirm('Möchtest du diese Kategorie wirklich löschen?');"
                            >

                                @csrf

                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="
                                        text-xs
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
    {{-- BEIDE --}}
    {{-- ========================================================= --}}

    @if ($bothCategories->isNotEmpty())

        <div class="mt-10 pb-8">

            <div class="mb-4">

                <h2 class="text-lg font-semibold text-slate-900">
                    Einnahmen & Ausgaben
                </h2>

                <p class="text-sm text-slate-500 mt-1">
                    Kategorien, die für beide Buchungsarten verwendet werden können.
                </p>

            </div>


            <div
                class="
                    grid
                    grid-cols-1
                    sm:grid-cols-2
                    lg:grid-cols-3
                    xl:grid-cols-4
                    gap-4
                "
            >

                @foreach ($bothCategories as $category)

                    <div
                        class="
                            bg-white
                            rounded-2xl
                            border
                            border-slate-100
                            shadow-sm
                            p-5
                            hover:shadow-md
                            transition
                        "
                    >

                        <div class="flex items-start justify-between">

                            <div
                                class="
                                    w-12
                                    h-12
                                    rounded-2xl
                                    bg-slate-100
                                    flex
                                    items-center
                                    justify-center
                                    text-2xl
                                "
                            >
                                {{ $category->icon ?: '📁' }}
                            </div>


                            <span
                                class="
                                    text-[11px]
                                    font-medium
                                    text-slate-600
                                    bg-slate-100
                                    px-2
                                    py-1
                                    rounded-full
                                "
                            >
                                Beide
                            </span>

                        </div>


                        <h3 class="font-medium text-slate-900 mt-4">
                            {{ $category->name }}
                        </h3>


                        <div
                            class="
                                flex
                                items-center
                                gap-3
                                mt-5
                                pt-4
                                border-t
                                border-slate-100
                            "
                        >

                            <a
                                href="{{ route('categories.edit', $category) }}"
                                class="
                                    text-xs
                                    font-medium
                                    text-slate-500
                                    hover:text-slate-900
                                "
                            >
                                Bearbeiten
                            </a>


                            <form
                                method="POST"
                                action="{{ route('categories.destroy', $category) }}"
                                onsubmit="return confirm('Möchtest du diese Kategorie wirklich löschen?');"
                            >

                                @csrf

                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="
                                        text-xs
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

        </div>

    @endif


</div>

@endsection