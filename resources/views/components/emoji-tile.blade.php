@props([
    'emoji' => null,
    'fallback' => 'tag',
    'color' => null,
    'size' => 'md',
])

@php
    $sizes = [
        'sm' => 'w-8 h-8 text-base rounded-lg',
        'md' => 'w-10 h-10 text-lg rounded-xl',
        'lg' => 'w-12 h-12 text-xl rounded-2xl',
    ];
@endphp

{{-- Einheitliche Kachel für frei wählbare Emojis (Kategorien, Budgets). --}}
<span
    {{ $attributes->merge(['class' => ($sizes[$size] ?? $sizes['md']) . ' shrink-0 flex items-center justify-center bg-slate-100 text-slate-500 dark:bg-white/5 dark:text-slate-400']) }}
    @if ($color) style="background-color: {{ $color }}26" @endif
    aria-hidden="true"
>
    @if ($emoji)
        {{ $emoji }}
    @else
        <x-icon :name="$fallback" class="w-[45%] h-[45%]" />
    @endif
</span>
