        @if($dashboardWidgets['categories'] && $expensesByCategory->isNotEmpty())

        {{-- AUSGABEN NACH KATEGORIE --}}

        <div
            class="
                bg-white
                dark:bg-slate-900
                rounded-3xl
                border
                border-slate-200
                dark:border-slate-800
                shadow-sm
                overflow-hidden
            "
        >

            <div
                class="
                    p-6
                    border-b
                    border-slate-100
                    dark:border-slate-800
                "
            >

                <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                    Ausgaben
                </p>

                <h3 class="font-semibold text-slate-900 dark:text-white mt-1">
                    Nach Kategorie
                </h3>

                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    {{ $currentMonth }}
                </p>

            </div>


            @if ($expensesByCategory->isEmpty())

                <div class="p-8 text-center">

                    <div class="w-12 h-12 mx-auto rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-xl">
                        📊
                    </div>

                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-4">
                        Noch keine Ausgaben.
                    </p>

                </div>

            @else

                <div class="p-5 space-y-5">

                    @foreach ($expensesByCategory as $item)

                        @php

                            $percentage =
                                $monthlyExpense > 0
                                    ? (
                                        $item['amount'] /
                                        $monthlyExpense
                                    ) * 100
                                    : 0;

                        @endphp

                        <div>

                            <div class="flex items-center justify-between gap-3 text-sm">

                                <div class="flex items-center gap-2 min-w-0">

                                    <span class="flex-shrink-0">
                                        {{ $item['category']?->icon ?: '📁' }}
                                    </span>

                                    <span class="text-slate-700 dark:text-slate-300 truncate">
                                        {{ $item['category']?->name ?: 'Ohne Kategorie' }}
                                    </span>

                                </div>

                                <span class="font-medium text-slate-900 dark:text-white whitespace-nowrap">
                                    {{ number_format(
                                        $item['amount'],
                                        2,
                                        ',',
                                        '.'
                                    ) }} €
                                </span>

                            </div>


                            <div
                                class="
                                    h-2
                                    bg-slate-100
                                    dark:bg-slate-800
                                    rounded-full
                                    mt-2
                                    overflow-hidden
                                "
                            >

                                <div
                                    class="
                                        h-full
                                        bg-slate-900
                                        dark:bg-emerald-500
                                        rounded-full
                                    "
                                    style="width: {{ min($percentage, 100) }}%"
                                ></div>

                            </div>

                        </div>

                    @endforeach

                </div>

            @endif

        </div>

    </div>


    {{-- ========================================================= --}}
        @endif
