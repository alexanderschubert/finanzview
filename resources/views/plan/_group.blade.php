@php
    $money = fn ($value) => number_format((float) $value, 2, ',', '.') . ' €';
    $sum = (float) $group->items->sum('amount');
@endphp

<section id="group-{{ $group->id }}" class="fv-card p-2 scroll-mt-4">

    <div class="flex items-center justify-between gap-3 px-3 pt-3 pb-2">
        <h3 class="flex min-w-0 items-center gap-2.5 font-semibold text-slate-900 dark:text-white">
            <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $color }}; box-shadow: 0 0 10px {{ $color }}99" aria-hidden="true"></span>
            <span class="truncate">{{ $group->name }}</span>
            <a href="{{ route('plan.groups.edit', $group) }}" class="text-slate-400 hover:text-slate-700 dark:hover:text-white transition" aria-label="Gruppe {{ $group->name }} bearbeiten">
                <x-icon name="pencil" class="w-4 h-4" />
            </a>
        </h3>

        <span class="tabular-nums font-semibold {{ $group->isIncome() ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white' }}">{{ $money($sum) }}</span>
    </div>

    <ul>
        @foreach ($group->items as $item)
            @php
                $left = \App\Services\PlanService::monthsLeft($item->ends_on, $month);
            @endphp

            <li class="flex items-center gap-2 rounded-2xl px-3 py-2 hover:bg-slate-900/[0.03] dark:hover:bg-white/5 transition">
                <a href="{{ route('plan.items.edit', $item) }}" class="min-w-0 flex-1" aria-label="{{ $item->name }} bearbeiten">
                    <span class="break-words text-[15px] text-slate-900 dark:text-white">{{ $item->name }}</span>

                    @if ($left !== null)
                        @if ($left < 0)
                            <span class="ml-1.5 rounded-full bg-slate-900/5 dark:bg-white/10 px-2 py-0.5 text-[11px] text-slate-500 dark:text-slate-400 whitespace-nowrap">beendet</span>
                        @elseif ($left === 0)
                            <span class="ml-1.5 rounded-full bg-emerald-500/15 px-2 py-0.5 text-[11px] text-emerald-700 dark:text-emerald-300 whitespace-nowrap">letzte Rate</span>
                        @else
                            <span class="ml-1.5 rounded-full px-2 py-0.5 text-[11px] whitespace-nowrap {{ $left <= 2 ? 'bg-amber-500/15 text-amber-700 dark:text-amber-300' : 'bg-slate-900/5 dark:bg-white/10 text-slate-500 dark:text-slate-400' }}">noch {{ $left }} Mon.</span>
                        @endif
                    @endif

                    @if ($item->note)
                        <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">{{ $item->note }}</span>
                    @endif
                </a>

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
