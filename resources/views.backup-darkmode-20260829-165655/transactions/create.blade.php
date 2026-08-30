@extends('layouts.app')

@section('title', 'Neue Buchung – Finanzblick')

@section('eyebrow', 'Finanzverwaltung')

@section('page_title', 'Neue Buchung')


@section('content')

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">


    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <div class="mb-6">

        <a
            href="{{ route('transactions.index') }}"
            class="text-sm text-slate-500 hover:text-slate-900"
        >
            ← Buchungen
        </a>


        <div class="mt-4">

            <h2 class="text-2xl sm:text-3xl font-semibold text-slate-900">
                Neue Buchung
            </h2>

            <p class="text-slate-500 mt-1">
                Erfasse eine Einnahme oder Ausgabe.
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
                bg-red-50
                border
                border-red-100
                p-4
                text-sm
                text-red-700
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



    @if ($accounts->isEmpty())

        {{-- ===================================================== --}}
        {{-- KEIN KONTO --}}
        {{-- ===================================================== --}}

        <div
            class="
                bg-white
                rounded-3xl
                shadow-sm
                border
                border-slate-100
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
                    flex
                    items-center
                    justify-center
                    text-3xl
                "
            >
                🏦
            </div>


            <h2 class="text-lg font-semibold text-slate-900 mt-5">
                Noch kein Konto vorhanden
            </h2>


            <p class="text-sm text-slate-500 mt-2 max-w-md mx-auto">
                Bevor du eine Buchung erfassen kannst,
                musst du mindestens ein Konto erstellen.
            </p>


            <a
                href="{{ route('accounts.create') }}"
                class="
                    inline-flex
                    items-center
                    justify-center
                    mt-6
                    rounded-xl
                    bg-slate-950
                    px-5
                    py-3
                    text-sm
                    font-medium
                    text-white
                    hover:bg-slate-800
                "
            >
                Konto erstellen
            </a>

        </div>


    @else


        {{-- ===================================================== --}}
        {{-- FORMULAR --}}
        {{-- ===================================================== --}}

        <form
            method="POST"
            action="{{ route('transactions.store') }}"
            id="transaction-form"
        >

            @csrf


            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">


                {{-- ================================================= --}}
                {{-- HAUPTBEREICH --}}
                {{-- ================================================= --}}

                <div class="lg:col-span-2 space-y-6">


                    {{-- ============================================= --}}
                    {{-- ART + BETRAG --}}
                    {{-- ============================================= --}}

                    <div
                        class="
                            bg-white
                            rounded-3xl
                            shadow-sm
                            border
                            border-slate-100
                            p-6
                            sm:p-8
                        "
                    >

                        <div class="flex items-center justify-between mb-6">

                            <div>

                                <h3 class="font-semibold text-slate-900">
                                    Buchung
                                </h3>

                                <p class="text-sm text-slate-500 mt-1">
                                    Grundlegende Angaben
                                </p>

                            </div>


                            <div
                                class="
                                    w-10
                                    h-10
                                    rounded-xl
                                    bg-slate-100
                                    flex
                                    items-center
                                    justify-center
                                "
                            >
                                💳
                            </div>

                        </div>



                        {{-- ART --}}

                        <div>

                            <label
                                for="transaction_type"
                                class="block text-sm font-medium text-slate-700 mb-2"
                            >
                                Art
                            </label>


                            <div class="grid grid-cols-2 gap-3">

                                <label class="cursor-pointer">

                                    <input
                                        type="radio"
                                        name="type"
                                        value="expense"
                                        class="peer sr-only"
                                        @checked(old('type', 'expense') === 'expense')
                                    >

                                    <div
                                        class="
                                            rounded-2xl
                                            border-2
                                            border-slate-200
                                            p-4
                                            transition
                                            peer-checked:border-red-500
                                            peer-checked:bg-red-50
                                        "
                                    >

                                        <div class="text-2xl">
                                            ↘️
                                        </div>

                                        <p class="font-medium text-slate-900 mt-2">
                                            Ausgabe
                                        </p>

                                        <p class="text-xs text-slate-500 mt-1">
                                            Geld wird ausgegeben
                                        </p>

                                    </div>

                                </label>


                                <label class="cursor-pointer">

                                    <input
                                        type="radio"
                                        name="type"
                                        value="income"
                                        class="peer sr-only"
                                        @checked(old('type') === 'income')
                                    >

                                    <div
                                        class="
                                            rounded-2xl
                                            border-2
                                            border-slate-200
                                            p-4
                                            transition
                                            peer-checked:border-emerald-500
                                            peer-checked:bg-emerald-50
                                        "
                                    >

                                        <div class="text-2xl">
                                            ↗️
                                        </div>

                                        <p class="font-medium text-slate-900 mt-2">
                                            Einnahme
                                        </p>

                                        <p class="text-xs text-slate-500 mt-1">
                                            Geld kommt hinzu
                                        </p>

                                    </div>

                                </label>

                            </div>

                        </div>



                        {{-- BETRAG --}}

                        <div class="mt-6">

                            <label
                                for="amount"
                                class="block text-sm font-medium text-slate-700 mb-2"
                            >
                                Betrag
                            </label>


                            <div class="relative">

                                <input
                                    type="number"
                                    id="amount"
                                    name="amount"
                                    step="0.01"
                                    min="0.01"
                                    value="{{ old('amount') }}"
                                    required
                                    placeholder="0,00"
                                    class="
                                        w-full
                                        rounded-2xl
                                        border
                                        border-slate-200
                                        px-5
                                        py-4
                                        pr-14
                                        text-2xl
                                        font-semibold
                                        text-slate-900
                                        focus:outline-none
                                        focus:ring-2
                                        focus:ring-slate-200
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



                    {{-- ============================================= --}}
                    {{-- DETAILS --}}
                    {{-- ============================================= --}}

                    <div
                        class="
                            bg-white
                            rounded-3xl
                            shadow-sm
                            border
                            border-slate-100
                            p-6
                            sm:p-8
                        "
                    >

                        <div class="mb-6">

                            <h3 class="font-semibold text-slate-900">
                                Details
                            </h3>

                            <p class="text-sm text-slate-500 mt-1">
                                Weitere Informationen zur Buchung
                            </p>

                        </div>



                        <div class="space-y-6">


                            {{-- BESCHREIBUNG --}}

                            <div>

                                <label
                                    for="description"
                                    class="block text-sm font-medium text-slate-700 mb-2"
                                >
                                    Beschreibung
                                </label>

                                <input
                                    type="text"
                                    id="description"
                                    name="description"
                                    value="{{ old('description') }}"
                                    required
                                    placeholder="z. B. Einkauf REWE"
                                    class="
                                        w-full
                                        rounded-xl
                                        border
                                        border-slate-200
                                        px-4
                                        py-3
                                        focus:outline-none
                                        focus:ring-2
                                        focus:ring-slate-200
                                    "
                                >

                            </div>



                            {{-- HÄNDLER --}}

                            <div>

                                <label
                                    for="merchant"
                                    class="block text-sm font-medium text-slate-700 mb-2"
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
                                    value="{{ old('merchant') }}"
                                    placeholder="z. B. REWE"
                                    class="
                                        w-full
                                        rounded-xl
                                        border
                                        border-slate-200
                                        px-4
                                        py-3
                                        focus:outline-none
                                        focus:ring-2
                                        focus:ring-slate-200
                                    "
                                >

                            </div>



                            {{-- NOTIZEN --}}

                            <div>

                                <label
                                    for="notes"
                                    class="block text-sm font-medium text-slate-700 mb-2"
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
                                        w-full
                                        rounded-xl
                                        border
                                        border-slate-200
                                        px-4
                                        py-3
                                        resize-none
                                        focus:outline-none
                                        focus:ring-2
                                        focus:ring-slate-200
                                    "
                                >{{ old('notes') }}</textarea>

                            </div>


                        </div>

                    </div>

                </div>



                {{-- ================================================= --}}
                {{-- SEITENBEREICH --}}
                {{-- ================================================= --}}

                <div class="space-y-6">


                    {{-- ============================================= --}}
                    {{-- KONTO --}}
                    {{-- ============================================= --}}

                    <div
                        class="
                            bg-white
                            rounded-3xl
                            shadow-sm
                            border
                            border-slate-100
                            p-6
                        "
                    >

                        <h3 class="font-semibold text-slate-900">
                            Konto
                        </h3>

                        <p class="text-sm text-slate-500 mt-1 mb-4">
                            Wo wurde die Buchung erfasst?
                        </p>


                        <select
                            name="account_id"
                            id="account_id"
                            required
                            class="
                                w-full
                                rounded-xl
                                border
                                border-slate-200
                                bg-white
                                px-4
                                py-3
                                text-sm
                                focus:outline-none
                                focus:ring-2
                                focus:ring-slate-200
                            "
                        >

                            <option value="">
                                Konto auswählen
                            </option>

                            @foreach ($accounts as $account)

                                <option
                                    value="{{ $account->id }}"
                                    @selected(
                                        old('account_id') == $account->id
                                    )
                                >

                                    {{ $account->icon ?: '🏦' }}
                                    {{ $account->name }}

                                </option>

                            @endforeach

                        </select>

                    </div>



                    {{-- ============================================= --}}
                    {{-- KATEGORIE --}}
                    {{-- ============================================= --}}

                    <div
                        class="
                            bg-white
                            rounded-3xl
                            shadow-sm
                            border
                            border-slate-100
                            p-6
                        "
                    >

                        <h3 class="font-semibold text-slate-900">
                            Kategorie
                        </h3>

                        <p class="text-sm text-slate-500 mt-1 mb-4">
                            Ordne die Buchung einer Kategorie zu.
                        </p>


                        <select
                            name="category_id"
                            id="category_id"
                            class="
                                w-full
                                rounded-xl
                                border
                                border-slate-200
                                bg-white
                                px-4
                                py-3
                                text-sm
                                focus:outline-none
                                focus:ring-2
                                focus:ring-slate-200
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
                                        old('category_id') == $category->id
                                    )
                                >

                                    {{ $category->icon ?: '📁' }}
                                    {{ $category->name }}

                                </option>

                            @endforeach

                        </select>

                    </div>



                    {{-- ============================================= --}}
                    {{-- DATUM --}}
                    {{-- ============================================= --}}

                    <div
                        class="
                            bg-white
                            rounded-3xl
                            shadow-sm
                            border
                            border-slate-100
                            p-6
                        "
                    >

                        <h3 class="font-semibold text-slate-900">
                            Datum
                        </h3>

                        <p class="text-sm text-slate-500 mt-1 mb-4">
                            Wann wurde die Buchung durchgeführt?
                        </p>


                        <input
                            type="date"
                            name="transaction_date"
                            value="{{ old(
                                'transaction_date',
                                now()->format('Y-m-d')
                            ) }}"
                            required
                            class="
                                w-full
                                rounded-xl
                                border
                                border-slate-200
                                px-4
                                py-3
                                text-sm
                                focus:outline-none
                                focus:ring-2
                                focus:ring-slate-200
                            "
                        >

                    </div>



                    {{-- ============================================= --}}
                    {{-- STATUS --}}
                    {{-- ============================================= --}}

                    <label
                        class="
                            flex
                            items-start
                            gap-3
                            bg-white
                            rounded-3xl
                            shadow-sm
                            border
                            border-slate-100
                            p-6
                            cursor-pointer
                        "
                    >

                        <input
                            type="checkbox"
                            name="is_pending"
                            value="1"
                            @checked(old('is_pending'))
                            class="
                                w-5
                                h-5
                                mt-0.5
                                rounded
                                border-slate-300
                            "
                        >

                        <span>

                            <span class="block text-sm font-medium text-slate-900">
                                Ausstehend
                            </span>

                            <span class="block text-xs text-slate-500 mt-1">
                                Buchung ist noch nicht endgültig gebucht.
                            </span>

                        </span>

                    </label>

                </div>

            </div>



            {{-- ===================================================== --}}
            {{-- AKTIONEN --}}
            {{-- ===================================================== --}}

            <div
                class="
                    mt-6
                    bg-white
                    rounded-3xl
                    shadow-sm
                    border
                    border-slate-100
                    p-5
                    flex
                    flex-col-reverse
                    sm:flex-row
                    sm:items-center
                    sm:justify-end
                    gap-3
                "
            >

                <a
                    href="{{ route('transactions.index') }}"
                    class="
                        inline-flex
                        items-center
                        justify-center
                        rounded-xl
                        border
                        border-slate-200
                        bg-white
                        px-5
                        py-3
                        text-sm
                        font-medium
                        text-slate-600
                        hover:bg-slate-50
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
                        bg-slate-950
                        px-6
                        py-3
                        text-sm
                        font-medium
                        text-white
                        hover:bg-slate-800
                    "
                >
                    Buchung speichern
                </button>

            </div>

        </form>

    @endif

</div>



{{-- ========================================================= --}}
{{-- KATEGORIEN FILTER --}}
{{-- ========================================================= --}}

<script>

document.addEventListener('DOMContentLoaded', function () {

    const typeInputs = document.querySelectorAll(
        'input[name="type"]'
    );

    const categorySelect =
        document.getElementById('category_id');

    if (!typeInputs.length || !categorySelect) {
        return;
    }


    const categories =
        Array.from(categorySelect.options)
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
            document.createElement('option');

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
                document.createElement('option');

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

</script>

@endsection