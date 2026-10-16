@if($dashboardWidgets['summary'])

    {{-- GESAMTVERMÖGEN: Hauptkachel in der Markenfarbe mit Verlaufskurve --}}

    @php
        $heroValues = $wealthMonths->pluck('balance')->map(fn ($value) => (float) $value)->values();
        $heroWidth = 420;
        $heroHeight = 120;
        $heroLine = null;

        if ($heroValues->count() > 1) {
            $heroMin = $heroValues->min();
            $heroRange = max(1, $heroValues->max() - $heroMin);

            $heroPoints = $heroValues->map(fn ($value, $index) => [
                round($index / ($heroValues->count() - 1) * $heroWidth, 1),
                round(12 + (1 - ($value - $heroMin) / $heroRange) * ($heroHeight - 28), 1),
            ]);

            $heroLine = $heroPoints->map(fn ($p, $i) => ($i === 0 ? 'M' : 'L') . $p[0] . ' ' . $p[1])->implode(' ');
            $heroEnd = $heroPoints->last();
            $heroChange = $totalBalance - $heroValues->first();
        }
    @endphp

    <a href="{{ route('accounts.index') }}" class="fv-card relative flex h-full min-h-[230px] flex-col justify-between overflow-hidden">

        <div class="relative p-6">
            <div class="flex items-start justify-between gap-3">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Gesamtvermögen</p>

                @isset($heroChange)
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold tabular-nums {{ $heroChange >= 0 ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-300' : 'bg-rose-500/15 text-rose-600 dark:text-rose-300' }}">
                        {{ $heroChange > 0 ? '+' : ($heroChange < 0 ? '−' : '±') }}{{ number_format(abs($heroChange), 0, ',', '.') }} € in {{ $heroValues->count() }} Monaten
                    </span>
                @endisset
            </div>

            <p class="fv-display mt-3 text-[38px] sm:text-5xl leading-tight font-extrabold text-slate-900 dark:text-white">
                {{ number_format($totalBalance, 2, ',', '.') }} €
            </p>

            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Alle berücksichtigten Konten</p>
        </div>

        @if ($heroLine)
            <svg viewBox="0 0 {{ $heroWidth }} {{ $heroHeight }}" preserveAspectRatio="none" class="relative block w-full h-24 sm:h-28" role="img" aria-label="Verlauf des Gesamtvermögens">
                <defs>
                    <linearGradient id="heroStroke" x1="0" x2="1" y1="0" y2="0">
                        <stop offset="0" stop-color="#38bdf8" />
                        <stop offset="1" stop-color="#34d399" />
                    </linearGradient>
                    <linearGradient id="heroArea" x1="0" x2="0" y1="0" y2="1">
                        <stop offset="0" stop-color="#34d399" stop-opacity="0.35" />
                        <stop offset="1" stop-color="#34d399" stop-opacity="0" />
                    </linearGradient>
                </defs>
                <path d="{{ $heroLine }} L{{ $heroWidth }} {{ $heroHeight }} L0 {{ $heroHeight }} Z" fill="url(#heroArea)" />
                <path d="{{ $heroLine }}" fill="none" stroke="url(#heroStroke)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" style="filter: drop-shadow(0 0 6px rgba(52,211,153,.6))" />
            </svg>
        @endif

    </a>

@endif
