@if($dashboardWidgets['quick_actions'])

    {{-- SCHNELLZUGRIFF --}}

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        @foreach ([
            ['route' => 'transactions.create', 'icon' => 'plus', 'title' => 'Neue Buchung', 'primary' => true],
            ['route' => 'accounts.index', 'icon' => 'landmark', 'title' => 'Konten', 'primary' => false],
            ['route' => 'budgets.index', 'icon' => 'target', 'title' => 'Budgets', 'primary' => false],
            ['route' => 'reports.index', 'icon' => 'chart', 'title' => 'Analysen', 'primary' => false],
        ] as $quickAction)
            <a
                href="{{ route($quickAction['route']) }}"
                class="fv-card flex items-center gap-3 p-4 hover:-translate-y-0.5 transition"
            >
                <span class="w-9 h-9 rounded-full flex items-center justify-center {{ $quickAction['primary'] ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 dark:bg-white/5 dark:text-slate-300' }}">
                    <x-icon :name="$quickAction['icon']" class="w-[18px] h-[18px]" />
                </span>

                <span class="text-sm font-medium text-slate-900 dark:text-white">{{ $quickAction['title'] }}</span>
            </a>
        @endforeach
    </div>

@endif
