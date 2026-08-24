@extends('layouts.app')

@section('title', 'Kategorie erstellen – Finanzblick')

@section('eyebrow', 'Finanzverwaltung')

@section('page_title', 'Neue Kategorie')


@section('content')

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">


    {{-- HEADER --}}

    <div class="mb-6">

        <a
            href="{{ route('categories.index') }}"
            class="text-sm text-slate-500 hover:text-slate-900"
        >
            ← Kategorien
        </a>

        <h2 class="text-3xl font-semibold text-slate-900 mt-4">
            Neue Kategorie
        </h2>

        <p class="text-slate-500 mt-1">
            Erstelle eine Kategorie für deine Buchungen.
        </p>

    </div>



    {{-- FEHLER --}}

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



    {{-- FORMULAR --}}

    <div
        class="
            bg-white
            rounded-3xl
            shadow-sm
            border
            border-slate-100
            overflow-hidden
        "
    >

        <form
            method="POST"
            action="{{ route('categories.store') }}"
        >

            @csrf


            <div class="p-6 sm:p-8 space-y-6">


                {{-- NAME --}}

                <div>

                    <label
                        for="name"
                        class="block text-sm font-medium text-slate-700 mb-2"
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
                            w-full
                            rounded-xl
                            border
                            border-slate-200
                            px-4
                            py-3
                            text-slate-900
                            placeholder:text-slate-400
                            focus:outline-none
                            focus:ring-2
                            focus:ring-slate-200
                        "
                    >

                </div>



                {{-- TYP --}}

                <div>

                    <label
                        for="type"
                        class="block text-sm font-medium text-slate-700 mb-2"
                    >
                        Kategorie-Typ
                    </label>

                    <select
                        id="type"
                        name="type"
                        required
                        class="
                            w-full
                            rounded-xl
                            border
                            border-slate-200
                            px-4
                            py-3
                            bg-white
                            text-slate-900
                            focus:outline-none
                            focus:ring-2
                            focus:ring-slate-200
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


                    <p class="text-xs text-slate-400 mt-2">
                        Bestimmt, bei welcher Art von Buchung die Kategorie angezeigt wird.
                    </p>

                </div>



                {{-- ICON --}}

                <div>

                    <label
                        for="icon"
                        class="block text-sm font-medium text-slate-700 mb-2"
                    >
                        Icon
                    </label>

                    <input
                        type="text"
                        id="icon"
                        name="icon"
                        value="{{ old('icon') }}"
                        maxlength="10"
                        placeholder="⛽️"
                        class="
                            w-full
                            rounded-xl
                            border
                            border-slate-200
                            px-4
                            py-3
                            text-2xl
                            focus:outline-none
                            focus:ring-2
                            focus:ring-slate-200
                        "
                    >

                    <p class="text-xs text-slate-400 mt-2">
                        Zum Beispiel: 🏠 🍔 🚗 ⛽️ 💡 🛒
                    </p>

                </div>



                {{-- BESCHREIBUNG --}}

                <div>

                    <label
                        for="description"
                        class="block text-sm font-medium text-slate-700 mb-2"
                    >
                        Beschreibung
                        <span class="text-slate-400 font-normal">
                            (optional)
                        </span>
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        rows="3"
                        placeholder="Kurze Beschreibung der Kategorie..."
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
                    >{{ old('description') }}</textarea>

                </div>



                {{-- AKTIV --}}

                <label
                    class="
                        flex
                        items-center
                        gap-3
                        rounded-2xl
                        bg-slate-50
                        border
                        border-slate-100
                        p-4
                        cursor-pointer
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
                            rounded
                            border-slate-300
                        "
                    >

                    <span>

                        <span class="block text-sm font-medium text-slate-900">
                            Kategorie aktiv
                        </span>

                        <span class="block text-xs text-slate-500 mt-1">
                            Aktive Kategorien können bei neuen Buchungen ausgewählt werden.
                        </span>

                    </span>

                </label>

            </div>



            {{-- FOOTER --}}

            <div
                class="
                    px-6
                    sm:px-8
                    py-5
                    bg-slate-50
                    border-t
                    border-slate-100
                    flex
                    flex-col-reverse
                    sm:flex-row
                    sm:justify-end
                    gap-3
                "
            >

                <a
                    href="{{ route('categories.index') }}"
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
                        hover:bg-slate-100
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
                        px-5
                        py-3
                        text-sm
                        font-medium
                        text-white
                        hover:bg-slate-800
                    "
                >
                    Kategorie erstellen
                </button>

            </div>

        </form>

    </div>

</div>

@endsection