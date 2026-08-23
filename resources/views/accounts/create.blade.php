<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Konto erstellen – Finanzblick</title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])
</head>

<body class="min-h-screen bg-slate-100">

<div class="max-w-2xl mx-auto px-4 sm:px-6 py-8">

    <a
        href="{{ route('accounts.index') }}"
        class="text-sm text-slate-500"
    >
        ← Konten
    </a>

    <div class="bg-white rounded-3xl shadow-sm p-6 sm:p-8 mt-4">

        <h1 class="text-2xl font-semibold text-slate-900">
            Konto hinzufügen
        </h1>

        <p class="text-slate-500 mt-1 mb-8">
            Erstelle ein neues Finanzkonto.
        </p>

        @if ($errors->any())
            <div class="mb-6 rounded-xl bg-red-50 p-4 text-red-700">
                <ul class="list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('accounts.store') }}"
            class="space-y-6"
        >

            @csrf

            <div>
                <label class="block text-sm font-medium mb-2">
                    Kontoname
                </label>

                <input
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    placeholder="z. B. Hauptkonto"
                    required
                    class="w-full rounded-xl border border-slate-200 px-4 py-3"
                >
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">
                    Bank / Anbieter
                </label>

                <input
                    type="text"
                    name="institution"
                    value="{{ old('institution') }}"
                    placeholder="z. B. ING, DKB, PayPal, American Express"
                    class="w-full rounded-xl border border-slate-200 px-4 py-3"
                >
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">
                    Kontotyp
                </label>

                <select
                    name="type"
                    class="w-full rounded-xl border border-slate-200 px-4 py-3"
                >
                    <option value="checking">🏦 Girokonto</option>
                    <option value="savings">💰 Tagesgeld / Sparkonto</option>
                    <option value="credit_card">💳 Kreditkarte</option>
                    <option value="paypal">🅿️ PayPal</option>
                    <option value="cash">💵 Bargeld</option>
                    <option value="investment">📈 Depot / Investment</option>
                    <option value="loan">💸 Kredit</option>
                    <option value="other">📦 Sonstiges</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">
                    Startsaldo
                </label>

                <input
                    type="number"
                    step="0.01"
                    name="opening_balance"
                    value="{{ old('opening_balance', 0) }}"
                    class="w-full rounded-xl border border-slate-200 px-4 py-3"
                >
            </div>

            <div>
    <label class="block text-sm font-medium mb-2">
        Währung
    </label>

    <select
        name="currency"
        required
        class="w-full rounded-xl border border-slate-200 px-4 py-3"
    >
        <option value="EUR" {{ old('currency', 'EUR') === 'EUR' ? 'selected' : '' }}>
            EUR – Euro
        </option>

        <option value="USD" {{ old('currency') === 'USD' ? 'selected' : '' }}>
            USD – US-Dollar
        </option>

        <option value="CHF" {{ old('currency') === 'CHF' ? 'selected' : '' }}>
            CHF – Schweizer Franken
        </option>

        <option value="GBP" {{ old('currency') === 'GBP' ? 'selected' : '' }}>
            GBP – Britisches Pfund
        </option>
    </select>
</div>

            <div>
                <label class="block text-sm font-medium mb-2">
                    IBAN
                </label>

                <input
                    type="text"
                    name="iban"
                    value="{{ old('iban') }}"
                    class="w-full rounded-xl border border-slate-200 px-4 py-3"
                >
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">
                    Symbol / Icon
                </label>

                <input
                    type="text"
                    name="icon"
                    value="{{ old('icon') }}"
                    placeholder="🏦"
                    maxlength="20"
                    class="w-full rounded-xl border border-slate-200 px-4 py-3"
                >
            </div>

            <label class="flex items-center gap-3">

                <input
                    type="checkbox"
                    name="include_in_total"
                    value="1"
                    checked
                >

                <span class="text-sm text-slate-700">
                    In Gesamtvermögen berücksichtigen
                </span>

            </label>

            <button
                type="submit"
                class="w-full rounded-xl bg-slate-950 py-3.5 font-medium text-white"
            >
                Konto erstellen
            </button>

        </form>

    </div>

</div>

</body>
</html>