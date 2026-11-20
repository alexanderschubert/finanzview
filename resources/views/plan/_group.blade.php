@php
    $money = fn ($value) => number_format((float) $value, 2, ',', '.') . ' €';
    $sum = \App\Services\PlanService::groupSum($group, $month);
@endphp

<section id="group-{{ $group->id }}" class="fv-card p-2 scroll-mt-4">

    @php
        $tint = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $group->color) ? $group->color : $color;
        $kindIcon = ['expense' => 'wallet', 'rate' => 'repeat', 'loan' => 'banknote', 'income' => 'trending-up'][$group->kind] ?? 'wallet';
        $activeCount = $group->items->filter(fn ($i) => $i->stateIn($month) === 'due')->count();
        $share = ($incomeTotal ?? 0) > 0 && ! $group->isIncome() ? round($sum / $incomeTotal * 100) : null;
    @endphp

    <div class="flex items-center gap-3 px-3 pt-3 pb-3">
        <span class="w-11 h-11 shrink-0 rounded-2xl flex items-center justify-center text-xl"
            style="background-color: {{ $tint }}26; box-shadow: inset 0 0 0 1.5px {{ $tint }}55; color: {{ $tint }}" aria-hidden="true">
            @if (filled($group->icon))
                {{ $group->icon }}
            @else
                <x-icon :name="$kindIcon" class="w-5 h-5" />
            @endif
        </span>

        <div class="min-w-0 flex-1">
            <h3 class="flex min-w-0 items-center gap-2 font-semibold text-slate-900 dark:text-white">
                <span class="truncate">{{ $group->name }}</span>
                <a href="{{ route('plan.groups.edit', $group) }}" class="text-slate-400 hover:text-slate-700 dark:hover:text-white transition" aria-label="Gruppe {{ $group->name }} bearbeiten">
                    <x-icon name="pencil" class="w-4 h-4" />
                </a>
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                {{ $activeCount }} Posten fällig @if ($share !== null)· {{ $share }} % der Einkünfte @endif
            </p>
        </div>

        <span class="fv-display text-lg font-extrabold tabular-nums {{ $group->isIncome() ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white' }}">{{ $money($sum) }}</span>
    </div>

    @if ($group->items->isNotEmpty())
        <div class="hidden sm:flex items-center gap-2 px-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
            <span class="flex-1">Posten</span>
            <span class="w-14 text-center">Fällig</span>
            <span class="w-28 text-right pr-3">Betrag</span>
        </div>
    @endif

    <ul class="divide-y divide-slate-900/5 dark:divide-white/5">
        @foreach ($group->items as $item)
            @php
                $state = $item->stateIn($month);
                $left = $state === 'due' && $item->intervalMonths() === 1 ? \App\Services\PlanService::monthsLeft($item->ends_on, $month) : null;
                $next = $item->nextDueAfter($month);
                $chip = 'bg-slate-900/5 dark:bg-white/10 text-slate-500 dark:text-slate-400';
            @endphp

            <li class="flex items-center gap-2 px-3 py-2.5 first:rounded-t-xl last:rounded-b-xl hover:bg-slate-900/[0.03] dark:hover:bg-white/5 transition {{ $state === 'due' ? '' : 'opacity-60' }}">
                <a href="{{ route('plan.items.edit', $item) }}" class="min-w-0 flex-1" aria-label="{{ $item->name }} bearbeiten">
                    <span class="break-words text-[15px] text-slate-900 dark:text-white">{{ $item->name }}</span>

                    @if ($state === 'upcoming')
                        <span class="ml-1.5 rounded-full px-2 py-0.5 text-[11px] whitespace-nowrap {{ $chip }}">beginnt {{ $item->starts_on->translatedFormat('M Y') }}</span>
                    @elseif ($state === 'ended')
                        <span class="ml-1.5 rounded-full px-2 py-0.5 text-[11px] whitespace-nowrap {{ $chip }}">beendet</span>
                    @elseif ($state === 'off')
                        <span class="ml-1.5 rounded-full px-2 py-0.5 text-[11px] whitespace-nowrap {{ $chip }}">{{ $item->intervalLabel() }} · nächste Zahlung {{ $next ? $next->translatedFormat('M Y') : 'keine' }}</span>
                    @else
                        @if ($item->intervalMonths() > 1)
                            <span class="ml-1.5 rounded-full px-2 py-0.5 text-[11px] whitespace-nowrap bg-sky-500/15 text-sky-700 dark:text-sky-300">{{ $item->intervalLabel() }} · fällig</span>
                        @endif

                        @if ($left !== null)
                            @if ($left === 0)
                                <span class="ml-1.5 rounded-full bg-emerald-500/15 px-2 py-0.5 text-[11px] text-emerald-700 dark:text-emerald-300 whitespace-nowrap">letzte Rate</span>
                            @else
                                <span class="ml-1.5 rounded-full px-2 py-0.5 text-[11px] whitespace-nowrap {{ $left <= 2 ? 'bg-amber-500/15 text-amber-700 dark:text-amber-300' : $chip }}">noch {{ $left }} Mon.</span>
                            @endif
                        @endif
                    @endif

                    @if ($item->note)
                        <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">{{ $item->note }}</span>
                    @endif
                </a>

                <span class="hidden sm:block w-14 shrink-0 text-center text-xs tabular-nums text-slate-500 dark:text-slate-400" title="Zahlungstag">
                    @if ($item->due_day && $state === 'due')
                        <span class="rounded-full bg-slate-900/5 dark:bg-white/10 px-2 py-0.5">{{ $item->due_day }}.</span>
                    @else
                        <span aria-hidden="true">–</span>
                    @endif
                </span>

                <form method="POST" action="{{ route('plan.items.update', $item) }}" class="shrink-0">
                    @csrf
                    @method('PUT')
                    <input
                        type="text"
                        name="amount"
                        inputmode="decimal"
                        value="{{ number_format((float) $item->amount, 2, ',', '.') }}"
                        aria-label="Betrag {{ $item->name }}"
                        onchange="this.form.requestSubmit()"
                        class="fv-input !w-28 !py-1.5 text-right tabular-nums font-semibold"
                    >
                </form>
            </li>
        @endforeach
    </ul>

    <form method="POST" action="{{ route('plan.items.store', $group) }}" class="flex items-center gap-2 px-3 pt-1 pb-2">
        @csrf
        <input type="text" name="name" required maxlength="255" placeholder="Neuer Posten" aria-label="Neuer Posten in {{ $group->name }}" class="fv-input !py-1.5 min-w-0 flex-1">
        <input type="text" name="amount" inputmode="decimal" placeholder="0,00" aria-label="Betrag" class="fv-input !w-28 !py-1.5 text-right tabular-nums">
        <button type="submit" class="shrink-0 w-9 h-9 rounded-full flex items-center justify-center text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500/10 transition" aria-label="Posten hinzufügen">
            <x-icon name="plus" class="w-5 h-5" />
        </button>
    </form>

</section>
