            @if($dashboardWidgets['loans'] && $loans->isNotEmpty())

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
                                    Kredite
                                </p>

                                <h3 class="text-xl font-semibold text-slate-900 dark:text-white mt-1">
                                    Aktive Kredite
                                </h3>

                            </div>

                            <div
                                class="
                                    w-11
                                    h-11
                                    rounded-2xl
                                    bg-amber-50
                                    dark:bg-amber-950/40
                                    flex
                                    items-center
                                    justify-center
                                    text-lg
                                "
                            >
                                🏦
                            </div>

                        </div>


                        <div class="mt-6 space-y-4">

                            @foreach($loans as $loan)

                                @php
                                    $principal = (float) $loan->principal_amount;
                                    $remaining = max(0, (float) $loan->remainingAmount);
                                    $paid = max(0, $principal - $remaining);

                                    $progress = $principal > 0
                                        ? min(100, max(0, ($paid / $principal) * 100))
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
                                            :provider="$loan->provider"
                                            fallback-icon="🏦"
                                            size="sm"
                                        />

                                        <div class="min-w-0 flex-1">

                                            <p class="font-medium text-slate-900 dark:text-white truncate">
                                                {{ $loan->name }}
                                            </p>

                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                                {{ $loan->creditor_name ?: 'Kredit' }}
                                            </p>

                                        </div>

                                        <div class="text-right shrink-0">

                                            <p class="font-semibold text-slate-900 dark:text-white">
                                                {{ number_format($remaining, 2, ',', '.') }} €
                                            </p>

                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                                Restbetrag
                                            </p>

                                        </div>

                                    </div>


                                    <div class="mt-4">

                                        <div class="flex items-center justify-between mb-2">

                                            <span class="text-xs text-slate-500 dark:text-slate-400">
                                                Tilgungsfortschritt
                                            </span>

                                            <span class="text-xs font-medium text-slate-600 dark:text-slate-300">
                                                {{ number_format($progress, 0, ',', '.') }} %
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
                                                    bg-emerald-500
                                                "
                                                style="width: {{ $progress }}%"
                                            ></div>

                                        </div>

                                    </div>


                                    <div
                                        class="
                                            flex
                                            items-center
                                            justify-between
                                            gap-4
                                            mt-4
                                            pt-3
                                            border-t
                                            border-slate-200
                                            dark:border-slate-800
                                        "
                                    >

                                        <span class="text-xs text-slate-500 dark:text-slate-400">
                                            Ursprünglich
                                        </span>

                                        <span class="text-xs font-medium text-slate-700 dark:text-slate-300">
                                            {{ number_format($principal, 2, ',', '.') }} €
                                        </span>

                                    </div>

                                </div>

                            @endforeach

                        </div>

                    </div>

                </div>

            @endif
