@if(($dashboardWidgets['cockpit'] ?? false) && $cockpit)

    {{-- COCKPIT: was bis Monatsende noch frei ist --}}

    @php
        $money = fn ($value) => number_format((float) $value, 2, ',', '.') . ' €';
        $moneyShort = fn ($value) => number_format((float) $value, 0, ',', '.') . ' €';

        $remaining = $cockpit['remaining'];
        $over = $remaining < 0;
        $ratio = (float) $cockpit['ratio'];
        $circumference = 2 * M_PI * 15.9;
        $dash = min(100, max(0, $ratio)) / 100 * $circumference;

        $tone = $over || $ratio > 100 ? 'text-red-500' : ($ratio >= 90 ? 'text-amber-500' : 'text-emerald-500');
        $label = $cockpit['has_plan'] ? 'Noch frei bis Monatsende' : 'Saldo im Monat';
    @endphp

    <div class="fv-card relative flex h-full flex-wrap items-center gap-6 overflow-hidden p-5 sm:p-6">

        <div class="relative mx-auto h-[150px] w-[150px] shrink-0">
            <svg viewBox="0 0 42 42" class="h-full w-full -rotate-90" role="img" aria-label="{{ round($ratio) }} Prozent verbraucht">
                <circle cx="21" cy="21" r="15.9" fill="none" stroke="currentColor" stroke-width="4" class="text-slate-900/10 dark:text-white/10" />
                <circle cx="21" cy="21" r="15.9" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" class="{{ $tone }}"
                    stroke-dasharray="{{ number_format($dash, 1, '.', '') }} {{ number_format($circumference, 1, '.', '') }}"
                    style="filter: drop-shadow(0 0 4px currentColor)" />
            </svg>

            <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                <span class="text-[11px] text-slate-500 dark:text-slate-400">Monat</span>
                <span class="fv-display text-[24px] font-extrabold tabular-nums text-slate-900 dark:text-white">{{ round($ratio) }} %</span>
                <span class="text-[11px] text-slate-400 dark:text-slate-500">verbraucht</span>
            </div>
        </div>

        <div class="min-w-[220px] flex-1">
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ $label }}</p>

            <p class="fv-display text-[44px] leading-[1.1] font-extrabold tabular-nums {{ $over ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                {{ $over ? '−' : '' }}{{ $money(abs($remaining)) }}
            </p>

            <p class="mt-1 text-[13px] text-slate-500 dark:text-slate-400">
                @if ($cockpit['has_plan'])
                    Plan {{ $moneyShort($cockpit['planned']) }} · bisher {{ $moneyShort($cockpit['spent']) }}
                @else
                    Einnahmen minus Ausgaben. Mit einem <a href="{{ route('plan.index', ['month' => $selectedMonth]) }}" class="fv-link">Monatsplan</a> sieht man, was noch frei ist.
                @endif

                @if ($cockpit['days_left'] > 0)
                    · <span class="font-medium text-slate-700 dark:text-slate-200">{{ $cockpit['days_left'] }} {{ $cockpit['days_left'] === 1 ? 'Tag' : 'Tage' }}</span> übrig@if ($cockpit['per_day']), etwa <span class="font-medium text-slate-700 dark:text-slate-200">{{ $moneyShort($cockpit['per_day']) }} pro Tag</span>@endif
                @elseif (! $cockpit['is_current'])
                    · Monat abgeschlossen
                @endif
            </p>

            <div class="mt-3 flex flex-wrap gap-2">
                <a href="{{ route('accounts.index') }}" class="rounded-full bg-emerald-500/15 px-3 py-1 text-xs font-semibold text-emerald-700 dark:text-emerald-300">Gesamtvermögen {{ $moneyShort($totalBalance) }}</a>

                @if ($savingsRate > 0)
                    <span class="rounded-full bg-emerald-500/15 px-3 py-1 text-xs font-semibold text-emerald-700 dark:text-emerald-300">Sparquote {{ round($savingsRate) }} %</span>
                @endif
            </div>
        </div>

    </div>

@endif
