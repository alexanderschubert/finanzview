<!DOCTYPE html>
<html lang="de">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">

    <title>@yield('title') – FinanzView</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('finanzview.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

    <meta name="theme-color" content="#f2f2f7" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0b0b0c" media="(prefers-color-scheme: dark)">

    {{-- Theme vor dem CSS setzen, damit nichts aufblitzt --}}
    <script>
        (function () {
            let saved = null;

            try {
                saved = localStorage.getItem('finanzview-theme');
            } catch (e) {}

            const isDark = saved === 'dark'
                || (saved !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches);

            document.documentElement.classList.toggle('dark', isDark);
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>

<body class="min-h-screen bg-slate-100 text-slate-900 dark:bg-slate-950 dark:text-white">

    {{-- Dezenter Farbschein im Hintergrund --}}
    <div aria-hidden="true" class="fv-glow pointer-events-none fixed inset-x-0 top-0 h-[420px]"></div>


    {{-- Hell / Dunkel --}}
    <button
        type="button"
        id="theme-toggle"
        aria-label="Darstellung wechseln"
        title="Darstellung wechseln"
        class="fixed top-4 right-4 z-10 w-10 h-10 rounded-full flex items-center justify-center text-slate-500 hover:text-slate-900 hover:bg-slate-900/5 dark:text-slate-400 dark:hover:text-white dark:hover:bg-white/10 transition"
    >
        <x-icon name="moon" class="w-5 h-5 dark:hidden" />
        <x-icon name="sun" class="w-5 h-5 hidden dark:block" />
    </button>


    <main class="relative min-h-screen flex flex-col items-center justify-center px-5 py-12">

        <div class="w-full max-w-[400px]">

            {{-- Marke --}}
            <div class="flex flex-col items-center text-center mb-8">
                <x-logo class="w-16 h-16 rounded-[18px] shadow-lg shadow-emerald-900/20" />

                <h1 class="mt-5 text-[28px] font-semibold tracking-tight">
                    @yield('heading')
                </h1>

                @hasSection('intro')
                    <p class="mt-2 text-[15px] text-slate-500 dark:text-slate-400 max-w-[320px]">
                        @yield('intro')
                    </p>
                @endif
            </div>


            {{-- Karte --}}
            <div class="fv-card p-6 sm:p-8">

                @if (session('status'))
                    <div class="mb-5 flex gap-3 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 p-3.5 text-sm text-emerald-800 dark:text-emerald-300">
                        <x-icon name="check-circle" class="w-5 h-5 mt-px" />
                        <p>{{ session('status') }}</p>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-5 flex gap-3 rounded-xl bg-red-50 dark:bg-red-500/10 p-3.5 text-sm text-red-700 dark:text-red-300" role="alert">
                        <x-icon name="alert" class="w-5 h-5 mt-px" />
                        <div class="space-y-1">
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                @endif

                @yield('content')

            </div>


            @hasSection('footer')
                <div class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
                    @yield('footer')
                </div>
            @endif

        </div>

        <p class="mt-10 text-xs text-slate-400 dark:text-slate-600">
            FinanzView · Persönliche Finanzverwaltung
        </p>

    </main>


    <script>
        document.getElementById('theme-toggle').addEventListener('click', function () {
            const isDark = document.documentElement.classList.toggle('dark');

            try {
                localStorage.setItem('finanzview-theme', isDark ? 'dark' : 'light');
            } catch (e) {}
        });

        // Passwort ein-/ausblenden: <button data-toggle-password="feld-id">
        document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
            const input = document.getElementById(button.dataset.togglePassword);

            button.addEventListener('click', function () {
                const show = input.type === 'password';

                input.type = show ? 'text' : 'password';
                button.setAttribute('aria-label', show ? 'Passwort ausblenden' : 'Passwort anzeigen');
                button.querySelector('[data-icon-show]').classList.toggle('hidden', show);
                button.querySelector('[data-icon-hide]').classList.toggle('hidden', !show);
            });
        });
    </script>

</body>
</html>
