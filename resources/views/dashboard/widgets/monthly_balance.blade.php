@if($dashboardWidgets['monthly_balance'])

    @php
        $positive = $monthlyBalance >= 0;
        $flow = max($monthlyIncome, 0.01);
        $spentShare = min(100, ($monthlyExpense / $flow) * 100);
    @endphp

    {{-- MONATSSALDO --}}

    <x-section title="Monatssaldo" :subtitle="$currentMonth">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <p class="text-[34px] leading-none font-semibold tracking-tight tabular-nums {{ $positive ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                {{ $monthlyBalance > 0 ? '+' : ($monthlyBalance < 0 ? '−' : '') }}{{ number_format(abs($monthlyBalance), 2, ',', '.') }} €
            </p>

            <span class="rounded-full px-3 py-1 text-xs font-medium {{ $positive ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300' }}">
                {{ $positive ? 'Positiver Monat' : 'Mehr ausgegeben als eingenommen' }}
            </span>
        </div>

        @if ($monthlyIncome > 0)
            <div class="mt-5">
                <x-progress :value="$spentShare" :tone="$spentShare >= 100 ? 'negative' : ($spentShare >= 80 ? 'warning' : 'positive')" />
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                    {{ number_format($spentShare, 0, ',', '.') }} % der Einnahmen ausgegeben
                </p>
            </div>
        @endif
    </x-section>

@endif
