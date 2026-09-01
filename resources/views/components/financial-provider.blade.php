@props([
    'provider' => null,
    'fallbackIcon' => '🏦',
    'size' => 'md',
])

@php
    $sizeClasses = match ($size) {
        'sm' => 'w-10 h-10 text-lg',
        'lg' => 'w-16 h-16 text-3xl',
        default => 'w-14 h-14 text-2xl',
    };

    $backgroundColor = $provider?->color ?: '#f1f5f9';
@endphp

<div
    {{ $attributes->merge([
        'class' => "$sizeClasses rounded-2xl flex items-center justify-center flex-shrink-0 overflow-hidden"
    ]) }}
    style="background-color: {{ $backgroundColor }}20;"
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
