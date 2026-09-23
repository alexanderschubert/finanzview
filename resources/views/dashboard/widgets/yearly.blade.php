@if($dashboardWidgets['yearly'])

    @php
        $yearlyBalance = $yearlyIncome - $yearlyExpense;
    @endphp

    {{-- JAHRESWERTE --}}

    <x-section title="Dieses Jahr">
        <dl class="divide-y divide-slate-100 dark:divide-white/5 text-sm">
            <div class="flex items-center justify-between gap-4 pb-3">
                <dt class="text-slate-500 dark:text-slate-400">Einnahmen</dt>
                <dd class="font-medium tabular-nums text-emerald-600 dark:text-emerald-400">
                    +{{ number_format($yearlyIncome, 2, ',', '.') }} €
                </dd>
            </div>

            <div class="flex items-center justify-between gap-4 py-3">
                <dt class="text-slate-500 dark:text-slate-400">Ausgaben</dt>
                <dd class="font-medium tabular-nums text-red-600 dark:text-red-400">
                    −{{ number_format($yearlyExpense, 2, ',', '.') }} €
                </dd>
            </div>

            <div class="flex items-center justify-between gap-4 pt-3">
                <dt class="font-medium text-slate-900 dark:text-white">Saldo</dt>
                <dd class="font-semibold tabular-nums {{ $yearlyBalance < 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white' }}">
                    {{ $yearlyBalance > 0 ? '+' : ($yearlyBalance < 0 ? '−' : '') }}{{ number_format(abs($yearlyBalance), 2, ',', '.') }} €
                </dd>
            </div>
        </dl>
    </x-section>

@endif
