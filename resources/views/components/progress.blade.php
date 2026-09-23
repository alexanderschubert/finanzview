@props([
    'value' => 0,
    'tone' => 'positive',
])

@php
    $colors = [
        'positive' => 'bg-emerald-500',
        'warning' => 'bg-amber-500',
        'negative' => 'bg-red-500',
        'neutral' => 'bg-slate-400',
    ];

    $width = min(100, max(0, (float) $value));
@endphp

<div {{ $attributes->merge(['class' => 'h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-white/10']) }}
    role="progressbar" aria-valuenow="{{ round($width) }}" aria-valuemin="0" aria-valuemax="100">
    <div class="h-full rounded-full {{ $colors[$tone] ?? $colors['positive'] }} transition-[width] duration-500"
        style="width: {{ $width }}%"></div>
</div>
