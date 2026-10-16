@if($dashboardWidgets['savings_rate'])

    {{-- SPARQUOTE als Halbkreis --}}

    @php
        $rate = (float) $savingsRate;
        $filled = max(0, min(100, $rate));
    @endphp

    <div class="flex h-full flex-col rounded-3xl bg-violet-600 dark:bg-violet-700 p-5 text-white">
        <p class="text-sm font-medium text-white/80">Sparquote</p>

        <svg viewBox="0 0 80 46" class="mx-auto mt-3 w-full max-w-[170px]" role="img" aria-label="Sparquote {{ number_format($rate, 1, ',', '.') }} Prozent">
            <path d="M8 42 A32 32 0 0 1 72 42" fill="none" stroke="rgba(0,0,0,.25)" stroke-width="8" stroke-linecap="round" pathLength="100" />
            @if ($filled > 0)
                <path d="M8 42 A32 32 0 0 1 72 42" fill="none" stroke="white" stroke-opacity=".9" stroke-width="8" stroke-linecap="round" pathLength="100" stroke-dasharray="{{ number_format($filled, 1, '.', '') }} 100" />
            @endif
        </svg>

        <p class="-mt-4 text-center text-[26px] font-bold tracking-tight tabular-nums">
            {{ number_format($rate, 1, ',', '.') }} %
        </p>

        <p class="mt-1 text-center text-xs text-white/70">
            {{ $rate < 0 ? 'Mehr ausgegeben als eingenommen' : 'Anteil der Einnahmen, der übrig bleibt' }}
        </p>
    </div>

@endif
