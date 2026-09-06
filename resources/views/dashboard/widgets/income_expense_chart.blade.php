    @if($dashboardWidgets['income_expense_chart'])


    {{-- EINNAHMEN / AUSGABEN --}}
    {{-- ========================================================= --}}

    <div
        class="
            bg-white
            dark:bg-slate-900
            rounded-3xl
            border
            border-slate-200
            dark:border-slate-800
            shadow-sm
            mt-5
            overflow-hidden
        "
    >

        <div class="p-6 sm:p-8">

            <div
                class="
                    flex
                    flex-col
                    sm:flex-row
                    sm:items-start
                    sm:justify-between
                    gap-4
                "
            >

                <div>

                    <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                        Entwicklung
                    </p>

                    <h3 class="text-xl font-semibold text-slate-900 dark:text-white mt-1">
                        Einnahmen & Ausgaben
                    </h3>

                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Vergleich der letzten sechs Monate.
                    </p>

                </div>


                <div
                    class="
                        flex
                        items-center
                        gap-4
                        text-xs
                        text-slate-500
                        dark:text-slate-400
                    "
                >

                    <div class="flex items-center gap-2">

                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>

                        Einnahmen

                    </div>

                    <div class="flex items-center gap-2">

                        <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>

                        Ausgaben

                    </div>

                </div>

            </div>


            @php

                $maxChartValue = max(
                    1,
                    $chartMonths->max(function ($month) {
                        return max(
                            $month['income'],
                            $month['expense']
                        );
                    })
                );

            @endphp


            <div class="space-y-7 mt-8">

                @foreach ($chartMonths as $chartMonth)

                    <div>

                        <div class="flex items-center justify-between gap-4 mb-2">

                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                {{ $chartMonth['label'] }}
                            </span>

                            <span
                                class="
                                    text-xs
                                    font-medium
                                    whitespace-nowrap
                                    {{ $chartMonth['balance'] >= 0
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : 'text-red-600 dark:text-red-400' }}
                                "
                            >

                                Saldo
                                {{ $chartMonth['balance'] >= 0 ? '+' : '' }}

                                {{ number_format(
                                    $chartMonth['balance'],
                                    2,
                                    ',',
                                    '.'
                                ) }} €

                            </span>

                        </div>


                        {{-- EINNAHMEN --}}

                        <div class="flex items-center gap-3">

                            <span class="hidden sm:block w-20 text-xs text-slate-400 dark:text-slate-500">
                                Einnahmen
                            </span>

                            <div
                                class="
                                    flex-1
                                    h-3
                                    bg-slate-100
                                    dark:bg-slate-800
                                    rounded-full
                                    overflow-hidden
                                "
                            >

                                <div
                                    class="h-full bg-emerald-500 rounded-full transition-all"
                                    style="width: {{ ($chartMonth['income'] / $maxChartValue) * 100 }}%"
                                ></div>

                            </div>

                            <span
                                class="
                                    w-24
                                    text-right
                                    text-xs
                                    font-medium
                                    text-slate-700
                                    dark:text-slate-300
                                "
                            >
                                {{ number_format($chartMonth['income'], 0, ',', '.') }} €
                            </span>

                        </div>


                        {{-- AUSGABEN --}}

                        <div class="flex items-center gap-3 mt-2">

                            <span class="hidden sm:block w-20 text-xs text-slate-400 dark:text-slate-500">
                                Ausgaben
                            </span>

                            <div
                                class="
                                    flex-1
                                    h-3
                                    bg-slate-100
                                    dark:bg-slate-800
                                    rounded-full
                                    overflow-hidden
                                "
                            >

                                <div
                                    class="h-full bg-red-500 rounded-full transition-all"
                                    style="width: {{ ($chartMonth['expense'] / $maxChartValue) * 100 }}%"
                                ></div>

                            </div>

                            <span
                                class="
                                    w-24
                                    text-right
                                    text-xs
                                    font-medium
                                    text-slate-700
                                    dark:text-slate-300
                                "
                            >
                                {{ number_format($chartMonth['expense'], 0, ',', '.') }} €
                            </span>

                        </div>

                    </div>

                @endforeach

            </div>

        </div>

    </div>


    @endif
