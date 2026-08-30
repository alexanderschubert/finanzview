@extends('layouts.app')

@section('title', 'Finanzen – Finanzblick')

@section('eyebrow', 'Einstellungen')

@section('page_title', 'Finanzen')

@section('content')

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- =========================================================
         HEADER
    ========================================================== --}}

    <div class="mb-8">

        <a
            href="{{ route('settings.index') }}"
            class="inline-flex items-center text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition"
        >
            ← Einstellungen
        </a>

        <h2 class="text-3xl font-semibold tracking-tight text-slate-900 dark:text-white mt-5">
            Finanzielle Einstellungen
        </h2>

        <p class="text-slate-500 dark:text-slate-400 mt-2">
            Lege fest, wie Finanzblick deine Finanzdaten darstellen und neue Buchungen behandeln soll.
        </p>

    </div>


    {{-- =========================================================
         ERFOLG
    ========================================================== --}}

    @if(session('success'))

        <div
            class="
                mb-6
                rounded-2xl
                border
                border-emerald-200
                dark:border-emerald-900
                bg-emerald-50
                dark:bg-emerald-950/30
                px-5
                py-4
                text-sm
                text-emerald-700
                dark:text-emerald-400
            "
        >
            {{ session('success') }}
        </div>

    @endif


    {{-- =========================================================
         FEHLER
    ========================================================== --}}

    @if($errors->any())

        <div
            class="
                mb-6
                rounded-2xl
                border
                border-red-200
                dark:border-red-900
                bg-red-50
                dark:bg-red-950/30
                px-5
                py-4
                text-sm
                text-red-700
                dark:text-red-400
            "
        >

            <p class="font-medium">
                Bitte überprüfe deine Eingaben.
            </p>

            <ul class="mt-2 list-disc list-inside space-y-1">

                @foreach($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif


    {{-- =========================================================
         FORMULAR
    ========================================================== --}}

    <form
        method="POST"
        action="{{ route('settings.financial.update') }}"
        class="space-y-6"
    >

        @csrf

        @method('PUT')


        {{-- =====================================================
             WÄHRUNG
        ====================================================== --}}

        <section
            class="
                rounded-3xl
                bg-white
                dark:bg-slate-900
                border
                border-slate-200
                dark:border-slate-800
                p-6
                sm:p-8
            "
        >

            <div class="mb-6">

                <div class="flex items-center gap-3">

                    <div
                        class="
                            w-11
                            h-11
                            rounded-2xl
                            bg-emerald-50
                            dark:bg-emerald-950/40
                            flex
                            items-center
                            justify-center
                            text-xl
                        "
                    >
                        💶
                    </div>

                    <div>

                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                            Währung
                        </h3>

                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Standardwährung für deine Finanzdaten.
                        </p>

                    </div>

                </div>

            </div>


            <div>

                <label
                    for="currency"
                    class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                >
                    Standardwährung
                </label>

                <select
                    id="currency"
                    name="currency"
                    required
                    class="
                        w-full
                        rounded-xl
                        border
                        border-slate-200
                        dark:border-slate-700
                        bg-white
                        dark:bg-slate-800
                        px-4
                        py-3
                        text-slate-900
                        dark:text-white
                        outline-none
                        focus:ring-2
                        focus:ring-emerald-500/20
                        focus:border-emerald-500
                    "
                >

                    <option
                        value="EUR"
                        @selected(old('currency', $setting->currency ?? 'EUR') === 'EUR')
                    >
                        Euro (€)
                    </option>

                    <option
                        value="USD"
                        @selected(old('currency', $setting->currency ?? 'EUR') === 'USD')
                    >
                        US-Dollar ($)
                    </option>

                    <option
                        value="GBP"
                        @selected(old('currency', $setting->currency ?? 'EUR') === 'GBP')
                    >
                        Britisches Pfund (£)
                    </option>

                    <option
                        value="CHF"
                        @selected(old('currency', $setting->currency ?? 'EUR') === 'CHF')
                    >
                        Schweizer Franken (CHF)
                    </option>

                </select>

            </div>

        </section>


        {{-- =====================================================
             NACHKOMMASTELLEN
        ====================================================== --}}

        <section
            class="
                rounded-3xl
                bg-white
                dark:bg-slate-900
                border
                border-slate-200
                dark:border-slate-800
                p-6
                sm:p-8
            "
        >

            <div class="mb-6">

                <div class="flex items-center gap-3">

                    <div
                        class="
                            w-11
                            h-11
                            rounded-2xl
                            bg-blue-50
                            dark:bg-blue-950/40
                            flex
                            items-center
                            justify-center
                            text-xl
                        "
                    >
                        🔢
                    </div>

                    <div>

                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                            Zahlenformat
                        </h3>

                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Bestimme die Anzahl der Nachkommastellen bei Geldbeträgen.
                        </p>

                    </div>

                </div>

            </div>


            <div>

                <label
                    for="decimal_places"
                    class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                >
                    Nachkommastellen
                </label>

                <select
                    id="decimal_places"
                    name="decimal_places"
                    required
                    class="
                        w-full
                        rounded-xl
                        border
                        border-slate-200
                        dark:border-slate-700
                        bg-white
                        dark:bg-slate-800
                        px-4
                        py-3
                        text-slate-900
                        dark:text-white
                        outline-none
                        focus:ring-2
                        focus:ring-emerald-500/20
                        focus:border-emerald-500
                    "
                >

                    <option
                        value="2"
                        @selected((int) old('decimal_places', $setting->decimal_places ?? 2) === 2)
                    >
                        2 Nachkommastellen – 1.234,56 €
                    </option>

                    <option
                        value="0"
                        @selected((int) old('decimal_places', $setting->decimal_places ?? 2) === 0)
                    >
                        Keine Nachkommastellen – 1.235 €
                    </option>

                </select>

            </div>

        </section>


        {{-- =====================================================
             DATUM
        ====================================================== --}}

        <section
            class="
                rounded-3xl
                bg-white
                dark:bg-slate-900
                border
                border-slate-200
                dark:border-slate-800
                p-6
                sm:p-8
            "
        >

            <div class="mb-6">

                <div class="flex items-center gap-3">

                    <div
                        class="
                            w-11
                            h-11
                            rounded-2xl
                            bg-violet-50
                            dark:bg-violet-950/40
                            flex
                            items-center
                            justify-center
                            text-xl
                        "
                    >
                        📅
                    </div>

                    <div>

                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                            Datumsformat
                        </h3>

                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Bestimme, wie Datumsangaben dargestellt werden.
                        </p>

                    </div>

                </div>

            </div>


            <div>

                <label
                    for="date_format"
                    class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                >
                    Datumsformat
                </label>

                <select
                    id="date_format"
                    name="date_format"
                    required
                    class="
                        w-full
                        rounded-xl
                        border
                        border-slate-200
                        dark:border-slate-700
                        bg-white
                        dark:bg-slate-800
                        px-4
                        py-3
                        text-slate-900
                        dark:text-white
                        outline-none
                        focus:ring-2
                        focus:ring-emerald-500/20
                        focus:border-emerald-500
                    "
                >

                    <option
                        value="d.m.Y"
                        @selected(old('date_format', $setting->date_format ?? 'd.m.Y') === 'd.m.Y')
                    >
                        31.12.2026
                    </option>

                    <option
                        value="Y-m-d"
                        @selected(old('date_format', $setting->date_format ?? 'd.m.Y') === 'Y-m-d')
                    >
                        2026-12-31
                    </option>

                    <option
                        value="d/m/Y"
                        @selected(old('date_format', $setting->date_format ?? 'd.m.Y') === 'd/m/Y')
                    >
                        31/12/2026
                    </option>

                    <option
                        value="m/d/Y"
                        @selected(old('date_format', $setting->date_format ?? 'd.m.Y') === 'm/d/Y')
                    >
                        12/31/2026
                    </option>

                </select>

            </div>

        </section>


        {{-- =====================================================
             WOCHENBEGINN
        ====================================================== --}}

        <section
            class="
                rounded-3xl
                bg-white
                dark:bg-slate-900
                border
                border-slate-200
                dark:border-slate-800
                p-6
                sm:p-8
            "
        >

            <div class="mb-6">

                <div class="flex items-center gap-3">

                    <div
                        class="
                            w-11
                            h-11
                            rounded-2xl
                            bg-amber-50
                            dark:bg-amber-950/40
                            flex
                            items-center
                            justify-center
                            text-xl
                        "
                    >
                        🗓️
                    </div>

                    <div>

                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                            Wochenbeginn
                        </h3>

                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Lege fest, mit welchem Tag die Woche beginnt.
                        </p>

                    </div>

                </div>

            </div>


            <div>

                <label
                    for="first_day_of_week"
                    class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                >
                    Erster Tag der Woche
                </label>

                <select
                    id="first_day_of_week"
                    name="first_day_of_week"
                    required
                    class="
                        w-full
                        rounded-xl
                        border
                        border-slate-200
                        dark:border-slate-700
                        bg-white
                        dark:bg-slate-800
                        px-4
                        py-3
                        text-slate-900
                        dark:text-white
                        outline-none
                        focus:ring-2
                        focus:ring-emerald-500/20
                        focus:border-emerald-500
                    "
                >

                    <option
                        value="1"
                        @selected((int) old('first_day_of_week', $setting->first_day_of_week ?? 1) === 1)
                    >
                        Montag
                    </option>

                    <option
                        value="6"
                        @selected((int) old('first_day_of_week', $setting->first_day_of_week ?? 1) === 6)
                    >
                        Samstag
                    </option>

                    <option
                        value="7"
                        @selected((int) old('first_day_of_week', $setting->first_day_of_week ?? 1) === 7)
                    >
                        Sonntag
                    </option>

                </select>

            </div>

        </section>


        {{-- =====================================================
             STANDARDBUCHUNG
        ====================================================== --}}

        <section
            class="
                rounded-3xl
                bg-white
                dark:bg-slate-900
                border
                border-slate-200
                dark:border-slate-800
                p-6
                sm:p-8
            "
        >

            <div class="mb-6">

                <div class="flex items-center gap-3">

                    <div
                        class="
                            w-11
                            h-11
                            rounded-2xl
                            bg-emerald-50
                            dark:bg-emerald-950/40
                            flex
                            items-center
                            justify-center
                            text-xl
                        "
                    >
                        🏦
                    </div>

                    <div>

                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                            Standardkonto
                        </h3>

                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Dieses Konto kann bei neuen Buchungen automatisch vorausgewählt werden.
                        </p>

                    </div>

                </div>

            </div>


            <div>

                <label
                    for="default_account_id"
                    class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                >
                    Standardkonto
                </label>

                <select
                    id="default_account_id"
                    name="default_account_id"
                    class="
                        w-full
                        rounded-xl
                        border
                        border-slate-200
                        dark:border-slate-700
                        bg-white
                        dark:bg-slate-800
                        px-4
                        py-3
                        text-slate-900
                        dark:text-white
                        outline-none
                        focus:ring-2
                        focus:ring-emerald-500/20
                        focus:border-emerald-500
                    "
                >

                    <option value="">
                        Kein Standardkonto
                    </option>

                    @foreach($accounts as $account)

                        <option
                            value="{{ $account->id }}"
                            @selected(
                                old(
                                    'default_account_id',
                                    $setting->default_account_id ?? null
                                ) == $account->id
                            )
                        >
                            {{ $account->icon ?: '🏦' }}
                            {{ $account->name }}
                        </option>

                    @endforeach

                </select>

                @if($accounts->isEmpty())

                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                        Du hast noch kein Konto angelegt.
                    </p>

                @endif

            </div>

        </section>


        {{-- =====================================================
             STANDARDoKATEGORIE
        ====================================================== --}}

        <section
            class="
                rounded-3xl
                bg-white
                dark:bg-slate-900
                border
                border-slate-200
                dark:border-slate-800
                p-6
                sm:p-8
            "
        >

            <div class="mb-6">

                <div class="flex items-center gap-3">

                    <div
                        class="
                            w-11
                            h-11
                            rounded-2xl
                            bg-slate-100
                            dark:bg-slate-800
                            flex
                            items-center
                            justify-center
                            text-xl
                        "
                    >
                        🏷️
                    </div>

                    <div>

                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                            Standardkategorie
                        </h3>

                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Diese Kategorie kann bei neuen Buchungen vorausgewählt werden.
                        </p>

                    </div>

                </div>

            </div>


            <div>

                <label
                    for="default_category_id"
                    class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                >
                    Standardkategorie
                </label>

                <select
                    id="default_category_id"
                    name="default_category_id"
                    class="
                        w-full
                        rounded-xl
                        border
                        border-slate-200
                        dark:border-slate-700
                        bg-white
                        dark:bg-slate-800
                        px-4
                        py-3
                        text-slate-900
                        dark:text-white
                        outline-none
                        focus:ring-2
                        focus:ring-emerald-500/20
                        focus:border-emerald-500
                    "
                >

                    <option value="">
                        Keine Standardkategorie
                    </option>

                    @foreach($categories as $category)

                        <option
                            value="{{ $category->id }}"
                            @selected(
                                old(
                                    'default_category_id',
                                    $setting->default_category_id ?? null
                                ) == $category->id
                            )
                        >
                            {{ $category->icon ?: '🏷️' }}
                            {{ $category->name }}
                        </option>

                    @endforeach

                </select>

                @if($categories->isEmpty())

                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                        Du hast noch keine aktive Kategorie angelegt.
                    </p>

                @endif

            </div>

        </section>


        {{-- =====================================================
             BUTTONS
        ====================================================== --}}

        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">

            <a
                href="{{ route('settings.index') }}"
                class="
                    inline-flex
                    items-center
                    justify-center
                    rounded-xl
                    border
                    border-slate-200
                    dark:border-slate-700
                    px-5
                    py-3
                    text-sm
                    font-medium
                    text-slate-700
                    dark:text-slate-200
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
                    items-center
                    justify-center
                    rounded-xl
                    bg-slate-950
                    dark:bg-white
                    px-6
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
                Einstellungen speichern
            </button>

        </div>

    </form>

</div>

@endsection