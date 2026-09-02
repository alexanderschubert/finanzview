@extends('layouts.app')

@section('title', 'Wiederkehrend – FinanzView')

@section('eyebrow', 'Finanzen')

@section('page_title', 'Wiederkehrend')

@section('content')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <div class="mb-8">

        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-5">

            <div>

                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Automatische Buchungen
                </p>

                <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900 dark:text-white mt-1">
                    Wiederkehrend
                </h2>

                <p class="text-slate-500 dark:text-slate-400 mt-2">
                    Verwalte regelmäßige Einnahmen und Ausgaben.
                </p>

            </div>

            <a
                href="{{ route('recurring-transactions.create') }}"
                class="
                    inline-flex
                    items-center
                    justify-center
                    gap-2
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
                <span class="text-lg leading-none">+</span>
                Neue wiederkehrende Buchung
            </a>

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
    {{-- LEER --}}
    {{-- ========================================================= --}}

    @if($recurringTransactions->isEmpty())

        <section
            class="
                rounded-3xl
                bg-white
                dark:bg-slate-900
                border
                border-slate-200
                dark:border-slate-800
                p-8
                sm:p-12
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
                    dark:bg-slate-800
                    flex
                    items-center
                    justify-center
                    text-3xl
                "
            >
                🔄
            </div>

            <h3 class="text-xl font-semibold text-slate-900 dark:text-white mt-5">
                Noch keine wiederkehrenden Buchungen
            </h3>

            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 max-w-md mx-auto">
                Lege deine regelmäßigen Einnahmen und Ausgaben an,
                damit du sie nicht jedes Mal manuell erfassen musst.
            </p>

            <div class="mt-6">

                <a
                    href="{{ route('recurring-transactions.create') }}"
                    class="
                        inline-flex
                        items-center
                        justify-center
                        gap-2
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
                    <span class="text-lg leading-none">+</span>
                    Erste Buchung erstellen
                </a>

            </div>

        </section>

    @else


        {{-- ===================================================== --}}
        {{-- ÜBERSICHT --}}
        {{-- ===================================================== --}}

        <div class="space-y-4">

            @foreach($recurringTransactions as $recurring)

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

                    <div class="p-5 sm:p-6">

                        <div class="flex flex-col lg:flex-row lg:items-center gap-5">

                            {{-- ================================= --}}
                            {{-- ICON --}}
                            {{-- ================================= --}}

                            <div
                                class="
                                    w-12
                                    h-12
                                    shrink-0
                                    rounded-2xl
                                    flex
                                    items-center
                                    justify-center
                                    text-xl
                                    {{ $recurring->type === 'income'
                                        ? 'bg-emerald-50 dark:bg-emerald-500/10'
                                        : 'bg-red-50 dark:bg-red-500/10' }}
                                "
                            >
                                {{ $recurring->type === 'income' ? '↗️' : '↘️' }}
                            </div>


                            {{-- ================================= --}}
                            {{-- BESCHREIBUNG --}}
                            {{-- ================================= --}}

                            <div class="min-w-0 flex-1">

                                <div class="flex flex-wrap items-center gap-2">

                                    <h3 class="font-semibold text-slate-900 dark:text-white truncate">
                                        {{ $recurring->description }}
                                    </h3>

                                    @if($recurring->is_active)

                                        <span
                                            class="
                                                inline-flex
                                                items-center
                                                rounded-full
                                                bg-emerald-50
                                                dark:bg-emerald-500/10
                                                px-2.5
                                                py-1
                                                text-xs
                                                font-medium
                                                text-emerald-700
                                                dark:text-emerald-400
                                            "
                                        >
                                            Aktiv
                                        </span>

                                    @else

                                        <span
                                            class="
                                                inline-flex
                                                items-center
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
                                            Inaktiv
                                        </span>

                                    @endif

                                </div>

                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">

                                    {{ $recurring->account->name }}

                                    @if($recurring->category)
                                        · {{ $recurring->category->name }}
                                    @endif

                                </p>

                            </div>


                            {{-- ================================= --}}
                            {{-- BETRAG --}}
                            {{-- ================================= --}}

                            <div class="lg:text-right">

                                <p
                                    class="
                                        text-lg
                                        font-semibold
                                        {{ $recurring->type === 'income'
                                            ? 'text-emerald-600 dark:text-emerald-400'
                                            : 'text-red-600 dark:text-red-400' }}
                                    "
                                >

                                    {{ $recurring->type === 'income' ? '+' : '-' }}
                                    {{ number_format((float) $recurring->amount, 2, ',', '.') }}
                                    €

                                </p>

                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                                    {{ match($recurring->frequency) {
                                        'weekly' => 'Wöchentlich',
                                        'monthly' => 'Monatlich',
                                        'quarterly' => 'Vierteljährlich',
                                        'yearly' => 'Jährlich',
                                        default => $recurring->frequency,
                                    } }}
                                </p>

                            </div>


                            {{-- ================================= --}}
                            {{-- NÄCHSTE AUSFÜHRUNG --}}
                            {{-- ================================= --}}

                            <div class="lg:min-w-32">

                                <p class="text-xs text-slate-400 dark:text-slate-500">
                                    Nächste Ausführung
                                </p>

                                <p class="text-sm font-medium text-slate-700 dark:text-slate-200 mt-1">
                                    {{ $recurring->next_date?->format('d.m.Y') }}
                                </p>

                            </div>


                            {{-- ================================= --}}
                            {{-- AKTIONEN --}}
                            {{-- ================================= --}}

                            <div class="flex items-center gap-2">

                                <a
                                    href="{{ route('recurring-transactions.show', $recurring) }}"
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
                                        px-4
                                        py-2.5
                                        text-sm
                                        font-medium
                                        text-slate-700
                                        dark:text-slate-200
                                        hover:bg-slate-100
                                        dark:hover:bg-slate-700
                                        transition
                                    "
                                >
                                    Anzeigen
                                </a>

                                <a
                                    href="{{ route('recurring-transactions.edit', $recurring) }}"
                                    class="
                                        inline-flex
                                        items-center
                                        justify-center
                                        rounded-xl
                                        bg-slate-950
                                        dark:bg-white
                                        px-4
                                        py-2.5
                                        text-sm
                                        font-medium
                                        text-white
                                        dark:text-slate-950
                                        hover:bg-slate-800
                                        dark:hover:bg-slate-200
                                        transition
                                    "
                                >
                                    Bearbeiten
                                </a>

                            </div>

                        </div>

                    </div>

                </section>

            @endforeach

        </div>


        {{-- ===================================================== --}}
        {{-- ZUSATZINFO --}}
        {{-- ===================================================== --}}

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

                <div class="text-xl">
                    ℹ️
                </div>

                <div>

                    <p class="text-sm font-medium text-slate-700 dark:text-slate-300">
                        Wiederkehrende Buchungen
                    </p>

                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Diese Übersicht verwaltet deine regelmäßigen Einnahmen und Ausgaben.
                        Die nächste Ausführung kannst du jederzeit bearbeiten.
                    </p>

                </div>

            </div>

        </div>

    @endif

</div>

@endsection