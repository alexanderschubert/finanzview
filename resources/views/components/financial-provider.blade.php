@props([
    'provider' => null,
    'fallbackIcon' => '🏦',
    'size' => 'md',
])

@php
    $sizeClasses = match ($size) {
        'sm' => 'w-10 h-10 text-lg rounded-xl',
        'lg' => 'w-16 h-16 text-3xl rounded-[18px]',
        default => 'w-14 h-14 text-2xl rounded-2xl',
    };

    // Ohne Anbieterfarbe: neutrale Kachel (hell/dunkel über Klassen).
    $backgroundColor = $provider?->color;
@endphp

<div
    {{ $attributes->merge([
        'class' => "$sizeClasses flex items-center justify-center flex-shrink-0 overflow-hidden"
            . ($backgroundColor ? '' : ' bg-slate-100 dark:bg-white/5'),
    ]) }}
    @if ($backgroundColor) style="background-color: {{ $backgroundColor }}26;" @endif
>
    @if ($provider?->logo)
        <img
            src="{{ asset($provider->logo) }}"
            alt="{{ $provider->name }}"
            class="w-2/3 h-2/3 object-contain"
        >
    @elseif ($provider?->emoji)
        <span aria-hidden="true">
            {{ $provider->emoji }}
        </span>
    @else
        <span aria-hidden="true">
            {{ $fallbackIcon }}
        </span>
    @endif
</div>
