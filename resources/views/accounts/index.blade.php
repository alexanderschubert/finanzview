<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Konten – Finanzblick</title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])
</head>

<body class="min-h-screen bg-slate-100">

<div class="max-w-6xl mx-auto px-4 sm:px-6 py-8">

    <div class="flex items-center justify-between mb-8">

        <div>
            <a
                href="{{ route('dashboard') }}"
                class="text-sm text-slate-500 hover:text-slate-900"
            >
                ← Dashboard
            </a>

            <h1 class="text-3xl font-semibold text-slate-900 mt-2">
                Konten
            </h1>

            <p class="text-slate-500 mt-1">
                Deine Girokonten, Karten und Zahlungsdienste.
            </p>
        </div>

        <a
            href="{{ route('accounts.create') }}"
            class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-medium text-white hover:bg-slate-800"
        >
            + Konto hinzufügen
        </a>

    </div>

    @if (session('success'))
        <div class="mb-6 rounded-xl bg-emerald-50 px-4 py-3 text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if ($accounts->count())

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

            @foreach ($accounts as $account)

                <div class="bg-white rounded-3xl p-6 shadow-sm">

                    <div class="flex items-start justify-between">

                        <div class="flex items-center gap-3">

                            <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center text-2xl">
                                {{ $account->icon ?: '🏦' }}
                            </div>

                            <div>

                                <h2 class="font-semibold text-slate-900">
                                    {{ $account->name }}
                                </h2>

                                <p class="text-sm text-slate-500">
                                    {{ $account->institution ?: $account->type }}
                                </p>

                            </div>

                        </div>

                        <a
                            href="{{ route('accounts.edit', $account) }}"
                            class="text-sm text-slate-500 hover:text-slate-900"
                        >
                            Bearbeiten
                        </a>

                    </div>

                    <div class="mt-6">

                        <p class="text-3xl font-semibold text-slate-900">
                            {{ number_format($account->opening_balance, 2, ',', '.') }}
                            {{ $account->currency }}
                        </p>

                        <p class="text-sm text-slate-500 mt-1">
                            Startsaldo
                        </p>

                    </div>

                    <div class="mt-5 pt-5 border-t border-slate-100 flex items-center justify-between">

                        <span class="text-sm text-slate-500">
                            {{ $account->is_active ? 'Aktiv' : 'Deaktiviert' }}
                        </span>

                        <form
                            method="POST"
                            action="{{ route('accounts.destroy', $account) }}"
                            onsubmit="return confirm('Dieses Konto wirklich löschen?');"
                        >
                            @csrf
                            @method('DELETE')

                            <button
                                type="submit"
                                class="text-sm text-red-500 hover:text-red-700"
                            >
                                Löschen
                            </button>

                        </form>

                    </div>

                </div>

            @endforeach

        </div>

    @else

        <div class="bg-white rounded-3xl p-12 text-center shadow-sm">

            <div class="text-5xl mb-4">
                🏦
            </div>

            <h2 class="text-xl font-semibold text-slate-900">
                Noch keine Konten
            </h2>

            <p class="text-slate-500 mt-2 mb-6">
                Lege dein erstes Girokonto, deine VISA oder dein PayPal-Konto an.
            </p>

            <a
                href="{{ route('accounts.create') }}"
                class="inline-block rounded-xl bg-slate-950 px-5 py-3 text-sm font-medium text-white"
            >
                Erstes Konto erstellen
            </a>

        </div>

    @endif

</div>

</body>
</html>