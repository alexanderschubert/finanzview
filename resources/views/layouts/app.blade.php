@php
    $user = auth()->user();
    $theme = $user->theme ?? 'system';

    /*
     * Avatar
     */
    $avatarUrl = $user->profile_photo_url ?? null;

    $initials = collect(
        preg_split('/\s+/', trim($user->name))
    )
        ->filter()
        ->take(2)
        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
        ->join('');

    if (!$initials) {
        $initials = strtoupper(substr($user->email, 0, 1));
    }

    /*
     * Navigation: [Route, aktive Routen, Icon, Beschriftung]
     */
    $navigation = [
        null => [
            ['dashboard', 'dashboard', 'home', 'Übersicht'],
            ['accounts.index', 'accounts.*', 'landmark', 'Konten'],
            ['transactions.index', 'transactions.*', 'arrows', 'Buchungen'],
            ['categories.index', 'categories.*', 'tag', 'Kategorien'],
        ],
        'Planung' => [
            ['budgets.index', 'budgets.*', 'target', 'Budgets'],
            ['recurring-transactions.index', 'recurring-transactions.*', 'repeat', 'Wiederkehrend'],
            ['loans.index', 'loans.*', 'banknote', 'Kredite'],
            ['credit-cards.index', 'credit-cards.*', 'card', 'Kreditkarten'],
        ],
        'Auswertung' => [
            ['reports.index', 'reports.*', 'chart', 'Analysen'],
        ],
    ];

    $systemNavigation = [
        ['settings.index', 'settings.*', 'settings', 'Einstellungen'],
    ];

    if ($user->isAdmin()) {
        $systemNavigation[] = ['admin.index', 'admin.*', 'shield', 'Administration'];
    }

    /*
     * Handy: Tab-Leiste unten. Alles andere liegt unter "Mehr".
     */
    $tabBar = [
        ['dashboard', 'dashboard', 'home', 'Übersicht'],
        ['transactions.index', 'transactions.*', 'arrows', 'Buchungen'],
        ['budgets.index', 'budgets.*', 'target', 'Budgets'],
    ];

    $tabBarRoutes = collect($tabBar)->pluck(1)->all();

    $moreIsActive = ! request()->routeIs(...$tabBarRoutes)
        && ! request()->routeIs('transactions.create');
@endphp

<!DOCTYPE html>

<html
    lang="de"
    data-theme="{{ $theme }}"
    class="bg-slate-100 dark:bg-slate-950"
>

<head>

    <meta charset="UTF-8">

    <link rel="icon" type="image/svg+xml" href="{{ asset('finanzview.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

    <meta name="theme-color" content="#f2f2f7" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0b0b0c" media="(prefers-color-scheme: dark)">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="FinanzView">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0, viewport-fit=cover"
    >

    <title>
        @yield('title', 'FinanzView')
    </title>

    {{-- =====================================================
         THEME FRÜH SETZEN
    ====================================================== --}}

    <script>
        (function () {

            const theme = @json($theme);

            const prefersDark = window.matchMedia(
                '(prefers-color-scheme: dark)'
            ).matches;

            const isDark =
                theme === 'dark' ||
                (theme === 'system' && prefersDark);

            document.documentElement.classList.toggle(
                'dark',
                isDark
            );

        })();
    </script>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])

</head>


<body
    class="
        m-0
        min-h-screen
        w-full
        bg-slate-100
        text-slate-900
        dark:bg-slate-950
        dark:text-slate-100
    "
>

