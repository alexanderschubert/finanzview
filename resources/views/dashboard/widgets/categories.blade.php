@if($dashboardWidgets['categories'] && $expensesByCategory->isNotEmpty())

    {{-- AUSGABEN NACH KATEGORIE als bunte Leiste --}}

    @php
        $palette = ['#4a90e2', '#e0653a', '#8a80e8', '#2fb585', '#e0a02e', '#e0618f'];
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
        <x-slot:actions>
            <p class="text-lg font-semibold tabular-nums text-slate-900 dark:text-white">{{ number_format($monthlyExpense, 0, ',', '.') }} €</p>
        </x-slot:actions>

        <div class="flex h-4 gap-0.5 overflow-hidden rounded-full" role="img" aria-label="Verteilung der Ausgaben">
            @foreach ($segments as $segment)
                <span title="{{ $segment['label'] }}" style="width: {{ round($segment['amount'] / $segmentTotal * 100, 2) }}%; background-color: {{ $segment['color'] }}"></span>
            @endforeach
        </div>

        <ul class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2.5">
            @foreach ($segments as $segment)
                <li class="flex items-center gap-2.5 text-sm min-w-0">
                    <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $segment['color'] }}"></span>
                    <span class="flex-1 truncate text-slate-700 dark:text-slate-200">{{ $segment['icon'] }} {{ $segment['label'] }}</span>
                    <span class="tabular-nums whitespace-nowrap text-slate-900 dark:text-white">
                        {{ number_format($segment['amount'], 0, ',', '.') }} €
                        <span class="ml-1 text-xs text-slate-400">{{ number_format($segment['amount'] / $segmentTotal * 100, 0, ',', '.') }} %</span>
                    </span>
                </li>
            @endforeach
        </ul>
    </x-section>

@endif
