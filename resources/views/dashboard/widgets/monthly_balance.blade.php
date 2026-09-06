        @if($dashboardWidgets['monthly_balance'])

        {{-- MONATSSALDO --}}

        <div
            class="
                {{ $dashboardWidgets['monthly_balance'] && $dashboardWidgets['yearly']
                    ? 'lg:col-span-2'
                    : '' }}
                rounded-3xl
                border
                p-6
                shadow-sm
                {{ $monthlyBalance >= 0
                    ? 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-100 dark:border-emerald-900'
                    : 'bg-red-50 dark:bg-red-950/30 border-red-100 dark:border-red-900' }}
            "
        >

            <div
                class="
                    flex
                    flex-col
                    sm:flex-row
                    sm:items-center
                    sm:justify-between
                    gap-5
                "
            >

                <div>

                    <p class="text-sm font-medium text-slate-600 dark:text-slate-300">
                        Monatssaldo
                    </p>

                    <p
                        class="
                            text-3xl
                            font-semibold
                            tracking-tight
                            mt-2
                            {{ $monthlyBalance >= 0
                                ? 'text-emerald-700 dark:text-emerald-400'
                                : 'text-red-700 dark:text-red-400' }}
                        "
                    >

                        {{ $monthlyBalance >= 0 ? '+' : '' }}

                        {{ number_format(
                            $monthlyBalance,
                            2,
                            ',',
                            '.'
                        ) }} €

                    </p>

                </div>


                <div
                    class="
                        inline-flex
                        items-center
                        rounded-full
                        px-4
                        py-2
                        text-sm
                        font-medium
                        self-start
                        {{ $monthlyBalance >= 0
                            ? 'bg-white dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300'
                            : 'bg-white dark:bg-red-900/60 text-red-700 dark:text-red-300' }}
                    "
                >

                    {{ $monthlyBalance >= 0
                        ? 'Positiver Monat'
                        : 'Mehr Ausgaben als Einnahmen' }}

                </div>

            </div>

        </div>


        @endif
