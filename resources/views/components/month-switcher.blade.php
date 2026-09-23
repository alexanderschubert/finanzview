@props([
    'route',
    'month',
    'params' => [],
])

@php
    // $month im Format Y-m
    $current = \Carbon\Carbon::createFromFormat('!Y-m', $month);
    $isThisMonth = $current->isSameMonth(now());
@endphp

{{-- Monatsauswahl mit Pfeilen, im Stil eines iOS-Segmented-Controls. --}}
<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-1']) }}>
    <div class="inline-flex items-center rounded-full bg-white dark:bg-slate-900 p-1 shadow-sm ring-1 ring-slate-900/5 dark:ring-white/10">
        <a
            href="{{ route($route, array_merge($params, ['month' => $current->copy()->subMonth()->format('Y-m')])) }}"
            class="w-8 h-8 rounded-full flex items-center justify-center text-slate-500 hover:bg-slate-100 hover:text-slate-900 dark:hover:bg-white/10 dark:hover:text-white transition"
            aria-label="Vorheriger Monat"
        >
            <x-icon name="chevron-left" class="w-4 h-4" />
        </a>

        <span class="min-w-[9.5rem] px-2 text-center text-sm font-semibold text-slate-900 dark:text-white tabular-nums">
            {{ $current->translatedFormat('F Y') }}
        </span>

        <a
            href="{{ route($route, array_merge($params, ['month' => $current->copy()->addMonth()->format('Y-m')])) }}"
            class="w-8 h-8 rounded-full flex items-center justify-center text-slate-500 hover:bg-slate-100 hover:text-slate-900 dark:hover:bg-white/10 dark:hover:text-white transition"
            aria-label="Nächster Monat"
        >
            <x-icon name="chevron-right" class="w-4 h-4" />
        </a>
    </div>

    @unless ($isThisMonth)
        <a href="{{ route($route, $params) }}" class="fv-link text-sm px-2">Heute</a>
    @endunless
</div>
