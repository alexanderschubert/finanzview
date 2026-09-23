@if($dashboardWidgets['accounts'] && $accounts->isNotEmpty())

    {{-- KONTEN --}}

    <x-section title="Konten" subtitle="Aktuelle Kontostände" :href="route('accounts.index')">
        <ul class="-mx-2 divide-y divide-slate-100 dark:divide-white/5">
            @foreach ($accounts as $account)
                <li>
                    <a href="{{ route('accounts.edit', $account) }}" class="flex items-center gap-3 rounded-xl px-2 py-2.5 hover:bg-slate-50 dark:hover:bg-white/5 transition">
                        <x-financial-provider :provider="$account->provider" :fallback-icon="$account->icon ?: '🏦'" size="sm" />

                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-slate-900 dark:text-white truncate">{{ $account->name }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                {{ $account->institution ?: ucfirst($account->type) }}
                            </p>
                        </div>

                        <p class="font-semibold tabular-nums whitespace-nowrap {{ $account->calculated_balance < 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white' }}">
                            {{ number_format($account->calculated_balance, 2, ',', '.') }} {{ $account->currency === 'EUR' ? '€' : $account->currency }}
                        </p>

                        <x-icon name="chevron-right" class="w-4 h-4 text-slate-300 dark:text-slate-600" />
                    </a>
                </li>
            @endforeach
        </ul>
    </x-section>

@endif
