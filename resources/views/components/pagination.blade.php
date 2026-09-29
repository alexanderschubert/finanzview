@props(['paginator'])

{{-- Seitennavigation im FinanzView-Stil (ersetzt die Laravel-Vorlage). --}}
@if ($paginator->hasPages())
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();

        // Erste, letzte und zwei Seiten um die aktuelle herum; Lücken als „…“.
        $pages = collect([1, $last, ...range(max(1, $current - 1), min($last, $current + 1))])
            ->unique()->sort()->values();

        $item = 'min-w-10 h-10 px-3 rounded-xl flex items-center justify-center text-sm font-medium tabular-nums transition';
        $idle = 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/10';
        $disabled = 'text-slate-300 dark:text-slate-600 pointer-events-none';
    @endphp

    <nav aria-label="Seiten" {{ $attributes->merge(['class' => 'flex flex-col sm:flex-row items-center justify-between gap-3']) }}>
        <p class="text-[13px] text-slate-500 dark:text-slate-400">
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} von {{ $paginator->total() }}
        </p>

        <div class="fv-card flex items-center gap-1 p-1">
            @if ($paginator->onFirstPage())
                <span class="{{ $item }} {{ $disabled }}" aria-hidden="true"><x-icon name="chevron-left" class="w-4 h-4" /></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $item }} {{ $idle }}" aria-label="Vorherige Seite"><x-icon name="chevron-left" class="w-4 h-4" /></a>
            @endif

            @foreach ($pages as $index => $page)
                @if ($index > 0 && $page - $pages[$index - 1] > 1)
                    <span class="{{ $item }} {{ $disabled }} px-1" aria-hidden="true">…</span>
                @endif

                @if ($page === $current)
                    <span class="{{ $item }} bg-emerald-600 text-white" aria-current="page">{{ $page }}</span>
                @else
                    <a href="{{ $paginator->url($page) }}" class="{{ $item }} {{ $idle }}" aria-label="Seite {{ $page }}">{{ $page }}</a>
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $item }} {{ $idle }}" aria-label="Nächste Seite"><x-icon name="chevron-right" class="w-4 h-4" /></a>
            @else
                <span class="{{ $item }} {{ $disabled }}" aria-hidden="true"><x-icon name="chevron-right" class="w-4 h-4" /></span>
            @endif
        </div>
    </nav>
@endif
