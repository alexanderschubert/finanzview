@extends('layouts.app')

@section('title', 'Kategorie erstellen – Finanzblick')

@section('eyebrow', 'Finanzverwaltung')

@section('page_title', 'Neue Kategorie')


@section('content')

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">


    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <div class="mb-6">

        <a
            href="{{ route('categories.index') }}"
            class="
                text-sm
                text-slate-500
                dark:text-slate-400
                hover:text-slate-900
                dark:hover:text-white
                transition
            "
        >
            ← Kategorien
        </a>


        <div class="flex items-center gap-4 mt-4">

            {{-- ICON HEADER --}}

            <div
                id="header-icon"
                class="
                    w-14
                    h-14
                    rounded-2xl
                    flex
                    items-center
                    justify-center
                    text-2xl
                    flex-shrink-0
                    transition
                "
                style="background-color: #fee2e2;"
            >
                📁
            </div>


            <div class="min-w-0">

                <h2
                    class="
                        text-2xl
                        sm:text-3xl
                        font-semibold
                        tracking-tight
                        text-slate-900
                        dark:text-white
                    "
                >
                    Neue Kategorie
                </h2>

                <p class="text-slate-500 dark:text-slate-400 mt-1">
                    Erstelle eine Kategorie für deine Buchungen.
                </p>

            </div>

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
                dark:bg-red-950/40
                border
                border-red-100
                dark:border-red-900
                p-5
                text-sm
                text-red-700
                dark:text-red-300
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

    <div
        class="
            bg-white
            dark:bg-slate-900
            rounded-3xl
            shadow-sm
            border
            border-slate-100
            dark:border-slate-800
            overflow-hidden
        "
    >

        <form
            method="POST"
            action="{{ route('categories.store') }}"
        >

            @csrf


            <div class="p-6 sm:p-8 space-y-6">


                {{-- ================================================= --}}
                {{-- KATEGORIEINFORMATIONEN --}}
                {{-- ================================================= --}}

                <div>

                    <div class="mb-6">

                        <h3 class="font-semibold text-slate-900 dark:text-white">
                            Kategorieinformationen
                        </h3>

                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Grundlegende Angaben zur Kategorie.
                        </p>

                    </div>


                    <div class="space-y-6">


                        {{-- ================================================= --}}
                        {{-- NAME --}}
                        {{-- ================================================= --}}

                        <div>

                            <label
                                for="name"
                                class="
                                    block
                                    text-sm
                                    font-medium
                                    text-slate-700
                                    dark:text-slate-300
                                    mb-2
                                "
                            >
                                Name
                            </label>

                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="{{ old('name') }}"
                                required
                                autofocus
                                placeholder="z. B. Tanken"
                                class="
                                    box-border
                                    w-full
                                    min-w-0
                                    rounded-xl
                                    border
                                    border-slate-200
                                    dark:border-slate-700
                                    bg-white
                                    dark:bg-slate-800
                                    text-slate-900
                                    dark:text-white
                                    px-4
                                    py-3
                                    placeholder-slate-400
                                    focus:outline-none
                                    focus:ring-2
                                    focus:ring-emerald-500/20
                                    focus:border-emerald-500
                                "
                            >

                        </div>



                        {{-- ================================================= --}}
                        {{-- TYP --}}
                        {{-- ================================================= --}}

                        <div>

                            <label
                                for="type"
                                class="
                                    block
                                    text-sm
                                    font-medium
                                    text-slate-700
                                    dark:text-slate-300
                                    mb-2
                                "
                            >
                                Kategorie-Typ
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
                                    border
                                    border-slate-200
                                    dark:border-slate-700
                                    bg-white
                                    dark:bg-slate-800
                                    text-slate-900
                                    dark:text-white
                                    px-4
                                    py-3
                                    focus:outline-none
                                    focus:ring-2
                                    focus:ring-emerald-500/20
                                    focus:border-emerald-500
                                "
                            >

                                <option
                                    value="expense"
                                    @selected(old('type', 'expense') === 'expense')
                                >
                                    Ausgabe
                                </option>

                                <option
                                    value="income"
                                    @selected(old('type') === 'income')
                                >
                                    Einnahme
                                </option>

                                <option
                                    value="both"
                                    @selected(old('type') === 'both')
                                >
                                    Einnahme & Ausgabe
                                </option>

                            </select>


                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">
                                Bestimmt, bei welcher Art von Buchung die Kategorie angezeigt wird.
                            </p>

                        </div>



                        {{-- ================================================= --}}
                        {{-- ICON --}}
                        {{-- ================================================= --}}

                        <div>

                            <label
                                for="icon"
                                class="
                                    block
                                    text-sm
                                    font-medium
                                    text-slate-700
                                    dark:text-slate-300
                                    mb-2
                                "
                            >
                                Icon
                            </label>


                            <div class="flex gap-3 min-w-0">

                                {{-- ICON VORSCHAU --}}

                                <div
                                    id="icon-preview"
                                    class="
                                        w-14
                                        h-14
                                        rounded-xl
                                        flex
                                        items-center
                                        justify-center
                                        text-2xl
                                        flex-shrink-0
                                        transition
                                    "
                                    style="background-color: #fee2e2;"
                                >
                                    📁
                                </div>


                                {{-- ICON INPUT --}}

                                <input
                                    type="text"
                                    id="icon"
                                    name="icon"
                                    value="{{ old('icon') }}"
                                    maxlength="10"
                                    placeholder="📁"
                                    class="
                                        min-w-0
                                        w-0
                                        flex-1
                                        box-border
                                        max-w-full
                                        rounded-xl
                                        border
                                        border-slate-200
                                        dark:border-slate-700
                                        bg-white
                                        dark:bg-slate-800
                                        text-slate-900
                                        dark:text-white
                                        px-4
                                        py-3
                                        text-xl
                                        placeholder-slate-400
                                        focus:outline-none
                                        focus:ring-2
                                        focus:ring-emerald-500/20
                                        focus:border-emerald-500
                                    "
                                >

                            </div>


                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">
                                Zum Beispiel: 🏠 🍔 🚗 ⛽️ 💡 🛒
                            </p>

                        </div>



                        {{-- ================================================= --}}
                        {{-- BESCHREIBUNG --}}
                        {{-- ================================================= --}}

                        <div>

                            <label
                                for="description"
                                class="
                                    block
                                    text-sm
                                    font-medium
                                    text-slate-700
                                    dark:text-slate-300
                                    mb-2
                                "
                            >
                                Beschreibung

                                <span class="text-slate-400 font-normal">
                                    (optional)
                                </span>

                            </label>

                            <textarea
                                id="description"
                                name="description"
                                rows="4"
                                placeholder="Kurze Beschreibung der Kategorie..."
                                class="
                                    box-border
                                    w-full
                                    min-w-0
                                    rounded-xl
                                    border
                                    border-slate-200
                                    dark:border-slate-700
                                    bg-white
                                    dark:bg-slate-800
                                    text-slate-900
                                    dark:text-white
                                    px-4
                                    py-3
                                    resize-none
                                    placeholder-slate-400
                                    focus:outline-none
                                    focus:ring-2
                                    focus:ring-emerald-500/20
                                    focus:border-emerald-500
                                "
                            >{{ old('description') }}</textarea>

                        </div>

                    </div>

                </div>



                {{-- ========================================================= --}}
                {{-- STATUS --}}
                {{-- ========================================================= --}}

                <div
                    class="
                        pt-6
                        border-t
                        border-slate-100
                        dark:border-slate-800
                    "
                >

                    <label
                        class="
                            flex
                            items-start
                            gap-3
                            rounded-2xl
                            bg-slate-50
                            dark:bg-slate-800/60
                            border
                            border-slate-100
                            dark:border-slate-700
                            p-4
                            cursor-pointer
                            transition
                            hover:bg-slate-100
                            dark:hover:bg-slate-800
                        "
                    >

                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            @checked(old('is_active', true))
                            class="
                                w-5
                                h-5
                                mt-0.5
                                rounded
                                border-slate-300
                                dark:border-slate-600
                                bg-white
                                dark:bg-slate-800
                                text-emerald-600
                                focus:ring-emerald-500
                                flex-shrink-0
                            "
                        >

                        <span class="min-w-0">

                            <span
                                class="
                                    block
                                    text-sm
                                    font-medium
                                    text-slate-900
                                    dark:text-white
                                "
                            >
                                Kategorie aktiv
                            </span>

                            <span
                                class="
                                    block
                                    text-xs
                                    text-slate-500
                                    dark:text-slate-400
                                    mt-1
                                "
                            >
                                Aktive Kategorien können bei neuen Buchungen ausgewählt werden.
                            </span>

                        </span>

                    </label>

                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- FOOTER --}}
            {{-- ========================================================= --}}

            <div
                class="
                    px-6
                    sm:px-8
                    py-5
                    bg-slate-50
                    dark:bg-slate-800/60
                    border-t
                    border-slate-100
                    dark:border-slate-800
                    flex
                    flex-col-reverse
                    sm:flex-row
                    sm:items-center
                    sm:justify-end
                    gap-3
                "
            >

                {{-- ABBRECHEN --}}

                <a
                    href="{{ route('categories.index') }}"
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
                        text-slate-600
                        dark:text-slate-300
                        hover:bg-slate-100
                        dark:hover:bg-slate-700
                        transition
                    "
                >
                    Abbrechen
                </a>


                {{-- ERSTELLEN --}}

                <button
                    type="submit"
                    class="
                        inline-flex
                        items-center
                        justify-center
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
                    Kategorie erstellen
                </button>

            </div>

        </form>

    </div>

