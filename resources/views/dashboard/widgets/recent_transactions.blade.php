    @if($dashboardWidgets['recent_transactions'])

    {{-- LETZTE BUCHUNGEN --}}
    {{-- ========================================================= --}}

    <div
        class="
            bg-white
            dark:bg-slate-900
            rounded-3xl
            border
            border-slate-200
            dark:border-slate-800
            shadow-sm
            mt-5
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
                    Aktivitäten
                </p>

                <h3 class="font-semibold text-slate-900 dark:text-white mt-1">
                    Letzte Buchungen
                </h3>

                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Deine zuletzt erfassten Transaktionen
                </p>

            </div>

            <a
                href="{{ route('transactions.index') }}"
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


        @if ($recentTransactions->isEmpty())

            <div class="p-10 text-center">

                <div class="w-14 h-14 mx-auto rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-2xl">
                    💳
                </div>

                <p class="font-medium text-slate-900 dark:text-white mt-4">
                    Noch keine Buchungen
                </p>

                <a
                    href="{{ route('transactions.create') }}"
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
                    Erste Buchung erstellen
                </a>

            </div>

        @else

            <div class="divide-y divide-slate-100 dark:divide-slate-800">

                @foreach ($recentTransactions as $transaction)

                    <a
                        href="{{ route('transactions.edit', $transaction) }}"
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

                        <div
                            class="
                                w-11
                                h-11
                                rounded-2xl
                                flex
                                items-center
                                justify-center
                                flex-shrink-0
                                {{ $transaction->type === 'income'
                                    ? 'bg-emerald-50 dark:bg-emerald-950/50'
                                    : 'bg-red-50 dark:bg-red-950/50' }}
                            "
                        >
                            {{ $transaction->category?->icon ?: '💳' }}
                        </div>

                        <div class="flex-1 min-w-0">

                            <p class="font-medium text-slate-900 dark:text-white truncate">
                                {{ $transaction->description }}
                            </p>

                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 truncate">

                                {{ $transaction->transaction_date?->format('d.m.Y') }}

                                @if ($transaction->category)
                                    · {{ $transaction->category->name }}
                                @endif

                                @if ($transaction->account)
                                    · {{ $transaction->account->name }}
                                @endif

                            </p>

                        </div>

                        <p
                            class="
                                font-semibold
                                whitespace-nowrap
                                {{ $transaction->type === 'income'
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-red-600 dark:text-red-400' }}
                            "
                        >

                            {{ $transaction->type === 'income' ? '+' : '-' }}

                            {{ number_format(
                                $transaction->amount,
                                2,
                                ',',
                                '.'
                            ) }} €

                        </p>

                        <span class="hidden sm:block text-slate-300 dark:text-slate-600">
                            →
                        </span>

                    </a>

                @endforeach

            </div>

        @endif

    </div>


    {{-- ========================================================= --}}
    @endif
