@if($dashboardWidgets['upcoming'] ?? false)

    {{-- NÄCHSTE ZAHLUNGEN: aus Monatsplan (mit Zahlungstag) und Krediten --}}

    @php
        $money = fn ($value) => number_format((float) $value, 2, ',', '.') . ' €';
        $icons = ['income' => '💼', 'loan' => '🏦'];
    @endphp

    <div class="fv-card flex h-full flex-col p-5">

        <div class="flex items-center justify-between gap-3">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Nächste Zahlungen</p>
            <a href="{{ route('plan.calendar', ['month' => $selectedMonth]) }}" class="fv-link text-sm">Kalender</a>
        </div>

        @if ($upcoming->isEmpty())

            <div class="flex flex-1 flex-col items-center justify-center py-6 text-center">
                <span class="w-10 h-10 rounded-full bg-slate-100 dark:bg-white/5 flex items-center justify-center text-slate-400">
                    <x-icon name="calendar" class="w-5 h-5" />
                </span>
                <p class="mt-2 text-sm font-medium text-slate-900 dark:text-white">Keine Zahlungen mit festem Tag</p>
                <p class="mt-1 max-w-xs text-xs text-slate-500 dark:text-slate-400">Trage im Monatsplan beim Posten einen Zahlungstag ein, dann erscheint er hier.</p>
            </div>

        @else

            <ul class="mt-2 divide-y divide-slate-100 dark:divide-white/5">
                @foreach ($upcoming as $item)
                    <li class="flex items-center gap-3 py-2.5">
                        <div class="w-10 shrink-0 text-center leading-tight">
                            <p class="text-[10px] font-bold uppercase text-red-500">{{ $item['date']->translatedFormat('M') }}</p>
                            <p class="fv-display text-lg font-extrabold tabular-nums text-slate-900 dark:text-white">{{ $item['date']->format('j') }}</p>
                        </div>

                        <span class="w-8 h-8 shrink-0 rounded-xl bg-slate-900/5 dark:bg-white/10 flex items-center justify-center text-sm" aria-hidden="true">{{ $icons[$item['kind']] ?? '🧾' }}</span>

                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-slate-900 dark:text-white">{{ $item['title'] }}</p>
                            <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $item['group'] }}</p>
                        </div>

                        <p class="shrink-0 font-semibold tabular-nums {{ $item['kind'] === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white' }}">
                            {{ $item['kind'] === 'income' ? '+' : '−' }}{{ $money($item['amount']) }}
                        </p>
                    </li>
                @endforeach
            </ul>

        @endif

    </div>

@endif
