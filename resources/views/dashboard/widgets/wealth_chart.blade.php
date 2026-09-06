    @if($dashboardWidgets['wealth_chart'])


    {{-- VERMÖGENSENTWICKLUNG --}}
    {{-- ========================================================= --}}

    @php

        $wealthValues = $wealthMonths
            ->pluck('balance')
            ->map(fn ($value) => (float) $value);

        $wealthMax = max(
            1,
            $wealthValues->max()
        );

        $wealthMin = min(
            0,
            $wealthValues->min()
        );

        $wealthRange = $wealthMax - $wealthMin;

        if ($wealthRange <= 0) {
            $wealthRange = 1;
        }

        $svgWidth = 900;
        $svgHeight = 320;

        $paddingLeft = 10;
        $paddingRight = 10;
        $paddingTop = 25;
        $paddingBottom = 50;

        $innerWidth =
            $svgWidth -
            $paddingLeft -
            $paddingRight;

        $innerHeight =
            $svgHeight -
            $paddingTop -
            $paddingBottom;

        $wealthPoints = [];

        foreach ($wealthMonths as $index => $wealthMonth) {

            $count = max(
                $wealthMonths->count() - 1,
                1
            );

            $x =
                $paddingLeft +
                ($index / $count) *
                $innerWidth;

            $normalized =
                (
                    $wealthMonth['balance'] -
                    $wealthMin
                ) /
                $wealthRange;

            $y =
                $paddingTop +
                (1 - $normalized) *
                $innerHeight;

            $wealthPoints[] = [
                'x' => $x,
                'y' => $y,
                'balance' => $wealthMonth['balance'],
                'label' => $wealthMonth['label'],
                'full_label' => $wealthMonth['full_label'],
            ];
        }

        $wealthLinePoints = collect($wealthPoints)
            ->map(
                fn ($point) =>
                    $point['x'] . ',' . $point['y']
            )
            ->implode(' ');

        $wealthBottom =
            $paddingTop + $innerHeight;

        if (count($wealthPoints) > 0) {

            $wealthAreaPoints =
                $wealthLinePoints
                . ' '
                . $wealthPoints[count($wealthPoints) - 1]['x']
                . ','
                . $wealthBottom
                . ' '
                . $wealthPoints[0]['x']
                . ','
                . $wealthBottom;

        } else {

            $wealthAreaPoints = '';

        }

    @endphp


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
                        Vermögen
                    </p>

                    <h3 class="text-xl font-semibold text-slate-900 dark:text-white mt-1">
                        Vermögensentwicklung
                    </h3>

                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Entwicklung deines Gesamtvermögens über die letzten sechs Monate.
                    </p>

                </div>


                <div class="sm:text-right">

                    <p class="text-xs text-slate-400 dark:text-slate-500">
                        Aktuell
                    </p>

                    <p class="text-xl font-semibold text-slate-900 dark:text-white mt-1">
                        {{ number_format($totalBalance, 2, ',', '.') }} €
                    </p>

                </div>

            </div>


            @if ($wealthMonths->isNotEmpty())

                <div class="mt-8 overflow-x-auto">

                    <div class="min-w-[650px]">

                        <svg
                            viewBox="0 0 {{ $svgWidth }} {{ $svgHeight }}"
                            class="w-full h-auto"
                            preserveAspectRatio="none"
                        >

                            @for ($i = 0; $i <= 4; $i++)

                                @php

                                    $lineY =
                                        $paddingTop +
                                        ($i / 4) *
                                        $innerHeight;

                                @endphp

                                <line
                                    x1="{{ $paddingLeft }}"
                                    y1="{{ $lineY }}"
                                    x2="{{ $svgWidth - $paddingRight }}"
                                    y2="{{ $lineY }}"
                                    stroke="currentColor"
                                    class="text-slate-200 dark:text-slate-700"
                                    stroke-width="1"
                                />

                            @endfor


                            <polygon
                                points="{{ $wealthAreaPoints }}"
                                fill="#10b981"
                                opacity="0.08"
                            />


                            <polyline
                                points="{{ $wealthLinePoints }}"
                                fill="none"
                                stroke="#10b981"
                                stroke-width="4"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />


                            @foreach ($wealthPoints as $point)

                                <circle
                                    cx="{{ $point['x'] }}"
                                    cy="{{ $point['y'] }}"
                                    r="6"
                                    class="fill-white dark:fill-slate-900"
                                    stroke="#10b981"
                                    stroke-width="3"
                                />

                            @endforeach


                            @foreach ($wealthPoints as $point)

                                <text
                                    x="{{ $point['x'] }}"
                                    y="{{ $svgHeight - 15 }}"
                                    text-anchor="middle"
                                    font-size="13"
                                    fill="currentColor"
                                    class="text-slate-400 dark:text-slate-500"
                                >
                                    {{ $point['label'] }}
                                </text>

                            @endforeach

                        </svg>

                    </div>

                </div>


                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mt-6">

                    @foreach ($wealthMonths as $wealthMonth)

                        <div
                            class="
                                rounded-2xl
                                bg-slate-50
                                dark:bg-slate-800
                                px-3
                                py-3
                            "
                        >

                            <p class="text-xs text-slate-400 dark:text-slate-500">
                                {{ $wealthMonth['full_label'] }}
                            </p>

                            <p
                                class="
                                    text-sm
                                    font-semibold
                                    mt-1
                                    {{ $wealthMonth['balance'] >= 0
                                        ? 'text-slate-900 dark:text-white'
                                        : 'text-red-600 dark:text-red-400' }}
                                "
                            >
                                {{ number_format(
                                    $wealthMonth['balance'],
                                    2,
                                    ',',
                                    '.'
                                ) }} €
                            </p>

                        </div>

                    @endforeach

                </div>

            @else

                <div
                    class="
                        mt-8
                        rounded-2xl
                        bg-slate-50
                        dark:bg-slate-800
                        p-8
                        text-center
                    "
                >

                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Noch keine Vermögensdaten vorhanden.
                    </p>

                </div>

            @endif

        </div>

    </div>
    @endif
