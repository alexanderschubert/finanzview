@if($dashboardWidgets['budgets'])

    {{-- BUDGETS --}}

    <x-section title="Budgets" :subtitle="$currentMonth" :href="route('budgets.index', ['month' => $selectedMonth])">
        @if ($budgets->isEmpty())
            <x-empty-state icon="target" title="Noch keine Budgets" :href="route('budgets.create')" action="Budget erstellen">
                Lege ein Budget an, um deine Ausgaben besser zu planen.
            </x-empty-state>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
                @foreach ($budgets->take(6) as $budget)
                    @php
                        $percentage = (float) $budget->calculated_percentage;
                        $tone = $budget->calculated_exceeded ? 'text-red-500' : ($percentage >= 80 ? 'text-amber-500' : 'text-emerald-500');
                        $circumference = 2 * M_PI * 15;
                        $dash = min(100, max(0, $percentage)) / 100 * $circumference;
                    @endphp

                    <a
                        href="{{ route('budgets.show', ['budget' => $budget, 'month' => $selectedMonth]) }}"
                        class="flex items-center gap-4 rounded-2xl bg-slate-50 dark:bg-white/5 p-4 hover:bg-slate-100 dark:hover:bg-white/10 transition"
                    >
                        @if ($budget->calculated_applicable)
                            <span class="relative w-16 h-16 shrink-0">
                                <svg viewBox="0 0 36 36" class="w-16 h-16 -rotate-90" role="img" aria-label="{{ round($percentage) }} Prozent verbraucht">
                                    <circle cx="18" cy="18" r="15" fill="none" stroke="currentColor" class="text-slate-200 dark:text-white/10" stroke-width="5" />
                                    <circle cx="18" cy="18" r="15" fill="none" stroke="currentColor" class="{{ $tone }}" stroke-width="5" stroke-linecap="round" stroke-dasharray="{{ number_format($dash, 1, '.', '') }} {{ number_format($circumference, 1, '.', '') }}" />
                                </svg>
                                <span class="absolute inset-0 flex items-center justify-center text-[13px] font-semibold tabular-nums text-slate-900 dark:text-white">{{ round($percentage) }}%</span>
                            </span>
                        @else
                            <x-emoji-tile :emoji="$budget->icon" fallback="target" :color="$budget->color" size="lg" />
                        @endif

                        <div class="min-w-0 flex-1">
                            <p class="flex items-center gap-1.5 text-sm font-medium text-slate-900 dark:text-white">
                                <span class="truncate">{{ $budget->icon }} {{ $budget->name }}</span>
                            </p>

                            @if (!$budget->calculated_applicable)
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Beginnt am {{ $budget->start_date->format('d.m.Y') }}</p>
                            @else
                                <p class="mt-0.5 text-lg font-semibold tabular-nums {{ $budget->calculated_exceeded ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white' }}">
                                    {{ number_format($budget->calculated_spent, 0, ',', '.') }} €
                                </p>
                                <p class="text-xs tabular-nums {{ $budget->calculated_remaining < 0 ? 'font-medium text-red-600 dark:text-red-400' : 'text-slate-500 dark:text-slate-400' }}">
                                    von {{ number_format($budget->amount, 0, ',', '.') }} € ·
                                    @if ($budget->calculated_remaining >= 0)
                                        noch {{ number_format($budget->calculated_remaining, 0, ',', '.') }} €
                                    @else
                                        {{ number_format(abs($budget->calculated_remaining), 0, ',', '.') }} € drüber
                                    @endif
                                </p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </x-section>

@endif
