@if($dashboardWidgets['credit_cards'] && $creditCards->isNotEmpty())

    {{-- KREDITKARTEN --}}

    <x-section title="Kreditkarten" subtitle="Aktueller Saldo" :href="route('credit-cards.index')">
        <ul class="space-y-5">
            @foreach($creditCards as $creditCard)
                @php
                    $creditLimit = (float) $creditCard->credit_limit;
                    $currentBalance = (float) $creditCard->current_balance;
                    $usage = $creditLimit > 0 ? min(100, max(0, ($currentBalance / $creditLimit) * 100)) : 0;
                @endphp

                <li>
                    <a href="{{ route('credit-cards.show', $creditCard) }}" class="block">
                        <div class="flex items-center gap-3">
                            <x-financial-provider :provider="$creditCard->provider" fallback-icon="💳" size="sm" />

                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-slate-900 dark:text-white truncate">{{ $creditCard->name }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                    @if($creditCard->last_four)
                                        •••• {{ $creditCard->last_four }}
                                    @else
                                        {{ $creditCard->issuer ?: 'Kreditkarte' }}
                                    @endif
                                </p>
                            </div>

                            <div class="text-right shrink-0">
                                <p class="font-semibold tabular-nums text-slate-900 dark:text-white">{{ number_format($currentBalance, 2, ',', '.') }} €</p>
                                @if($creditLimit > 0)
                                    <p class="text-xs text-slate-500 dark:text-slate-400">von {{ number_format($creditLimit, 0, ',', '.') }} €</p>
                                @endif
                            </div>
                        </div>

                        @if($creditLimit > 0)
                            <div class="mt-3 flex items-center gap-3">
                                <x-progress :value="$usage" :tone="$usage >= 80 ? 'negative' : ($usage >= 50 ? 'warning' : 'positive')" class="flex-1" />
                                <span class="w-10 text-right text-xs font-medium tabular-nums text-slate-500 dark:text-slate-400">
                                    {{ number_format($usage, 0, ',', '.') }} %
                                </span>
                            </div>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    </x-section>

@endif
