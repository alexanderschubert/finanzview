@props(['tabs'])

{{-- Tabs innerhalb eines Bereichs (z. B. Planung: Monatsplan · Budgets · Wiederkehrend). --}}
<nav aria-label="Bereich" {{ $attributes->merge(['class' => 'items-center gap-1 overflow-x-auto']) }}>
    @foreach ($tabs as $tab)
        <a href="{{ route($tab['route']) }}" class="fv-tab" @if ($tab['active']) aria-current="page" @endif>{{ $tab['label'] }}</a>
    @endforeach
</nav>
