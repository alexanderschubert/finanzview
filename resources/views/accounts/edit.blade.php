<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Konto bearbeiten – Finanzblick</title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])
</head>

<body class="min-h-screen bg-slate-100">

<div class="max-w-2xl mx-auto px-4 sm:px-6 py-8">

    <a
        href="{{ route('accounts.index') }}"
        class="text-sm text-slate-500 hover:text-slate-900"
    >
        ← Konten
    </a>

    <div class="bg-white rounded-3xl shadow-sm p-6 sm:p-8 mt-4">

        <div class="flex items-center gap-4 mb-8">

            <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center text-3xl">
                {{ $account->icon ?: '🏦' }}
            </div>

            <div>

                <h1 class="text-2xl font-semibold text-slate-900">
                    Konto bearbeiten
                </h1>

                <p class="text-slate-500 mt-1">
                    {{ $account->name }}
                </p>

            </div>

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
        <option value="EUR" @selected(old('currency', $account->currency) === 'EUR')>
            EUR – Euro
        </option>

        <option value="USD" @selected(old('currency', $account->currency) === 'USD')>
            USD – US-Dollar
        </option>

        <option value="CHF" @selected(old('currency', $account->currency) === 'CHF')>
            CHF – Schweizer Franken
        </option>

        <option value="GBP" @selected(old('currency', $account->currency) === 'GBP')>
            GBP – Britisches Pfund
        </option>
    </select>
</div>

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
            action="{{ route('accounts.update', $account) }}"
            class="space-y-6"
        >

            @csrf
            @method('PUT')

            <div>

                <label class="block text-sm font-medium mb-2">
                    Kontoname
                </label>

                <input
                    type="text"
                    name="name"
                    value="{{ old('name', $account->name) }}"
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
                    value="{{ old('institution', $account->institution) }}"
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

                    <option
                        value="checking"
                        @selected(old('type', $account->type) === 'checking')
                    >
                        🏦 Girokonto
                    </option>

                    <option
                        value="savings"
                        @selected(old('type', $account->type) === 'savings')
                    >
                        💰 Tagesgeld / Sparkonto
                    </option>

                    <option
                        value="credit_card"
                        @selected(old('type', $account->type) === 'credit_card')
                    >
                        💳 Kreditkarte
                    </option>

                    <option
                        value="paypal"
                        @selected(old('type', $account->type) === 'paypal')
                    >
                        🅿️ PayPal
                    </option>

                    <option
                        value="cash"
                        @selected(old('type', $account->type) === 'cash')
                    >
                        💵 Bargeld
                    </option>

                    <option
                        value="investment"
                        @selected(old('type', $account->type) === 'investment')
                    >
                        📈 Depot / Investment
                    </option>

                    <option
                        value="loan"
                        @selected(old('type', $account->type) === 'loan')
                    >
                        💸 Kredit
                    </option>

                    <option
                        value="other"
                        @selected(old('type', $account->type) === 'other')
                    >
                        📦 Sonstiges
                    </option>

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
                    value="{{ old('opening_balance', $account->opening_balance) }}"
                    class="w-full rounded-xl border border-slate-200 px-4 py-3"
                >

                <p class="text-xs text-slate-500 mt-2">
                    Der Startsaldo wird als Ausgangspunkt für die spätere Saldo-Berechnung verwendet.
                </p>

            </div>

            <div>

                <label class="block text-sm font-medium mb-2">
                    Kreditlimit
                </label>

                <input
                    type="number"
                    step="0.01"
                    name="credit_limit"
                    value="{{ old('credit_limit', $account->credit_limit) }}"
                    class="w-full rounded-xl border border-slate-200 px-4 py-3"
                >

            </div>

            <div>

                <label class="block text-sm font-medium mb-2">
                    IBAN
                </label>

                <input
                    type="text"
                    name="iban"
                    value="{{ old('iban', $account->iban) }}"
                    class="w-full rounded-xl border border-slate-200 px-4 py-3"
                >

            </div>

            <div>

                <label class="block text-sm font-medium mb-2">
                    Kontonummer
                </label>

                <input
                    type="text"
                    name="account_number"
                    value="{{ old('account_number', $account->account_number) }}"
                    class="w-full rounded-xl border border-slate-200 px-4 py-3"
                >

            </div>

            <div>

                <label class="block text-sm font-medium mb-2">
                    Icon
                </label>

                <input
                    type="text"
                    name="icon"
                    value="{{ old('icon', $account->icon) }}"
                    maxlength="20"
                    class="w-full rounded-xl border border-slate-200 px-4 py-3"
                >

            </div>

            <div>

                <label class="block text-sm font-medium mb-2">
                    Notizen
                </label>

                <textarea
                    name="notes"
                    rows="4"
                    class="w-full rounded-xl border border-slate-200 px-4 py-3"
                >{{ old('notes', $account->notes) }}</textarea>

            </div>

            <div class="space-y-3">

                <label class="flex items-center gap-3">

                    <input
                        type="checkbox"
                        name="include_in_total"
                        value="1"
                        @checked(old('include_in_total', $account->include_in_total))
                    >

                    <span class="text-sm text-slate-700">
                        In Gesamtvermögen berücksichtigen
                    </span>

                </label>

                <label class="flex items-center gap-3">

                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        @checked(old('is_active', $account->is_active))
                    >

                    <span class="text-sm text-slate-700">
                        Konto ist aktiv
                    </span>

                </label>

            </div>

            <div class="flex gap-3 pt-4">

                <a
                    href="{{ route('accounts.index') }}"
                    class="flex-1 rounded-xl border border-slate-200 py-3.5 text-center font-medium text-slate-700"
                >
                    Abbrechen
                </a>

                <button
                    type="submit"
                    class="flex-1 rounded-xl bg-slate-950 py-3.5 font-medium text-white"
                >
                    Änderungen speichern
                </button>

            </div>

        </form>

        <div class="mt-8 pt-8 border-t border-slate-200">

            <form
                method="POST"
                action="{{ route('accounts.destroy', $account) }}"
                onsubmit="return confirm('Dieses Konto wirklich löschen?');"
            >

                @csrf
                @method('DELETE')

                <button
                    type="submit"
                    class="w-full rounded-xl bg-red-50 py-3.5 font-medium text-red-600 hover:bg-red-100"
                >
                    Konto löschen
                </button>

            </form>

        </div>

    </div>

</div>

</body>
</html>