@php
    $user = auth()->user();
    $theme = $user->theme ?? 'system';

    /*
     * Avatar:
     * Falls später ein echtes Profilbild vorhanden ist,
     * kann dieses hier verwendet werden.
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
@endphp

<!DOCTYPE html>

<html
    lang="de"
    data-theme="{{ $theme }}"
>

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        @yield('title', 'Finanzblick')
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
        min-h-screen
        bg-slate-100
        text-slate-900
        dark:bg-slate-950
        dark:text-slate-100
        transition-colors
        duration-200
    "
>


<div class="min-h-screen flex">


    {{-- =====================================================
         SIDEBAR
    ====================================================== --}}

    <aside
        class="
            hidden
            lg:flex
            w-64
            flex-col
            bg-slate-950
            text-white
            flex-shrink-0
        "
    >

        {{-- =================================================
             LOGO
        ================================================== --}}

        <div class="h-20 px-6 flex items-center">

            <a
                href="{{ route('dashboard') }}"
                class="flex items-center gap-3"
            >

                <img
                    src="{{ asset('finanzblick.svg') }}"
                    alt="Finanzblick"
                    class="w-10 h-10"
                >

                <div>

                    <div class="font-semibold text-lg leading-none">
                        Finanzblick
                    </div>

                    <div class="text-xs text-slate-400 mt-1">
                        Deine Finanzen
                    </div>

                </div>

            </a>

        </div>


        {{-- =================================================
             NAVIGATION
        ================================================== --}}

        <nav class="flex-1 px-4 py-6 space-y-1">


            {{-- ÜBERSICHT --}}

            <a
                href="{{ route('dashboard') }}"
                class="
                    flex items-center gap-3
                    px-4 py-3
                    rounded-xl
                    text-sm font-medium
                    transition
                    {{ request()->routeIs('dashboard')
                        ? 'bg-emerald-500/20 text-emerald-400'
                        : 'text-slate-400 hover:bg-white/5 hover:text-white' }}
                "
            >

                <span class="text-lg">
                    🏠
                </span>

                <span>
                    Übersicht
                </span>

            </a>


            {{-- KONTEN --}}

            <a
                href="{{ route('accounts.index') }}"
                class="
                    flex items-center gap-3
                    px-4 py-3
                    rounded-xl
                    text-sm font-medium
                    transition
                    {{ request()->routeIs('accounts.*')
                        ? 'bg-emerald-500/20 text-emerald-400'
                        : 'text-slate-400 hover:bg-white/5 hover:text-white' }}
                "
            >

                <span class="text-lg">
                    🏦
                </span>

                <span>
                    Konten
                </span>

            </a>


            {{-- BUCHUNGEN --}}

            <a
                href="{{ route('transactions.index') }}"
                class="
                    flex items-center gap-3
                    px-4 py-3
                    rounded-xl
                    text-sm font-medium
                    transition
                    {{ request()->routeIs('transactions.*')
                        ? 'bg-emerald-500/20 text-emerald-400'
                        : 'text-slate-400 hover:bg-white/5 hover:text-white' }}
                "
            >

                <span class="text-lg">
                    💳
                </span>

                <span>
                    Buchungen
                </span>

            </a>


            {{-- KATEGORIEN --}}

            <a
                href="{{ route('categories.index') }}"
                class="
                    flex items-center gap-3
                    px-4 py-3
                    rounded-xl
                    text-sm font-medium
                    transition
                    {{ request()->routeIs('categories.*')
                        ? 'bg-emerald-500/20 text-emerald-400'
                        : 'text-slate-400 hover:bg-white/5 hover:text-white' }}
                "
            >

                <span class="text-lg">
                    🗂️
                </span>

                <span>
                    Kategorien
                </span>

            </a>


            {{-- =================================================
                 PLANUNG
            ================================================== --}}

            <div class="pt-6 pb-3">

                <p
                    class="
                        px-4
                        text-[11px]
                        font-semibold
                        uppercase
                        tracking-wider
                        text-slate-500
                    "
                >
                    Planung
                </p>

            </div>


            {{-- BUDGETS --}}

            <a
                href="{{ route('budgets.index') }}"
                class="
                    flex items-center gap-3
                    px-4 py-3
                    rounded-xl
                    text-sm font-medium
                    transition
                    {{ request()->routeIs('budgets.*')
                        ? 'bg-emerald-500/20 text-emerald-400'
                        : 'text-slate-400 hover:bg-white/5 hover:text-white' }}
                "
            >

                <span class="text-lg">
                    🎯
                </span>

                <span>
                    Budgets
                </span>

            </a>


            {{-- KREDITE --}}

            <a
                href="{{ route('loans.index') }}"
                class="
                    flex items-center gap-3
                    px-4 py-3
                    rounded-xl
                    text-sm font-medium
                    transition
                    {{ request()->routeIs('loans.*')
                        ? 'bg-emerald-500/20 text-emerald-400'
                        : 'text-slate-400 hover:bg-white/5 hover:text-white' }}
                "
            >

                <span class="text-lg">
                    💳
                </span>

                <span>
                    Kredite
                </span>

            </a>


            {{-- ANALYSEN --}}

            <a
                href="#"
                class="
                    flex items-center gap-3
                    px-4 py-3
                    rounded-xl
                    text-sm font-medium
                    text-slate-500
                    cursor-not-allowed
                "
            >

                <span class="text-lg">
                    📊
                </span>

                <span>
                    Analysen
                </span>

                <span class="ml-auto text-[10px] text-slate-600">
                    BALD
                </span>

            </a>


            {{-- WIEDERKEHREND --}}

            <a
                href="#"
                class="
                    flex items-center gap-3
                    px-4 py-3
                    rounded-xl
                    text-sm font-medium
                    text-slate-500
                    cursor-not-allowed
                "
            >

                <span class="text-lg">
                    🔄
                </span>

                <span>
                    Wiederkehrend
                </span>

                <span class="ml-auto text-[10px] text-slate-600">
                    BALD
                </span>

            </a>

        </nav>


        {{-- =====================================================
             SIDEBAR UNTEN
        ====================================================== --}}

        <div class="p-4 border-t border-white/10">


            {{-- EINSTELLUNGEN --}}

            <a
                href="{{ route('settings.index') }}"
                class="
                    flex items-center gap-3
                    px-4 py-3
                    rounded-xl
                    text-sm font-medium
                    transition
                    {{ request()->routeIs('settings.*')
                        ? 'bg-emerald-500/20 text-emerald-400'
                        : 'text-slate-400 hover:bg-white/5 hover:text-white' }}
                "
            >

                <span class="text-lg">
                    ⚙️
                </span>

                <span>
                    Einstellungen
                </span>

            </a>


            {{-- =================================================
                 BENUTZER SIDEBAR
            ================================================== --}}

            <div class="mt-3 flex items-center gap-3 px-4 py-3">

                {{-- AVATAR --}}

                @if ($avatarUrl)

                    <img
                        src="{{ $avatarUrl }}"
                        alt="{{ $user->name }}"
                        class="
                            w-9
                            h-9
                            rounded-full
                            object-cover
                            flex-shrink-0
                        "
                    >

                @else

                    <div
                        class="
                            w-9
                            h-9
                            rounded-full
                            bg-emerald-500
                            text-white
                            flex
                            items-center
                            justify-center
                            text-sm
                            font-semibold
                            flex-shrink-0
                        "
                    >
                        {{ $initials }}
                    </div>

                @endif


                <div class="min-w-0 flex-1">

                    <p class="text-sm font-medium truncate">
                        {{ $user->name }}
                    </p>

                    <p class="text-xs text-slate-500 truncate">
                        {{ $user->email }}
                    </p>

                </div>


                {{-- LOGOUT --}}

                <form
                    method="POST"
                    action="{{ route('logout') }}"
                >

                    @csrf

                    <button
                        type="submit"
                        title="Abmelden"
                        class="
                            w-8
                            h-8
                            rounded-lg
                            flex
                            items-center
                            justify-center
                            text-slate-500
                            hover:text-white
                            hover:bg-white/5
                            transition
                        "
                    >
                        ⏻
                    </button>

                </form>

            </div>

        </div>

    </aside>


    {{-- =====================================================
         RECHTER BEREICH
    ====================================================== --}}

    <div class="flex-1 min-w-0 flex flex-col">


        {{-- =================================================
             TOPBAR
        ================================================== --}}

        <header
            class="
                h-20
                bg-white
                dark:bg-slate-900
                border-b
                border-slate-200
                dark:border-slate-800
                flex
                items-center
                justify-between
                px-4
                sm:px-6
                lg:px-8
                transition-colors
                duration-200
                relative
            "
        >


            {{-- MOBILE LOGO --}}

            <div class="flex items-center gap-3 lg:hidden">

                <img
                    src="{{ asset('finanzblick.svg') }}"
                    alt="Finanzblick"
                    class="w-9 h-9"
                >

                <span
                    class="
                        font-semibold
                        text-slate-900
                        dark:text-white
                    "
                >
                    Finanzblick
                </span>

            </div>


            {{-- DESKTOP SEITENTITEL --}}

            <div class="hidden lg:block">

                <p class="text-sm text-slate-400 dark:text-slate-500">

                    @yield(
                        'eyebrow',
                        'Finanzübersicht'
                    )

                </p>

                <h1
                    class="
                        text-lg
                        font-semibold
                        text-slate-900
                        dark:text-white
                    "
                >

                    @yield(
                        'page_title',
                        'Übersicht'
                    )

                </h1>

            </div>


            {{-- =================================================
                 TOPBAR RECHTS
            ================================================== --}}

            <div class="flex items-center gap-2 sm:gap-3">


                {{-- =================================================
                     BENACHRICHTIGUNGEN
                ================================================== --}}

                <button
                    type="button"
                    class="
                        relative
                        w-10
                        h-10
                        rounded-xl
                        border
                        border-slate-200
                        dark:border-slate-700
                        bg-white
                        dark:bg-slate-800
                        flex
                        items-center
                        justify-center
                        text-slate-500
                        dark:text-slate-400
                        hover:bg-slate-50
                        dark:hover:bg-slate-700
                        hover:text-slate-900
                        dark:hover:text-white
                        transition
                    "
                    title="Benachrichtigungen"
                >

                    <span class="text-lg">
                        🔔
                    </span>

                    {{-- Badge für zukünftige Benachrichtigungen --}}

                    <span
                        class="
                            hidden
                            absolute
                            top-1
                            right-1
                            w-2
                            h-2
                            rounded-full
                            bg-red-500
                            ring-2
                            ring-white
                            dark:ring-slate-900
                        "
                    ></span>

                </button>


                {{-- =================================================
                     NEUE BUCHUNG
                ================================================== --}}

                <a
                    href="{{ route('transactions.create') }}"
                    class="
                        hidden
                        sm:inline-flex
                        items-center
                        justify-center
                        rounded-xl
                        bg-emerald-600
                        px-4
                        py-2.5
                        text-sm
                        font-medium
                        text-white
                        hover:bg-emerald-700
                        transition
                    "
                >
                    + Buchung
                </a>


                {{-- =================================================
                     PROFIL
                ================================================== --}}

                <details class="relative">

                    <summary
                        class="
                            list-none
                            cursor-pointer
                            select-none
                        "
                    >

                        @if ($avatarUrl)

                            <img
                                src="{{ $avatarUrl }}"
                                alt="{{ $user->name }}"
                                class="
                                    w-10
                                    h-10
                                    rounded-xl
                                    object-cover
                                    border
                                    border-slate-200
                                    dark:border-slate-700
                                "
                            >

                        @else

                            <div
                                class="
                                    w-10
                                    h-10
                                    rounded-xl
                                    bg-emerald-50
                                    dark:bg-emerald-950/50
                                    border
                                    border-emerald-100
                                    dark:border-emerald-900
                                    flex
                                    items-center
                                    justify-center
                                    font-semibold
                                    text-emerald-700
                                    dark:text-emerald-400
                                    hover:bg-emerald-100
                                    dark:hover:bg-emerald-950
                                    transition
                                "
                                title="{{ $user->name }}"
                            >
                                {{ $initials }}
                            </div>

                        @endif

                    </summary>


                    {{-- =================================================
                         PROFIL DROPDOWN
                    ================================================== --}}

                    <div
                        class="
                            absolute
                            right-0
                            top-14
                            z-50
                            w-72
                            overflow-hidden
                            rounded-2xl
                            border
                            border-slate-200
                            dark:border-slate-700
                            bg-white
                            dark:bg-slate-900
                            shadow-xl
                        "
                    >

                        {{-- USER HEADER --}}

                        <div
                            class="
                                p-4
                                border-b
                                border-slate-100
                                dark:border-slate-800
                            "
                        >

                            <div class="flex items-center gap-3">

                                @if ($avatarUrl)

                                    <img
                                        src="{{ $avatarUrl }}"
                                        alt="{{ $user->name }}"
                                        class="
                                            w-11
                                            h-11
                                            rounded-xl
                                            object-cover
                                        "
                                    >

                                @else

                                    <div
                                        class="
                                            w-11
                                            h-11
                                            rounded-xl
                                            bg-emerald-100
                                            dark:bg-emerald-950
                                            flex
                                            items-center
                                            justify-center
                                            font-semibold
                                            text-emerald-700
                                            dark:text-emerald-400
                                        "
                                    >
                                        {{ $initials }}
                                    </div>

                                @endif


                                <div class="min-w-0">

                                    <p
                                        class="
                                            font-semibold
                                            text-slate-900
                                            dark:text-white
                                            truncate
                                        "
                                    >
                                        {{ $user->name }}
                                    </p>

                                    <p
                                        class="
                                            text-xs
                                            text-slate-500
                                            dark:text-slate-400
                                            truncate
                                            mt-0.5
                                        "
                                    >
                                        {{ $user->email }}
                                    </p>

                                </div>

                            </div>

                        </div>


                        {{-- MENU --}}

                        <div class="p-2">


                            {{-- EINSTELLUNGEN --}}

                            <a
                                href="{{ route('settings.index') }}"
                                class="
                                    flex
                                    items-center
                                    gap-3
                                    rounded-xl
                                    px-3
                                    py-2.5
                                    text-sm
                                    text-slate-700
                                    dark:text-slate-200
                                    hover:bg-slate-100
                                    dark:hover:bg-slate-800
                                    transition
                                "
                            >

                                <span class="text-lg">
                                    ⚙️
                                </span>

                                <span>
                                    Einstellungen
                                </span>

                            </a>


                            {{-- ABMELDEN --}}

                            <form
                                method="POST"
                                action="{{ route('logout') }}"
                            >

                                @csrf

                                <button
                                    type="submit"
                                    class="
                                        w-full
                                        flex
                                        items-center
                                        gap-3
                                        rounded-xl
                                        px-3
                                        py-2.5
                                        text-sm
                                        text-red-600
                                        dark:text-red-400
                                        hover:bg-red-50
                                        dark:hover:bg-red-950/30
                                        transition
                                    "
                                >

                                    <span class="text-lg">
                                        ⏻
                                    </span>

                                    <span>
                                        Abmelden
                                    </span>

                                </button>

                            </form>

                        </div>

                    </div>

                </details>

            </div>

        </header>


        {{-- =====================================================
             MOBILE NAVIGATION
        ====================================================== --}}

        <div
            class="
                lg:hidden
                bg-white
                dark:bg-slate-900
                border-b
                border-slate-200
                dark:border-slate-800
                px-4
                py-3
            "
        >

            <div class="flex gap-2 overflow-x-auto">


                <a
                    href="{{ route('dashboard') }}"
                    class="
                        whitespace-nowrap
                        px-4
                        py-2
                        rounded-xl
                        text-sm
                        font-medium
                        {{ request()->routeIs('dashboard')
                            ? 'bg-emerald-600 text-white'
                            : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300' }}
                    "
                >
                    🏠 Übersicht
                </a>


                <a
                    href="{{ route('accounts.index') }}"
                    class="
                        whitespace-nowrap
                        px-4
                        py-2
                        rounded-xl
                        text-sm
                        font-medium
                        {{ request()->routeIs('accounts.*')
                            ? 'bg-emerald-600 text-white'
                            : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300' }}
                    "
                >
                    🏦 Konten
                </a>


                <a
                    href="{{ route('transactions.index') }}"
                    class="
                        whitespace-nowrap
                        px-4
                        py-2
                        rounded-xl
                        text-sm
                        font-medium
                        {{ request()->routeIs('transactions.*')
                            ? 'bg-emerald-600 text-white'
                            : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300' }}
                    "
                >
                    💳 Buchungen
                </a>


                <a
                    href="{{ route('categories.index') }}"
                    class="
                        whitespace-nowrap
                        px-4
                        py-2
                        rounded-xl
                        text-sm
                        font-medium
                        {{ request()->routeIs('categories.*')
                            ? 'bg-emerald-600 text-white'
                            : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300' }}
                    "
                >
                    🗂️ Kategorien
                </a>


                <a
                    href="{{ route('budgets.index') }}"
                    class="
                        whitespace-nowrap
                        px-4
                        py-2
                        rounded-xl
                        text-sm
                        font-medium
                        {{ request()->routeIs('budgets.*')
                            ? 'bg-emerald-600 text-white'
                            : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300' }}
                    "
                >
                    🎯 Budgets
                </a>


                <a
                    href="{{ route('loans.index') }}"
                    class="
                        whitespace-nowrap
                        px-4
                        py-2
                        rounded-xl
                        text-sm
                        font-medium
                        {{ request()->routeIs('loans.*')
                            ? 'bg-emerald-600 text-white'
                            : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300' }}
                    "
                >
                    💳 Kredite
                </a>


                <a
                    href="{{ route('settings.index') }}"
                    class="
                        whitespace-nowrap
                        px-4
                        py-2
                        rounded-xl
                        text-sm
                        font-medium
                        {{ request()->routeIs('settings.*')
                            ? 'bg-emerald-600 text-white'
                            : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300' }}
                    "
                >
                    ⚙️ Einstellungen
                </a>

            </div>

        </div>


        {{-- =====================================================
             CONTENT
        ====================================================== --}}

        <main
            class="
                flex-1
                bg-slate-100
                dark:bg-slate-950
                transition-colors
                duration-200
            "
        >

            @yield('content')

        </main>

    </div>

</div>


{{-- =========================================================
     DROPDOWN BEI KLICK AUSSERHALB SCHLIESSEN
========================================================= --}}

<script>
    document.addEventListener('click', function (event) {

        document
            .querySelectorAll('details[open]')
            .forEach(function (details) {

                if (!details.contains(event.target)) {
                    details.removeAttribute('open');
                }

            });

    });
</script>


</body>

</html>