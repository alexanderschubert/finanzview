@props(['tabs'])

{{-- Tabs innerhalb eines Bereichs (z. B. Planung: Monatsplan · Budgets · Wiederkehrend). --}}
<nav aria-label="Bereich" {{ $attributes->merge(['class' => 'items-center gap-1 overflow-x-auto']) }}>
    @foreach ($tabs as $tab)
        <a href="{{ route($tab['route']) }}" class="fv-tab" @if ($tab['active']) aria-current="page" @endif>{{ $tab['label'] }}@if (($tab['badge'] ?? 0) > 0) <span class="ml-1.5 rounded-full bg-orange-500 px-1.5 text-[11px] font-semibold text-white tabular-nums">{{ $tab['badge'] }}</span> @endif</a>
    @endforeach
</nav>
