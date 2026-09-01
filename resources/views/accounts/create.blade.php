@extends('layouts.app')

@section('title', 'Neues Konto – Finanzblick')
@section('eyebrow', 'Finanzverwaltung')
@section('page_title', 'Neues Konto')

@section('content')

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- HEADER --}}
    <div class="mb-6">

        <a
            href="{{ route('accounts.index') }}"
            class="text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition"
        >
            ← Konten
        </a>

        <div class="mt-4">

            <h2 class="text-2xl sm:text-3xl font-semibold text-slate-900 dark:text-white">
                Neues Konto
            </h2>

            <p class="text-slate-500 dark:text-slate-400 mt-1">
                Füge ein Bankkonto, Bargeld oder einen anderen Vermögenswert hinzu.
            </p>

        </div>

    </div>


    {{-- FEHLER --}}
    @if ($errors->any())

        <div class="mb-6 rounded-2xl bg-red-50 dark:bg-red-950/40 border border-red-100 dark:border-red-900/50 p-4 text-sm text-red-700 dark:text-red-300">

            <p class="font-medium mb-2">
                Bitte überprüfe deine Eingaben.
            </p>

            <ul class="list-disc list-inside space-y-1">

                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach

            </ul>

        </div>

    @endif


    <form
        method="POST"
        action="{{ route('accounts.store') }}"
    >

        @csrf


        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">


            {{-- ================================================= --}}
            {{-- HAUPTBEREICH --}}
            {{-- ================================================= --}}

            <div class="lg:col-span-2 space-y-6">


                {{-- GRUNDINFORMATIONEN --}}

                <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 p-6 sm:p-8">

                    <div class="mb-6">

                        <h3 class="font-semibold text-slate-900 dark:text-white">
                            Kontoinformationen
                        </h3>

                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Grundlegende Angaben zum Konto.
                        </p>

                    </div>


                    <div class="space-y-6">


                        {{-- NAME --}}

                        <div>

                            <label
                                for="name"
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                            >
                                Kontoname
                            </label>

                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="{{ old('name') }}"
                                required
                                autofocus
                                placeholder="z. B. Girokonto"
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder:text-slate-400 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-slate-200 dark:focus:ring-slate-600"
                            >

                        </div>


                        {{-- ANBIETER --}}

                        <div>

                            <label
                                for="provider_id"
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                            >
                                Bank / Anbieter
                                <span class="font-normal text-slate-400">
                                    (optional)
                                </span>
                            </label>

                            <select
                                id="provider_id"
                                name="provider_id"
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-4 py-3 focus:outline-none focus:ring-2 focus:ring-slate-200 dark:focus:ring-slate-600"
                            >
                                <option value="">Kein Anbieter ausgewählt</option>

                                @foreach ($providers as $provider)
                                    <option
                                        value="{{ $provider->id }}"
                                        @selected(old('provider_id') == $provider->id)
                                    >
                                        {{ $provider->emoji ?? '🏦' }} {{ $provider->name }}
                                    </option>
                                @endforeach
                            </select>

                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                Der Anbieter wird zentral verwaltet und kann später mit einem Logo dargestellt werden.
                            </p>

                        </div>


                        {{-- TYP --}}

                        <div>

                            <label
                                for="type"
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                            >
                                Kontoart
                            </label>

                            <select
                                id="type"
                                name="type"
                                required
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-4 py-3 focus:outline-none focus:ring-2 focus:ring-slate-200 dark:focus:ring-slate-600"
                            >

                                <option value="checking" @selected(old('type', 'checking') === 'checking')>
                                    Girokonto
                                </option>

                                <option value="savings" @selected(old('type') === 'savings')>
                                    Sparkonto
                                </option>

                                <option value="credit_card" @selected(old('type') === 'credit_card')>
                                    Kreditkarte
                                </option>

                                <option value="paypal" @selected(old('type') === 'paypal')>
                                    PayPal
                                </option>

                                <option value="cash" @selected(old('type') === 'cash')>
                                    Bargeld
                                </option>

                                <option value="investment" @selected(old('type') === 'investment')>
                                    Investment
                                </option>

                                <option value="loan" @selected(old('type') === 'loan')>
                                    Kredit
                                </option>

                                <option value="other" @selected(old('type') === 'other')>
                                    Sonstiges
                                </option>

                            </select>

                        </div>


                        {{-- WÄHRUNG --}}

                        <div>

                            <label
                                for="currency"
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                            >
                                Währung
                            </label>

                            <select
                                id="currency"
                                name="currency"
                                required
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-4 py-3 focus:outline-none focus:ring-2 focus:ring-slate-200 dark:focus:ring-slate-600"
                            >

                                <option value="EUR" @selected(old('currency', 'EUR') === 'EUR')>
                                    🇪🇺 EUR – Euro
                                </option>

                                <option value="USD" @selected(old('currency') === 'USD')>
                                    🇺🇸 USD – US-Dollar
                                </option>

                                <option value="GBP" @selected(old('currency') === 'GBP')>
                                    🇬🇧 GBP – Britisches Pfund
                                </option>

                                <option value="CHF" @selected(old('currency') === 'CHF')>
                                    🇨🇭 CHF – Schweizer Franken
                                </option>

                            </select>

                        </div>

                    </div>

                </div>


                {{-- STARTSALDO --}}

                <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 p-6 sm:p-8">

                    <div class="mb-6">

                        <h3 class="font-semibold text-slate-900 dark:text-white">
                            Startsaldo
                        </h3>

                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Der Kontostand, mit dem Finanzblick starten soll.
                        </p>

                    </div>


                    <div>

                        <label
                            for="opening_balance"
                            class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                        >
                            Eröffnungssaldo
                        </label>

                        <div class="relative">

                            <input
                                type="number"
                                id="opening_balance"
                                name="opening_balance"
                                step="0.01"
                                value="{{ old('opening_balance', '0.00') }}"
                                required
                                class="w-full rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-5 py-4 pr-14 text-2xl font-semibold focus:outline-none focus:ring-2 focus:ring-slate-200 dark:focus:ring-slate-600"
                            >

                            <span class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-400 font-medium">
                                €
                            </span>

                        </div>

                    </div>

                </div>


                {{-- DETAILS --}}

                <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 p-6 sm:p-8">

                    <div class="mb-6">

                        <h3 class="font-semibold text-slate-900 dark:text-white">
                            Weitere Angaben
                        </h3>

                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Optionale Informationen.
                        </p>

                    </div>


                    <div class="space-y-6">


                        {{-- IBAN --}}

                        <div>

                            <label
                                for="iban"
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                            >
                                IBAN
                                <span class="font-normal text-slate-400">
                                    (optional)
                                </span>
                            </label>

                            <input
                                type="text"
                                id="iban"
                                name="iban"
                                value="{{ old('iban') }}"
                                placeholder="DE00 0000 0000 0000 0000 00"
                                autocomplete="off"
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder:text-slate-400 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-slate-200 dark:focus:ring-slate-600"
                            >

                        </div>


                        {{-- KONTO NUMMER --}}

                        <div>

                            <label
                                for="account_number"
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                            >
                                Kontonummer
                                <span class="font-normal text-slate-400">
                                    (optional)
                                </span>
                            </label>

                            <input
                                type="text"
                                id="account_number"
                                name="account_number"
                                value="{{ old('account_number') }}"
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder:text-slate-400 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-slate-200 dark:focus:ring-slate-600"
                            >

                        </div>


                        {{-- NOTIZEN --}}

                        <div>

                            <label
                                for="notes"
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                            >
                                Notizen
                                <span class="font-normal text-slate-400">
                                    (optional)
                                </span>
                            </label>

                            <textarea
                                id="notes"
                                name="notes"
                                rows="4"
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder:text-slate-400 px-4 py-3 resize-none focus:outline-none focus:ring-2 focus:ring-slate-200 dark:focus:ring-slate-600"
                            >{{ old('notes') }}</textarea>

                        </div>

                    </div>

                </div>

            </div>


            {{-- ================================================= --}}
            {{-- SIDEBAR --}}
            {{-- ================================================= --}}

            <div class="space-y-6">


                {{-- DARSTELLUNG --}}

                <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 p-6">

                    <h3 class="font-semibold text-slate-900 dark:text-white">
                        Darstellung
                    </h3>

                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 mb-5">
                        Icon und Farbe des Kontos.
                    </p>


                    {{-- ICON --}}

                    <div>

                        <label
                            for="icon"
                            class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                        >
                            Icon
                        </label>

                        <div class="flex gap-3">

                            <div
                                id="icon-preview"
                                class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-2xl"
                            >
                                {{ old('icon', '🏦') }}
                            </div>

                            <input
                                type="text"
                                id="icon"
                                name="icon"
                                value="{{ old('icon', '🏦') }}"
                                maxlength="10"
                                class="min-w-0 w-0 flex-1 box-border max-w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-4 py-3 text-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                            >

                        </div>

                    </div>


                    {{-- FARBE --}}

                    <div class="mt-5">

                        <label
                            for="color"
                            class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                        >
                            Farbe
                        </label>

                        <input
                            type="color"
                            id="color"
                            name="color"
                            value="{{ old('color', '#f1f5f9') }}"
                            class="w-full h-12 rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer bg-white dark:bg-slate-800"
                        >

                    </div>

                </div>


                {{-- OPTIONEN --}}

                <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 p-6">

                    <h3 class="font-semibold text-slate-900 dark:text-white">
                        Optionen
                    </h3>

                    <div class="space-y-4 mt-5">


                        <label class="flex items-start gap-3 cursor-pointer">

                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                checked
                                class="w-5 h-5 mt-0.5 rounded border-slate-300 dark:border-slate-600"
                            >

                            <span>

                                <span class="block text-sm font-medium text-slate-900 dark:text-white">
                                    Konto aktiv
                                </span>

                                <span class="block text-xs text-slate-500 dark:text-slate-400 mt-1">
                                    Das Konto kann für neue Buchungen verwendet werden.
                                </span>

                            </span>

                        </label>


                        <label class="flex items-start gap-3 cursor-pointer">

                            <input
                                type="checkbox"
                                name="include_in_total"
                                value="1"
                                checked
                                class="w-5 h-5 mt-0.5 rounded border-slate-300 dark:border-slate-600"
                            >

                            <span>

                                <span class="block text-sm font-medium text-slate-900 dark:text-white">
                                    Im Gesamtvermögen
                                </span>

                                <span class="block text-xs text-slate-500 dark:text-slate-400 mt-1">
                                    Der Kontostand wird beim Gesamtvermögen berücksichtigt.
                                </span>

                            </span>

                        </label>

                    </div>

                </div>

            </div>

        </div>


        {{-- AKTIONEN --}}

        <div
            class="mt-6 bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 p-5 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-3"
        >

            <a
                href="{{ route('accounts.index') }}"
                class="inline-flex items-center justify-center rounded-xl border border-slate-200 dark:border-slate-700 px-5 py-3 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition"
            >
                Abbrechen
            </a>

            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-xl bg-slate-950 dark:bg-white px-6 py-3 text-sm font-medium text-white dark:text-slate-900 hover:bg-slate-800 dark:hover:bg-slate-200 transition"
            >
                Konto erstellen
            </button>

        </div>

    </form>

</div>


<script>

document.addEventListener('DOMContentLoaded', function () {

    const iconInput = document.getElementById('icon');
    const iconPreview = document.getElementById('icon-preview');

    if (!iconInput || !iconPreview) {
        return;
    }

    iconInput.addEventListener('input', function () {

        iconPreview.textContent =
            this.value.trim() || '🏦';

    });

});

</script>