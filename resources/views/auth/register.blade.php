<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Registrieren – Finanzblick</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-950 flex items-center justify-center p-6">

    <div class="w-full max-w-md">

        <div class="text-center mb-8">
            <div class="text-5xl mb-4">💰</div>

            <h1 class="text-3xl font-semibold text-white">
                Finanzblick
            </h1>

            <p class="text-slate-400 mt-2">
                Erstelle dein persönliches Finanzkonto.
            </p>
        </div>

        <div class="bg-white rounded-3xl shadow-2xl p-8">

            <h2 class="text-2xl font-semibold text-slate-900">
                Konto erstellen
            </h2>

            <p class="text-slate-500 mt-1 mb-6">
                Deine Finanzdaten bleiben deinem Benutzerkonto zugeordnet.
            </p>

            @if ($errors->any())
                <div class="mb-5 rounded-xl bg-red-50 p-4 text-red-700">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="/register" class="space-y-5">

                @csrf

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        Name
                    </label>

                    <input
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:ring-2 focus:ring-slate-900"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        E-Mail
                    </label>

                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:ring-2 focus:ring-slate-900"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        Passwort
                    </label>

                    <input
                        type="password"
                        name="password"
                        required
                        class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:ring-2 focus:ring-slate-900"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        Passwort wiederholen
                    </label>

                    <input
                        type="password"
                        name="password_confirmation"
                        required
                        class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:ring-2 focus:ring-slate-900"
                    >
                </div>

                <button
                    type="submit"
                    class="w-full rounded-xl bg-slate-950 py-3.5 font-medium text-white hover:bg-slate-800 transition"
                >
                    Konto erstellen
                </button>

            </form>

            <div class="text-center mt-6">
                <span class="text-sm text-slate-500">
                    Bereits registriert?
                </span>

                <a
                    href="/login"
                    class="text-sm font-medium text-slate-950 hover:underline"
                >
                    Anmelden
                </a>
            </div>

        </div>

    </div>

</body>
</html>