<div class="flex min-h-screen w-full">


    {{-- =====================================================
         SEITENLEISTE (DESKTOP)
    ====================================================== --}}

    <aside
        class="
            fv-glass
            print:hidden
            hidden
            lg:flex
            sticky
            top-0
            h-screen
            w-64
            flex-shrink-0
            flex-col
            border-r
            border-slate-200/80
            dark:border-white/5
        "
    >

        {{-- LOGO --}}

        <a
            href="{{ route('dashboard') }}"
            class="flex items-center gap-3 px-5 h-16"
        >
            <x-logo class="w-9 h-9 drop-shadow-sm" />

            <span class="text-[17px] font-semibold tracking-tight text-slate-900 dark:text-white">
                FinanzView
            </span>
        </a>


        {{-- NAVIGATION --}}

        <nav class="flex-1 overflow-y-auto px-3 pb-4 space-y-6">

            @foreach ($navigation as $group => $items)

                <div class="space-y-0.5">

                    @if ($group)
                        <p class="px-3 pb-1.5 text-xs font-semibold text-slate-400 dark:text-slate-500">
                            {{ $group }}
                        </p>
                    @endif

                    @foreach ($items as [$route, $pattern, $icon, $label])
                        <a
                            href="{{ route($route) }}"
                            class="fv-nav-link"
                            @if (request()->routeIs($pattern)) aria-current="page" @endif
                        >
                            <x-icon :name="$icon" class="w-[18px] h-[18px]" />
                            <span>{{ $label }}</span>
                        </a>
                    @endforeach

                </div>

            @endforeach

        </nav>


        {{-- UNTEN: SYSTEM UND BENUTZER --}}

        <div class="px-3 pb-4 pt-3 space-y-0.5 border-t border-slate-200/80 dark:border-white/5">

            @foreach ($systemNavigation as [$route, $pattern, $icon, $label])
                <a
                    href="{{ route($route) }}"
                    class="fv-nav-link"
                    @if (request()->routeIs($pattern)) aria-current="page" @endif
                >
                    <x-icon :name="$icon" class="w-[18px] h-[18px]" />
                    <span>{{ $label }}</span>
                </a>
            @endforeach

            <div class="mt-2 flex items-center gap-3 rounded-xl px-3 py-2">

                @if ($avatarUrl)
                    <img src="{{ $avatarUrl }}" alt="{{ $user->name }}" class="w-8 h-8 rounded-full object-cover">
                @else
                    <div class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs font-semibold">
                        {{ $initials }}
                    </div>
                @endif

                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-slate-900 dark:text-white truncate">
                        {{ $user->name }}
                    </p>
                    <p class="text-xs text-slate-500 truncate">
                        {{ $user->email }}
                    </p>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        title="Abmelden"
                        aria-label="Abmelden"
                        class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-900 hover:bg-slate-900/5 dark:hover:text-white dark:hover:bg-white/5 transition"
                    >
                        <x-icon name="logout" class="w-[18px] h-[18px]" />
                    </button>
                </form>

            </div>

        </div>

    </aside>


    {{-- =====================================================
         RECHTER BEREICH
    ====================================================== --}}

    <div class="flex flex-1 min-w-0 min-h-screen flex-col">


        {{-- =================================================
             KOPFZEILE
        ================================================== --}}

        <header
            class="
                fv-glass
                print:hidden
                sticky
                top-0
                z-30
                h-14
                lg:h-16
                flex-shrink-0
                border-b
                border-slate-200/80
                dark:border-white/5
                flex
                items-center
                justify-between
                gap-3
                px-4
                sm:px-6
                lg:px-8
            "
        >

            {{-- LOGO (HANDY) --}}

            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 lg:hidden min-w-0">
                <x-logo class="w-8 h-8" />
                <span class="font-semibold tracking-tight text-slate-900 dark:text-white truncate">
                    @yield('page_title', 'FinanzView')
                </span>
            </a>


            {{-- SEITENTITEL (DESKTOP) --}}

            <div class="hidden lg:block min-w-0">
                <p class="text-xs font-medium text-slate-400 dark:text-slate-500">
                    @yield('eyebrow', 'Finanzübersicht')
                </p>
                <h1 class="text-[15px] font-semibold text-slate-900 dark:text-white truncate">
                    @yield('page_title', 'Übersicht')
                </h1>
            </div>


            {{-- RECHTS --}}

            <div class="flex items-center gap-2">

                <a
                    href="{{ route('transactions.create') }}"
                    class="
                        hidden
                        sm:inline-flex
                        items-center
                        gap-1.5
                        rounded-full
                        bg-emerald-600
                        pl-3
                        pr-4
                        py-2
                        text-sm
                        font-medium
                        text-white
                        shadow-sm
                        shadow-emerald-900/20
                        hover:bg-emerald-700
                        active:scale-[0.98]
                        transition
                    "
                >
                    <x-icon name="plus" class="w-4 h-4" />
                    Buchung
                </a>


                {{-- PROFIL --}}

                <details class="relative">

                    <summary class="list-none cursor-pointer select-none" aria-label="Profilmenü">

                        @if ($avatarUrl)
                            <img src="{{ $avatarUrl }}" alt="{{ $user->name }}" class="w-9 h-9 rounded-full object-cover">
                        @else
                            <div
                                class="w-9 h-9 rounded-full bg-emerald-600 text-white flex items-center justify-center text-sm font-semibold hover:bg-emerald-700 transition"
                                title="{{ $user->name }}"
                            >
                                {{ $initials }}
                            </div>
                        @endif

                    </summary>

                    <div
                        class="
                            absolute
                            right-0
                            top-12
                            z-50
                            w-64
                            overflow-hidden
                            rounded-2xl
                            border
                            border-slate-200/80
                            dark:border-white/10
                            bg-white/95
                            dark:bg-slate-800/95
                            backdrop-blur-xl
                            shadow-2xl
                            shadow-slate-900/15
                        "
                    >

                        <div class="px-4 py-3 border-b border-slate-100 dark:border-white/5">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">
                                {{ $user->name }}
                            </p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 truncate mt-0.5">
                                {{ $user->email }}
                            </p>
                        </div>

                        <div class="p-1.5">

                            <a
                                href="{{ route('settings.index') }}"
                                class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-white/5 transition"
                            >
                                <x-icon name="settings" class="w-[18px] h-[18px] text-slate-400" />
                                Einstellungen
                            </a>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button
                                    type="submit"
                                    class="w-full flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition"
                                >
                                    <x-icon name="logout" class="w-[18px] h-[18px]" />
                                    Abmelden
                                </button>
                            </form>

                        </div>

                    </div>

                </details>

            </div>

        </header>


        {{-- =====================================================
             INHALT
        ====================================================== --}}

        <main class="flex-1 w-full min-w-0 pb-24 lg:pb-0 print:pb-0">

            @yield('content')

        </main>

    </div>

