@if($dashboardWidgets['categories'] && $expensesByCategory->isNotEmpty())

    {{-- AUSGABEN NACH KATEGORIE als Donut --}}

    @php
        $palette = ['#38bdf8', '#fb923c', '#a78bfa', '#34d399', '#fbbf24', '#f472b6'];
        $top = $expensesByCategory->take(6)->values();
        $restAmount = $expensesByCategory->skip(6)->sum('amount');

        $segments = $top->map(fn ($item, $index) => [
            'label' => $item['category']?->display_name ?: 'Ohne Kategorie',
            'icon' => $item['category']?->icon,
            'amount' => (float) $item['amount'],
            'color' => $palette[$index % count($palette)],
        ]);

        if ($restAmount > 0) {
            $segments->push(['label' => 'Übrige', 'icon' => null, 'amount' => (float) $restAmount, 'color' => '#8c8f93']);
        }

        $segmentTotal = max(0.01, $segments->sum('amount'));
    @endphp

    <x-section title="Ausgaben nach Kategorie" :subtitle="$currentMonth">
        @php
            $radius = 15.9155;
            $offset = 0;
        @endphp

        <div class="@container"><div class="flex flex-col items-center gap-6 @lg:flex-row">
            <div class="relative h-44 w-44 shrink-0">
                <svg viewBox="0 0 42 42" class="h-full w-full -rotate-90" role="img" aria-label="Verteilung der Ausgaben">
                    <circle cx="21" cy="21" r="{{ $radius }}" fill="none" stroke="currentColor" stroke-width="5" class="text-slate-200/70 dark:text-white/10" />
                    @foreach ($segments as $segment)
                        @php
                            $share = $segment['amount'] / $segmentTotal * 100;
                            $gap = $segments->count() > 1 ? min(1.2, $share / 2) : 0;
                        @endphp
                        <circle cx="21" cy="21" r="{{ $radius }}" fill="none" stroke="{{ $segment['color'] }}" stroke-width="5" stroke-linecap="butt"
                            stroke-dasharray="{{ round($share - $gap, 2) }} {{ round(100 - $share + $gap, 2) }}"
                            stroke-dashoffset="{{ round(-$offset, 2) }}"
                            style="filter: drop-shadow(0 0 2px {{ $segment['color'] }}99)">
                            <title>{{ $segment['label'] }}</title>
                        </circle>
                        @php $offset += $share; @endphp
                    @endforeach
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                    <span class="text-xs text-slate-500 dark:text-slate-400">Ausgaben</span>
                    <span class="fv-display text-xl font-extrabold tabular-nums text-slate-900 dark:text-white">{{ number_format($monthlyExpense, 0, ',', '.') }} €</span>
                </div>
            </div>

            <ul class="grid w-full flex-1 grid-cols-1 gap-y-2.5">
                @foreach ($segments as $segment)
                    <li class="flex items-center gap-2.5 text-sm min-w-0">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $segment['color'] }}; box-shadow: 0 0 8px {{ $segment['color'] }}99"></span>
                        <span class="flex-1 truncate text-slate-700 dark:text-slate-200">{{ $segment['icon'] }} {{ $segment['label'] }}</span>
                        <span class="tabular-nums whitespace-nowrap text-slate-900 dark:text-white">
                            {{ number_format($segment['amount'], 0, ',', '.') }} €
                            <span class="ml-1 text-xs text-slate-400">{{ number_format($segment['amount'] / $segmentTotal * 100, 0, ',', '.') }} %</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
        </div>
    </x-section>

@endif
