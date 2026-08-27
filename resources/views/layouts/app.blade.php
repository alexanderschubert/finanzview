<!DOCTYPE html>
<html lang="de">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="theme-color"
        content="#16a34a"
    >

    <link
        rel="icon"
        type="image/svg+xml"
        href="{{ asset('finanzblick.svg') }}"
    >

    <title>
        @yield('title', 'Finanzblick')
    </title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])

</head>


<body class="min-h-screen bg-slate-100 text-slate-900">


<div class="min-h-screen flex">


    {{-- ===================================================== --}}
    {{-- SIDEBAR --}}
    {{-- ===================================================== --}}

    <aside
        class="
            hidden lg:flex
            w-64
            flex-col
            bg-slate-950
            text-white
            flex-shrink-0
        "
    >

        {{-- LOGO --}}

        <div class="h-20 px-6 flex items-center">

            <a
                href="{{ route('dashboard') }}"
                class="flex items-center gap-3"
            >

                <div class="w-10 h-10 rounded-2xl overflow-hidden flex-shrink-0">

                    <img
                        src="{{ asset('finanzblick.svg') }}"
                        alt="Finanzblick"
                        class="w-full h-full"
                    >

                </div>


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


        {{-- NAVIGATION --}}

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
                        ? 'bg-finanz-600 text-white'
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
                        ? 'bg-finanz-600 text-white'
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
                        ? 'bg-finanz-600 text-white'
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
                        ? 'bg-finanz-600 text-white'
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


            {{-- TRENNER --}}

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
                        ? 'bg-finanz-600 text-white'
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


        {{-- SIDEBAR UNTEN --}}

        <div class="p-4 border-t border-white/10">


            {{-- EINSTELLUNGEN --}}

            <a
                href="#"
                class="
                    flex items-center gap-3
                    px-4 py-3
                    rounded-xl
                    text-sm font-medium
                    text-slate-400
                    hover:bg-white/5
                    hover:text-white
                "
            >

                <span class="text-lg">
                    ⚙️
                </span>

                <span>
                    Einstellungen
                </span>

            </a>


            {{-- BENUTZER --}}

            <div class="mt-3 flex items-center gap-3 px-4 py-3">

                <div
                    class="
                        w-9 h-9
                        rounded-full
                        bg-finanz-600
                        flex items-center justify-center
                        text-sm font-semibold
                    "
                >

                    {{ strtoupper(
                        substr(
                            auth()->user()->name,
                            0,
                            1
                        )
                    ) }}

                </div>


                <div class="min-w-0 flex-1">

                    <p class="text-sm font-medium truncate">
                        {{ auth()->user()->name }}
                    </p>

                    <p class="text-xs text-slate-500 truncate">
                        {{ auth()->user()->email }}
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
                        class="text-slate-500 hover:text-white transition"
                    >
                        ⏻
                    </button>

                </form>

            </div>

        </div>

    </aside>


    {{-- ===================================================== --}}
    {{-- RECHTER BEREICH --}}
    {{-- ===================================================== --}}

    <div class="flex-1 min-w-0 flex flex-col">


        {{-- TOPBAR --}}

        <header
            class="
                h-20
                bg-white
                border-b
                border-slate-200
                flex
                items-center
                justify-between
                px-4
                sm:px-6
                lg:px-8
            "
        >


            {{-- MOBILE LOGO --}}

            <div class="flex items-center gap-3 lg:hidden">

                <div class="w-9 h-9 rounded-xl overflow-hidden">

                    <img
                        src="{{ asset('finanzblick.svg') }}"
                        alt="Finanzblick"
                        class="w-full h-full"
                    >

                </div>

                <span class="font-semibold">
                    Finanzblick
                </span>

            </div>


            {{-- DESKTOP SEITENTITEL --}}

            <div class="hidden lg:block">

                <p class="text-sm text-slate-400">
                    @yield('eyebrow', 'Finanzübersicht')
                </p>

                <h1 class="text-lg font-semibold text-slate-900">
                    @yield('page_title', 'Übersicht')
                </h1>

            </div>


            {{-- TOPBAR RECHTS --}}

            <div class="flex items-center gap-3">


                {{-- BENACHRICHTIGUNGEN --}}

                <button
                    type="button"
                    class="
                        w-10 h-10
                        rounded-xl
                        border
                        border-slate-200
                        bg-white
                        flex items-center justify-center
                        text-slate-500
                        hover:bg-slate-50
                    "
                    title="Benachrichtigungen"
                >
                    🔔
                </button>


                {{-- NEUE BUCHUNG --}}

                <a
                    href="{{ route('transactions.create') }}"
                    class="
                        hidden sm:inline-flex
                        items-center
                        justify-center
                        rounded-xl
                        bg-finanz-600
                        px-4
                        py-2.5
                        text-sm
                        font-medium
                        text-white
                        hover:bg-finanz-700
                        transition
                    "
                >
                    + Buchung
                </a>


                {{-- MOBILE BENUTZER --}}

                <div
                    class="
                        w-10 h-10
                        rounded-xl
                        bg-finanz-100
                        flex items-center justify-center
                        font-semibold
                        text-finanz-700
                    "
                >

                    {{ strtoupper(
                        substr(
                            auth()->user()->name,
                            0,
                            1
                        )
                    ) }}

                </div>

            </div>

        </header>


        {{-- MOBILE NAVIGATION --}}

        <div class="lg:hidden bg-white border-b border-slate-200 px-4 py-3">

            <div class="flex gap-2 overflow-x-auto">

                <a
                    href="{{ route('dashboard') }}"
                    class="
                        whitespace-nowrap
                        px-4 py-2
                        rounded-xl
                        text-sm font-medium
                        {{ request()->routeIs('dashboard')
                            ? 'bg-finanz-600 text-white'
                            : 'bg-slate-100 text-slate-600' }}
                    "
                >
                    Übersicht
                </a>


                <a
                    href="{{ route('accounts.index') }}"
                    class="
                        whitespace-nowrap
                        px-4 py-2
                        rounded-xl
                        text-sm font-medium
                        {{ request()->routeIs('accounts.*')
                            ? 'bg-finanz-600 text-white'
                            : 'bg-slate-100 text-slate-600' }}
                    "
                >
                    Konten
                </a>


                <a
                    href="{{ route('transactions.index') }}"
                    class="
                        whitespace-nowrap
                        px-4 py-2
                        rounded-xl
                        text-sm font-medium
                        {{ request()->routeIs('transactions.*')
                            ? 'bg-finanz-600 text-white'
                            : 'bg-slate-100 text-slate-600' }}
                    "
                >
                    Buchungen
                </a>


                <a
                    href="{{ route('categories.index') }}"
                    class="
                        whitespace-nowrap
                        px-4 py-2
                        rounded-xl
                        text-sm font-medium
                        {{ request()->routeIs('categories.*')
                            ? 'bg-finanz-600 text-white'
                            : 'bg-slate-100 text-slate-600' }}
                    "
                >
                    Kategorien
                </a>


                <a
                    href="{{ route('budgets.index') }}"
                    class="
                        whitespace-nowrap
                        px-4 py-2
                        rounded-xl
                        text-sm font-medium
                        {{ request()->routeIs('budgets.*')
                            ? 'bg-finanz-600 text-white'
                            : 'bg-slate-100 text-slate-600' }}
                    "
                >
                    Budgets
                </a>

            </div>

        </div>


        {{-- CONTENT --}}

        <main class="flex-1">

            @yield('content')

        </main>


    </div>

</div>

</body>

</html>
