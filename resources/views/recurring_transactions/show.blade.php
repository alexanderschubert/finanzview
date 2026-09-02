@extends('layouts.app')

@section('title', 'Wiederkehrende Buchung – FinanzView')

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
            {{ $recurringTransaction->description }}
        </h2>

        <p class="text-slate-500 dark:text-slate-400 mt-2">
            Details und Einstellungen dieser wiederkehrenden Buchung.
        </p>

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
    {{-- HAUPTKARTE --}}
    {{-- ========================================================= --}}

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

        {{-- ===================================================== --}}
        {{-- KARTENHEADER --}}
        {{-- ===================================================== --}}

        <div
            class="
                p-6
                sm:p-8
                border-b
                border-slate-200
                dark:border-slate-800
            "
        >

            <div class="flex flex-col sm:flex-row sm:items-center gap-5">

                {{-- ICON --}}

                <div
                    class="
                        w-14
                        h-14
                        shrink-0
                        rounded-2xl
                        flex
                        items-center
                        justify-center
                        text-2xl
                        {{ $recurringTransaction->type === 'income'
                            ? 'bg-emerald-50 dark:bg-emerald-500/10'
                            : 'bg-red-50 dark:bg-red-500/10' }}
                    "
                >
                    {{ $recurringTransaction->type === 'income' ? '↗️' : '↘️' }}
                </div>


                {{-- TITEL + STATUS --}}

                <div class="min-w-0 flex-1">

                    <div class="flex flex-wrap items-center gap-2">

                        <h3
                            class="
                                text-xl
                                font-semibold
                                text-slate-900
                                dark:text-white
                            "
                        >
                            {{ $recurringTransaction->description }}
                        </h3>

                        @if($recurringTransaction->is_active)

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

                        {{ $recurringTransaction->type === 'income'
                            ? 'Regelmäßige Einnahme'
                            : 'Regelmäßige Ausgabe' }}

                    </p>

                </div>


                {{-- BETRAG --}}

                <div class="sm:text-right">

                    <p
                        class="
                            text-2xl
                            font-semibold
                            {{ $recurringTransaction->type === 'income'
                                ? 'text-emerald-600 dark:text-emerald-400'
                                : 'text-red-600 dark:text-red-400' }}
                        "
                    >
                        {{ $recurringTransaction->type === 'income' ? '+' : '-' }}
                        {{ number_format((float) $recurringTransaction->amount, 2, ',', '.') }}
                        €
                    </p>

                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                        {{ match($recurringTransaction->frequency) {
                            'weekly' => 'Wöchentlich',
                            'monthly' => 'Monatlich',
                            'quarterly' => 'Vierteljährlich',
                            'yearly' => 'Jährlich',
                            default => $recurringTransaction->frequency,
                        } }}
                    </p>

                </div>

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- DETAILS --}}
        {{-- ===================================================== --}}

        <div class="p-6 sm:p-8">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">


                {{-- KONTO --}}

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

                    <p class="text-xs text-slate-400 dark:text-slate-500">
                        Konto
                    </p>

                    <p
                        class="
                            text-sm
                            font-medium
                            text-slate-900
                            dark:text-white
                            mt-2
                        "
                    >
                        {{ $recurringTransaction->account?->icon ?: '🏦' }}
                        {{ $recurringTransaction->account?->name ?? 'Kein Konto' }}
                    </p>

                </div>


                {{-- KATEGORIE --}}

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

                    <p class="text-xs text-slate-400 dark:text-slate-500">
                        Kategorie
                    </p>

                    <p
                        class="
                            text-sm
                            font-medium
                            text-slate-900
                            dark:text-white
                            mt-2
                        "
                    >

                        @if($recurringTransaction->category)

                            {{ $recurringTransaction->category->icon ?: '📁' }}
                            {{ $recurringTransaction->category->name }}

                        @else

                            <span class="text-slate-400 dark:text-slate-500">
                                Keine Kategorie
                            </span>

                        @endif

                    </p>

                </div>


                {{-- BUCHUNGSART --}}

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

                    <p class="text-xs text-slate-400 dark:text-slate-500">
                        Buchungsart
                    </p>

                    <p
                        class="
                            text-sm
                            font-medium
                            mt-2
                            {{ $recurringTransaction->type === 'income'
                                ? 'text-emerald-600 dark:text-emerald-400'
                                : 'text-red-600 dark:text-red-400' }}
                        "
                    >
                        {{ $recurringTransaction->type === 'income'
                            ? '↗ Einnahme'
                            : '↘ Ausgabe' }}
                    </p>

                </div>


                {{-- INTERVALL --}}

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

                    <p class="text-xs text-slate-400 dark:text-slate-500">
                        Intervall
                    </p>

                    <p
                        class="
                            text-sm
                            font-medium
                            text-slate-900
                            dark:text-white
                            mt-2
                        "
                    >
                        {{ match($recurringTransaction->frequency) {
                            'weekly' => 'Wöchentlich',
                            'monthly' => 'Monatlich',
                            'quarterly' => 'Vierteljährlich',
                            'yearly' => 'Jährlich',
                            default => $recurringTransaction->frequency,
                        } }}
                    </p>

                </div>


                {{-- NÄCHSTE AUSFÜHRUNG --}}

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

                    <p class="text-xs text-slate-400 dark:text-slate-500">
                        Nächste Ausführung
                    </p>

                    <p
                        class="
                            text-sm
                            font-medium
                            text-slate-900
                            dark:text-white
                            mt-2
                        "
                    >
                        {{ $recurringTransaction->next_date?->format('d.m.Y') ?? '—' }}
                    </p>

                </div>


                {{-- ENDDATUM --}}

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

                    <p class="text-xs text-slate-400 dark:text-slate-500">
                        Enddatum
                    </p>

                    <p
                        class="
                            text-sm
                            font-medium
                            text-slate-900
                            dark:text-white
                            mt-2
                        "
                    >
                        {{ $recurringTransaction->end_date?->format('d.m.Y') ?? 'Unbegrenzt' }}
                    </p>

                </div>

            </div>

        </div>

    </section>


    {{-- ========================================================= --}}
    {{-- INFO --}}
    {{-- ========================================================= --}}

    <div
        class="
            mt-5
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

                <p
                    class="
                        text-sm
                        font-medium
                        text-slate-700
                        dark:text-slate-300
                    "
                >
                    Wiederkehrende Buchung
                </p>

                <p
                    class="
                        text-sm
                        text-slate-500
                        dark:text-slate-400
                        mt-1
                    "
                >
                    Diese Buchung wird entsprechend dem eingestellten
                    Intervall berücksichtigt. Die nächste Ausführung ist
                    für
                    <span class="font-medium text-slate-700 dark:text-slate-300">
                        {{ $recurringTransaction->next_date?->format('d.m.Y') ?? 'kein Datum' }}
                    </span>
                    vorgesehen.
                </p>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- AKTIONEN --}}
    {{-- ========================================================= --}}

    <div
        class="
            mt-5
            flex
            flex-col-reverse
            sm:flex-row
            sm:items-center
            sm:justify-between
            gap-3
        "
    >

        {{-- ZURÜCK --}}

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
            ← Zurück
        </a>


        <div class="flex flex-col sm:flex-row gap-3">


            {{-- LÖSCHEN --}}

            <form
                method="POST"
                action="{{ route('recurring-transactions.destroy', $recurringTransaction) }}"
                onsubmit="return confirm('Möchtest du diese wiederkehrende Buchung wirklich löschen?');"
            >

                @csrf
                @method('DELETE')

                <button
                    type="submit"
                    class="
                        w-full
                        sm:w-auto
                        inline-flex
                        items-center
                        justify-center
                        rounded-xl
                        border
                        border-red-200
                        dark:border-red-900
                        bg-white
                        dark:bg-slate-900
                        px-5
                        py-3
                        text-sm
                        font-medium
                        text-red-600
                        dark:text-red-400
                        hover:bg-red-50
                        dark:hover:bg-red-950/30
                        transition
                    "
                >
                    Löschen
                </button>

            </form>


            {{-- BEARBEITEN --}}

            <a
                href="{{ route('recurring-transactions.edit', $recurringTransaction) }}"
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
                Bearbeiten
            </a>

        </div>

    </div>

</div>

@endsection