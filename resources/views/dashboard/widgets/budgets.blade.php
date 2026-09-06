    @if($dashboardWidgets['budgets'])

    {{-- BUDGETS --}}
    {{-- ========================================================= --}}

    <div class="mt-5">

        <div
            class="
                flex
                flex-col
                sm:flex-row
                sm:items-center
                sm:justify-between
                gap-3
                mb-4
            "
        >

            <div>

                <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                    Planung
                </p>

                <h3 class="text-xl font-semibold text-slate-900 dark:text-white mt-1">
                    Deine Budgets
                </h3>

                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Budgetverbrauch für {{ $currentMonth }}
                </p>

            </div>


            <a
                href="{{ route('budgets.index', ['month' => $selectedMonth]) }}"
                class="
                    text-sm
                    font-medium
                    text-slate-500
                    dark:text-slate-400
                    hover:text-emerald-600
                    dark:hover:text-emerald-400
                    transition
                "
            >
                Alle Budgets →
            </a>

        </div>


        @if ($budgets->isEmpty())

            <div
                class="
                    bg-white
                    dark:bg-slate-900
                    rounded-3xl
                    border
                    border-slate-200
                    dark:border-slate-800
                    shadow-sm
                    p-10
                    text-center
                "
            >

                <div
                    class="
                        w-14
                        h-14
                        mx-auto
                        rounded-2xl
                        bg-emerald-50
                        dark:bg-emerald-950/50
                        flex
                        items-center
                        justify-center
                        text-2xl
                    "
                >
                    🎯
                </div>

                <h4 class="font-semibold text-slate-900 dark:text-white mt-4">
                    Noch keine Budgets
                </h4>

                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Lege dein erstes Budget an, um deine Ausgaben besser zu planen.
                </p>

                <a
                    href="{{ route('budgets.create') }}"
                    class="
                        inline-flex
                        mt-5
                        rounded-xl
                        bg-emerald-600
                        px-5
                        py-3
                        text-sm
                        font-medium
                        text-white
                        hover:bg-emerald-700
                        transition
                    "
                >
                    + Budget erstellen
                </a>

            </div>

        @else

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">

                @foreach ($budgets->take(3) as $budget)

                    <a
                        href="{{ route('budgets.show', [
                            'budget' => $budget,
                            'month' => $selectedMonth,
                        ]) }}"
                        class="
                            block
                            bg-white
                            dark:bg-slate-900
                            rounded-3xl
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

                        <div class="flex items-center gap-3">

                            <div
                                class="
                                    w-11
                                    h-11
                                    rounded-2xl
                                    flex
                                    items-center
                                    justify-center
                                    text-xl
                                    flex-shrink-0
                                "
                                style="background-color: {{ $budget->color ?: '#ecfdf5' }}"
                            >
                                {{ $budget->icon ?: '🎯' }}
                            </div>

                            <div class="flex-1 min-w-0">

                                <p class="font-semibold text-slate-900 dark:text-white truncate">
                                    {{ $budget->name }}
                                </p>

                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">

                                    @switch($budget->period)

                                        @case('monthly')
                                            Monatlich
                                            @break

                                        @case('yearly')
                                            Jährlich
                                            @break

                                        @default
                                            Benutzerdefiniert

                                    @endswitch

                                    ·

                                    {{ number_format(
                                        $budget->amount,
                                        2,
                                        ',',
                                        '.'
                                    ) }} €

                                </p>

                            </div>


                            @if (!$budget->calculated_applicable)

                                <span class="rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-xs font-medium text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                    Nicht aktiv
                                </span>

                            @elseif ($budget->calculated_exceeded)

                                <span class="rounded-full bg-red-50 dark:bg-red-950/50 px-2.5 py-1 text-xs font-medium text-red-600 dark:text-red-400 whitespace-nowrap">
                                    Überschritten
                                </span>

                            @elseif ($budget->calculated_percentage >= 80)

                                <span class="rounded-full bg-amber-50 dark:bg-amber-950/50 px-2.5 py-1 text-xs font-medium text-amber-600 dark:text-amber-400 whitespace-nowrap">
                                    Achtung
                                </span>

                            @else

                                <span class="rounded-full bg-emerald-50 dark:bg-emerald-950/50 px-2.5 py-1 text-xs font-medium text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                    OK
                                </span>

                            @endif

                        </div>


                        @if (!$budget->calculated_applicable)

                            <div class="mt-6">

                                <div class="rounded-2xl bg-slate-50 dark:bg-slate-800 p-4">

                                    <div class="flex items-center gap-3">

                                        <div class="w-9 h-9 rounded-xl bg-slate-200 dark:bg-slate-700 flex items-center justify-center">
                                            🕐
                                        </div>

                                        <div>

                                            <p class="text-sm font-medium text-slate-700 dark:text-slate-200">
                                                Noch nicht gültig
                                            </p>

                                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                                                Das Budget beginnt am
                                                {{ $budget->start_date->format('d.m.Y') }}.
                                            </p>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        @else

                            <div class="mt-6">

                                <div class="flex items-center justify-between">

                                    <span class="text-sm text-slate-500 dark:text-slate-400">
                                        Verbrauch
                                    </span>

                                    <span class="
                                        text-sm
                                        font-semibold
                                        {{ $budget->calculated_exceeded
                                            ? 'text-red-600 dark:text-red-400'
                                            : 'text-slate-900 dark:text-white' }}
                                    ">
                                        {{ number_format(
                                            $budget->calculated_spent,
                                            2,
                                            ',',
                                            '.'
                                        ) }} €
                                    </span>

                                </div>


                                <div
                                    class="
                                        h-3
                                        bg-slate-100
                                        dark:bg-slate-800
                                        rounded-full
                                        overflow-hidden
                                        mt-3
                                    "
                                >

                                    <div
                                        class="
                                            h-full
                                            rounded-full
                                            transition-all
                                            {{ $budget->calculated_exceeded
                                                ? 'bg-red-500'
                                                : (
                                                    $budget->calculated_percentage >= 80
                                                        ? 'bg-amber-500'
                                                        : 'bg-emerald-500'
                                                ) }}
                                        "
                                        style="width: {{ min(max($budget->calculated_percentage, 0), 100) }}%"
                                    ></div>

                                </div>


                                <div class="flex items-center justify-between mt-2">

                                    <span class="text-xs text-slate-400 dark:text-slate-500">
                                        {{ number_format(
                                            $budget->calculated_percentage,
                                            1,
                                            ',',
                                            '.'
                                        ) }} %
                                    </span>


                                    @if ($budget->calculated_remaining >= 0)

                                        <span class="text-xs text-emerald-600 dark:text-emerald-400">

                                            Noch

                                            {{ number_format(
                                                $budget->calculated_remaining,
                                                2,
                                                ',',
                                                '.'
                                            ) }} €

                                        </span>

                                    @else

                                        <span class="text-xs font-medium text-red-600 dark:text-red-400">

                                            {{ number_format(
                                                abs($budget->calculated_remaining),
                                                2,
                                                ',',
                                                '.'
                                            ) }} €

                                            über Budget

                                        </span>

                                    @endif

                                </div>

                            </div>

                        @endif


                        @if ($budget->categories->isNotEmpty())

                            <div class="flex flex-wrap gap-2 mt-5">

                                @foreach ($budget->categories->take(3) as $category)

                                    <span class="rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-xs text-slate-600 dark:text-slate-300">

                                        {{ $category->icon ?: '📁' }}

                                        {{ $category->name }}

                                    </span>

                                @endforeach


                                @if ($budget->categories->count() > 3)

                                    <span class="rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-xs text-slate-500 dark:text-slate-400">
                                        +{{ $budget->categories->count() - 3 }}
                                    </span>

                                @endif

                            </div>

                        @endif

                    </a>

                @endforeach

            </div>

        @endif

    </div>


    {{-- ========================================================= --}}
    {{-- ========================================================= --}}
    @endif