</div>



{{-- ========================================================= --}}
{{-- JAVASCRIPT --}}
{{-- ========================================================= --}}

<script>

document.addEventListener('DOMContentLoaded', function () {

    const iconInput =
        document.getElementById('icon');

    const iconPreview =
        document.getElementById('icon-preview');

    const headerIcon =
        document.getElementById('header-icon');

    const typeInput =
        document.getElementById('type');


    function updateIcon()
    {

        const icon =
            iconInput.value.trim() || '📁';


        if (iconPreview) {

            iconPreview.textContent =
                icon;

        }


        if (headerIcon) {

            headerIcon.textContent =
                icon;

        }

    }


    function updateColors()
    {

        const type =
            typeInput
                ? typeInput.value
                : 'expense';


        let backgroundColor;


        if (type === 'income') {

            backgroundColor =
                '#d1fae5';

        } else if (type === 'expense') {

            backgroundColor =
                '#fee2e2';

        } else {

            backgroundColor =
                '#f1f5f9';

        }


        if (iconPreview) {

            iconPreview.style.backgroundColor =
                backgroundColor;

        }


        if (headerIcon) {

            headerIcon.style.backgroundColor =
                backgroundColor;

        }

    }


    if (iconInput) {

        iconInput.addEventListener(
            'input',
            updateIcon
        );

    }


    if (typeInput) {

        typeInput.addEventListener(
            'change',
            updateColors
        );

    }


    updateIcon();
    updateColors();

});

</script>

@endsection