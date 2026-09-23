@if($dashboardWidgets['loans'] && $loans->isNotEmpty())

    {{-- KREDITE --}}

    <x-section title="Kredite" subtitle="Tilgungsfortschritt" :href="route('loans.index')">
        <ul class="space-y-5">
            @foreach($loans as $loan)
                @php
                    $principal = (float) $loan->principal_amount;
                    $remaining = max(0, (float) $loan->remainingAmount);
                    $paid = max(0, $principal - $remaining);
                    $progress = $principal > 0 ? min(100, max(0, ($paid / $principal) * 100)) : 0;
                @endphp

                <li>
                    <a href="{{ route('loans.show', $loan) }}" class="block">
                        <div class="flex items-center gap-3">
                            <x-financial-provider :provider="$loan->provider" fallback-icon="🏦" size="sm" />

                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-slate-900 dark:text-white truncate">{{ $loan->name }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                    {{ $loan->creditor_name ?: 'Kredit' }} · {{ number_format($principal, 2, ',', '.') }} €
                                </p>
                            </div>

                            <div class="text-right shrink-0">
                                <p class="font-semibold tabular-nums text-slate-900 dark:text-white">{{ number_format($remaining, 2, ',', '.') }} €</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">offen</p>
                            </div>
                        </div>

                        <div class="mt-3 flex items-center gap-3">
                            <x-progress :value="$progress" class="flex-1" />
                            <span class="w-10 text-right text-xs font-medium tabular-nums text-slate-500 dark:text-slate-400">
                                {{ number_format($progress, 0, ',', '.') }} %
                            </span>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    </x-section>

@endif
