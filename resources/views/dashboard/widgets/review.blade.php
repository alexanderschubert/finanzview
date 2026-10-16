@if($dashboardWidgets['review'] ?? false)

    {{-- ZU PRÜFEN: offene Aufgaben auf einen Blick --}}

    @php $open = $reviewTransfers + $reviewUncategorized; @endphp

    @if ($open > 0)
        <div class="flex h-full flex-col rounded-3xl bg-orange-600 dark:bg-orange-700 p-5 text-white">
            <p class="text-sm font-medium text-white/80">Zu prüfen</p>
            <p class="mt-2 text-[34px] leading-none font-bold tracking-tight tabular-nums">{{ $open }}</p>

            <ul class="mt-4 space-y-2 text-sm">
                @if ($reviewTransfers > 0)
                    <li>
                        <a href="{{ route('transactions.transfers') }}" class="flex items-center justify-between gap-2 rounded-xl bg-black/20 px-3 py-2 hover:bg-black/30 transition">
                            <span>{{ $reviewTransfers === 1 ? '1 mögliche Umbuchung' : $reviewTransfers . ' mögliche Umbuchungen' }}</span>
                            <x-icon name="chevron-right" class="w-4 h-4 shrink-0" />
                        </a>
                    </li>
                @endif

                @if ($reviewUncategorized > 0)
                    <li>
                        <a href="{{ route('transactions.index', ['uncategorized' => 1]) }}" class="flex items-center justify-between gap-2 rounded-xl bg-black/20 px-3 py-2 hover:bg-black/30 transition">
                            <span>{{ $reviewUncategorized }} ohne Kategorie</span>
                            <x-icon name="chevron-right" class="w-4 h-4 shrink-0" />
                        </a>
                    </li>
                @endif
            </ul>
        </div>
    @else
        <div class="fv-card flex h-full flex-col p-5">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Zu prüfen</p>
            <div class="mt-3 flex items-center gap-3">
                <span class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400 flex items-center justify-center">
                    <x-icon name="check-circle" class="w-5 h-5" />
                </span>
                <p class="font-medium text-slate-900 dark:text-white">Alles erledigt</p>
            </div>
            <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">Keine offenen Umbuchungen, jede Buchung hat eine Kategorie.</p>
        </div>
    @endif

@endif
