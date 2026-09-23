@if($dashboardWidgets['categories'] && $expensesByCategory->isNotEmpty())

    {{-- AUSGABEN NACH KATEGORIE --}}

    <x-section title="Ausgaben nach Kategorie" :subtitle="$currentMonth">
        <ul class="space-y-4">
            @foreach ($expensesByCategory as $item)
                @php
                    $percentage = $monthlyExpense > 0 ? ($item['amount'] / $monthlyExpense) * 100 : 0;
                @endphp

                <li class="flex items-center gap-3">
                    <x-emoji-tile :emoji="$item['category']?->icon" size="sm" />

                    <div class="flex-1 min-w-0">
                        <div class="flex items-baseline justify-between gap-3 text-sm">
                            <span class="font-medium text-slate-700 dark:text-slate-200 truncate">
                                {{ $item['category']?->name ?: 'Ohne Kategorie' }}
                            </span>
                            <span class="tabular-nums whitespace-nowrap text-slate-900 dark:text-white">
                                {{ number_format($item['amount'], 2, ',', '.') }} €
                                <span class="ml-1 text-xs text-slate-400">{{ number_format($percentage, 0, ',', '.') }} %</span>
                            </span>
                        </div>

                        <x-progress :value="$percentage" tone="neutral" class="mt-2 h-1.5" />
                    </div>
                </li>
            @endforeach
        </ul>
    </x-section>

@endif
