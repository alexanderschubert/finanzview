@if($dashboardWidgets['budgets'])

    {{-- BUDGETS --}}

    <x-section title="Budgets" :subtitle="$currentMonth" :href="route('budgets.index', ['month' => $selectedMonth])">
        @if ($budgets->isEmpty())
            <x-empty-state icon="target" title="Noch keine Budgets" :href="route('budgets.create')" action="Budget erstellen">
                Lege ein Budget an, um deine Ausgaben besser zu planen.
            </x-empty-state>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                @foreach ($budgets->take(3) as $budget)
                    @php
                        $percentage = (float) $budget->calculated_percentage;
                        $tone = $budget->calculated_exceeded ? 'negative' : ($percentage >= 80 ? 'warning' : 'positive');
                    @endphp

                    <a
                        href="{{ route('budgets.show', ['budget' => $budget, 'month' => $selectedMonth]) }}"
                        class="block rounded-2xl bg-slate-50 dark:bg-white/5 p-4 hover:bg-slate-100 dark:hover:bg-white/10 transition"
                    >
                        <div class="flex items-center gap-3">
                            <x-emoji-tile :emoji="$budget->icon" fallback="target" :color="$budget->color" />

                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-slate-900 dark:text-white truncate">{{ $budget->name }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    @switch($budget->period)
                                        @case('monthly') Monatlich @break
                                        @case('yearly') Jährlich @break
                                        @default Benutzerdefiniert
                                    @endswitch
                                </p>
                            </div>

                            @if (!$budget->calculated_applicable)
                                <span class="rounded-full bg-slate-200/70 dark:bg-white/10 px-2 py-0.5 text-[11px] font-medium text-slate-500 dark:text-slate-400">Noch nicht aktiv</span>
                            @elseif ($budget->calculated_exceeded)
                                <span class="rounded-full bg-red-100 dark:bg-red-500/15 px-2 py-0.5 text-[11px] font-medium text-red-700 dark:text-red-300">Überschritten</span>
                            @elseif ($percentage >= 80)
                                <span class="rounded-full bg-amber-100 dark:bg-amber-500/15 px-2 py-0.5 text-[11px] font-medium text-amber-700 dark:text-amber-300">Fast erreicht</span>
                            @endif
                        </div>

                        @if (!$budget->calculated_applicable)
                            <p class="mt-4 text-xs text-slate-500 dark:text-slate-400">
                                Beginnt am {{ $budget->start_date->format('d.m.Y') }}.
                            </p>
                        @else
                            <div class="mt-4 flex items-baseline justify-between gap-2">
                                <p class="text-lg font-semibold tabular-nums {{ $budget->calculated_exceeded ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white' }}">
                                    {{ number_format($budget->calculated_spent, 2, ',', '.') }} €
                                </p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 tabular-nums">
                                    von {{ number_format($budget->amount, 2, ',', '.') }} €
                                </p>
                            </div>

                            <x-progress :value="$percentage" :tone="$tone" class="mt-2" />

                            <p class="mt-2 text-xs tabular-nums {{ $budget->calculated_remaining < 0 ? 'font-medium text-red-600 dark:text-red-400' : 'text-slate-500 dark:text-slate-400' }}">
                                @if ($budget->calculated_remaining >= 0)
                                    Noch {{ number_format($budget->calculated_remaining, 2, ',', '.') }} € verfügbar
                                @else
                                    {{ number_format(abs($budget->calculated_remaining), 2, ',', '.') }} € über Budget
                                @endif
                            </p>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </x-section>

@endif
