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

    <a href="{{ route('accounts.index') }}" class="relative flex h-full min-h-[210px] flex-col justify-between overflow-hidden rounded-3xl text-white shadow-lg shadow-emerald-900/20 bg-linear-to-br from-emerald-500 to-emerald-800">

        <div aria-hidden="true" class="absolute -right-8 -top-10 w-40 h-40 rounded-full border-[18px] border-white/10"></div>

        <div class="relative p-5">
            <div class="flex items-start justify-between gap-3">
                <p class="text-sm font-medium text-white/80">Gesamtvermögen</p>

                @isset($heroChange)
                    <span class="rounded-full bg-black/20 px-2.5 py-1 text-xs font-medium tabular-nums">
                        {{ $heroChange > 0 ? '+' : ($heroChange < 0 ? '−' : '±') }}{{ number_format(abs($heroChange), 0, ',', '.') }} € in {{ $heroValues->count() }} Monaten
                    </span>
                @endisset
            </div>

            <p class="mt-2 text-[34px] sm:text-[40px] leading-tight font-bold tracking-tight tabular-nums">
                {{ number_format($totalBalance, 2, ',', '.') }} €
            </p>

            <p class="mt-1 text-xs text-white/70">Alle berücksichtigten Konten</p>
        </div>

        @if ($heroLine)
            <svg viewBox="0 0 {{ $heroWidth }} {{ $heroHeight }}" preserveAspectRatio="none" class="relative block w-full h-24 sm:h-28" role="img" aria-label="Verlauf des Gesamtvermögens">
                <path d="{{ $heroLine }} L{{ $heroWidth }} {{ $heroHeight }} L0 {{ $heroHeight }} Z" fill="white" fill-opacity="0.18" />
                <path d="{{ $heroLine }}" fill="none" stroke="white" stroke-opacity="0.9" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
            </svg>
        @endif

    </a>

@endif
