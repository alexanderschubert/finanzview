<x-app-layout>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Header --}}
        <div class="mb-8">
            <a
                href="{{ route('admin.index') }}"
                class="inline-flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 hover:text-violet-600 dark:hover:text-violet-400 transition"
            >
                ← Zur Benutzerverwaltung
            </a>

            <div class="mt-5 flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-violet-100 dark:bg-violet-950/40">
                    <span class="text-2xl">👤</span>
                </div>

                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                        Benutzer bearbeiten
                    </h1>

                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {{ $user->email }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Meldungen --}}
        @if (session('error'))
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300">
                <p class="font-semibold mb-2">
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
            action="{{ route('admin.users.update', $user) }}"
            class="space-y-6"
        >
            @csrf
            @method('PATCH')

            {{-- Persönliche Daten --}}
            <div class="rounded-3xl bg-white dark:bg-slate-900 p-6 shadow-sm ring-1 ring-slate-200 dark:ring-slate-800">

                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                        Persönliche Daten
                    </h2>

                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Name und E-Mail-Adresse des Benutzers.
                    </p>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">

                    {{-- Name --}}
                    <div>
                        <label
                            for="name"
                            class="block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                            Name
                        </label>

                        <input
                            id="name"
                            name="name"
                            type="text"
                            value="{{ old('name', $user->name) }}"
                            required
                            class="mt-2 block w-full rounded-xl border-0 bg-slate-100 dark:bg-slate-800 px-4 py-3 text-sm text-slate-900 dark:text-white ring-1 ring-inset ring-slate-200 dark:ring-slate-700 focus:ring-2 focus:ring-violet-500"
                        >
                    </div>

                    {{-- E-Mail --}}
                    <div>
                        <label
                            for="email"
                            class="block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                            E-Mail-Adresse
                        </label>

                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email', $user->email) }}"
                            required
                            class="mt-2 block w-full rounded-xl border-0 bg-slate-100 dark:bg-slate-800 px-4 py-3 text-sm text-slate-900 dark:text-white ring-1 ring-inset ring-slate-200 dark:ring-slate-700 focus:ring-2 focus:ring-violet-500"
                        >
                    </div>

                </div>
            </div>

            {{-- Berechtigungen --}}
            <div class="rounded-3xl bg-white dark:bg-slate-900 p-6 shadow-sm ring-1 ring-slate-200 dark:ring-slate-800">

                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                        Berechtigungen
                    </h2>

                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Benutzerstatus und Administratorrechte.
                    </p>
                </div>

                <div class="space-y-4">

                    {{-- Aktiv --}}
                    <label class="flex cursor-pointer items-center justify-between gap-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 p-4">

                        <div>
                            <p class="font-medium text-slate-900 dark:text-white">
                                Benutzer aktiv
                            </p>

                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                Deaktivierte Benutzer können sich nicht anmelden.
                            </p>
                        </div>

                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            @checked(old('is_active', $user->is_active))
                            class="h-5 w-5 rounded border-slate-300 text-violet-600 focus:ring-violet-500"
                        >

                    </label>

                    {{-- Admin --}}
                    <label class="flex cursor-pointer items-center justify-between gap-4 rounded-2xl bg-violet-50 dark:bg-violet-950/20 p-4">

                        <div>
                            <p class="font-medium text-slate-900 dark:text-white">
                                Administrator
                            </p>

                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                Administratoren haben Zugriff auf die Benutzerverwaltung.
                            </p>
                        </div>

                        <input
                            type="checkbox"
                            name="is_admin"
                            value="1"
                            @checked(old('is_admin', $user->is_admin))
                            class="h-5 w-5 rounded border-slate-300 text-violet-600 focus:ring-violet-500"
                        >

                    </label>

                </div>
            </div>

            {{-- Passwort --}}
            <div class="rounded-3xl bg-white dark:bg-slate-900 p-6 shadow-sm ring-1 ring-slate-200 dark:ring-slate-800">

                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                        Passwort ändern
                    </h2>

                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Nur ausfüllen, wenn ein neues Passwort vergeben werden soll.
                    </p>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">

                    {{-- Neues Passwort --}}
                    <div>
                        <label
                            for="password"
                            class="block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                            Neues Passwort
                        </label>

                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            class="mt-2 block w-full rounded-xl border-0 bg-slate-100 dark:bg-slate-800 px-4 py-3 text-sm text-slate-900 dark:text-white ring-1 ring-inset ring-slate-200 dark:ring-slate-700 focus:ring-2 focus:ring-violet-500"
                        >
                    </div>

                    {{-- Passwort bestätigen --}}
                    <div>
                        <label
                            for="password_confirmation"
                            class="block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                            Passwort bestätigen
                        </label>

                        <input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            class="mt-2 block w-full rounded-xl border-0 bg-slate-100 dark:bg-slate-800 px-4 py-3 text-sm text-slate-900 dark:text-white ring-1 ring-inset ring-slate-200 dark:ring-slate-700 focus:ring-2 focus:ring-violet-500"
                        >
                    </div>

                </div>
            </div>

            {{-- Aktionen --}}
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">

                <a
                    href="{{ route('admin.index') }}"
                    class="inline-flex items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 px-5 py-3 text-sm font-semibold text-slate-700 dark:text-slate-300 transition hover:bg-slate-200 dark:hover:bg-slate-700"
                >
                    Abbrechen
                </a>

                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-xl bg-violet-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-violet-700"
                >
                    Änderungen speichern
                </button>

            </div>

        </form>

    </div>

</x-app-layout>
