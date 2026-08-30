@extends('layouts.app')

@section('title', 'Budgets – Finanzblick')
@section('eyebrow', 'Finanzplanung')
@section('page_title', 'Budgets')

@section('content')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-5">

        <div>

            <p class="text-sm text-slate-500">
                Finanzplanung
            </p>

            <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900 mt-1">
                Deine Budgets
            </h2>

            <p class="text-slate-500 mt-2">
                Plane deine Ausgaben und behalte deine Ziele im Blick.
            </p>

        </div>


        <a
            href="{{ route('budgets.create') }}"
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
            + Budget erstellen
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
                border
                border-emerald-100
                bg-emerald-50
                px-5
                py-4
                text-sm
                text-emerald-700
            "
        >
            {{ session('success') }}
        </div>

    @endif


    {{-- ========================================================= --}}
    {{-- BUDGETÜBERSICHT --}}
    {{-- ========================================================= --}}

    @if ($budgets->isEmpty())

        <div
            class="
                mt-8
                bg-white
                rounded-3xl
                border
                border-slate-100
                shadow-sm
                p-10
                text-center
            "
        >

            <div class="text-5xl">
                🎯
            </div>

            <h3 class="font-semibold text-slate-900 mt-4">
                Noch keine Budgets
            </h3>

            <p class="text-sm text-slate-500 mt-2">
                Erstelle dein erstes Budget, um deine Ausgaben zu planen.
            </p>

            <a
                href="{{ route('budgets.create') }}"
                class="
                    inline-flex
                    mt-5
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
                Erstes Budget erstellen
            </a>

        </div>

    @else


        {{-- ===================================================== --}}
        {{-- BUDGET KARTEN --}}
        {{-- ===================================================== --}}

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 mt-8">

            @foreach ($budgets as $budget)

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

                    {{-- ================================================= --}}
                    {{-- KARTENINHALT --}}
                    {{-- ================================================= --}}

                    <div class="p-6">


                        {{-- HEADER --}}

                        <div class="flex items-start justify-between gap-4">

                            <div class="flex items-center gap-4 min-w-0">

                                <div
                                    class="
                                        w-12
                                        h-12
                                        rounded-2xl
                                        flex
                                        items-center
                                        justify-center
                                        text-xl
                                        flex-shrink-0
                                    "
                                    style="
                                        background-color:
                                        {{ $budget->color ?: '#f1f5f9' }}
                                    "
                                >
                                    {{ $budget->icon ?: '🎯' }}
                                </div>


                                <div class="min-w-0">

                                    <h3 class="font-semibold text-slate-900 truncate">
                                        {{ $budget->name }}
                                    </h3>

                                    <p class="text-xs text-slate-400 mt-1">
                                        {{ ucfirst($budget->period) }}
                                    </p>

                                </div>

                            </div>


                            @if ($budget->is_active)

                                <span
                                    class="
                                        flex-shrink-0
                                        rounded-full
                                        bg-emerald-50
                                        px-2.5
                                        py-1
                                        text-xs
                                        font-medium
                                        text-emerald-700
                                    "
                                >
                                    Aktiv
                                </span>

                            @else

                                <span
                                    class="
                                        flex-shrink-0
                                        rounded-full
                                        bg-slate-100
                                        px-2.5
                                        py-1
                                        text-xs
                                        font-medium
                                        text-slate-500
                                    "
                                >
                                    Inaktiv
                                </span>

                            @endif

                        </div>


                        {{-- ZEITRAUM --}}

                        <div class="mt-5">

                            <p class="text-xs text-slate-400">
                                Zeitraum
                            </p>

                            <p class="text-sm text-slate-600 mt-1">

                                {{ $budget->start_date->format('d.m.Y') }}

                                –

                                {{ $budget->end_date->format('d.m.Y') }}

                            </p>

                        </div>


                        {{-- BUDGETBETRAG --}}

                        <div class="mt-6">

                            <p class="text-xs text-slate-400">
                                Budget
                            </p>

                            <p class="text-3xl font-semibold text-slate-900 mt-1">

                                {{ number_format(
                                    $budget->amount,
                                    2,
                                    ',',
                                    '.'
                                ) }}

                                €

                            </p>

                        </div>


                        {{-- VERBRAUCH --}}

                        <div class="mt-6">

                            <div class="flex items-center justify-between">

                                <span class="text-sm text-slate-500">
                                    Ausgegeben
                                </span>

                                <span
                                    class="
                                        text-sm
                                        font-semibold
                                        {{ $budget->calculated_exceeded
                                            ? 'text-red-600'
                                            : 'text-slate-900' }}
                                    "
                                >

                                    {{ number_format(
                                        $budget->calculated_spent,
                                        2,
                                        ',',
                                        '.'
                                    ) }}

                                    €

                                </span>

                            </div>


                            {{-- PROGRESSBAR --}}

                            <div
                                class="
                                    h-3
                                    bg-slate-100
                                    rounded-full
                                    overflow-hidden
                                    mt-3
                                "
                            >

                                <div
                                    class="
                                        h-full
                                        rounded-full
                                        transition-all
                                        {{ $budget->calculated_exceeded
                                            ? 'bg-red-500'
                                            : (
                                                $budget->calculated_percentage >= 80
                                                    ? 'bg-amber-500'
                                                    : 'bg-emerald-500'
                                            ) }}
                                    "
                                    style="
                                        width:
                                        {{ min(
                                            $budget->calculated_percentage,
                                            100
                                        ) }}%
                                    "
                                ></div>

                            </div>


                            {{-- PROZENT --}}

                            <div class="flex items-center justify-between mt-2">

                                <span class="text-xs text-slate-400">

                                    {{ number_format(
                                        $budget->calculated_percentage,
                                        1,
                                        ',',
                                        '.'
                                    ) }} %

                                </span>


                                @if ($budget->calculated_remaining >= 0)

                                    <span class="text-xs text-emerald-600">

                                        Noch

                                        {{ number_format(
                                            $budget->calculated_remaining,
                                            2,
                                            ',',
                                            '.'
                                        ) }}

                                        €

                                    </span>

                                @else

                                    <span class="text-xs font-medium text-red-600">

                                        {{ number_format(
                                            abs(
                                                $budget->calculated_remaining
                                            ),
                                            2,
                                            ',',
                                            '.'
                                        ) }}

                                        €

                                        über Budget

                                    </span>

                                @endif

                            </div>

                        </div>


                        {{-- KATEGORIEN --}}

                        <div class="mt-6">

                            <p class="text-xs text-slate-400 mb-2">
                                Kategorien
                            </p>


                            @if ($budget->categories->isEmpty())

                                <span class="text-sm text-slate-400">
                                    Keine Kategorien zugeordnet
                                </span>

                            @else

                                <div class="flex flex-wrap gap-2">

                                    @foreach ($budget->categories as $category)

                                        <span
                                            class="
                                                inline-flex
                                                items-center
                                                gap-1.5
                                                rounded-full
                                                bg-slate-100
                                                px-2.5
                                                py-1
                                                text-xs
                                                text-slate-600
                                            "
                                        >

                                            {{ $category->icon ?: '📁' }}

                                            {{ $category->name }}

                                        </span>

                                    @endforeach

                                </div>

                            @endif

                        </div>

                    </div>


                    {{-- ================================================= --}}
                    {{-- AKTIONEN --}}
                    {{-- ================================================= --}}

                    <div
                        class="
                            border-t
                            border-slate-100
                            px-6
                            py-4
                            flex
                            items-center
                            justify-between
                        "
                    >

                        <a
                            href="{{ route('budgets.show', $budget) }}"
                            class="
                                text-sm
                                font-medium
                                text-slate-600
                                hover:text-slate-900
                            "
                        >
                            Details →
                        </a>


                        <div class="flex items-center gap-4">

                            <a
                                href="{{ route('budgets.edit', $budget) }}"
                                class="
                                    text-sm
                                    text-slate-500
                                    hover:text-slate-900
                                "
                            >
                                Bearbeiten
                            </a>


                            <form
                                method="POST"
                                action="{{ route('budgets.destroy', $budget) }}"
                                onsubmit="return confirm('Möchtest du dieses Budget wirklich löschen?');"
                            >

                                @csrf

                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="
                                        text-sm
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

</div>

@endsection