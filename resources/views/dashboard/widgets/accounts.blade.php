        @if($dashboardWidgets['accounts'] && $accounts->isNotEmpty())

        {{-- KONTEN --}}

        <div
            class="
                {{ $dashboardWidgets['accounts'] && $dashboardWidgets['categories'] && $expensesByCategory->isNotEmpty()
                    ? 'lg:col-span-2'
                    : '' }}
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

            <div
                class="
                    p-6
                    border-b
                    border-slate-100
                    dark:border-slate-800
                    flex
                    items-center
                    justify-between
                    gap-4
                "
            >

                <div class="min-w-0">

                    <p class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                        Konten
                    </p>

                    <h3 class="font-semibold text-slate-900 dark:text-white mt-1">
                        Deine Konten
                    </h3>

                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Aktuelle Kontostände
                    </p>

                </div>

                <a
                    href="{{ route('accounts.index') }}"
                    class="
                        text-sm
                        text-slate-500
                        dark:text-slate-400
                        hover:text-emerald-600
                        dark:hover:text-emerald-400
                        transition
                        whitespace-nowrap
                    "
                >
                    Alle anzeigen →
                </a>

            </div>


            @if ($accounts->isEmpty())

                <div class="p-10 text-center">

                    <div class="w-14 h-14 mx-auto rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-2xl">
                        🏦
                    </div>

                    <p class="font-medium text-slate-900 dark:text-white mt-4">
                        Noch keine Konten
                    </p>

                    <a
                        href="{{ route('accounts.create') }}"
                        class="
                            inline-flex
                            mt-4
                            rounded-xl
                            bg-emerald-600
                            px-4
                            py-2
                            text-sm
                            font-medium
                            text-white
                            hover:bg-emerald-700
                            transition
                        "
                    >
                        Konto erstellen
                    </a>

                </div>

            @else

                <div class="divide-y divide-slate-100 dark:divide-slate-800">

                    @foreach ($accounts as $account)

                        <a
                            href="{{ route('accounts.edit', $account) }}"
                            class="
                                flex
                                items-center
                                gap-4
                                p-5
                                hover:bg-slate-50
                                dark:hover:bg-slate-800
                                transition
                            "
                        >

                            <x-financial-provider
                                :provider="$account->provider"
                                :fallback-icon="$account->icon ?: '🏦'"
                                size="sm"
                            />

                            <div class="flex-1 min-w-0">

                                <p class="font-medium text-slate-900 dark:text-white truncate">
                                    {{ $account->name }}
                                </p>

                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 truncate">

                                    @if ($account->institution)
                                        {{ $account->institution }}
                                    @else
                                        {{ ucfirst($account->type) }}
                                    @endif

                                </p>

                            </div>

                            <div class="text-right flex-shrink-0">

                                <p
                                    class="
                                        font-semibold
                                        {{ $account->calculated_balance >= 0
                                            ? 'text-slate-900 dark:text-white'
                                            : 'text-red-600 dark:text-red-400' }}
                                    "
                                >

                                    {{ number_format(
                                        $account->calculated_balance,
                                        2,
                                        ',',
                                        '.'
                                    ) }}

                                    {{ $account->currency }}

                                </p>

                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                                    Kontostand
                                </p>

                            </div>

                            <span class="hidden sm:block text-slate-300 dark:text-slate-600">
                                →
                            </span>

                        </a>

                    @endforeach

                </div>

            @endif

        </div>


        @endif
