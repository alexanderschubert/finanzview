<!DOCTYPE html>
<html lang="de">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
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


{{-- ========================================================= --}}
{{-- APP SHELL --}}
{{-- ========================================================= --}}

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

                <div
                    class="
                        w-10 h-10
                        rounded-2xl
                        bg-white
                        text-slate-950
                        flex items-center justify-center
                        font-bold
                        text-lg
                    "
                >
                    F
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
                        ? 'bg-white/10 text-white'
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
                        ? 'bg-white/10 text-white'
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
                        ? 'bg-white/10 text-white'
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
                        ? 'bg-white/10 text-white'
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
                    🎯
                </span>

                <span>
                    Budgets
                </span>

                <span class="ml-auto text-[10px] text-slate-600">
                    BALD
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
                        bg-white/10
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


        {{-- ================================================= --}}
        {{-- TOPBAR --}}
        {{-- ================================================= --}}

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

                <div
                    class="
                        w-9 h-9
                        rounded-xl
                        bg-slate-950
                        text-white
                        flex items-center justify-center
                        font-bold
                    "
                >
                    F
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
                        bg-slate-950
                        px-4
                        py-2.5
                        text-sm
                        font-medium
                        text-white
                        hover:bg-slate-800
                    "
                >
                    + Buchung
                </a>


                {{-- MOBILE BENUTZER --}}

                <div
                    class="
                        w-10 h-10
                        rounded-xl
                        bg-slate-100
                        flex items-center justify-center
                        font-semibold
                        text-slate-700
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



        {{-- ================================================= --}}
        {{-- MOBILE NAVIGATION --}}
        {{-- ================================================= --}}

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
                            ? 'bg-slate-950 text-white'
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
                            ? 'bg-slate-950 text-white'
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
                            ? 'bg-slate-950 text-white'
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
                            ? 'bg-slate-950 text-white'
                            : 'bg-slate-100 text-slate-600' }}
                    "
                >
                    Kategorien
                </a>

            </div>

        </div>



        {{-- ================================================= --}}
        {{-- CONTENT --}}
        {{-- ================================================= --}}

        <main class="flex-1">

            @yield('content')

        </main>


    </div>

</div>

</body>

</html>