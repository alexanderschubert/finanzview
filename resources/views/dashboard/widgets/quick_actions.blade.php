    @if($dashboardWidgets['quick_actions'])

    {{-- SCHNELLZUGRIFF --}}
    {{-- ========================================================= --}}

    <div class="mt-5">

        <div class="mb-4">

            <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                Schnellzugriff
            </p>

            <h3 class="font-semibold text-slate-900 dark:text-white mt-1">
                Was möchtest du tun?
            </h3>

        </div>


        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">

            @foreach ([
                [
                    'route' => 'accounts.index',
                    'icon' => '🏦',
                    'title' => 'Konten',
                ],
                [
                    'route' => 'transactions.index',
                    'icon' => '💳',
                    'title' => 'Buchungen',
                ],
                [
                    'route' => 'categories.index',
                    'icon' => '🗂️',
                    'title' => 'Kategorien',
                ],
                [
                    'route' => 'budgets.index',
                    'icon' => '🎯',
                    'title' => 'Budgets',
                ],
            ] as $quickAction)

                <a
                    href="{{ route($quickAction['route']) }}"
                    class="
                        bg-white
                        dark:bg-slate-900
                        rounded-2xl
                        border
                        border-slate-200
                        dark:border-slate-800
                        shadow-sm
                        p-5
                        hover:shadow-md
                        hover:-translate-y-0.5
                        hover:bg-slate-50
                        dark:hover:bg-slate-800
                        transition
                    "
                >

                    <div
                        class="
                            w-10
                            h-10
                            rounded-xl
                            bg-emerald-50
                            dark:bg-emerald-950/50
                            flex
                            items-center
                            justify-center
                            text-xl
                        "
                    >
                        {{ $quickAction['icon'] }}
                    </div>

                    <p class="font-medium text-slate-900 dark:text-white mt-4">
                        {{ $quickAction['title'] }}
                    </p>

                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                        Verwalten
                    </p>

                </a>

            @endforeach

        </div>

    </div>

</div>
    @endif
