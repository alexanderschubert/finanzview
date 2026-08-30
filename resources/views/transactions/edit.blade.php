@extends('layouts.app')

@section('title', 'Buchung bearbeiten – Finanzblick')

@section('eyebrow', 'Finanzverwaltung')

@section('page_title', 'Buchung bearbeiten')

@section('content')

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <div class="mb-6">

        <a
            href="{{ route('transactions.index') }}"
            class="text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition"
        >
            ← Buchungen
        </a>

        <div class="mt-4">

            <h2 class="text-2xl sm:text-3xl font-semibold text-slate-900 dark:text-white">
                Buchung bearbeiten
            </h2>

            <p class="text-slate-500 dark:text-slate-400 mt-1">
                Ändere die Angaben dieser Buchung.
            </p>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- FEHLER --}}
    {{-- ========================================================= --}}

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

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif


    {{-- ========================================================= --}}
    {{-- FORMULAR --}}
    {{-- ========================================================= --}}

    <form
        method="POST"
        action="{{ route('transactions.update', $transaction) }}"
        id="transaction-edit-form"
    >

        @csrf

        @method('PUT')


        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">


            {{-- ================================================= --}}
            {{-- HAUPTBEREICH --}}
            {{-- ================================================= --}}

            <div class="lg:col-span-2 space-y-6 min-w-0">


                {{-- ================================================= --}}
                {{-- BUCHUNG --}}
                {{-- ================================================= --}}

                <div
                    class="
                        bg-white dark:bg-slate-900
                        rounded-3xl
                        shadow-sm
                        border border-slate-100 dark:border-slate-800
                        p-6 sm:p-8
                    "
                >

                    <div class="flex items-center justify-between mb-6">

                        <div>

                            <h3 class="font-semibold text-slate-900 dark:text-white">
                                Buchung
                            </h3>

                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                                Art und Betrag
                            </p>

                        </div>


                        <div
                            class="
                                w-10
                                h-10
                                rounded-xl
                                bg-slate-100 dark:bg-slate-800
                                flex
                                items-center
                                justify-center
                            "
                        >
                            💳
                        </div>

                    </div>


                    {{-- ================================================= --}}
                    {{-- ART --}}
                    {{-- ================================================= --}}

                    <div>

                        <label
                            class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                        >
                            Art
                        </label>


                        <div class="grid grid-cols-2 gap-3">


                            {{-- AUSGABE --}}

                            <label class="cursor-pointer">

                                <input
                                    type="radio"
                                    name="type"
                                    value="expense"
                                    class="peer sr-only"
                                    @checked(
                                        old('type', $transaction->type) === 'expense'
                                    )
                                >

                                <div
                                    class="
                                        rounded-2xl
                                        border-2
                                        border-slate-200 dark:border-slate-700
                                        bg-white dark:bg-slate-800
                                        p-4
                                        transition
                                        hover:bg-slate-50 dark:hover:bg-slate-700
                                        peer-checked:border-red-500
                                        peer-checked:bg-red-950/30
                                    "
                                >

                                    <div class="text-2xl">
                                        ↘️
                                    </div>

                                    <p class="font-medium text-slate-900 dark:text-white mt-2">
                                        Ausgabe
                                    </p>

                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                        Geld wird ausgegeben
                                    </p>

                                </div>

                            </label>


                            {{-- EINNAHME --}}

                            <label class="cursor-pointer">

                                <input
                                    type="radio"
                                    name="type"
                                    value="income"
                                    class="peer sr-only"
                                    @checked(
                                        old('type', $transaction->type) === 'income'
                                    )
                                >

                                <div
                                    class="
                                        rounded-2xl
                                        border-2
                                        border-slate-200 dark:border-slate-700
                                        bg-white dark:bg-slate-800
                                        p-4
                                        transition
                                        hover:bg-slate-50 dark:hover:bg-slate-700
                                        peer-checked:border-emerald-500
                                        peer-checked:bg-emerald-950/30
                                    "
                                >

                                    <div class="text-2xl">
                                        ↗️
                                    </div>

                                    <p class="font-medium text-slate-900 dark:text-white mt-2">
                                        Einnahme
                                    </p>

                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                        Geld kommt hinzu
                                    </p>

                                </div>

                            </label>

                        </div>

                    </div>


                    {{-- ================================================= --}}
                    {{-- BETRAG --}}
                    {{-- ================================================= --}}

                    <div class="mt-6">

                        <label
                            for="amount"
                            class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                        >
                            Betrag
                        </label>


                        <div class="relative min-w-0">

                            <input
                                type="number"
                                id="amount"
                                name="amount"
                                step="0.01"
                                min="0.01"
                                value="{{ old('amount', $transaction->amount) }}"
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
                                    placeholder-slate-400
                                    focus:outline-none
                                    focus:ring-2
                                    focus:ring-emerald-500/20
                                    focus:border-emerald-500
                                "
                            >

                            <span
                                class="
                                    absolute
                                    right-5
                                    top-1/2
                                    -translate-y-1/2
                                    text-slate-400
                                    font-medium
                                "
                            >
                                €
                            </span>

                        </div>

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- DETAILS --}}
                {{-- ================================================= --}}

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
                            Details
                        </h3>

                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Weitere Informationen zur Buchung
                        </p>

                    </div>


                    <div class="space-y-6">


                        {{-- BESCHREIBUNG --}}

                        <div>

                            <label
                                for="description"
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                            >
                                Beschreibung
                            </label>

                            <input
                                type="text"
                                id="description"
                                name="description"
                                value="{{ old(
                                    'description',
                                    $transaction->description
                                ) }}"
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
                                    placeholder-slate-400
                                    focus:outline-none
                                    focus:ring-2
                                    focus:ring-emerald-500/20
                                    focus:border-emerald-500
                                "
                            >

                        </div>


                        {{-- HÄNDLER --}}

                        <div>

                            <label
                                for="merchant"
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                            >
                                Händler

                                <span class="font-normal text-slate-400">
                                    (optional)
                                </span>

                            </label>

                            <input
                                type="text"
                                id="merchant"
                                name="merchant"
                                value="{{ old(
                                    'merchant',
                                    $transaction->merchant
                                ) }}"
                                placeholder="z. B. REWE"
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
                                placeholder="Zusätzliche Informationen..."
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
                            >{{ old('notes', $transaction->notes) }}</textarea>

                        </div>

                    </div>

                </div>

            </div>


            {{-- ================================================= --}}
            {{-- SIDEBAR --}}
            {{-- ================================================= --}}

            <div class="space-y-6 min-w-0">


                {{-- ================================================= --}}
                {{-- KONTO --}}
                {{-- ================================================= --}}

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
                        Konto
                    </h3>

                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 mb-4">
                        Wo wurde die Buchung erfasst?
                    </p>


                    <select
                        name="account_id"
                        id="account_id"
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

                        <option value="">
                            Konto auswählen
                        </option>

                        @foreach ($accounts as $account)

                            <option
                                value="{{ $account->id }}"
                                @selected(
                                    old(
                                        'account_id',
                                        $transaction->account_id
                                    ) == $account->id
                                )
                            >

                                {{ $account->icon ?: '🏦' }}
                                {{ $account->name }}

                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- ================================================= --}}
                {{-- KATEGORIE --}}
                {{-- ================================================= --}}

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
                        Kategorie
                    </h3>

                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 mb-4">
                        Ordne die Buchung einer Kategorie zu.
                    </p>


                    <select
                        name="category_id"
                        id="category_id"
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

                        <option value="">
                            Keine Kategorie
                        </option>


                        @foreach ($categories as $category)

                            <option
                                value="{{ $category->id }}"
                                data-type="{{ $category->type }}"
                                data-name="{{ $category->name }}"
                                data-icon="{{ $category->icon ?: '📁' }}"
                                @selected(
                                    old(
                                        'category_id',
                                        $transaction->category_id
                                    ) == $category->id
                                )
                            >

                                {{ $category->icon ?: '📁' }}
                                {{ $category->name }}

                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- ================================================= --}}
                {{-- DATUM --}}
                {{-- ================================================= --}}

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
                        Datum
                    </h3>

                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 mb-4">
                        Wann wurde die Buchung durchgeführt?
                    </p>


                    <input
                        type="date"
                        name="transaction_date"
                        value="{{ old(
                            'transaction_date',
                            optional($transaction->transaction_date)->format('Y-m-d')
                        ) }}"
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

                </div>


                {{-- ================================================= --}}
                {{-- STATUS --}}
                {{-- ================================================= --}}

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

                    <label class="flex items-start gap-3 cursor-pointer">

                        <input
                            type="checkbox"
                            name="is_pending"
                            value="1"
                            @checked(
                                old(
                                    'is_pending',
                                    $transaction->is_pending
                                )
                            )
                            class="
                                w-5
                                h-5
                                mt-0.5
                                rounded
                                border-slate-300 dark:border-slate-600
                                bg-white dark:bg-slate-800
                                text-emerald-600
                                focus:ring-emerald-500
                                flex-shrink-0
                            "
                        >

                        <span class="min-w-0">

                            <span class="block text-sm font-medium text-slate-900 dark:text-white">
                                Ausstehend
                            </span>

                            <span class="block text-xs text-slate-500 dark:text-slate-400 mt-1">
                                Buchung ist noch nicht endgültig gebucht.
                            </span>

                        </span>

                    </label>

                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- AKTIONEN --}}
        {{-- ========================================================= --}}

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
                flex
                flex-col
                sm:flex-row
                sm:items-center
                sm:justify-between
                gap-3
            "
        >

            {{-- LÖSCHEN --}}

            <button
                type="button"
                onclick="deleteTransaction()"
                class="
                    w-full
                    sm:w-auto
                    inline-flex
                    items-center
                    justify-center
                    rounded-xl
                    px-5 py-3
                    text-sm
                    font-medium
                    text-red-600 dark:text-red-400
                    hover:bg-red-50 dark:hover:bg-red-950/30
                    transition
                "
            >
                Buchung löschen
            </button>


            {{-- RECHTS --}}

            <div class="flex flex-col sm:flex-row gap-3">

                <a
                    href="{{ route('transactions.index') }}"
                    class="
                        inline-flex
                        items-center
                        justify-center
                        rounded-xl
                        border border-slate-200 dark:border-slate-700
                        bg-white dark:bg-slate-800
                        px-5 py-3
                        text-sm
                        font-medium
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
                        inline-flex
                        items-center
                        justify-center
                        rounded-xl
                        bg-slate-950 dark:bg-white
                        px-6 py-3
                        text-sm
                        font-medium
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


