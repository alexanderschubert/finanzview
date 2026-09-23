@if($dashboardWidgets['summary'])

    {{-- GESAMTVERMÖGEN: hervorgehobene Karte in der Markenfarbe --}}

    <div class="relative h-full overflow-hidden rounded-3xl p-5 text-white shadow-lg shadow-emerald-900/20 bg-linear-to-br from-emerald-500 to-emerald-800">

        <div aria-hidden="true" class="absolute -right-8 -top-10 w-40 h-40 rounded-full border-[18px] border-white/10"></div>

        <div class="relative">
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-medium text-white/80">Gesamtvermögen</p>

                <span class="w-8 h-8 rounded-full bg-white/15 flex items-center justify-center">
                    <x-icon name="wallet" class="w-4 h-4" />
                </span>
            </div>

            <p class="mt-3 text-[26px] leading-tight font-semibold tracking-tight tabular-nums">
                {{ number_format($totalBalance, 2, ',', '.') }} €
            </p>

            <p class="mt-1 text-xs text-white/70">Alle berücksichtigten Konten</p>
        </div>

    </div>

@endif
