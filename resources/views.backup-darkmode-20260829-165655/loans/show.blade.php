<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $loan->name }} – Finanzblick</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white">

<div class="min-h-screen">

    {{-- =========================================================
         HEADER
    ========================================================== --}}

    <header class="border-b border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/80 backdrop-blur">

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="min-h-20 py-4 flex items-center justify-between gap-4">

                <div class="flex items-center gap-4 min-w-0">

                    <a
                        href="{{ route('loans.index') }}"
                        class="w-10 h-10 shrink-0 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center hover:bg-slate-200 dark:hover:bg-slate-700 transition"
                    >
                        ←
                    </a>

                    <div class="min-w-0">

                        <div class="flex items-center gap-3">

                            <div
                                class="w-11 h-11 shrink-0 rounded-2xl flex items-center justify-center text-2xl"
                                style="background-color: {{ $loan->creditor_color ?: '#10b981' }}20;"
                            >
                                {{ $loan->creditor_icon ?: '💳' }}
                            </div>

                            <div class="min-w-0">

                                <h1 class="text-xl sm:text-2xl font-semibold truncate">
                                    {{ $loan->name }}
                                </h1>

                                <p class="text-sm text-slate-500 dark:text-slate-400 truncate">
                                    {{ $loan->creditor_name ?: 'Kredit' }}
                                </p>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="flex items-center gap-2 shrink-0">

                    <a
                        href="{{ route('loans.edit', $loan) }}"
                        class="
                            inline-flex
                            items-center
                            gap-2
                            rounded-xl
                            border
                            border-slate-200
                            dark:border-slate-700
                            px-4
                            py-2.5
                            text-sm
                            font-medium
                            hover:bg-slate-100
                            dark:hover:bg-slate-800
                            transition
                        "
                    >
                        <span>✏️</span>
                        <span class="hidden sm:inline">Bearbeiten</span>
                    </a>

                </div>

            </div>

        </div>

    </header>


    {{-- =========================================================
         CONTENT
    ========================================================== --}}

    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Erfolgsmeldung --}}

        @if(session('success'))

            <div class="mb-6 rounded-2xl border border-emerald-200 dark:border-emerald-900/50 bg-emerald-50 dark:bg-emerald-950/30 px-5 py-4 text-sm text-emerald-700 dark:text-emerald-400">
                {{ session('success') }}
            </div>

        @endif


        {{-- =====================================================
             HAUPTKARTE
        ====================================================== --}}

        <section class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 overflow-hidden">

            <div class="p-6 sm:p-8">

                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-8">

                    {{-- Linke Seite --}}

                    <div class="flex items-start gap-4">

                        <div
                            class="w-16 h-16 shrink-0 rounded-2xl flex items-center justify-center text-3xl"
                            style="background-color: {{ $loan->creditor_color ?: '#10b981' }}20;"
                        >
                            {{ $loan->creditor_icon ?: '💳' }}
                        </div>

                        <div>

                            <div class="flex flex-wrap items-center gap-2">

                                <h2 class="text-2xl font-semibold">
                                    {{ $loan->name }}
                                </h2>

                                @if($loan->is_active)

                                    <span class="rounded-full bg-emerald-50 dark:bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                                        Aktiv
                                    </span>

                                @else

                                    <span class="rounded-full bg-slate-100 dark:bg-slate-800 px-3 py-1 text-xs font-medium text-slate-500">
                                        Inaktiv
                                    </span>

                                @endif

                            </div>

                            <p class="mt-1 text-slate-500 dark:text-slate-400">
                                {{ $loan->creditor_name ?: 'Kein Gläubiger angegeben' }}
                            </p>

                        </div>

                    </div>


                    {{-- Restschuld --}}

                    <div class="lg:text-right">

                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            Aktuelle Restschuld
                        </p>

                        <p class="text-3xl font-semibold mt-1">
                            {{ number_format($loan->remaining_amount, 2, ',', '.') }} €
                        </p>

                    </div>

                </div>


                {{-- =================================================
                     FORTSCHRITT
                ================================================== --}}

                <div class="mt-8">

                    <div class="flex items-center justify-between mb-3">

                        <div>

                            <p class="text-sm font-medium">
                                Tilgungsfortschritt
                            </p>

                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                {{ number_format($loan->paid_amount, 2, ',', '.') }} €
                                von
                                {{ number_format($loan->principal_amount, 2, ',', '.') }} €
                                getilgt
                            </p>

                        </div>

                        <div
                            class="text-lg font-semibold"
                            style="color: {{ $loan->creditor_color ?: '#10b981' }};"
                        >
                            {{ number_format($loan->progress, 1, ',', '.') }} %
                        </div>

                    </div>


                    <div class="h-4 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">

                        <div
                            class="h-full rounded-full transition-all duration-700"
                            style="
                                width: {{ min(100, max(0, $loan->progress)) }}%;
                                background-color: {{ $loan->creditor_color ?: '#10b981' }};
                            "
                        ></div>

                    </div>

                </div>

            </div>


            {{-- =================================================
                 KENNZAHLEN
            ================================================== --}}

            <div class="grid grid-cols-2 md:grid-cols-4 border-t border-slate-200 dark:border-slate-800">

                <div class="p-5 sm:p-6">

                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Ursprünglicher Betrag
                    </p>

                    <p class="text-lg font-semibold mt-1">
                        {{ number_format($loan->principal_amount, 2, ',', '.') }} €
                    </p>

                </div>


                <div class="p-5 sm:p-6 border-l border-slate-200 dark:border-slate-800">

                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Monatliche Rate
                    </p>

                    <p class="text-lg font-semibold mt-1">
                        {{ number_format($loan->installment_amount, 2, ',', '.') }} €
                    </p>

                </div>


                <div class="p-5 sm:p-6 border-t md:border-t-0 md:border-l border-slate-200 dark:border-slate-800">

                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Noch offen
                    </p>

                    <p class="text-lg font-semibold mt-1">
                        @if($loan->remaining_installments !== null)
                            {{ $loan->remaining_installments }} Raten
                        @else
                            –
                        @endif
                    </p>

                </div>


                <div class="p-5 sm:p-6 border-l border-t md:border-t-0 border-slate-200 dark:border-slate-800">

                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Zinssatz
                    </p>

                    <p class="text-lg font-semibold mt-1">
                        @if($loan->interest_rate !== null)
                            {{ number_format($loan->interest_rate, 3, ',', '.') }} %
                        @else
                            –
                        @endif
                    </p>

                </div>

            </div>

        </section>


        {{-- =====================================================
             ZWEI SPALTEN
        ====================================================== --}}

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">


            {{-- =================================================
                 TILGUNGSPLAN
            ================================================== --}}

            <section class="lg:col-span-2 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">

                <details open>

                    <summary class="list-none cursor-pointer p-6 sm:p-8">

                        <div class="flex items-center justify-between gap-4">

                            <div>

                                <h2 class="text-lg font-semibold">
                                    Tilgungsplan
                                </h2>

                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                                    Raten und Zahlungsstatus
                                </p>

                            </div>

                            <span class="text-xl">
                                ▼
                            </span>

                        </div>

                    </summary>


                    <div class="px-6 sm:px-8 pb-8">

                        @if($payments->count() > 0)

                            <div class="overflow-x-auto">

                                <table class="w-full text-sm">

                                    <thead>

                                        <tr class="border-b border-slate-200 dark:border-slate-800 text-left">

                                            <th class="py-3 pr-4 font-medium text-slate-500 dark:text-slate-400">
                                                Rate
                                            </th>

                                            <th class="py-3 px-4 font-medium text-slate-500 dark:text-slate-400">
                                                Fälligkeit
                                            </th>

                                            <th class="py-3 px-4 font-medium text-slate-500 dark:text-slate-400">
                                                Betrag
                                            </th>

                                            <th class="py-3 px-4 font-medium text-slate-500 dark:text-slate-400">
                                                Typ
                                            </th>

                                            <th class="py-3 pl-4 text-right font-medium text-slate-500 dark:text-slate-400">
                                                Status
                                            </th>

                                        </tr>

                                    </thead>


                                    <tbody>

                                        @foreach($payments as $payment)

                                            <tr class="border-b border-slate-100 dark:border-slate-800/70 last:border-0">

                                                <td class="py-4 pr-4 font-medium">
                                                    {{ $payment->installment_number }}
                                                </td>

                                                <td class="py-4 px-4 whitespace-nowrap">
                                                    {{ $payment->due_date->format('d.m.Y') }}
                                                </td>

                                                <td class="py-4 px-4 whitespace-nowrap font-medium">
                                                    {{ number_format($payment->amount, 2, ',', '.') }} €
                                                </td>

                                                <td class="py-4 px-4">

                                                    @if($payment->payment_type === 'extra')

                                                        <span class="inline-flex rounded-full bg-violet-50 dark:bg-violet-500/10 px-2.5 py-1 text-xs font-medium text-violet-600 dark:text-violet-400">
                                                            Sondertilgung
                                                        </span>

                                                    @else

                                                        <span class="inline-flex rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-xs font-medium text-slate-600 dark:text-slate-400">
                                                            Rate
                                                        </span>

                                                    @endif

                                                </td>

                                                <td class="py-4 pl-4 text-right">

                                                    @if($payment->status === 'paid')

                                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 dark:bg-emerald-500/10 px-2.5 py-1 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                                                            ✓ Bezahlt
                                                        </span>

                                                    @elseif($payment->status === 'overdue')

                                                        <span class="inline-flex rounded-full bg-red-50 dark:bg-red-500/10 px-2.5 py-1 text-xs font-medium text-red-600 dark:text-red-400">
                                                            Überfällig
                                                        </span>

                                                    @elseif($payment->status === 'cancelled')

                                                        <span class="inline-flex rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-xs font-medium text-slate-500">
                                                            Storniert
                                                        </span>

                                                    @else

                                                        <span class="inline-flex rounded-full bg-amber-50 dark:bg-amber-500/10 px-2.5 py-1 text-xs font-medium text-amber-600 dark:text-amber-400">
                                                            Geplant
                                                        </span>

                                                    @endif

                                                </td>

                                            </tr>

                                        @endforeach

                                    </tbody>

                                </table>

                            </div>

                        @else

                            <div class="rounded-2xl bg-slate-50 dark:bg-slate-800/50 p-8 text-center">

                                <div class="text-3xl">
                                    📅
                                </div>

                                <h3 class="font-medium mt-3">
                                    Noch kein Tilgungsplan vorhanden
                                </h3>

                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                                    Die Raten werden hier angezeigt, sobald ein Tilgungsplan angelegt wurde.
                                </p>

                            </div>

                        @endif

                    </div>

                </details>

            </section>


            {{-- =================================================
                 SONSTIGES
            ================================================== --}}

            <div class="space-y-6">


                {{-- Sondertilgung --}}

                <section class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">

                    <details>

                        <summary class="list-none cursor-pointer p-6">

                            <div class="flex items-center justify-between gap-3">

                                <div>

                                    <h2 class="font-semibold">
                                        Sondertilgungen
                                    </h2>

                                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                                        Zusätzliche Zahlungen
                                    </p>

                                </div>

                                <span>
                                    ▼
                                </span>

                            </div>

                        </summary>


                        <div class="px-6 pb-6">

                            @if($extraPayments->count() > 0)

                                <div class="space-y-3">

                                    @foreach($extraPayments as $payment)

                                        <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 p-3">

                                            <div>

                                                <p class="text-sm font-medium">
                                                    {{ $payment->paid_date?->format('d.m.Y') ?? $payment->due_date->format('d.m.Y') }}
                                                </p>

                                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                                    Sondertilgung
                                                </p>

                                            </div>

                                            <p class="font-semibold">
                                                {{ number_format($payment->amount, 2, ',', '.') }} €
                                            </p>

                                        </div>

                                    @endforeach

                                </div>

                            @else

                                <p class="text-sm text-slate-500 dark:text-slate-400">
                                    Noch keine Sondertilgungen erfasst.
                                </p>

                            @endif


                            {{-- Sondertilgung Formular --}}

                            <form
                                method="POST"
                                action="{{ route('loans.extra-payment', $loan) }}"
                                class="mt-5 pt-5 border-t border-slate-200 dark:border-slate-800 space-y-4"
                            >

                                @csrf

                                <div>

                                    <label
                                        for="extra_amount"
                                        class="block text-sm font-medium mb-2"
                                    >
                                        Betrag
                                    </label>

                                    <div class="relative">

                                        <input
                                            type="number"
                                            id="extra_amount"
                                            name="amount"
                                            min="0.01"
                                            step="0.01"
                                            required
                                            placeholder="500,00"
                                            class="
                                                w-full
                                                rounded-xl
                                                border border-slate-200
                                                dark:border-slate-700
                                                bg-white
                                                dark:bg-slate-800
                                                px-4 py-3 pr-10
                                                outline-none
                                                focus:ring-2
                                                focus:ring-emerald-500/20
                                                focus:border-emerald-500
                                            "
                                        >

                                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">
                                            €
                                        </span>

                                    </div>

                                </div>


                                <div>

                                    <label
                                        for="extra_paid_date"
                                        class="block text-sm font-medium mb-2"
                                    >
                                        Datum
                                    </label>

                                    <input
                                        type="date"
                                        id="extra_paid_date"
                                        name="paid_date"
                                        value="{{ now()->format('Y-m-d') }}"
                                        required
                                        class="
                                            w-full
                                            rounded-xl
                                            border border-slate-200
                                            dark:border-slate-700
                                            bg-white
                                            dark:bg-slate-800
                                            px-4 py-3
                                            outline-none
                                            focus:ring-2
                                            focus:ring-emerald-500/20
                                            focus:border-emerald-500
                                        "
                                    >

                                </div>


                                <button
                                    type="submit"
                                    class="
                                        w-full
                                        rounded-xl
                                        bg-slate-950
                                        dark:bg-white
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
                                    + Sondertilgung erfassen
                                </button>

                            </form>

                        </div>

                    </details>

                </section>


                {{-- Kreditinformationen --}}

                <section class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6">

                    <h2 class="font-semibold mb-5">
                        Kreditinformationen
                    </h2>


                    <div class="space-y-4 text-sm">

                        <div class="flex justify-between gap-4">

                            <span class="text-slate-500 dark:text-slate-400">
                                Kreditart
                            </span>

                            <span class="font-medium">
                                @switch($loan->type)

                                    @case('loan')
                                        Ratenkredit
                                        @break

                                    @case('installment')
                                        Finanzierung
                                        @break

                                    @case('paypal_installment')
                                        PayPal Ratenzahlung
                                        @break

                                    @default
                                        Sonstige
                                @endswitch
                            </span>

                        </div>


                        @if($loan->start_date)

                            <div class="flex justify-between gap-4">

                                <span class="text-slate-500 dark:text-slate-400">
                                    Startdatum
                                </span>

                                <span class="font-medium">
                                    {{ $loan->start_date->format('d.m.Y') }}
                                </span>

                            </div>

                        @endif


                        @if($loan->end_date)

                            <div class="flex justify-between gap-4">

                                <span class="text-slate-500 dark:text-slate-400">
                                    Enddatum
                                </span>

                                <span class="font-medium">
                                    {{ $loan->end_date->format('d.m.Y') }}
                                </span>

                            </div>

                        @endif


                        @if($loan->account)

                            <div class="flex justify-between gap-4">

                                <span class="text-slate-500 dark:text-slate-400">
                                    Zahlungskonto
                                </span>

                                <span class="font-medium text-right">
                                    {{ $loan->account->name }}
                                </span>

                            </div>

                        @endif

                    </div>

                </section>


                {{-- Notizen --}}

                @if($loan->notes)

                    <section class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6">

                        <h2 class="font-semibold mb-3">
                            Notizen
                        </h2>

                        <p class="text-sm text-slate-600 dark:text-slate-400 whitespace-pre-line">
                            {{ $loan->notes }}
                        </p>

                    </section>

                @endif

            </div>

        </div>


        {{-- =====================================================
             LÖSCHEN
        ====================================================== --}}

        <div class="mt-8 text-center">

            <form
                method="POST"
                action="{{ route('loans.destroy', $loan) }}"
                onsubmit="return confirm('Möchtest du diesen Kredit wirklich löschen?');"
            >

                @csrf
                @method('DELETE')

                <button
                    type="submit"
                    class="text-sm text-red-500 hover:text-red-600 transition"
                >
                    Kredit löschen
                </button>

            </form>

        </div>

    </main>

</div>

</body>
</html>