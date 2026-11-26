@if($dashboardWidgets['review'] ?? false)

    {{-- ZU PRÜFEN: offene Aufgaben auf einen Blick --}}

    @php $open = $reviewTransfers + $reviewUncategorized; @endphp

    @if ($open > 0)
        <div class="flex h-full flex-col rounded-3xl bg-orange-600 dark:bg-orange-700 p-5 text-white">
            <p class="text-sm font-medium text-white/80">Zu prüfen</p>
            <p class="mt-2 text-[34px] leading-none font-bold tracking-tight tabular-nums">{{ $open }}</p>

            @if (($dashboardWidgets['cockpit'] ?? false) && $reviewItems->isNotEmpty())
                {{-- Direkt zuordnen: die drei neuesten Buchungen ohne Kategorie --}}
                <ul class="mt-4 space-y-2">
                    @foreach ($reviewItems as $item)
                        @php $chips = $reviewChips[$item->type] ?? collect(); @endphp

                        <li class="rounded-xl bg-black/20 px-3 py-2.5">
                            <form method="POST" action="{{ route('transactions.review.assign', $item) }}" class="flex flex-wrap items-center gap-2">
                                @csrf
                                <input type="hidden" name="back" value="dashboard">

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold">{{ $item->payee?->name ?: ($item->merchant ?: $item->description) }}</p>
                                    <p class="truncate text-xs text-white/75 tabular-nums">{{ $item->transaction_date?->format('d.m.') }} · {{ $item->account?->name }} · {{ $item->type === 'income' ? '+' : '−' }}{{ number_format((float) $item->amount, 2, ',', '.') }} €</p>
                                </div>

                                @foreach ($chips as $category)
                                    <button type="submit" name="category_id" value="{{ $category->id }}" class="rounded-full bg-white/20 px-2.5 py-1 text-xs font-medium hover:bg-white/30 transition">{{ $category->icon_text }} {{ $category->name }}</button>
                                @endforeach
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif

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
                        <a href="{{ route('transactions.review') }}" class="flex items-center justify-between gap-2 rounded-xl bg-black/20 px-3 py-2 hover:bg-black/30 transition">
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
