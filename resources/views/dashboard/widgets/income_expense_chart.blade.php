@if($dashboardWidgets['income_expense_chart'])

    @php
        $maxChartValue = max(1, $chartMonths->max(fn ($month) => max($month['income'], $month['expense'])) ?? 0);
    @endphp

    {{-- EINNAHMEN / AUSGABEN --}}

    <x-section title="Einnahmen & Ausgaben" subtitle="Letzte sechs Monate">
        <x-slot:actions>
            <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400 shrink-0">
                <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500"></span>Einnahmen</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-red-400"></span>Ausgaben</span>
            </div>
        </x-slot:actions>

        <div class="grid gap-2 sm:gap-4" style="grid-template-columns: repeat({{ max($chartMonths->count(), 1) }}, minmax(0, 1fr))">
            @foreach ($chartMonths as $chartMonth)
                <div class="flex flex-col items-center">
                    <div class="flex h-40 w-full items-end justify-center gap-1 sm:gap-1.5"
                        title="{{ $chartMonth['label'] }} – Einnahmen {{ number_format($chartMonth['income'], 2, ',', '.') }} €, Ausgaben {{ number_format($chartMonth['expense'], 2, ',', '.') }} €">
                        <div class="w-full max-w-5 rounded-t-md bg-emerald-500" style="height: {{ max(2, ($chartMonth['income'] / $maxChartValue) * 100) }}%"></div>
                        <div class="w-full max-w-5 rounded-t-md bg-red-400" style="height: {{ max(2, ($chartMonth['expense'] / $maxChartValue) * 100) }}%"></div>
                    </div>

                    <p class="mt-2 text-xs text-slate-400 dark:text-slate-500">{{ $chartMonth['label'] }}</p>
                    <p class="text-[11px] font-medium tabular-nums {{ $chartMonth['balance'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                        {{ $chartMonth['balance'] > 0 ? '+' : ($chartMonth['balance'] < 0 ? '−' : '') }}{{ number_format(abs($chartMonth['balance']), 0, ',', '.') }}
                    </p>
                </div>
            @endforeach
        </div>
    </x-section>

@endif
