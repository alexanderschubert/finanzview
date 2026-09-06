    @if($dashboardWidgets['expenses'])

    {{-- AUSGABEN --}}

        <div
            class="
                bg-white
                dark:bg-slate-900
                rounded-3xl
                border
                border-slate-200
                dark:border-slate-800
                shadow-sm
                p-6
            "
        >

            <div class="flex items-center justify-between">

                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Ausgaben
                </p>

                <div
                    class="
                        w-10
                        h-10
                        rounded-2xl
                        bg-red-50
                        dark:bg-red-950/50
                        flex
                        items-center
                        justify-center
                        text-red-600
                        dark:text-red-400
                    "
                >
                    ↘
                </div>

            </div>

            <p class="text-3xl font-semibold text-red-600 dark:text-red-400 mt-5">

                -{{ number_format(
                    $monthlyExpense,
                    2,
                    ',',
                    '.'
                ) }} €

            </p>

            <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">
                {{ $currentMonth }}
            </p>

        </div>


    @endif
