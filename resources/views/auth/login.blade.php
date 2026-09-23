<!DOCTYPE html>
<html lang="de">
<head>

    {{-- =====================================================
         THEME FRÜH SETZEN
         Vor dem Laden der CSS-Dateien ausführen,
         damit kein sichtbarer Wechsel entsteht.
    ====================================================== --}}

    <script>
        (function () {

            const savedTheme = localStorage.getItem('finanzview-theme');

            const prefersDark = window.matchMedia(
                '(prefers-color-scheme: dark)'
            ).matches;

            const isDark =
                savedTheme === 'dark' ||
                (savedTheme !== 'light' && prefersDark);

            document.documentElement.classList.toggle(
                'dark',
                isDark
            );

        })();
    </script>

    <meta charset="UTF-8">

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <meta name="theme-color" content="#16A34A">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login – FinanzView</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white">


        <!-- Theme Toggle -->
        <button
            type="button"
            id="theme-toggle"
            aria-label="Darstellung wechseln"
            title="Darstellung wechseln"
            class="
                fixed top-4 right-4 z-50
                w-10 h-10
                flex items-center justify-center
                rounded-xl
                border border-slate-200 dark:border-slate-700
                bg-white/90 dark:bg-slate-900/90
                text-slate-600 dark:text-slate-300
                shadow-sm
                backdrop-blur
                hover:bg-slate-50 dark:hover:bg-slate-800
                transition
            "
        >
            <span id="theme-icon-sun" class="hidden text-lg">
                ☀️
            </span>

            <span id="theme-icon-moon" class="hidden text-lg">
                🌙
            </span>
        </button>



    <div class="min-h-screen flex items-center justify-center p-4 sm:p-6">

        <div class="w-full max-w-5xl">

            <div
                class="
                    overflow-hidden
                    rounded-3xl
                    bg-white dark:bg-slate-900
                    shadow-xl
                    border border-slate-200 dark:border-slate-800
                "
            >

                <div class="grid grid-cols-1 lg:grid-cols-2">


                    {{-- ===================================================== --}}
                    {{-- LINKER BEREICH --}}
                    {{-- ===================================================== --}}

                    <div
                        class="
                            hidden lg:flex
                            relative
                            flex-col
                            justify-between
                            min-h-[620px]
                            bg-slate-950
                            p-10 xl:p-12
                            text-white
                            overflow-hidden
                        "
                    >

                        {{-- Dekorative Elemente --}}

                        <div
                            class="
                                absolute
                                -top-32
                                -right-32
                                w-80 h-80
                                rounded-full
                                bg-emerald-500/10
                            "
                        ></div>

                        <div
                            class="
                                absolute
                                -bottom-40
                                -left-40
                                w-96 h-96
                                rounded-full
                                bg-emerald-500/5
                            "
                        ></div>


                        {{-- Logo --}}

                        <div class="relative">

                            <div class="flex items-center gap-4">

                                <div
                                    class="
                                        w-14 h-14
                                        rounded-2xl
                                        bg-emerald-500
                                        flex items-center justify-center
                                        text-3xl
                                        shadow-lg shadow-emerald-500/20
                                    "
                                >
                                    💰
                                </div>

                                <div>

                                    <h1 class="text-2xl font-semibold tracking-tight">
                                        FinanzView
                                    </h1>

                                    <p class="text-sm text-slate-400">
                                        Deine Finanzen. Ein Blick.
                                    </p>

                                </div>

                            </div>

                        </div>


                        {{-- Haupttext --}}

                        <div class="relative max-w-md">

                            <p
                                class="
                                    text-sm
                                    font-medium
                                    text-emerald-400
                                    uppercase
                                    tracking-wider
                                "
                            >
                                Willkommen bei FinanzView
                            </p>

                            <h2
                                class="
                                    mt-4
                                    text-4xl xl:text-5xl
                                    font-semibold
                                    tracking-tight
                                    leading-tight
                                "
                            >
                                Deine Finanzen.
                                <br>
                                Einfach im Blick.
                            </h2>

                            <p
                                class="
                                    mt-6
                                    text-base
                                    leading-7
                                    text-slate-400
                                "
                            >
                                Verwalte Konten, Buchungen und Budgets
                                übersichtlich an einem Ort.
                            </p>


                            {{-- Vorteile --}}

                            <div class="mt-8 space-y-4">

                                <div class="flex items-center gap-3">

                                    <div
                                        class="
                                            w-8 h-8
                                            rounded-lg
                                            bg-emerald-500/10
                                            flex items-center justify-center
                                            text-emerald-400
                                        "
                                    >
                                        ✓
                                    </div>

                                    <span class="text-sm text-slate-300">
                                        Alle Konten zentral verwalten
                                    </span>

                                </div>

                                <div class="flex items-center gap-3">

                                    <div
                                        class="
                                            w-8 h-8
                                            rounded-lg
                                            bg-emerald-500/10
                                            flex items-center justify-center
                                            text-emerald-400
                                        "
                                    >
                                        ✓
                                    </div>

                                    <span class="text-sm text-slate-300">
                                        Einnahmen und Ausgaben im Blick
                                    </span>

                                </div>

                                <div class="flex items-center gap-3">

                                    <div
                                        class="
                                            w-8 h-8
                                            rounded-lg
                                            bg-emerald-500/10
                                            flex items-center justify-center
                                            text-emerald-400
                                        "
                                    >
                                        ✓
                                    </div>

                                    <span class="text-sm text-slate-300">
                                        Budgets und Vermögen übersichtlich planen
                                    </span>

                                </div>

                            </div>

                        </div>


                        {{-- Footer --}}

                        <div class="relative text-xs text-slate-500">
                            FinanzView · Persönliche Finanzverwaltung
                        </div>

                    </div>


                    {{-- ===================================================== --}}
                    {{-- LOGIN BEREICH --}}
                    {{-- ===================================================== --}}

                    <div
                        class="
                            flex
                            items-center
                            p-6 sm:p-10 xl:p-14
                        "
                    >

                        <div class="w-full max-w-md mx-auto">


                            {{-- Mobiles Logo --}}

                            <div class="lg:hidden text-center mb-8">

                                <div
                                    class="
                                        inline-flex
                                        w-16 h-16
                                        rounded-2xl
                                        bg-emerald-100 dark:bg-emerald-500/10
                                        items-center justify-center
                                        text-3xl
                                    "
                                >
                                    💰
                                </div>

                                <h1
                                    class="
                                        text-2xl
                                        font-semibold
                                        text-slate-900 dark:text-white
                                        mt-4
                                    "
                                >
                                    FinanzView
                                </h1>

                                <p
                                    class="
                                        text-sm
                                        text-slate-500 dark:text-slate-400
                                        mt-1
                                    "
                                >
                                    Deine Finanzen. Ein Blick.
                                </p>

                            </div>


                            {{-- Überschrift --}}

                            <div class="mb-8">

                                <p
                                    class="
                                        text-sm
                                        font-medium
                                        text-emerald-600 dark:text-emerald-400
                                    "
                                >
                                    Finanzverwaltung
                                </p>

                                <h2
                                    class="
                                        mt-2
                                        text-3xl
                                        font-semibold
                                        tracking-tight
                                        text-slate-900 dark:text-white
                                    "
                                >
                                    Willkommen zurück
                                </h2>

                                <p
                                    class="
                                        mt-2
                                        text-slate-500 dark:text-slate-400
                                    "
                                >
                                    Melde dich an, um deine Finanzen zu verwalten.
                                </p>

                            </div>


                            {{-- Fehler --}}

                            @if ($errors->any())

                                <div
                                    class="
                                        mb-6
                                        rounded-2xl
                                        border
                                        border-red-100 dark:border-red-900
                                        bg-red-50 dark:bg-red-950/30
                                        p-4
                                        text-sm
                                        text-red-700 dark:text-red-300
                                    "
                                >

                                    <div class="flex gap-3">

                                        <span class="text-lg">
                                            ⚠
                                        </span>

                                        <div>
                                            {{ $errors->first() }}
                                        </div>

                                    </div>

                                </div>

                            @endif


                            {{-- LOGIN FORMULAR --}}

                            <form
                                method="POST"
                                action="{{ url('/login') }}"
                                class="space-y-5"
                            >

                                @csrf


                                {{-- E-MAIL --}}

                                <div>

                                    <label
                                        for="email"
                                        class="
                                            block
                                            text-sm
                                            font-medium
                                            text-slate-700 dark:text-slate-300
                                            mb-2
                                        "
                                    >
                                        E-Mail-Adresse
                                    </label>

                                    <input
                                        type="email"
                                        id="email"
                                        name="email"
                                        value="{{ old('email') }}"
                                        required
                                        autofocus
                                        autocomplete="email"
                                        placeholder="name@beispiel.de"
                                        class="
                                            w-full
                                            rounded-xl
                                            border border-slate-200 dark:border-slate-700
                                            bg-white dark:bg-slate-800
                                            text-slate-900 dark:text-white
                                            px-4 py-3.5
                                            placeholder-slate-400
                                            outline-none
                                            transition
                                            focus:ring-2
                                            focus:ring-emerald-500/20
                                            focus:border-emerald-500
                                        "
                                    >

                                </div>


                                {{-- PASSWORT --}}

                                <div>

                                    <div class="flex items-center justify-between mb-2">

                                        <label
                                            for="password"
                                            class="
                                                block
                                                text-sm
                                                font-medium
                                                text-slate-700 dark:text-slate-300
                                            "
                                        >
                                            Passwort
                                        </label>

                                        <a
                                            href="{{ url('/forgot-password') }}"
                                            class="
                                                text-sm
                                                font-medium
                                                text-emerald-600 dark:text-emerald-400
                                                hover:text-emerald-700
                                                dark:hover:text-emerald-300
                                                transition
                                            "
                                        >
                                            Passwort vergessen?
                                        </a>

                                    </div>

                                    <div class="relative">

                                        <input
                                            type="password"
                                            id="password"
                                            name="password"
                                            required
                                            autocomplete="current-password"
                                            placeholder="••••••••"
                                            class="
                                                w-full
                                                rounded-xl
                                                border border-slate-200 dark:border-slate-700
                                                bg-white dark:bg-slate-800
                                                text-slate-900 dark:text-white
                                                px-4 py-3.5
                                                pr-12
                                                placeholder-slate-400
                                                outline-none
                                                transition
                                                focus:ring-2
                                                focus:ring-emerald-500/20
                                                focus:border-emerald-500
                                            "
                                        >

                                        <button
                                            type="button"
                                            id="toggle-password"
                                            class="
                                                absolute
                                                right-3
                                                top-1/2
                                                -translate-y-1/2
                                                w-9 h-9
                                                rounded-lg
                                                flex items-center justify-center
                                                text-slate-400
                                                hover:text-slate-700
                                                dark:hover:text-slate-200
                                                hover:bg-slate-100
                                                dark:hover:bg-slate-700
                                                transition
                                            "
                                            aria-label="Passwort anzeigen"
                                        >
                                            👁
                                        </button>

                                    </div>

                                </div>


                                {{-- REMEMBER --}}

                                <label
                                    class="
                                        flex
                                        items-center
                                        gap-3
                                        cursor-pointer
                                        select-none
                                    "
                                >

                                    <input
                                        type="checkbox"
                                        name="remember"
                                        value="1"
                                        class="
                                            w-4 h-4
                                            rounded
                                            border-slate-300 dark:border-slate-600
                                            bg-white dark:bg-slate-800
                                            text-emerald-600
                                            focus:ring-emerald-500
                                        "
                                    >

                                    <span
                                        class="
                                            text-sm
                                            text-slate-600 dark:text-slate-400
                                        "
                                    >
                                        Angemeldet bleiben
                                    </span>

                                </label>


                                {{-- BUTTON --}}

                                <button
                                    type="submit"
                                    class="
                                        w-full
                                        inline-flex
                                        items-center
                                        justify-center
                                        rounded-xl
                                        bg-slate-950 dark:bg-white
                                        px-5 py-3.5
                                        text-sm
                                        font-medium
                                        text-white dark:text-slate-950
                                        shadow-sm
                                        hover:bg-slate-800
                                        dark:hover:bg-slate-200
                                        focus:outline-none
                                        focus:ring-2
                                        focus:ring-emerald-500/30
                                        transition
                                    "
                                >
                                    Anmelden
                                </button>

                            </form>


                            {{-- REGISTRIERUNG --}}

                            <div class="relative my-8">

                                <div class="absolute inset-0 flex items-center">
                                    <div
                                        class="
                                            w-full
                                            border-t
                                            border-slate-200 dark:border-slate-800
                                        "
                                    ></div>
                                </div>

                                <div class="relative flex justify-center">

                                    <span
                                        class="
                                            px-4
                                            bg-white dark:bg-slate-900
                                            text-xs
                                            text-slate-400
                                        "
                                    >
                                        oder
                                    </span>

                                </div>

                            </div>


                            {{-- SINGLE SIGN-ON --}}

                            @php($oidc = app(\App\Services\OidcService::class))

                            @if ($oidc->enabled())

                                <a
                                    href="{{ route('oidc.redirect') }}"
                                    class="
                                        mb-6
                                        w-full
                                        inline-flex
                                        items-center
                                        justify-center
                                        gap-2
                                        rounded-xl
                                        border border-slate-200 dark:border-slate-700
                                        bg-white dark:bg-slate-800
                                        px-5 py-3.5
                                        text-sm
                                        font-medium
                                        text-slate-700 dark:text-slate-200
                                        hover:bg-slate-100
                                        dark:hover:bg-slate-700
                                        focus:outline-none
                                        focus:ring-2
                                        focus:ring-emerald-500/30
                                        transition
                                    "
                                >
                                    <span aria-hidden="true">🔑</span>
                                    {{ $oidc->buttonLabel() }}
                                </a>

                            @endif


                            <div class="text-center">

                                @if (\App\Models\ApplicationSetting::get('registration_enabled', true))
                                    <span
                                        class="
                                            text-sm
                                            text-slate-500 dark:text-slate-400
                                        "
                                    >
                                        Noch kein Konto?
                                    </span>

                                    <a
                                        href="{{ url('/register') }}"
                                        class="
                                            ml-1
                                            text-sm
                                            font-medium
                                            text-emerald-600 dark:text-emerald-400
                                            hover:text-emerald-700
                                            dark:hover:text-emerald-300
                                            transition
                                        "
                                    >
                                        Jetzt registrieren
                                    </a>
                                @endif

                            </div>


                        </div>

                    </div>

                </div>

            </div>

            <p
                class="
                    text-center
                    text-xs
                    text-slate-400
                    mt-5
                "
            >
                FinanzView
            </p>

        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- PASSWORT EIN-/AUSBLENDEN --}}
    {{-- ===================================================== --}}

    <script>

        document.addEventListener('DOMContentLoaded', function () {

            const password =
                document.getElementById('password');

            const toggle =
                document.getElementById('toggle-password');


            if (!password || !toggle) {
                return;
            }


            toggle.addEventListener('click', function () {

                const isPassword =
                    password.type === 'password';

                password.type =
                    isPassword ? 'text' : 'password';

                toggle.textContent =
                    isPassword ? '🙈' : '👁';

                toggle.setAttribute(
                    'aria-label',
                    isPassword
                        ? 'Passwort ausblenden'
                        : 'Passwort anzeigen'
                );

            });

        });

    </script>



    <script>
        (function () {

            const toggle = document.getElementById('theme-toggle');
            const sun = document.getElementById('theme-icon-sun');
            const moon = document.getElementById('theme-icon-moon');

            if (!toggle || !sun || !moon) {
                return;
            }

            function updateThemeIcon() {

                const isDark =
                    document.documentElement.classList.contains('dark');

                sun.classList.toggle('hidden', !isDark);
                moon.classList.toggle('hidden', isDark);
            }

            function setTheme(isDark) {

                document.documentElement.classList.toggle(
                    'dark',
                    isDark
                );

                localStorage.setItem(
                    'finanzview-theme',
                    isDark ? 'dark' : 'light'
                );

                updateThemeIcon();
            }

            toggle.addEventListener('click', function () {

                const isDark =
                    document.documentElement.classList.contains('dark');

                setTheme(!isDark);

            });

            updateThemeIcon();

        })();
    </script>

</body>
</html>