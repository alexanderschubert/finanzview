<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Zugriff verweigert – Finanzblick</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-100 dark:bg-slate-950 flex items-center justify-center px-6">

    <div class="w-full max-w-lg text-center">

        {{-- Icon --}}
        <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-3xl bg-violet-100 dark:bg-violet-950/40">
            <span class="text-4xl">🛡️</span>
        </div>

        {{-- Error code --}}
        <p class="text-sm font-semibold uppercase tracking-widest text-violet-600 dark:text-violet-400">
            Fehler 403
        </p>

        <h1 class="mt-3 text-3xl font-bold text-slate-900 dark:text-white">
            Zugriff verweigert
        </h1>

        <p class="mt-4 text-slate-600 dark:text-slate-400 leading-relaxed">
            Du hast keine Berechtigung, diese Seite aufzurufen.
            Dieser Bereich ist nur für Administratoren verfügbar.
        </p>

        <div class="mt-8 flex flex-col sm:flex-row justify-center gap-3">

            <a
                href="{{ url('/') }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-violet-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-violet-700"
            >
                ← Zur Übersicht
            </a>

            @auth
                <a
                    href="{{ route('settings.index') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-white dark:bg-slate-900 px-5 py-3 text-sm font-semibold text-slate-700 dark:text-slate-300 ring-1 ring-slate-200 dark:ring-slate-800 transition hover:bg-slate-50 dark:hover:bg-slate-800"
                >
                    Einstellungen
                </a>
            @endauth

        </div>

        <p class="mt-10 text-xs text-slate-400 dark:text-slate-600">
            Finanzblick
        </p>

    </div>

</body>
</html>