{{-- ========================================================= --}}
{{-- LÖSCH-FORMULAR --}}
{{-- ========================================================= --}}

<form
    id="delete-transaction-form"
    method="POST"
    action="{{ route('transactions.destroy', $transaction) }}"
    class="hidden"
>

    @csrf

    @method('DELETE')

</form>


{{-- ========================================================= --}}
{{-- JAVASCRIPT --}}
{{-- ========================================================= --}}

<script>

document.addEventListener('DOMContentLoaded', function () {

    const typeInputs =
        document.querySelectorAll(
            'input[name="type"]'
        );

    const categorySelect =
        document.getElementById(
            'category_id'
        );

    if (!typeInputs.length || !categorySelect) {
        return;
    }


    const categories =
        Array.from(
            categorySelect.options
        )
        .filter(option => option.dataset.type)
        .map(option => ({
            id: option.value,
            type: option.dataset.type,
            name: option.dataset.name,
            icon: option.dataset.icon
        }));


    const initialCategory =
        categorySelect.value;


    function getSelectedType()
    {

        const selected =
            document.querySelector(
                'input[name="type"]:checked'
            );

        return selected
            ? selected.value
            : 'expense';

    }


    function updateCategories()
    {

        const selectedType =
            getSelectedType();


        const currentValue =
            categorySelect.value ||
            initialCategory;


        categorySelect.innerHTML = '';


        const emptyOption =
            document.createElement(
                'option'
            );

        emptyOption.value = '';

        emptyOption.textContent =
            'Keine Kategorie';

        categorySelect.appendChild(
            emptyOption
        );


        categories.forEach(category => {

            const matches =
                category.type === selectedType ||
                category.type === 'both';


            if (!matches) {
                return;
            }


            const option =
                document.createElement(
                    'option'
                );

            option.value =
                category.id;

            option.textContent =
                category.icon +
                ' ' +
                category.name;

            categorySelect.appendChild(
                option
            );

        });


        const matchingOption =
            Array.from(
                categorySelect.options
            ).find(
                option =>
                    option.value === currentValue
            );


        if (matchingOption) {

            categorySelect.value =
                currentValue;

        } else {

            categorySelect.value =
                '';

        }

    }


    typeInputs.forEach(input => {

        input.addEventListener(
            'change',
            updateCategories
        );

    });


    updateCategories();

});


function deleteTransaction()
{

    const confirmed =
        confirm(
            'Möchtest du diese Buchung wirklich löschen?'
        );


    if (!confirmed) {
        return;
    }


    document
        .getElementById(
            'delete-transaction-form'
        )
        .submit();

}

</script>

@endsection