    @if($dashboardWidgets['savings_rate'])

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
                    Sparquote
                </p>

                <div
                    class="
                        w-10
                        h-10
                        rounded-2xl
                        bg-slate-100
                        dark:bg-slate-800
                        flex
                        items-center
                        justify-center
                        text-slate-600
                        dark:text-slate-300
                    "
                >
                    %
                </div>

            </div>

            <p
                class="
                    text-3xl
                    font-semibold
                    mt-5
                    {{ $savingsRate >= 0
                        ? 'text-slate-900 dark:text-white'
                        : 'text-red-600 dark:text-red-400' }}
                "
            >

                {{ number_format(
                    $savingsRate,
                    1,
                    ',',
                    '.'
                ) }} %

            </p>

            <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">
                Einnahmen minus Ausgaben
            </p>

        </div>

    @endif
