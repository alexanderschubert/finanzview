@extends('layouts.app')

@section('title', 'Benutzer bearbeiten – Finanzblick')

@section('eyebrow', 'Administration')

@section('page_title', 'Benutzer bearbeiten')

@section('content')

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- HEADER --}}

    <div class="mb-8">

        <a
            href="{{ route('admin.index') }}"
            class="
                inline-flex
                items-center
                text-sm
                text-slate-500
                dark:text-slate-400
                hover:text-slate-900
                dark:hover:text-white
                transition
            "
        >
            ← Administration
        </a>

        <div class="flex items-center gap-4 mt-5">

            <div
                class="
                    w-14
                    h-14
                    shrink-0
                    rounded-2xl
                    bg-violet-50
                    dark:bg-violet-500/10
                    flex
                    items-center
                    justify-center
                    text-xl
                    font-semibold
                    text-violet-600
                    dark:text-violet-400
                "
            >
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>

            <div class="min-w-0">

                <h2
                    class="
                        text-2xl
                        sm:text-3xl
                        font-semibold
                        text-slate-900
                        dark:text-white
                    "
                >
                    Benutzer bearbeiten
                </h2>

                <p class="text-slate-500 dark:text-slate-400 mt-1 truncate">
                    {{ $user->name }}
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
                bg-red-50
                dark:bg-red-950/40
                border
                border-red-100
                dark:border-red-900
                p-4
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
                    <li>{{ $error }}</li>
                @endforeach

            </ul>

        </div>

    @endif


    {{-- FORMULAR --}}

    <form
        method="POST"
        action="{{ route('admin.users.update', $user) }}"
    >

        @csrf
        @method('PATCH')


        <div class="space-y-6">


            {{-- PERSÖNLICHE DATEN --}}

            <div
                class="
                    bg-white
                    dark:bg-slate-900
                    rounded-3xl
                    shadow-sm
                    border
                    border-slate-100
                    dark:border-slate-800
                    p-6
                    sm:p-8
                "
            >

                <div class="mb-6">

                    <h3 class="font-semibold text-slate-900 dark:text-white">
                        Persönliche Daten
                    </h3>

                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Name und E-Mail-Adresse des Benutzers.
                    </p>

                </div>


                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    {{-- NAME --}}

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
                            id="name"
                            type="text"
                            name="name"
                            value="{{ old('name', $user->name) }}"
                            required
                            autocomplete="name"
                            class="
                                w-full
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
                                focus:ring-violet-500
                            "
                        >

                    </div>


                    {{-- E-MAIL --}}

                    <div>

                        <label
                            for="email"
                            class="
                                block
                                text-sm
                                font-medium
                                text-slate-700
                                dark:text-slate-300
                                mb-2
                            "
                        >
                            E-Mail-Adresse
                        </label>

                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email', $user->email) }}"
                            required
                            autocomplete="email"
                            class="
                                w-full
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
                                focus:ring-violet-500
                            "
                        >

                    </div>

                </div>

            </div>


            {{-- BERECHTIGUNGEN --}}

            <div
                class="
                    bg-white
                    dark:bg-slate-900
                    rounded-3xl
                    shadow-sm
                    border
                    border-slate-100
                    dark:border-slate-800
                    p-6
                    sm:p-8
                "
            >

                <div class="mb-6">

                    <h3 class="font-semibold text-slate-900 dark:text-white">
                        Berechtigungen
                    </h3>

                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Lege fest, welche Rechte der Benutzer besitzt.
                    </p>

                </div>


                <div class="space-y-5">

                    {{-- ADMIN --}}

                    <label
                        class="
                            flex
                            items-start
                            gap-4
                            cursor-pointer
                            rounded-2xl
                            border
                            border-slate-200
                            dark:border-slate-700
                            p-4
                            hover:bg-slate-50
                            dark:hover:bg-slate-800/60
                            transition
                        "
                    >

                        <input
                            type="checkbox"
                            name="is_admin"
                            value="1"
                            @checked(old('is_admin', $user->is_admin))
                            class="
                                mt-1
                                h-5
                                w-5
                                rounded
                                border-slate-300
                                text-violet-600
                                focus:ring-violet-500
                            "
                        >

                        <span>

                            <span class="block font-medium text-slate-900 dark:text-white">
                                Administrator
                            </span>

                            <span class="block text-sm text-slate-500 dark:text-slate-400 mt-1">
                                Zugriff auf den Administrationsbereich und die Benutzerverwaltung.
                            </span>

                        </span>

                    </label>


                    {{-- AKTIV --}}

                    <label
                        class="
                            flex
                            items-start
                            gap-4
                            cursor-pointer
                            rounded-2xl
                            border
                            border-slate-200
                            dark:border-slate-700
                            p-4
                            hover:bg-slate-50
                            dark:hover:bg-slate-800/60
                            transition
                        "
                    >

                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            @checked(old('is_active', $user->is_active))
                            class="
                                mt-1
                                h-5
                                w-5
                                rounded
                                border-slate-300
                                text-violet-600
                                focus:ring-violet-500
                            "
                        >

                        <span>

                            <span class="block font-medium text-slate-900 dark:text-white">
                                Benutzer aktiv
                            </span>

                            <span class="block text-sm text-slate-500 dark:text-slate-400 mt-1">
                                Deaktivierte Benutzer können sich nicht anmelden.
                            </span>

                        </span>

                    </label>

                </div>

            </div>


            {{-- PASSWORT --}}

            <div
                class="
                    bg-white
                    dark:bg-slate-900
                    rounded-3xl
                    shadow-sm
                    border
                    border-slate-100
                    dark:border-slate-800
                    p-6
                    sm:p-8
                "
            >

                <div class="mb-6">

                    <h3 class="font-semibold text-slate-900 dark:text-white">
                        Passwort ändern
                    </h3>

                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Leer lassen, wenn das aktuelle Passwort beibehalten werden soll.
                    </p>

                </div>


                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    {{-- PASSWORT --}}

                    <div>

                        <label
                            for="password"
                            class="
                                block
                                text-sm
                                font-medium
                                text-slate-700
                                dark:text-slate-300
                                mb-2
                            "
                        >
                            Neues Passwort
                        </label>

                        <input
                            id="password"
                            type="password"
                            name="password"
                            autocomplete="new-password"
                            class="
                                w-full
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
                                focus:ring-violet-500
                            "
                        >

                    </div>


                    {{-- PASSWORT BESTÄTIGEN --}}

                    <div>

                        <label
                            for="password_confirmation"
                            class="
                                block
                                text-sm
                                font-medium
                                text-slate-700
                                dark:text-slate-300
                                mb-2
                            "
                        >
                            Passwort bestätigen
                        </label>

                        <input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            autocomplete="new-password"
                            class="
                                w-full
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
                                focus:ring-violet-500
                            "
                        >

                    </div>

                </div>

            </div>


            {{-- BUTTONS --}}

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">

                <a
                    href="{{ route('admin.index') }}"
                    class="
                        inline-flex
                        items-center
                        justify-center
                        rounded-xl
                        px-5
                        py-3
                        text-sm
                        font-medium
                        text-slate-700
                        dark:text-slate-300
                        border
                        border-slate-200
                        dark:border-slate-700
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
                        px-5
                        py-3
                        text-sm
                        font-medium
                        text-white
                        bg-violet-600
                        hover:bg-violet-700
                        transition
                    "
                >
                    Änderungen speichern
                </button>

            </div>

        </div>

    </form>

</div>

@endsection