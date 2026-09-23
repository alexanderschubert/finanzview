@if($dashboardWidgets['recent_transactions'])

    {{-- LETZTE BUCHUNGEN --}}

    <x-section title="Letzte Buchungen" :href="route('transactions.index')">
        @if ($recentTransactions->isEmpty())
            <x-empty-state icon="arrows" title="Noch keine Buchungen" :href="route('transactions.create')" action="Buchung erfassen">
                Erfasse deine erste Einnahme oder Ausgabe.
            </x-empty-state>
        @else
            <ul class="-mx-2 divide-y divide-slate-100 dark:divide-white/5">
                @foreach ($recentTransactions as $transaction)
                    @php
                        $isTransfer = $transaction->type === 'transfer';
                        $isIncome = $transaction->type === 'income';
                    @endphp

                    <li>
                        <a href="{{ route('transactions.edit', $transaction) }}" class="flex items-center gap-3 rounded-xl px-2 py-2.5 hover:bg-slate-50 dark:hover:bg-white/5 transition">
                            @if ($isTransfer)
                                <x-emoji-tile fallback="arrows" />
                            @else
                                <x-emoji-tile :emoji="$transaction->category?->icon" fallback="tag" />
                            @endif

                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-slate-900 dark:text-white truncate">{{ $transaction->description }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                    {{ $transaction->transaction_date?->format('d.m.Y') }}
                                    @if ($isTransfer)
                                        · Umbuchung
                                    @elseif ($transaction->category)
                                        · {{ $transaction->category->name }}
                                    @endif
                                    @if ($transaction->account)
                                        · {{ $transaction->account->name }}
                                    @endif
                                </p>
                            </div>

                            <p class="font-semibold tabular-nums whitespace-nowrap {{ $isTransfer ? 'text-slate-500 dark:text-slate-400' : ($isIncome ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white') }}">
                                {{ $isTransfer ? '' : ($isIncome ? '+' : '−') }}{{ number_format($transaction->amount, 2, ',', '.') }} €
                            </p>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-section>

@endif
