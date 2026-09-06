            @if($dashboardWidgets['credit_cards'] && $creditCards->isNotEmpty())

                <div
                    class="
                        bg-white
                        dark:bg-slate-900
                        rounded-3xl
                        border
                        border-slate-200
                        dark:border-slate-800
                        shadow-sm
                        overflow-hidden
                    "
                >

                    <div class="p-6 sm:p-8">

                        <div class="flex items-center justify-between gap-4">

                            <div>

                                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">
                                    Kreditkarten
                                </p>

                                <h3 class="text-xl font-semibold text-slate-900 dark:text-white mt-1">
                                    Aktive Kreditkarten
                                </h3>

                            </div>

                            <div
                                class="
                                    w-11
                                    h-11
                                    rounded-2xl
                                    bg-violet-50
                                    dark:bg-violet-950/40
                                    flex
                                    items-center
                                    justify-center
                                    text-lg
                                "
                            >
                                💳
                            </div>

                        </div>


                        <div class="mt-6 space-y-4">

                            @foreach($creditCards as $creditCard)

                                @php
                                    $creditLimit = (float) $creditCard->credit_limit;
                                    $currentBalance = (float) $creditCard->current_balance;

                                    $usage = $creditLimit > 0
                                        ? min(100, max(0, ($currentBalance / $creditLimit) * 100))
                                        : 0;
                                @endphp

                                <div
                                    class="
                                        rounded-2xl
                                        border
                                        border-slate-100
                                        dark:border-slate-800
                                        bg-slate-50
                                        dark:bg-slate-950/40
                                        p-4
                                    "
                                >

                                    <div class="flex items-center gap-3">

                                        <x-financial-provider
                                            :provider="$creditCard->provider"
                                            fallback-icon="💳"
                                            size="sm"
                                        />

                                        <div class="min-w-0 flex-1">

                                            <p class="font-medium text-slate-900 dark:text-white truncate">
                                                {{ $creditCard->name }}
                                            </p>

                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">

                                                @if($creditCard->last_four)
                                                    •••• {{ $creditCard->last_four }}
                                                @elseif($creditCard->issuer)
                                                    {{ $creditCard->issuer }}
                                                @else
                                                    Kreditkarte
                                                @endif

                                            </p>

                                        </div>

                                        <div class="text-right shrink-0">

                                            <p class="font-semibold text-slate-900 dark:text-white">
                                                {{ number_format($currentBalance, 2, ',', '.') }} €
                                            </p>

                                            @if($creditLimit > 0)

                                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                                    von {{ number_format($creditLimit, 2, ',', '.') }} €
                                                </p>

                                            @endif

                                        </div>

                                    </div>


                                    @if($creditLimit > 0)

                                        <div class="mt-4">

                                            <div class="flex items-center justify-between mb-2">

                                                <span class="text-xs text-slate-500 dark:text-slate-400">
                                                    Auslastung
                                                </span>

                                                <span
                                                    class="
                                                        text-xs
                                                        font-medium
                                                        {{ $usage >= 80
                                                            ? 'text-red-600 dark:text-red-400'
                                                            : ($usage >= 50
                                                                ? 'text-amber-600 dark:text-amber-400'
                                                                : 'text-slate-600 dark:text-slate-300') }}
                                                    "
                                                >
                                                    {{ number_format($usage, 0, ',', '.') }} %
                                                </span>

                                            </div>

                                            <div
                                                class="
                                                    h-2
                                                    rounded-full
                                                    bg-slate-200
                                                    dark:bg-slate-800
                                                    overflow-hidden
                                                "
                                            >

                                                <div
                                                    class="
                                                        h-full
                                                        rounded-full
                                                        {{ $usage >= 80
                                                            ? 'bg-red-500'
                                                            : ($usage >= 50
                                                                ? 'bg-amber-500'
                                                                : 'bg-violet-500') }}
                                                    "
                                                    style="width: {{ $usage }}%"
                                                ></div>

                                            </div>

                                        </div>

                                    @endif

                                </div>

                            @endforeach

                        </div>

                    </div>

                </div>

            @endif
