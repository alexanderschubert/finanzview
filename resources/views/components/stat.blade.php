@props([
    'label',
    'icon' => null,
    'tone' => 'neutral',
    'hint' => null,
])

@php
    // [Wertfarbe, Icon-Hintergrund]
    $tones = [
        'neutral' => ['text-slate-900 dark:text-white', 'bg-slate-100 text-slate-500 dark:bg-white/5 dark:text-slate-400'],
        'positive' => ['text-emerald-600 dark:text-emerald-400', 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400'],
        'negative' => ['text-red-600 dark:text-red-400', 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400'],
    ];

    [$valueClass, $iconClass] = $tones[$tone] ?? $tones['neutral'];
@endphp

<div {{ $attributes->merge(['class' => 'fv-card p-5 h-full']) }}>
    <div class="flex items-center justify-between gap-3">
        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $label }}</p>

        @if ($icon)
            <span class="w-8 h-8 rounded-full flex items-center justify-center {{ $iconClass }}">
                <x-icon :name="$icon" class="w-4 h-4" />
            </span>
        @endif
    </div>

    <p class="mt-3 text-[26px] leading-tight font-semibold tracking-tight tabular-nums {{ $valueClass }}">
        {{ $slot }}
    </p>

    @if ($hint)
        <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ $hint }}</p>
    @endif
</div>
