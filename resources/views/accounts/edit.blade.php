@extends('layouts.app')

@section('title', 'Konto bearbeiten – Finanzblick')

@section('eyebrow', 'Finanzverwaltung')

@section('page_title', 'Konto bearbeiten')

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

        <div class="flex items-center gap-4 mt-4">

            <div
                id="header-icon"
                class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl flex-shrink-0"
                style="background-color: {{ $account->color ?: '#f1f5f9' }}"
            >
                {{ $account->icon ?: '🏦' }}
            </div>

            <div class="min-w-0">

                <h2 class="text-2xl sm:text-3xl font-semibold text-slate-900 dark:text-white">
                    Konto bearbeiten
                </h2>

                <p class="text-slate-500 dark:text-slate-400 mt-1 truncate">
                    {{ $account->name }}
                </p>

            </div>

        </div>

    </div>


    {{-- FEHLER --}}

    @if ($errors->any())

        <div
            class="
                mb-6
                rounded-2xl
                bg-red-50 dark:bg-red-950/40
                border border-red-100 dark:border-red-900
                p-4
                text-sm
                text-red-700 dark:text-red-300
            "
        >

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


    {{-- FORMULAR --}}

    <form
        method="POST"
        action="{{ route('accounts.update', $account) }}"
    >

        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">


            {{-- HAUPTBEREICH --}}

            <div class="lg:col-span-2 space-y-6 min-w-0">


                {{-- KONTOINFORMATIONEN --}}

                <div
                    class="
                        bg-white dark:bg-slate-900
                        rounded-3xl
                        shadow-sm
                        border border-slate-100 dark:border-slate-800
                        p-6 sm:p-8
                    "
                >

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
                                value="{{ old('name', $account->name) }}"
                                required
                                autofocus
                                class="
                                    box-border
                                    w-full
                                    min-w-0
                                    rounded-xl
                                    border border-slate-200 dark:border-slate-700
                                    bg-white dark:bg-slate-800
                                    text-slate-900 dark:text-white
                                    px-4 py-3
                                    placeholder-slate-400
                                    focus:outline-none
                                    focus:ring-2
                                    focus:ring-emerald-500/20
                                    focus:border-emerald-500
                                "
                            >

                        </div>


                        {{-- INSTITUTION --}}

                        <div>

                            <label
                                for="institution"
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                            >
                                Bank / Anbieter
                                <span class="font-normal text-slate-400">
                                    (optional)
                                </span>
                            </label>

                            <input
                                type="text"
                                id="institution"
                                name="institution"
                                value="{{ old('institution', $account->institution) }}"
                                placeholder="z. B. ING, DKB, PayPal"
                                class="
                                    box-border
                                    w-full
                                    min-w-0
                                    rounded-xl
                                    border border-slate-200 dark:border-slate-700
                                    bg-white dark:bg-slate-800
                                    text-slate-900 dark:text-white
                                    px-4 py-3
                                    placeholder-slate-400
                                    focus:outline-none
                                    focus:ring-2
                                    focus:ring-emerald-500/20
                                    focus:border-emerald-500
                                "
                            >

                        </div>


                        {{-- KONTOART --}}

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
                                class="
                                    box-border
                                    w-full
                                    min-w-0
                                    rounded-xl
                                    border border-slate-200 dark:border-slate-700
                                    bg-white dark:bg-slate-800
                                    text-slate-900 dark:text-white
                                    px-4 py-3
                                    focus:outline-none
                                    focus:ring-2
                                    focus:ring-emerald-500/20
                                    focus:border-emerald-500
                                "
                            >

                                <option value="checking" @selected(old('type', $account->type) === 'checking')>
                                    Girokonto
                                </option>

                                <option value="savings" @selected(old('type', $account->type) === 'savings')>
                                    Sparkonto
                                </option>

                                <option value="credit_card" @selected(old('type', $account->type) === 'credit_card')>
                                    Kreditkarte
                                </option>

                                <option value="paypal" @selected(old('type', $account->type) === 'paypal')>
                                    PayPal
                                </option>

                                <option value="cash" @selected(old('type', $account->type) === 'cash')>
                                    Bargeld
                                </option>

                                <option value="investment" @selected(old('type', $account->type) === 'investment')>
                                    Investment
                                </option>

                                <option value="loan" @selected(old('type', $account->type) === 'loan')>
                                    Kredit
                                </option>

                                <option value="other" @selected(old('type', $account->type) === 'other')>
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
                                class="
                                    box-border
                                    w-full
                                    min-w-0
                                    rounded-xl
                                    border border-slate-200 dark:border-slate-700
                                    bg-white dark:bg-slate-800
                                    text-slate-900 dark:text-white
                                    px-4 py-3
                                    focus:outline-none
                                    focus:ring-2
                                    focus:ring-emerald-500/20
                                    focus:border-emerald-500
                                "
                            >

                                <option value="EUR" @selected(old('currency', $account->currency) === 'EUR')>
                                    🇪🇺 EUR – Euro
                                </option>

                                <option value="USD" @selected(old('currency', $account->currency) === 'USD')>
                                    🇺🇸 USD – US-Dollar
                                </option>

                                <option value="GBP" @selected(old('currency', $account->currency) === 'GBP')>
                                    🇬🇧 GBP – Britisches Pfund
                                </option>

                                <option value="CHF" @selected(old('currency', $account->currency) === 'CHF')>
                                    🇨🇭 CHF – Schweizer Franken
                                </option>

                            </select>

                        </div>

                    </div>

                </div>


                {{-- STARTSALDO --}}

                <div
                    class="
                        bg-white dark:bg-slate-900
                        rounded-3xl
                        shadow-sm
                        border border-slate-100 dark:border-slate-800
                        p-6 sm:p-8
                    "
                >

                    <div class="mb-6">

                        <h3 class="font-semibold text-slate-900 dark:text-white">
                            Startsaldo
                        </h3>

                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Ausgangssaldo des Kontos.
                        </p>

                    </div>

                    <div>

                        <label
                            for="opening_balance"
                            class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                        >
                            Eröffnungssaldo
                        </label>

                        <div class="relative min-w-0">

                            <input
                                type="number"
                                id="opening_balance"
                                name="opening_balance"
                                step="0.01"
                                value="{{ old('opening_balance', $account->opening_balance) }}"
                                required
                                class="
                                    box-border
                                    w-full
                                    min-w-0
                                    rounded-2xl
                                    border border-slate-200 dark:border-slate-700
                                    bg-white dark:bg-slate-800
                                    text-slate-900 dark:text-white
                                    px-5 py-4 pr-14
                                    text-2xl font-semibold
                                    focus:outline-none
                                    focus:ring-2
                                    focus:ring-emerald-500/20
                                    focus:border-emerald-500
                                "
                            >

                            <span
                                class="
                                    absolute right-5 top-1/2 -translate-y-1/2
                                    text-slate-400
                                    font-medium
                                "
                            >
                                {{ $account->currency }}
                            </span>

                        </div>

                        <p class="text-xs text-slate-400 mt-2">
                            Bereits erfasste Buchungen werden zusätzlich berücksichtigt.
                        </p>

                    </div>

                </div>


                {{-- BANKDATEN --}}

                <div
                    class="
                        bg-white dark:bg-slate-900
                        rounded-3xl
                        shadow-sm
                        border border-slate-100 dark:border-slate-800
                        p-6 sm:p-8
                    "
                >

                    <div class="mb-6">

                        <h3 class="font-semibold text-slate-900 dark:text-white">
                            Kontodaten
                        </h3>

                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Optionale Angaben zum Konto.
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
                                value="{{ old('iban', $account->iban) }}"
                                autocomplete="off"
                                placeholder="DE00 0000 0000 0000 0000 00"
                                class="
                                    box-border
                                    w-full
                                    min-w-0
                                    rounded-xl
                                    border border-slate-200 dark:border-slate-700
                                    bg-white dark:bg-slate-800
                                    text-slate-900 dark:text-white
                                    px-4 py-3
                                    placeholder-slate-400
                                    focus:outline-none
                                    focus:ring-2
                                    focus:ring-emerald-500/20
                                    focus:border-emerald-500
                                "
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
                                value="{{ old('account_number', $account->account_number) }}"
                                class="
                                    box-border
                                    w-full
                                    min-w-0
                                    rounded-xl
                                    border border-slate-200 dark:border-slate-700
                                    bg-white dark:bg-slate-800
                                    text-slate-900 dark:text-white
                                    px-4 py-3
                                    placeholder-slate-400
                                    focus:outline-none
                                    focus:ring-2
                                    focus:ring-emerald-500/20
                                    focus:border-emerald-500
                                "
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
                                class="
                                    box-border
                                    w-full
                                    min-w-0
                                    rounded-xl
                                    border border-slate-200 dark:border-slate-700
                                    bg-white dark:bg-slate-800
                                    text-slate-900 dark:text-white
                                    px-4 py-3
                                    resize-none
                                    placeholder-slate-400
                                    focus:outline-none
                                    focus:ring-2
                                    focus:ring-emerald-500/20
                                    focus:border-emerald-500
                                "
                            >{{ old('notes', $account->notes) }}</textarea>

                        </div>

                    </div>

                </div>

            </div>


            {{-- SIDEBAR --}}

            <div class="space-y-6 min-w-0">


                {{-- DARSTELLUNG --}}

                <div
                    class="
                        w-full
                        min-w-0
                        box-border
                        bg-white dark:bg-slate-900
                        rounded-3xl
                        shadow-sm
                        border border-slate-100 dark:border-slate-800
                        p-6
                    "
                >

                    <h3 class="font-semibold text-slate-900 dark:text-white">
                        Darstellung
                    </h3>

                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 mb-5">
                        Icon und Farbe des Kontos.
                    </p>


                    {{-- ICON --}}

                    <div class="min-w-0 w-full">

                        <label
                            for="icon"
                            class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                        >
                            Icon
                        </label>

                        <div class="flex gap-3 min-w-0 w-full">

                            <div
                                id="icon-preview"
                                class="
                                    w-12
                                    h-12
                                    rounded-xl
                                    flex
                                    items-center
                                    justify-center
                                    text-xl
                                    flex-shrink-0
                                    bg-slate-100
                                    dark:bg-slate-800
                                "
                                style="
                                    background-color:
                                    {{ old('color', $account->color ?: '#f1f5f9') }};
                                "
                            >
                                {{ old('icon', $account->icon ?: '🏦') }}
                            </div>

                            <input
                                type="text"
                                id="icon"
                                name="icon"
                                value="{{ old('icon', $account->icon ?: '🏦') }}"
                                maxlength="10"
                                class="
                                    min-w-0
                                    w-0
                                    flex-1
                                    box-border
                                    max-w-full
                                    rounded-xl
                                    border border-slate-200 dark:border-slate-700
                                    bg-white dark:bg-slate-800
                                    text-slate-900 dark:text-white
                                    px-4 py-3
                                    text-xl
                                    focus:outline-none
                                    focus:ring-2
                                    focus:ring-emerald-500/20
                                    focus:border-emerald-500
                                "
                            >

                        </div>

                    </div>


                    {{-- FARBE --}}

                    <div class="mt-5 min-w-0">

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
                            value="{{ old('color', $account->color ?: '#f1f5f9') }}"
                            class="
                                box-border
                                block
                                w-full
                                max-w-full
                                h-12
                                rounded-xl
                                border border-slate-200 dark:border-slate-700
                                bg-white dark:bg-slate-800
                                cursor-pointer
                            "
                        >

                    </div>

                </div>


                {{-- OPTIONEN --}}

                <div
                    class="
                        w-full
                        min-w-0
                        box-border
                        bg-white dark:bg-slate-900
                        rounded-3xl
                        shadow-sm
                        border border-slate-100 dark:border-slate-800
                        p-6
                    "
                >

                    <h3 class="font-semibold text-slate-900 dark:text-white">
                        Optionen
                    </h3>

                    <div class="space-y-4 mt-5">

                        {{-- AKTIV --}}

                        <label class="flex items-start gap-3 cursor-pointer">

                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                @checked(old('is_active', $account->is_active))
                                class="
                                    w-5 h-5 mt-0.5 rounded
                                    border-slate-300 dark:border-slate-600
                                    bg-white dark:bg-slate-800
                                    text-emerald-600
                                    focus:ring-emerald-500
                                    flex-shrink-0
                                "
                            >

                            <span class="min-w-0">

                                <span class="block text-sm font-medium text-slate-900 dark:text-white">
                                    Konto aktiv
                                </span>

                                <span class="block text-xs text-slate-500 dark:text-slate-400 mt-1">
                                    Das Konto kann für neue Buchungen verwendet werden.
                                </span>

                            </span>

                        </label>


                        {{-- GESAMTVERMÖGEN --}}

                        <label class="flex items-start gap-3 cursor-pointer">

                            <input
                                type="checkbox"
                                name="include_in_total"
                                value="1"
                                @checked(old('include_in_total', $account->include_in_total))
                                class="
                                    w-5 h-5 mt-0.5 rounded
                                    border-slate-300 dark:border-slate-600
                                    bg-white dark:bg-slate-800
                                    text-emerald-600
                                    focus:ring-emerald-500
                                    flex-shrink-0
                                "
                            >

                            <span class="min-w-0">

                                <span class="block text-sm font-medium text-slate-900 dark:text-white">
                                    Im Gesamtvermögen
                                </span>

                                <span class="block text-xs text-slate-500 dark:text-slate-400 mt-1">
                                    Der Kontostand wird im Gesamtvermögen berücksichtigt.
                                </span>

                            </span>

                        </label>

                    </div>

                </div>


                {{-- AKTUELLER STATUS --}}

                <div
                    class="
                        w-full
                        box-border
                        rounded-3xl
                        bg-slate-950
                        text-white
                        p-6
                    "
                >

                    <p class="text-xs text-slate-400">
                        Aktueller Kontostand
                    </p>

                    <p class="text-2xl font-semibold mt-2">

                        {{ number_format(
                            $account->calculated_balance,
                            2,
                            ',',
                            '.'
                        ) }}

                        {{ $account->currency }}

                    </p>

                    <p class="text-xs text-slate-400 mt-2">
                        Wird automatisch aus Startsaldo und Buchungen berechnet.
                    </p>

                </div>

            </div>

        </div>


        {{-- AKTIONEN --}}

        <div
            class="
                mt-6
                w-full
                box-border
                bg-white dark:bg-slate-900
                rounded-3xl
                shadow-sm
                border border-slate-100 dark:border-slate-800
                p-5
                flex flex-col sm:flex-row
                sm:items-center sm:justify-between
                gap-3
            "
        >

            <button
                type="button"
                onclick="deleteAccount()"
                class="
                    w-full sm:w-auto
                    inline-flex items-center justify-center
                    rounded-xl
                    px-5 py-3
                    text-sm font-medium
                    text-red-600 dark:text-red-400
                    hover:bg-red-50 dark:hover:bg-red-950/30
                    transition
                "
            >
                Konto löschen
            </button>


            <div class="flex flex-col sm:flex-row gap-3">

                <a
                    href="{{ route('accounts.index') }}"
                    class="
                        inline-flex items-center justify-center
                        rounded-xl
                        border border-slate-200 dark:border-slate-700
                        bg-white dark:bg-slate-800
                        px-5 py-3
                        text-sm font-medium
                        text-slate-600 dark:text-slate-300
                        hover:bg-slate-50 dark:hover:bg-slate-700
                        transition
                    "
                >
                    Abbrechen
                </a>

                <button
                    type="submit"
                    class="
                        inline-flex items-center justify-center
                        rounded-xl
                        bg-slate-950 dark:bg-white
                        px-6 py-3
                        text-sm font-medium
                        text-white dark:text-slate-950
                        hover:bg-slate-800 dark:hover:bg-slate-200
                        transition
                    "
                >
                    Änderungen speichern
                </button>

            </div>

        </div>

    </form>

