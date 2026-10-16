@if(($dashboardWidgets['weekly'] ?? false) && $weekDays->isNotEmpty())

    {{-- DIESE WOCHE: Ausgaben der letzten sieben Tage --}}

    @php
        $weekMax = max(1, $weekDays->max('amount'));
        $weekTotal = $weekDays->sum('amount');
        $priciest = $weekDays->sortByDesc('amount')->first();
    @endphp

    <div class="fv-card flex h-full flex-col p-5">
        <div class="flex items-baseline justify-between gap-2">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Diese Woche</p>
            <p class="text-sm font-semibold tabular-nums text-slate-900 dark:text-white">{{ number_format($weekTotal, 0, ',', '.') }} €</p>
        </div>

        <div class="mt-4 flex h-24 items-end gap-1.5" role="img" aria-label="Ausgaben der letzten sieben Tage">
            @foreach ($weekDays as $day)
                @php $isTop = $weekTotal > 0 && $day['amount'] === $priciest['amount'] && $day['amount'] > 0; @endphp
                <div class="flex flex-1 flex-col items-center justify-end gap-1.5 h-full" title="{{ $day['long'] }}: {{ number_format($day['amount'], 2, ',', '.') }} €">
                    <div class="w-full rounded-md {{ $isTop ? 'bg-orange-500' : 'bg-sky-500/80 dark:bg-sky-400/70' }}" style="height: {{ max(4, round($day['amount'] / $weekMax * 100)) }}%; opacity: {{ $day['amount'] > 0 ? 1 : .25 }}"></div>
                    <span class="text-[11px] leading-none {{ $day['today'] ? 'font-semibold text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500' }}">{{ \Illuminate\Support\Str::substr($day['label'], 0, 2) }}</span>
                </div>
            @endforeach
        </div>

        <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
            @if ($weekTotal > 0)
                Teuerster Tag: {{ $priciest['long'] }}, {{ number_format($priciest['amount'], 0, ',', '.') }} €
            @else
                Keine Ausgaben in den letzten sieben Tagen
            @endif
        </p>
    </div>

@endif
