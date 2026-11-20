@if($dashboardWidgets['plan'] ?? false)

    {{-- PLAN GEGEN TATSÄCHLICH --}}

    @php
        $money = fn ($value) => number_format((float) $value, 0, ',', '.') . ' €';

        $rows = [];

        if ($planCompare) {
            $rows = [
                ['Ausgaben', (float) $planCompare['expense'], (float) $monthlyExpense, true],
                ['Einnahmen', (float) $planCompare['income'], (float) $monthlyIncome, false],
            ];
        }
    @endphp

    <div class="fv-card flex h-full flex-col p-5">

        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Plan gegen tatsächlich</p>
                <p class="text-xs text-slate-400 dark:text-slate-500">{{ $currentMonth }}</p>
            </div>

            <a href="{{ route('plan.index', ['month' => $selectedMonth]) }}" class="fv-link text-sm shrink-0">Monatsplan</a>
        </div>

        @if (! $planCompare)

            <div class="mt-4 flex flex-1 flex-col items-center justify-center text-center">
                <span class="w-10 h-10 rounded-full bg-slate-100 dark:bg-white/5 flex items-center justify-center text-slate-400">
                    <x-icon name="layout" class="w-5 h-5" />
                </span>
                <p class="mt-2 text-sm font-medium text-slate-900 dark:text-white">Noch kein Plan für diesen Monat</p>
                <a href="{{ route('plan.index', ['month' => $selectedMonth]) }}" class="fv-btn fv-btn-secondary mt-3 text-sm py-2">Plan anlegen</a>
            </div>

        @else

            <div class="mt-4 space-y-4">
                @foreach ($rows as [$label, $planned, $actual, $isExpense])
                    @php
                        $ratio = $planned > 0 ? $actual / $planned * 100 : ($actual > 0 ? 100 : 0);
                        $tone = $isExpense
                            ? ($ratio > 100 ? 'negative' : ($ratio >= 90 ? 'warning' : 'positive'))
                            : 'positive';
                        $diff = $planned - $actual;
                    @endphp

                    <div>
                        <div class="flex items-baseline justify-between gap-3">
                            <span class="text-sm text-slate-700 dark:text-slate-200">{{ $label }}</span>
                            <span class="text-sm tabular-nums text-slate-500 dark:text-slate-400">
                                <span class="font-semibold text-slate-900 dark:text-white">{{ $money($actual) }}</span> von {{ $money($planned) }}
                            </span>
                        </div>

                        <x-progress :value="$ratio" :tone="$tone" class="mt-1.5" />

                        <p class="mt-1 text-xs tabular-nums {{ $isExpense && $diff < 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-500 dark:text-slate-400' }}">
                            @if ($isExpense)
                                {{ $diff >= 0 ? 'Noch ' . $money($diff) . ' im Plan' : $money(abs($diff)) . ' über Plan' }}
                            @else
                                {{ $diff > 0 ? 'Noch ' . $money($diff) . ' erwartet' : 'Plan erreicht' }}
                            @endif
                        </p>
                    </div>
                @endforeach
            </div>

        @endif

    </div>

@endif