</div>


{{-- =========================================================
     TAB-LEISTE (HANDY)
========================================================= --}}

<nav
    class="
        fv-safe-bottom
        print:hidden
        lg:hidden
        fixed
        inset-x-0
        bottom-0
        z-40
        border-t
        border-slate-200/80
        dark:border-white/5
    "
    aria-label="Hauptnavigation"
>

    {{-- Glas-Hintergrund als eigene Ebene: backdrop-filter direkt auf der
         Leiste würde das "Mehr"-Menü (position: fixed) einsperren. --}}
    <div class="fv-glass absolute inset-0 -z-10"></div>

    <div class="grid grid-cols-5 h-16">

        @foreach (array_slice($tabBar, 0, 2) as [$route, $pattern, $icon, $label])
            <a
                href="{{ route($route) }}"
                class="flex flex-col items-center justify-center gap-1 text-[11px] font-medium {{ request()->routeIs($pattern) ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400' }}"
                @if (request()->routeIs($pattern)) aria-current="page" @endif
            >
                <x-icon :name="$icon" class="w-6 h-6" />
                {{ $label }}
            </a>
        @endforeach


        {{-- NEUE BUCHUNG --}}

        <div class="flex items-center justify-center">
            <a
                href="{{ route('transactions.create') }}"
                class="w-12 h-12 rounded-full bg-emerald-600 text-white flex items-center justify-center shadow-lg shadow-emerald-900/25 active:scale-95 transition"
                aria-label="Neue Buchung"
            >
                <x-icon name="plus" class="w-6 h-6" />
            </a>
        </div>


        @foreach (array_slice($tabBar, 2) as [$route, $pattern, $icon, $label])
            <a
                href="{{ route($route) }}"
                class="flex flex-col items-center justify-center gap-1 text-[11px] font-medium {{ request()->routeIs($pattern) ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400' }}"
                @if (request()->routeIs($pattern)) aria-current="page" @endif
            >
                <x-icon :name="$icon" class="w-6 h-6" />
                {{ $label }}
            </a>
        @endforeach


        {{-- MEHR --}}

        <details class="group">

            <summary
                class="list-none cursor-pointer h-full flex flex-col items-center justify-center gap-1 text-[11px] font-medium {{ $moreIsActive ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400' }}"
            >
                <x-icon name="more" class="w-6 h-6" />
                Mehr
            </summary>

            {{-- Abdunklung --}}
            <div data-close-details class="fixed inset-0 -z-10 bg-slate-950/30"></div>

            <div
                class="
                    fv-safe-bottom
                    fixed
                    inset-x-3
                    bottom-3
                    z-50
                    max-h-[75vh]
                    overflow-y-auto
                    rounded-3xl
                    bg-white
                    dark:bg-slate-900
                    border
                    border-slate-200/80
                    dark:border-white/10
                    shadow-2xl
                    p-2
                "
            >

                <div class="mx-auto mt-1 mb-2 h-1 w-9 rounded-full bg-slate-300 dark:bg-slate-700"></div>

                @foreach ($navigation as $group => $items)
                    @foreach ($items as [$route, $pattern, $icon, $label])
                        @continue(in_array($pattern, $tabBarRoutes, true))
                        <a
                            href="{{ route($route) }}"
                            class="fv-nav-link py-3"
                            @if (request()->routeIs($pattern)) aria-current="page" @endif
                        >
                            <x-icon :name="$icon" class="w-5 h-5" />
                            <span class="flex-1">{{ $label }}</span>
                            <x-icon name="chevron-right" class="w-4 h-4 text-slate-300 dark:text-slate-600" />
                        </a>
                    @endforeach
                @endforeach

                <div class="my-2 h-px bg-slate-100 dark:bg-white/5"></div>

                @foreach ($systemNavigation as [$route, $pattern, $icon, $label])
                    <a
                        href="{{ route($route) }}"
                        class="fv-nav-link py-3"
                        @if (request()->routeIs($pattern)) aria-current="page" @endif
                    >
                        <x-icon :name="$icon" class="w-5 h-5" />
                        <span class="flex-1">{{ $label }}</span>
                        <x-icon name="chevron-right" class="w-4 h-4 text-slate-300 dark:text-slate-600" />
                    </a>
                @endforeach

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="fv-nav-link py-3 w-full text-red-600 dark:text-red-400">
                        <x-icon name="logout" class="w-5 h-5" />
                        <span>Abmelden</span>
                    </button>
                </form>

            </div>

        </details>

    </div>

</nav>


{{-- =========================================================
     DROPDOWNS BEI KLICK AUSSERHALB SCHLIESSEN
========================================================= --}}

<script>
    document.addEventListener('click', function (event) {

        document
            .querySelectorAll('details[open]')
            .forEach(function (details) {

                const clickedInside = details.contains(event.target);
                const clickedBackdrop = event.target.hasAttribute('data-close-details');

                if (!clickedInside || clickedBackdrop) {
                    details.removeAttribute('open');
                }

            });

    });
</script>


</body>

</html>
