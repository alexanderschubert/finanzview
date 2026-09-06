        @if($dashboardWidgets['yearly'])

        {{-- JAHRESWERTE --}}

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

            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">
                Dieses Jahr
            </p>

            <div class="mt-4 space-y-4">

                <div class="flex items-center justify-between gap-4">

                    <span class="text-sm text-slate-500 dark:text-slate-400">
                        Einnahmen
                    </span>

                    <span class="font-semibold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                        +{{ number_format($yearlyIncome, 2, ',', '.') }} €
                    </span>

                </div>

                <div class="flex items-center justify-between gap-4">

                    <span class="text-sm text-slate-500 dark:text-slate-400">
                        Ausgaben
                    </span>

                    <span class="font-semibold text-red-600 dark:text-red-400 whitespace-nowrap">
                        -{{ number_format($yearlyExpense, 2, ',', '.') }} €
                    </span>

                </div>

                <div
                    class="
                        pt-3
                        border-t
                        border-slate-100
                        dark:border-slate-800
                        flex
                        items-center
                        justify-between
                        gap-4
                    "
                >

                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                        Jahressaldo
                    </span>

                    <span
                        class="
                            font-semibold
                            whitespace-nowrap
                            {{ ($yearlyIncome - $yearlyExpense) >= 0
                                ? 'text-slate-900 dark:text-white'
                                : 'text-red-600 dark:text-red-400' }}
                        "
                    >

                        {{ ($yearlyIncome - $yearlyExpense) >= 0 ? '+' : '' }}

                        {{ number_format(
                            $yearlyIncome - $yearlyExpense,
                            2,
                            ',',
                            '.'
                        ) }} €

                    </span>

                </div>

            </div>

        </div>

        @endif
