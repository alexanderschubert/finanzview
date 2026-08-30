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
                Kategorien
            </h2>

            <p class="text-slate-500 dark:text-slate-400 mt-2">
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

            Kategorie erstellen

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
                border
                border-emerald-100
                dark:border-emerald-900
                bg-emerald-50
                dark:bg-emerald-950/40
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
                border
                border-red-100
                dark:border-red-900
                bg-red-50
                dark:bg-red-950/40
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

                <div>

                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Ausgabenkategorien
                    </p>

                    <p class="text-3xl font-semibold text-red-600 dark:text-red-400 mt-2">
                        {{ $expenseCategories->count() }}
                    </p>

                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                        Für deine Ausgaben
                    </p>

                </div>

                <div
                    class="
                        w-11
                        h-11
                        rounded-2xl
                        bg-red-50
                        dark:bg-red-950/40
                        flex
                        items-center
                        justify-center
                        text-xl
                    "
                >
                    ↘
                </div>

            </div>

        </div>


        {{-- EINNAHMEN --}}

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

                <div>

                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Einnahmenkategorien
                    </p>

                    <p class="text-3xl font-semibold text-emerald-600 dark:text-emerald-400 mt-2">
                        {{ $incomeCategories->count() }}
                    </p>

                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                        Für deine Einnahmen
                    </p>

                </div>

                <div
                    class="
                        w-11
                        h-11
                        rounded-2xl
                        bg-emerald-50
                        dark:bg-emerald-950/40
                        flex
                        items-center
                        justify-center
                        text-xl
                    "
                >
                    ↗
                </div>

            </div>

        </div>


        {{-- AKTIV --}}

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

                <div>

                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Aktive Kategorien
                    </p>

                    <p class="text-3xl font-semibold text-slate-900 dark:text-white mt-2">
                        {{ $activeCategories->count() }}
                    </p>

                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                        Aktuell verwendbar
                    </p>

                </div>

                <div
                    class="
                        w-11
                        h-11
                        rounded-2xl
                        bg-slate-100
                        dark:bg-slate-800
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

        <div class="flex items-end justify-between gap-4 mb-4">

            <div>

                <p class="text-xs font-medium uppercase tracking-wider text-red-500 dark:text-red-400">
                    Ausgaben
                </p>

                <h3 class="text-xl font-semibold text-slate-900 dark:text-white mt-1">
                    Ausgabenkategorien
                </h3>

                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Kategorien für deine Ausgaben.
                </p>

            </div>

        </div>


        @if ($expenseCategories->isEmpty())

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
                    text-center
                "
            >

                <div
                    class="
                        w-14
                        h-14
                        mx-auto
                        rounded-2xl
                        bg-red-50
                        dark:bg-red-950/40
                        flex
                        items-center
                        justify-center
                        text-2xl
                    "
                >
                    ↘
                </div>

                <h4 class="font-semibold text-slate-900 dark:text-white mt-4">
                    Keine Ausgabenkategorien
                </h4>

                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Erstelle eine Kategorie für deine Ausgaben.
                </p>

                <a
                    href="{{ route('categories.create') }}"
                    class="
                        inline-flex
                        mt-5
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
                    + Kategorie erstellen
                </a>

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
                            group
                            bg-white
                            dark:bg-slate-900
                            rounded-3xl
                            border
                            border-slate-100
                            dark:border-slate-800
                            shadow-sm
                            p-5
                            hover:shadow-md
                            hover:-translate-y-0.5
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
                                    dark:bg-red-950/40
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
                                    Aktiv
                                </span>

                            @else

                                <span
                                    class="
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
                                    Inaktiv
                                </span>

                            @endif

                        </div>


                        <h4
                            class="
                                font-semibold
                                text-slate-900
                                dark:text-white
                                mt-5
                                truncate
                            "
                        >
                            {{ $category->name }}
                        </h4>


                        @if ($category->description)

                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 line-clamp-2">
                                {{ $category->description }}
                            </p>

                        @else

                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                                Ausgabenkategorie
                            </p>

                        @endif


                        <div
                            class="
                                flex
                                items-center
                                gap-4
                                mt-5
                                pt-4
                                border-t
                                border-slate-100
                                dark:border-slate-800
                            "
                        >

                            <a
                                href="{{ route('categories.edit', $category) }}"
                                class="
                                    text-xs
                                    font-medium
                                    text-slate-500
                                    dark:text-slate-400
                                    hover:text-slate-900
                                    dark:hover:text-white
                                    transition
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
                                        dark:text-red-400
                                        hover:text-red-700
                                        dark:hover:text-red-300
                                        transition
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

            <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                Einnahmen
            </p>

            <h3 class="text-xl font-semibold text-slate-900 dark:text-white mt-1">
                Einnahmenkategorien
            </h3>

            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Kategorien für deine Einnahmen.
            </p>

        </div>


        @if ($incomeCategories->isEmpty())

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
                    text-center
                "
            >

                <div
                    class="
                        w-14
                        h-14
                        mx-auto
                        rounded-2xl
                        bg-emerald-50
                        dark:bg-emerald-950/40
                        flex
                        items-center
                        justify-center
                        text-2xl
                    "
                >
                    ↗
                </div>

                <h4 class="font-semibold text-slate-900 dark:text-white mt-4">
                    Keine Einnahmenkategorien
                </h4>

                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Erstelle eine Kategorie für deine Einnahmen.
                </p>

                <a
                    href="{{ route('categories.create') }}"
                    class="
                        inline-flex
                        mt-5
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
                    + Kategorie erstellen
                </a>

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
                            group
                            bg-white
                            dark:bg-slate-900
                            rounded-3xl
                            border
                            border-slate-100
                            dark:border-slate-800
                            shadow-sm
                            p-5
                            hover:shadow-md
                            hover:-translate-y-0.5
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
                                    dark:bg-emerald-950/40
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
                                    Aktiv
                                </span>

                            @else

                                <span
                                    class="
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
                                    Inaktiv
                                </span>

                            @endif

                        </div>


                        <h4
                            class="
                                font-semibold
                                text-slate-900
                                dark:text-white
                                mt-5
                                truncate
                            "
                        >
                            {{ $category->name }}
                        </h4>


                        @if ($category->description)

                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 line-clamp-2">
                                {{ $category->description }}
                            </p>

                        @else

                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                                Einnahmenkategorie
                            </p>

                        @endif


                        <div
                            class="
                                flex
                                items-center
                                gap-4
                                mt-5
                                pt-4
                                border-t
                                border-slate-100
                                dark:border-slate-800
                            "
                        >

                            <a
                                href="{{ route('categories.edit', $category) }}"
                                class="
                                    text-xs
                                    font-medium
                                    text-slate-500
                                    dark:text-slate-400
                                    hover:text-slate-900
                                    dark:hover:text-white
                                    transition
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
                                        dark:text-red-400
                                        hover:text-red-700
                                        dark:hover:text-red-300
                                        transition
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

                <p class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    Beide
                </p>

                <h3 class="text-xl font-semibold text-slate-900 dark:text-white mt-1">
                    Einnahmen & Ausgaben
                </h3>

                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
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
                            group
                            bg-white
                            dark:bg-slate-900
                            rounded-3xl
                            border
                            border-slate-100
                            dark:border-slate-800
                            shadow-sm
                            p-5
                            hover:shadow-md
                            hover:-translate-y-0.5
                            transition
                        "
                    >

                        <div class="flex items-start justify-between gap-3">

                            <div
                                class="
                                    w-12
                                    h-12
                                    rounded-2xl
                                    bg-slate-100
                                    dark:bg-slate-800
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
                                    Aktiv
                                </span>

                            @else

                                <span
                                    class="
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
                                    Inaktiv
                                </span>

                            @endif

                        </div>


                        <h4
                            class="
                                font-semibold
                                text-slate-900
                                dark:text-white
                                mt-5
                                truncate
                            "
                        >
                            {{ $category->name }}
                        </h4>


                        @if ($category->description)

                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 line-clamp-2">
                                {{ $category->description }}
                            </p>

                        @else

                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                                Für Einnahmen und Ausgaben
                            </p>

                        @endif


                        <div
                            class="
                                flex
                                items-center
                                gap-4
                                mt-5
                                pt-4
                                border-t
                                border-slate-100
                                dark:border-slate-800
                            "
                        >

                            <a
                                href="{{ route('categories.edit', $category) }}"
                                class="
                                    text-xs
                                    font-medium
                                    text-slate-500
                                    dark:text-slate-400
                                    hover:text-slate-900
                                    dark:hover:text-white
                                    transition
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
                                        dark:text-red-400
                                        hover:text-red-700
                                        dark:hover:text-red-300
                                        transition
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