<nav class="bg-white border-b border-slate-200">

    <div class="max-w-7xl mx-auto px-4 sm:px-6">

        <div class="flex items-center justify-between h-16">

            {{-- LOGO --}}

            <a
                href="{{ route('dashboard') }}"
                class="flex items-center gap-3"
            >

                <div class="w-9 h-9 rounded-xl bg-slate-950 text-white flex items-center justify-center font-semibold">
                    F
                </div>

                <div>

                    <p class="font-semibold text-slate-900 leading-none">
                        Finanzblick
                    </p>

                    <p class="text-xs text-slate-400 mt-1">
                        Deine Finanzen
                    </p>

                </div>

            </a>


            {{-- DESKTOP NAVIGATION --}}

            <div class="hidden md:flex items-center gap-1">

                <a
                    href="{{ route('dashboard') }}"
                    class="
                        px-4 py-2 rounded-xl text-sm font-medium
                        {{ request()->routeIs('dashboard')
                            ? 'bg-slate-100 text-slate-900'
                            : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}
                    "
                >
                    Dashboard
                </a>


                <a
                    href="{{ route('accounts.index') }}"
                    class="
                        px-4 py-2 rounded-xl text-sm font-medium
                        {{ request()->routeIs('accounts.*')
                            ? 'bg-slate-100 text-slate-900'
                            : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}
                    "
                >
                    Konten
                </a>


                <a
                    href="{{ route('transactions.index') }}"
                    class="
                        px-4 py-2 rounded-xl text-sm font-medium
                        {{ request()->routeIs('transactions.*')
                            ? 'bg-slate-100 text-slate-900'
                            : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}
                    "
                >
                    Buchungen
                </a>


                <a
                    href="{{ route('categories.index') }}"
                    class="
                        px-4 py-2 rounded-xl text-sm font-medium
                        {{ request()->routeIs('categories.*')
                            ? 'bg-slate-100 text-slate-900'
                            : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}
                    "
                >
                    Kategorien
                </a>

            </div>


            {{-- BENUTZER --}}

            <div class="flex items-center gap-3">

                <div class="hidden sm:block text-right">

                    <p class="text-sm font-medium text-slate-900">
                        {{ auth()->user()->name }}
                    </p>

                    <p class="text-xs text-slate-400">
                        {{ auth()->user()->email }}
                    </p>

                </div>


                <div
                    class="w-9 h-9 rounded-full bg-slate-200 flex items-center justify-center text-sm font-semibold text-slate-700"
                >
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>


                <form
                    method="POST"
                    action="{{ route('logout') }}"
                >

                    @csrf

                    <button
                        type="submit"
                        title="Abmelden"
                        class="text-slate-400 hover:text-red-600 transition"
                    >
                        ⏻
                    </button>

                </form>

            </div>

        </div>


        {{-- MOBILE NAVIGATION --}}

        <div class="md:hidden flex gap-1 pb-3 overflow-x-auto">

            <a
                href="{{ route('dashboard') }}"
                class="
                    whitespace-nowrap px-4 py-2 rounded-xl text-sm font-medium
                    {{ request()->routeIs('dashboard')
                        ? 'bg-slate-100 text-slate-900'
                        : 'text-slate-500' }}
                "
            >
                Dashboard
            </a>


            <a
                href="{{ route('accounts.index') }}"
                class="
                    whitespace-nowrap px-4 py-2 rounded-xl text-sm font-medium
                    {{ request()->routeIs('accounts.*')
                        ? 'bg-slate-100 text-slate-900'
                        : 'text-slate-500' }}
                "
            >
                Konten
            </a>


            <a
                href="{{ route('transactions.index') }}"
                class="
                    whitespace-nowrap px-4 py-2 rounded-xl text-sm font-medium
                    {{ request()->routeIs('transactions.*')
                        ? 'bg-slate-100 text-slate-900'
                        : 'text-slate-500' }}
                "
            >
                Buchungen
            </a>


            <a
                href="{{ route('categories.index') }}"
                class="
                    whitespace-nowrap px-4 py-2 rounded-xl text-sm font-medium
                    {{ request()->routeIs('categories.*')
                        ? 'bg-slate-100 text-slate-900'
                        : 'text-slate-500' }}"
            >
                Kategorien
            </a>

        </div>

    </div>

</nav>