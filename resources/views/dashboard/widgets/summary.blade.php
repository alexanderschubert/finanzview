    @if($dashboardWidgets['summary'])

    {{-- GESAMTVERMÖGEN --}}

        <div
            class="
                relative
                overflow-hidden
                rounded-3xl
                bg-slate-950
                dark:bg-slate-900
                text-white
                p-6
                shadow-sm
            "
        >

            <div
                class="
                    absolute
                    -right-10
                    -top-10
                    w-36
                    h-36
                    rounded-full
                    bg-emerald-500/10
                "
            ></div>

            <div class="relative">

                <div class="flex items-center justify-between">

                    <p class="text-sm text-slate-400">
                        Gesamtvermögen
                    </p>

                    <div
                        class="
                            w-10
                            h-10
                            rounded-2xl
                            bg-white/10
                            flex
                            items-center
                            justify-center
                            text-lg
                        "
                    >
                        💰
                    </div>

                </div>

                <p class="text-3xl font-semibold tracking-tight mt-5">

                    {{ number_format(
                        $totalBalance,
                        2,
                        ',',
                        '.'
                    ) }} €

                </p>

                <p class="text-xs text-slate-500 mt-2">
                    Alle berücksichtigten Konten
                </p>

            </div>

        </div>


        @endif
