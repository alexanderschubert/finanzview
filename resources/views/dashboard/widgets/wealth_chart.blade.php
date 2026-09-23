@if($dashboardWidgets['wealth_chart'])

    @php
        $wealthValues = $wealthMonths->pluck('balance')->map(fn ($value) => (float) $value);

        $wealthMax = max(1, $wealthValues->max() ?? 0);
        $wealthMin = min(0, $wealthValues->min() ?? 0);
        $wealthRange = max(1, $wealthMax - $wealthMin);

        $svgWidth = 600;
        $svgHeight = 200;
        $padTop = 16;
        $padBottom = 8;
        $innerHeight = $svgHeight - $padTop - $padBottom;
        $count = max($wealthMonths->count() - 1, 1);

        $wealthPoints = $wealthMonths->values()->map(function ($wealthMonth, $index) use ($svgWidth, $count, $padTop, $innerHeight, $wealthMin, $wealthRange) {
            return [
                'x' => round(($index / $count) * $svgWidth, 2),
                'y' => round($padTop + (1 - (($wealthMonth['balance'] - $wealthMin) / $wealthRange)) * $innerHeight, 2),
                'label' => $wealthMonth['label'],
                'full_label' => $wealthMonth['full_label'],
                'balance' => (float) $wealthMonth['balance'],
            ];
        });

        $linePath = $wealthPoints->map(fn ($p, $i) => ($i === 0 ? 'M' : 'L') . $p['x'] . ' ' . $p['y'])->implode(' ');
        $areaPath = $wealthPoints->isNotEmpty()
            ? $linePath . ' L' . $svgWidth . ' ' . $svgHeight . ' L0 ' . $svgHeight . ' Z'
            : '';

        $firstBalance = $wealthPoints->first()['balance'] ?? 0;
        $change = $totalBalance - $firstBalance;
    @endphp

    {{-- VERMÖGENSENTWICKLUNG --}}

    <x-section title="Vermögensentwicklung" subtitle="Letzte sechs Monate">
        <x-slot:actions>
            <div class="text-right shrink-0">
                <p class="text-lg font-semibold tabular-nums text-slate-900 dark:text-white">
                    {{ number_format($totalBalance, 2, ',', '.') }} €
                </p>
                @if ($wealthPoints->count() > 1)
                    <p class="text-xs font-medium tabular-nums {{ $change < 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                        {{ $change > 0 ? '+' : ($change < 0 ? '−' : '±') }}{{ number_format(abs($change), 2, ',', '.') }} €
                    </p>
                @endif
            </div>
        </x-slot:actions>

        @if ($wealthPoints->isNotEmpty())
            <svg viewBox="0 0 {{ $svgWidth }} {{ $svgHeight }}" class="w-full h-44 sm:h-52 overflow-visible" preserveAspectRatio="none" role="img" aria-label="Verlauf des Gesamtvermögens">
                <defs>
                    <linearGradient id="fv-wealth-fill" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0" stop-color="var(--color-emerald-500)" stop-opacity="0.28" />
                        <stop offset="1" stop-color="var(--color-emerald-500)" stop-opacity="0" />
                    </linearGradient>
                </defs>

                @for ($i = 0; $i <= 3; $i++)
                    <line x1="0" x2="{{ $svgWidth }}" y1="{{ $padTop + ($i / 3) * $innerHeight }}" y2="{{ $padTop + ($i / 3) * $innerHeight }}"
                        stroke="currentColor" class="text-slate-100 dark:text-white/5" stroke-width="1" vector-effect="non-scaling-stroke" />
                @endfor

                <path d="{{ $areaPath }}" fill="url(#fv-wealth-fill)" />
                <path d="{{ $linePath }}" fill="none" stroke="var(--color-emerald-500)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
            </svg>

            <div class="mt-3 grid text-center" style="grid-template-columns: repeat({{ $wealthPoints->count() }}, minmax(0, 1fr))">
                @foreach ($wealthPoints as $point)
                    <div title="{{ $point['full_label'] }}: {{ number_format($point['balance'], 2, ',', '.') }} €">
                        <p class="text-xs text-slate-400 dark:text-slate-500">{{ $point['label'] }}</p>
                        <p class="hidden sm:block mt-0.5 text-xs font-medium tabular-nums {{ $point['balance'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-700 dark:text-slate-300' }}">
                            {{ number_format($point['balance'], 0, ',', '.') }} €
                        </p>
                    </div>
                @endforeach
            </div>
        @else
            <x-empty-state icon="chart" title="Noch keine Daten">
                Sobald Buchungen vorhanden sind, siehst du hier den Verlauf.
            </x-empty-state>
        @endif
    </x-section>

@endif
