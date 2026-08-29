<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Kredite – Finanzblick</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white">

    <div class="min-h-screen">

        {{-- =========================================================
             HEADER
        ========================================================== --}}

        <header class="border-b border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/80 backdrop-blur">

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                <div class="h-20 flex items-center justify-between">

                    <div class="flex items-center gap-4">

                        <a
                            href="{{ route('dashboard') }}"
                            class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center hover:bg-slate-200 dark:hover:bg-slate-700 transition"
                        >
                            ←
                        </a>

                        <div>
                            <h1 class="text-2xl font-semibold">
                                Kredite
                            </h1>

                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                Deine laufenden Finanzierungen im Überblick
                            </p>
                        </div>

                    </div>

                    <a
                        href="{{ route('loans.create') }}"
                        class="
                            inline-flex
                            items-center
                            gap-2
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
                        <span class="text-lg leading-none">+</span>
                        Kredit hinzufügen
                    </a>

                </div>

            </div>

        </header>


        {{-- =========================================================
             CONTENT
        ========================================================== --}}

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            {{-- =====================================================
                 ÜBERSICHT
            ====================================================== --}}

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">

                {{-- Restschuld --}}

                <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5">

                    <div class="flex items-center justify-between mb-4">

                        <div class="text-sm text-slate-500 dark:text-slate-400">
                            Restschuld
                        </div>

                        <div class="w-10 h-10 rounded-xl bg-red-50 dark:bg-red-500/10 flex items-center justify-center text-lg">
                            💳
                        </div>

                    </div>

                    <div class="text-2xl font-semibold">
                        {{ number_format($totalRemaining, 2, ',', '.') }} €
                    </div>

                </div>


                {{-- Getilgt --}}

                <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5">

                    <div class="flex items-center justify-between mb-4">

                        <div class="text-sm text-slate-500 dark:text-slate-400">
                            Bereits getilgt
                        </div>

                        <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center text-lg">
                            ✓
                        </div>

                    </div>

                    <div class="text-2xl font-semibold">
                        {{ number_format($totalPaid, 2, ',', '.') }} €
                    </div>

                    <div class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                        {{ number_format($overallProgress, 1, ',', '.') }} %
                    </div>

                </div>


                {{-- Monatliche Belastung --}}

                <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5">

                    <div class="flex items-center justify-between mb-4">

                        <div class="text-sm text-slate-500 dark:text-slate-400">
                            Monatliche Raten
                        </div>

                        <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center text-lg">
                            📅
                        </div>

                    </div>

                    <div class="text-2xl font-semibold">
                        {{ number_format($monthlyInstallments, 2, ',', '.') }} €
                    </div>

                    <div class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                        aktive Kredite
                    </div>

                </div>


                {{-- Anzahl Kredite --}}

                <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5">

                    <div class="flex items-center justify-between mb-4">

                        <div class="text-sm text-slate-500 dark:text-slate-400">
                            Kredite
                        </div>

                        <div class="w-10 h-10 rounded-xl bg-violet-50 dark:bg-violet-500/10 flex items-center justify-center text-lg">
                            📊
                        </div>

                    </div>

                    <div class="text-2xl font-semibold">
                        {{ $loans->count() }}
                    </div>

                    <div class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                        {{ $loans->where('is_active', true)->count() }} aktiv
                    </div>

                </div>

            </div>


            {{-- =====================================================
                 GESAMTFORTSCHRITT
            ====================================================== --}}

            @if($loans->count() > 0)

                <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 mb-8">

                    <div class="flex items-center justify-between mb-3">

                        <div>

                            <h2 class="font-semibold">
                                Gesamtfortschritt
                            </h2>

                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                                Fortschritt über alle Kredite
                            </p>

                        </div>

                        <div class="text-lg font-semibold">
                            {{ number_format($overallProgress, 1, ',', '.') }} %
                        </div>

                    </div>

                    <div class="h-3 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">

                        <div
                            class="h-full rounded-full bg-emerald-500 transition-all duration-500"
                            style="width: {{ min(100, max(0, $overallProgress)) }}%"
                        ></div>

                    </div>

                    <div class="flex justify-between mt-3 text-xs text-slate-500 dark:text-slate-400">

                        <span>
                            {{ number_format($totalPaid, 2, ',', '.') }} € getilgt
                        </span>

                        <span>
                            {{ number_format($totalPrincipal, 2, ',', '.') }} € ursprünglich
                        </span>

                    </div>

                </div>

            @endif


            {{-- =====================================================
                 KREDITLISTE
            ====================================================== --}}

            @if($loans->count() > 0)

                <div class="flex items-center justify-between mb-4">

                    <div>

                        <h2 class="text-xl font-semibold">
                            Deine Kredite
                        </h2>

                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Alle Finanzierungen und laufenden Raten
                        </p>

                    </div>

                </div>


                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">

                    @foreach($loans as $loan)

                        @php
                            $progress = $loan->progress;
                            $remaining = $loan->remaining_amount;

                            $icon = $loan->creditor_icon ?: '💳';
                            $creditor = $loan->creditor_name ?: 'Kredit';
                            $color = $loan->creditor_color ?: '#10b981';
                        @endphp


                        <a
                            href="{{ route('loans.show', $loan) }}"
                            class="
                                group
                                rounded-2xl
                                bg-white
                                dark:bg-slate-900
                                border
                                border-slate-200
                                dark:border-slate-800
                                p-6
                                hover:shadow-lg
                                hover:-translate-y-0.5
                                transition
                            "
                        >

                            {{-- Kopf --}}

                            <div class="flex items-start justify-between">

                                <div class="flex items-center gap-3">

                                    <div
                                        class="w-12 h-12 rounded-2xl flex items-center justify-center text-2xl shrink-0"
                                        style="background-color: {{ $color }}20;"
                                    >
                                        {{ $icon }}
                                    </div>

                                    <div class="min-w-0">

                                        <h3 class="font-semibold truncate">
                                            {{ $loan->name }}
                                        </h3>

                                        <p class="text-sm text-slate-500 dark:text-slate-400 truncate">
                                            {{ $creditor }}
                                        </p>

                                    </div>

                                </div>


                                @if($loan->is_active)

                                    <span class="shrink-0 rounded-full bg-emerald-50 dark:bg-emerald-500/10 px-2.5 py-1 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                                        Aktiv
                                    </span>

                                @else

                                    <span class="shrink-0 rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-xs font-medium text-slate-500">
                                        Inaktiv
                                    </span>

                                @endif

                            </div>


                            {{-- Restschuld --}}

                            <div class="mt-6">

                                <div class="flex items-end justify-between">

                                    <div>

                                        <p class="text-xs text-slate-500 dark:text-slate-400">
                                            Restschuld
                                        </p>

                                        <p class="text-2xl font-semibold mt-1">
                                            {{ number_format($remaining, 2, ',', '.') }} €
                                        </p>

                                    </div>

                                    <div class="text-sm font-medium text-slate-500 dark:text-slate-400">
                                        {{ number_format($progress, 1, ',', '.') }} %
                                    </div>

                                </div>


                                {{-- Fortschrittsbalken --}}

                                <div class="mt-3 h-2.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">

                                    <div
                                        class="h-full rounded-full transition-all duration-500"
                                        style="
                                            width: {{ min(100, max(0, $progress)) }}%;
                                            background-color: {{ $color }};
                                        "
                                    ></div>

                                </div>

                            </div>


                            {{-- Informationen --}}

                            <div class="grid grid-cols-2 gap-4 mt-6 pt-5 border-t border-slate-100 dark:border-slate-800">

                                <div>

                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        Monatliche Rate
                                    </p>

                                    <p class="font-medium mt-1">
                                        {{ number_format($loan->installment_amount, 2, ',', '.') }} €
                                    </p>

                                </div>


                                <div>

                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        Noch offen
                                    </p>

                                    <p class="font-medium mt-1">
                                        @if($loan->remaining_installments !== null)
                                            {{ $loan->remaining_installments }} Raten
                                        @else
                                            –
                                        @endif
                                    </p>

                                </div>

                            </div>


                            {{-- Laufzeit --}}

                            @if($loan->end_date)

                                <div class="mt-4 flex items-center justify-between text-sm">

                                    <span class="text-slate-500 dark:text-slate-400">
                                        Voraussichtliches Ende
                                    </span>

                                    <span class="font-medium">
                                        {{ $loan->end_date->format('m/Y') }}
                                    </span>

                                </div>

                            @endif


                            {{-- Footer --}}

                            <div class="mt-5 flex items-center justify-between text-sm">

                                <span class="text-slate-500 dark:text-slate-400">
                                    Details anzeigen
                                </span>

                                <span class="text-lg group-hover:translate-x-1 transition-transform">
                                    →
                                </span>

                            </div>

                        </a>

                    @endforeach

                </div>

            @else

                {{-- =================================================
                     KEINE KREDITE
                ================================================== --}}

                <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-10 text-center">

                    <div class="w-16 h-16 mx-auto rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-3xl">
                        💳
                    </div>

                    <h2 class="text-xl font-semibold mt-5">
                        Noch keine Kredite
                    </h2>

                    <p class="text-slate-500 dark:text-slate-400 mt-2 max-w-md mx-auto">
                        Lege deinen ersten Kredit oder eine Finanzierung an,
                        um Restschuld, Raten und Tilgungsfortschritt im Blick
                        zu behalten.
                    </p>

                    <a
                        href="{{ route('loans.create') }}"
                        class="
                            inline-flex
                            items-center
                            gap-2
                            mt-6
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
                        Ersten Kredit anlegen
                    </a>

                </div>

            @endif

        </main>

    </div>

</body>
</html>