</div>


{{-- LÖSCH-FORMULAR --}}

<form
    id="delete-account-form"
    method="POST"
    action="{{ route('accounts.destroy', $account) }}"
    class="hidden"
>

    @csrf
    @method('DELETE')

</form>


{{-- JAVASCRIPT --}}

<script>

document.addEventListener('DOMContentLoaded', function () {

    const iconInput =
        document.getElementById('icon');

    const iconPreview =
        document.getElementById('icon-preview');

    const colorInput =
        document.getElementById('color');

    const headerIcon =
        document.getElementById('header-icon');


    if (iconInput && iconPreview) {

        iconInput.addEventListener(
            'input',
            function () {

                iconPreview.textContent =
                    this.value.trim() || '🏦';

                if (headerIcon) {

                    headerIcon.textContent =
                        this.value.trim() || '🏦';

                }

            }
        );

    }


    if (colorInput) {

        colorInput.addEventListener(
            'input',
            function () {

                if (iconPreview) {
                    iconPreview.style.backgroundColor =
                        this.value;
                }

                if (headerIcon) {
                    headerIcon.style.backgroundColor =
                        this.value;
                }

            }
        );

    }

});


function deleteAccount()
{

    const confirmed =
        confirm(
            'Möchtest du das Konto „{{ addslashes($account->name) }}“ wirklich löschen?'
        );

    if (!confirmed) {
        return;
    }

    document
        .getElementById('delete-account-form')
        .submit();

}

</script>

@endsection