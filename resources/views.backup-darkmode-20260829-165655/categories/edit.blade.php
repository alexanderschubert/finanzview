@extends('layouts.app')

@section('title', 'Kategorie bearbeiten – Finanzblick')

@section('eyebrow', 'Finanzverwaltung')

@section('page_title', 'Kategorie bearbeiten')


@section('content')

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">


    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <div class="mb-6">

        <a
            href="{{ route('categories.index') }}"
            class="text-sm text-slate-500 hover:text-slate-900"
        >
            ← Kategorien
        </a>


        <div class="flex items-center gap-4 mt-4">

            <div
                class="
                    w-14
                    h-14
                    rounded-2xl
                    bg-slate-100
                    flex
                    items-center
                    justify-center
                    text-2xl
                    flex-shrink-0
                "
            >
                {{ $category->icon ?: '📁' }}
            </div>


            <div class="min-w-0">

                <h2 class="text-2xl sm:text-3xl font-semibold text-slate-900">
                    Kategorie bearbeiten
                </h2>

                <p class="text-slate-500 mt-1 truncate">
                    {{ $category->name }}
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



    {{-- ========================================================= --}}
    {{-- FORMULAR --}}
    {{-- ========================================================= --}}

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
            id="category-edit-form"
            method="POST"
            action="{{ route('categories.update', $category) }}"
        >

            @csrf

            @method('PUT')


            <div class="p-6 sm:p-8 space-y-6">


                {{-- ================================================= --}}
                {{-- NAME --}}
                {{-- ================================================= --}}

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
                        value="{{ old('name', $category->name) }}"
                        required
                        autofocus
                        placeholder="z. B. Lebensmittel"
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



                {{-- ================================================= --}}
                {{-- TYP --}}
                {{-- ================================================= --}}

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
                            @selected(old('type', $category->type) === 'expense')
                        >
                            Ausgabe
                        </option>

                        <option
                            value="income"
                            @selected(old('type', $category->type) === 'income')
                        >
                            Einnahme
                        </option>

                        <option
                            value="both"
                            @selected(old('type', $category->type) === 'both')
                        >
                            Einnahme & Ausgabe
                        </option>

                    </select>


                    <p class="text-xs text-slate-400 mt-2">
                        Bestimmt, bei welcher Art von Buchung die Kategorie verwendet werden kann.
                    </p>

                </div>



                {{-- ================================================= --}}
                {{-- ICON --}}
                {{-- ================================================= --}}

                <div>

                    <label
                        for="icon"
                        class="block text-sm font-medium text-slate-700 mb-2"
                    >
                        Icon
                    </label>

                    <div class="flex gap-3">

                        <div
                            id="icon-preview"
                            class="
                                w-14
                                h-14
                                rounded-xl
                                bg-slate-100
                                flex
                                items-center
                                justify-center
                                text-2xl
                                flex-shrink-0
                            "
                        >
                            {{ $category->icon ?: '📁' }}
                        </div>


                        <input
                            type="text"
                            id="icon"
                            name="icon"
                            value="{{ old('icon', $category->icon) }}"
                            maxlength="10"
                            placeholder="📁"
                            class="
                                flex-1
                                rounded-xl
                                border
                                border-slate-200
                                px-4
                                py-3
                                text-xl
                                focus:outline-none
                                focus:ring-2
                                focus:ring-slate-200
                            "
                        >

                    </div>


                    <p class="text-xs text-slate-400 mt-2">
                        Zum Beispiel: 🏠 🍔 🚗 ⛽️ 💡 🛒
                    </p>

                </div>



                {{-- ================================================= --}}
                {{-- BESCHREIBUNG --}}
                {{-- ================================================= --}}

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
                        rows="4"
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
                    >{{ old('description', $category->description) }}</textarea>

                </div>



                {{-- ================================================= --}}
                {{-- AKTIV --}}
                {{-- ================================================= --}}

                <label
                    class="
                        flex
                        items-start
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
                        @checked(old('is_active', $category->is_active))
                        class="
                            w-5
                            h-5
                            mt-0.5
                            rounded
                            border-slate-300
                            flex-shrink-0
                        "
                    >

                    <span>

                        <span class="block text-sm font-medium text-slate-900">
                            Kategorie aktiv
                        </span>

                        <span class="block text-xs text-slate-500 mt-1">
                            Deaktivierte Kategorien bleiben erhalten,
                            können aber nicht für neue Buchungen verwendet werden.
                        </span>

                    </span>

                </label>

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
                    border-t
                    border-slate-100
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
                    onclick="deleteCategory()"
                    class="
                        w-full
                        sm:w-auto
                        inline-flex
                        items-center
                        justify-center
                        rounded-xl
                        px-5
                        py-3
                        text-sm
                        font-medium
                        text-red-600
                        hover:bg-red-50
                        transition
                    "
                >
                    Kategorie löschen
                </button>


                {{-- RECHTS --}}

                <div class="flex flex-col sm:flex-row gap-3">

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
                            transition
                        "
                    >
                        Abbrechen
                    </a>


                    <button
                        type="submit"
                        form="category-edit-form"
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
                            transition
                        "
                    >
                        Änderungen speichern
                    </button>

                </div>

            </div>

        </form>

    </div>

</div>



{{-- ========================================================= --}}
{{-- LÖSCH-FORMULAR --}}
{{-- ========================================================= --}}

<form
    id="delete-category-form"
    method="POST"
    action="{{ route('categories.destroy', $category) }}"
    class="hidden"
>

    @csrf

    @method('DELETE')

</form>



{{-- ========================================================= --}}
{{-- JAVASCRIPT --}}
{{-- ========================================================= --}}

<script>

function deleteCategory()
{
    const confirmed = confirm(
        'Möchtest du die Kategorie „{{ addslashes($category->name) }}“ wirklich löschen?'
    );

    if (!confirmed) {
        return;
    }

    document
        .getElementById('delete-category-form')
        .submit();
}


document.addEventListener('DOMContentLoaded', function () {

    const iconInput = document.getElementById('icon');
    const iconPreview = document.getElementById('icon-preview');

    if (!iconInput || !iconPreview) {
        return;
    }

    iconInput.addEventListener('input', function () {

        iconPreview.textContent =
            this.value.trim() || '📁';

    });

});

</script>

@endsection