@props([
    'value' => 0,
    'tone' => 'positive',
    'size' => 180,
    'stroke' => 16,
])

@php
    $colors = [
        'positive' => 'var(--color-emerald-500)',
        'warning' => 'var(--color-amber-500)',
        'negative' => 'var(--color-red-500)',
    ];

    $radius = ($size - $stroke) / 2;
    $circumference = 2 * M_PI * $radius;
    $clamped = min(100, max(0, (float) $value));
    $offset = $circumference * (1 - $clamped / 100);
@endphp

{{-- Fortschrittsring im Stil der Apple-Aktivitätsringe. --}}
<div {{ $attributes->merge(['class' => 'relative inline-flex items-center justify-center']) }} style="width: {{ $size }}px; height: {{ $size }}px;"
    role="progressbar" aria-valuenow="{{ round($clamped) }}" aria-valuemin="0" aria-valuemax="100">
    <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 {{ $size }} {{ $size }}" class="-rotate-90">
        <circle cx="{{ $size / 2 }}" cy="{{ $size / 2 }}" r="{{ $radius }}" fill="none" stroke="currentColor" stroke-width="{{ $stroke }}"
            class="text-slate-100 dark:text-white/10" />
        <circle cx="{{ $size / 2 }}" cy="{{ $size / 2 }}" r="{{ $radius }}" fill="none"
            stroke="{{ $colors[$tone] ?? $colors['positive'] }}" stroke-width="{{ $stroke }}" stroke-linecap="round"
            stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $offset }}"
            class="transition-[stroke-dashoffset] duration-700" />
    </svg>

    <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
        {{ $slot }}
    </div>
</div>
