<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Kredit hinzufügen – Finanzblick</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white">

    <div class="min-h-screen">

        {{-- HEADER --}}
        <header class="border-b border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/80 backdrop-blur">

            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

                <div class="h-20 flex items-center gap-4">

                    <a
                        href="{{ route('loans.index') }}"
                        class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center hover:bg-slate-200 dark:hover:bg-slate-700 transition"
                    >
                        ←
                    </a>

                    <div>
                        <h1 class="text-2xl font-semibold">
                            Kredit hinzufügen
                        </h1>

                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            Erstelle eine neue Finanzierung
                        </p>
                    </div>

                </div>

            </div>

        </header>


        {{-- CONTENT --}}
        <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            @if ($errors->any())

                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 dark:border-red-900/50 dark:bg-red-950/30 p-5">

                    <div class="font-medium text-red-700 dark:text-red-400">
                        Bitte überprüfe deine Eingaben.
                    </div>

                    <ul class="mt-2 text-sm text-red-600 dark:text-red-400 list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>

                </div>

            @endif


            <form
                method="POST"
                action="{{ route('loans.store') }}"
                class="space-y-6"
            >

                @csrf


                {{-- =================================================
                     GRUNDINFORMATIONEN
                ================================================== --}}

                <section class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 sm:p-8">

                    <div class="mb-6">

                        <h2 class="text-lg font-semibold">
                            Grundinformationen
                        </h2>

                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Wer bekommt das Geld und wie heißt die Finanzierung?
                        </p>

                    </div>


                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                        {{-- Kreditname --}}

                        <div class="md:col-span-2">

                            <label
                                for="name"
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                            >
                                Bezeichnung
                            </label>

                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="{{ old('name') }}"
                                required
                                placeholder="z.B. Auto Finanzierung"
                                class="
                                    w-full rounded-xl
                                    border border-slate-200 dark:border-slate-700
                                    bg-white dark:bg-slate-800
                                    px-4 py-3
                                    outline-none
                                    focus:ring-2 focus:ring-emerald-500/20
                                    focus:border-emerald-500
                                "
                            >

                        </div>


                        {{-- Gläubiger --}}

                        <div>

                            <label
                                for="creditor_name"
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                            >
                                Gläubiger
                            </label>

                            <input
                                type="text"
                                id="creditor_name"
                                name="creditor_name"
                                value="{{ old('creditor_name') }}"
                                placeholder="z.B. PayPal, Sparkasse, auxmoney"
                                class="
                                    w-full rounded-xl
                                    border border-slate-200 dark:border-slate-700
                                    bg-white dark:bg-slate-800
                                    px-4 py-3
                                    outline-none
                                    focus:ring-2 focus:ring-emerald-500/20
                                    focus:border-emerald-500
                                "
                            >

                        </div>


                        {{-- Kreditart --}}

                        <div>

                            <label
                                for="type"
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                            >
                                Kreditart
                            </label>

                            <select
                                id="type"
                                name="type"
                                required
                                class="
                                    w-full rounded-xl
                                    border border-slate-200 dark:border-slate-700
                                    bg-white dark:bg-slate-800
                                    px-4 py-3
                                    outline-none
                                    focus:ring-2 focus:ring-emerald-500/20
                                    focus:border-emerald-500
                                "
                            >
                                <option value="loan" @selected(old('type') === 'loan')>
                                    Ratenkredit
                                </option>

                                <option value="installment" @selected(old('type') === 'installment')>
                                    Finanzierung
                                </option>

                                <option value="paypal_installment" @selected(old('type') === 'paypal_installment')>
                                    PayPal Ratenzahlung
                                </option>

                                <option value="other" @selected(old('type') === 'other')>
                                    Sonstige
                                </option>
                            </select>

                        </div>

                    </div>

                </section>


                {{-- =================================================
                     ICON / FARBE
                ================================================== --}}

                <section class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 sm:p-8">

                    <div class="mb-6">

                        <h2 class="text-lg font-semibold">
                            Darstellung
                        </h2>

                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Passe das Aussehen des Kredits in der Übersicht an.
                        </p>

                    </div>


                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                        {{-- Icon --}}

                        <div>

                            <label
                                for="creditor_icon"
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                            >
                                Icon
                            </label>

                            <div class="flex gap-3">

                                <div
                                    id="icon-preview"
                                    class="w-14 h-14 shrink-0 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-2xl"
                                >
                                    {{ old('creditor_icon', '💳') }}
                                </div>

                                <input
                                    type="text"
                                    id="creditor_icon"
                                    name="creditor_icon"
                                    value="{{ old('creditor_icon', '💳') }}"
                                    maxlength="20"
                                    class="
                                        min-w-0 flex-1
                                        rounded-xl
                                        border border-slate-200 dark:border-slate-700
                                        bg-white dark:bg-slate-800
                                        px-4 py-3
                                        text-xl
                                        outline-none
                                        focus:ring-2 focus:ring-emerald-500/20
                                        focus:border-emerald-500
                                    "
                                >

                            </div>

                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                                Zum Beispiel 💳 🏦 🛒 🚗 🏠
                            </p>

                        </div>


                        {{-- Farbe --}}

                        <div>

                            <label
                                for="creditor_color"
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                            >
                                Farbe
                            </label>

                            <div class="flex gap-3">

                                <input
                                    type="color"
                                    id="creditor_color"
                                    name="creditor_color"
                                    value="{{ old('creditor_color', '#10b981') }}"
                                    class="w-14 h-14 rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer bg-white dark:bg-slate-800"
                                >

                                <div class="flex-1 flex items-center">

                                    <span class="text-sm text-slate-500 dark:text-slate-400">
                                        Diese Farbe wird für Icon und Fortschrittsbalken verwendet.
                                    </span>

                                </div>

                            </div>

                        </div>

                    </div>

                </section>


                {{-- =================================================
                     FINANZEN
                ================================================== --}}

                <section class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 sm:p-8">

                    <div class="mb-6">

                        <h2 class="text-lg font-semibold">
                            Kreditdaten
                        </h2>

                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Beträge, Zinsen und monatliche Rate.
                        </p>

                    </div>


                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                        {{-- Ursprünglicher Betrag --}}

                        <div>

                            <label
                                for="principal_amount"
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                            >
                                Ursprünglicher Kreditbetrag
                            </label>

                            <div class="relative">

                                <input
                                    type="number"
                                    id="principal_amount"
                                    name="principal_amount"
                                    value="{{ old('principal_amount') }}"
                                    required
                                    min="0.01"
                                    step="0.01"
                                    placeholder="10000,00"
                                    class="
                                        w-full rounded-xl
                                        border border-slate-200 dark:border-slate-700
                                        bg-white dark:bg-slate-800
                                        px-4 py-3 pr-12
                                        outline-none
                                        focus:ring-2 focus:ring-emerald-500/20
                                        focus:border-emerald-500
                                    "
                                >

                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">
                                    €
                                </span>

                            </div>

                        </div>


                        {{-- Bereits bezahlt --}}

                        <div>

                            <label
                                for="paid_amount"
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                            >
                                Bereits getilgt
                            </label>

                            <div class="relative">

                                <input
                                    type="number"
                                    id="paid_amount"
                                    name="paid_amount"
                                    value="{{ old('paid_amount', 0) }}"
                                    min="0"
                                    step="0.01"
                                    placeholder="0,00"
                                    class="
                                        w-full rounded-xl
                                        border border-slate-200 dark:border-slate-700
                                        bg-white dark:bg-slate-800
                                        px-4 py-3 pr-12
                                        outline-none
                                        focus:ring-2 focus:ring-emerald-500/20
                                        focus:border-emerald-500
                                    "
                                >

                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">
                                    €
                                </span>

                            </div>

                        </div>


                        {{-- Zinssatz --}}

                        <div>

                            <label
                                for="interest_rate"
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                            >
                                Zinssatz
                            </label>

                            <div class="relative">

                                <input
                                    type="number"
                                    id="interest_rate"
                                    name="interest_rate"
                                    value="{{ old('interest_rate') }}"
                                    min="0"
                                    step="0.001"
                                    placeholder="5,990"
                                    class="
                                        w-full rounded-xl
                                        border border-slate-200 dark:border-slate-700
                                        bg-white dark:bg-slate-800
                                        px-4 py-3 pr-12
                                        outline-none
                                        focus:ring-2 focus:ring-emerald-500/20
                                        focus:border-emerald-500
                                    "
                                >

                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">
                                    %
                                </span>

                            </div>

                        </div>


                        {{-- Rate --}}

                        <div>

                            <label
                                for="installment_amount"
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                            >
                                Monatliche Rate
                            </label>

                            <div class="relative">

                                <input
                                    type="number"
                                    id="installment_amount"
                                    name="installment_amount"
                                    value="{{ old('installment_amount') }}"
                                    required
                                    min="0.01"
                                    step="0.01"
                                    placeholder="250,00"
                                    class="
                                        w-full rounded-xl
                                        border border-slate-200 dark:border-slate-700
                                        bg-white dark:bg-slate-800
                                        px-4 py-3 pr-12
                                        outline-none
                                        focus:ring-2 focus:ring-emerald-500/20
                                        focus:border-emerald-500
                                    "
                                >

                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">
                                    €
                                </span>

                            </div>

                        </div>

                    </div>

                </section>


                {{-- =================================================
                     LAUFZEIT
                ================================================== --}}

                <section class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 sm:p-8">

                    <div class="mb-6">

                        <h2 class="text-lg font-semibold">
                            Laufzeit & Raten
                        </h2>

                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Informationen zur Laufzeit des Kredits.
                        </p>

                    </div>


                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                        {{-- Gesamte Raten --}}

                        <div>

                            <label
                                for="total_installments"
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                            >
                                Anzahl der Raten
                            </label>

                            <input
                                type="number"
                                id="total_installments"
                                name="total_installments"
                                value="{{ old('total_installments') }}"
                                min="1"
                                placeholder="48"
                                class="
                                    w-full rounded-xl
                                    border border-slate-200 dark:border-slate-700
                                    bg-white dark:bg-slate-800
                                    px-4 py-3
                                    outline-none
                                    focus:ring-2 focus:ring-emerald-500/20
                                    focus:border-emerald-500
                                "
                            >

                        </div>


                        {{-- Bereits bezahlte Raten --}}

                        <div>

                            <label
                                for="paid_installments"
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                            >
                                Bereits bezahlte Raten
                            </label>

                            <input
                                type="number"
                                id="paid_installments"
                                name="paid_installments"
                                value="{{ old('paid_installments', 0) }}"
                                min="0"
                                placeholder="0"
                                class="
                                    w-full rounded-xl
                                    border border-slate-200 dark:border-slate-700
                                    bg-white dark:bg-slate-800
                                    px-4 py-3
                                    outline-none
                                    focus:ring-2 focus:ring-emerald-500/20
                                    focus:border-emerald-500
                                "
                            >

                        </div>


                        {{-- Startdatum --}}

                        <div>

                            <label
                                for="start_date"
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                            >
                                Startdatum
                            </label>

                            <input
                                type="date"
                                id="start_date"
                                name="start_date"
                                value="{{ old('start_date') }}"
                                class="
                                    w-full rounded-xl
                                    border border-slate-200 dark:border-slate-700
                                    bg-white dark:bg-slate-800
                                    px-4 py-3
                                    outline-none
                                    focus:ring-2 focus:ring-emerald-500/20
                                    focus:border-emerald-500
                                "
                            >

                        </div>


                        {{-- Enddatum --}}

                        <div>

                            <label
                                for="end_date"
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                            >
                                Voraussichtliches Enddatum
                            </label>

                            <input
                                type="date"
                                id="end_date"
                                name="end_date"
                                value="{{ old('end_date') }}"
                                class="
                                    w-full rounded-xl
                                    border border-slate-200 dark:border-slate-700
                                    bg-white dark:bg-slate-800
                                    px-4 py-3
                                    outline-none
                                    focus:ring-2 focus:ring-emerald-500/20
                                    focus:border-emerald-500
                                "
                            >

                        </div>

                    </div>

                </section>


                {{-- =================================================
                     KONTO
                ================================================== --}}

                <section class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 sm:p-8">

                    <div class="mb-6">

                        <h2 class="text-lg font-semibold">
                            Verknüpfung
                        </h2>

                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Optional kannst du den Kredit einem Konto zuordnen.
                        </p>

                    </div>


                    <div>

                        <label
                            for="account_id"
                            class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                        >
                            Zahlungskonto
                        </label>

                        <select
                            id="account_id"
                            name="account_id"
                            class="
                                w-full rounded-xl
                                border border-slate-200 dark:border-slate-700
                                bg-white dark:bg-slate-800
                                px-4 py-3
                                outline-none
                                focus:ring-2 focus:ring-emerald-500/20
                                focus:border-emerald-500
                            "
                        >

                            <option value="">
                                Kein Konto ausgewählt
                            </option>

                            @foreach($accounts as $account)

                                <option
                                    value="{{ $account->id }}"
                                    @selected(old('account_id') == $account->id)
                                >
                                    {{ $account->name }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                </section>


                {{-- =================================================
                     NOTIZEN
                ================================================== --}}

                <section class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 sm:p-8">

                    <label
                        for="notes"
                        class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                    >
                        Notizen
                    </label>

                    <textarea
                        id="notes"
                        name="notes"
                        rows="4"
                        placeholder="Optionale Informationen zum Kredit..."
                        class="
                            w-full rounded-xl
                            border border-slate-200 dark:border-slate-700
                            bg-white dark:bg-slate-800
                            px-4 py-3
                            outline-none
                            resize-y
                            focus:ring-2 focus:ring-emerald-500/20
                            focus:border-emerald-500
                        "
                    >{{ old('notes') }}</textarea>

                </section>


                {{-- =================================================
                     AKTIV
                ================================================== --}}

                <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5">

                    <label class="flex items-center gap-3 cursor-pointer">

                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            checked
                            class="w-5 h-5 rounded border-slate-300 text-emerald-500 focus:ring-emerald-500"
                        >

                        <div>

                            <div class="font-medium">
                                Kredit ist aktiv
                            </div>

                            <div class="text-sm text-slate-500 dark:text-slate-400">
                                Der Kredit wird in der aktiven Übersicht angezeigt.
                            </div>

                        </div>

                    </label>

                </div>


                {{-- =================================================
                     BUTTONS
                ================================================== --}}

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">

                    <a
                        href="{{ route('loans.index') }}"
                        class="
                            inline-flex
                            justify-center
                            items-center
                            rounded-xl
                            border border-slate-200
                            dark:border-slate-700
                            px-5 py-3
                            text-sm
                            font-medium
                            hover:bg-slate-100
                            dark:hover:bg-slate-800
                            transition
                        "
                    >
                        Abbrechen
                    </a>

                    <button
                        type="submit"
                        class="
                            inline-flex
                            justify-center
                            items-center
                            rounded-xl
                            bg-slate-950
                            dark:bg-white
                            px-6 py-3
                            text-sm
                            font-medium
                            text-white
                            dark:text-slate-950
                            hover:bg-slate-800
                            dark:hover:bg-slate-200
                            transition
                        "
                    >
                        Kredit speichern
                    </button>

                </div>

            </form>

        </main>

    </div>


    {{-- Icon-Live-Vorschau --}}

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const iconInput = document.getElementById('creditor_icon');
            const iconPreview = document.getElementById('icon-preview');

            if (iconInput && iconPreview) {

                iconInput.addEventListener('input', function () {
                    iconPreview.textContent = this.value || '💳';
                });

            }

        });
    </script>

</body>
</html>