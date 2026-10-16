@props([
    'value' => 0,
    'tone' => 'positive',
])

@php
    $colors = [
        'positive' => 'bg-emerald-500 dark:bg-emerald-400 dark:shadow-[0_0_12px_rgba(52,211,153,.6)]',
        'warning' => 'bg-amber-500 dark:bg-amber-400 dark:shadow-[0_0_12px_rgba(251,191,36,.6)]',
        'negative' => 'bg-red-500 dark:bg-rose-400 dark:shadow-[0_0_12px_rgba(251,113,133,.6)]',
        'neutral' => 'bg-slate-400',
    ];

    $width = min(100, max(0, (float) $value));
@endphp

<div {{ $attributes->merge(['class' => 'h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-white/10']) }}
    role="progressbar" aria-valuenow="{{ round($width) }}" aria-valuemin="0" aria-valuemax="100">
    <div class="h-full rounded-full {{ $colors[$tone] ?? $colors['positive'] }} transition-[width] duration-500"
        style="width: {{ $width }}%"></div>
</div>
