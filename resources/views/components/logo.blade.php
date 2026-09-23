@php
    // Eindeutige ID, falls das Logo mehrfach auf einer Seite steht.
    $gradientId = 'fv-logo-' . \Illuminate\Support\Str::random(6);
@endphp

<svg
    {{ $attributes->merge(['class' => 'w-10 h-10 shrink-0']) }}
    viewBox="0 0 512 512"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    role="img"
    aria-label="FinanzView"
>
    <defs>
        <linearGradient id="{{ $gradientId }}" x1="0" y1="0" x2="512" y2="512" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="#16A57A"/>
            <stop offset="1" stop-color="#0B5E49"/>
        </linearGradient>
    </defs>
    <rect width="512" height="512" rx="115" fill="url(#{{ $gradientId }})"/>
    <circle cx="256" cy="256" r="150" stroke="#A7EBD2" stroke-width="30"/>
    <path d="M150 300L215 245L265 280L362 190" stroke="#FFFFFF" stroke-width="34" stroke-linecap="round" stroke-linejoin="round"/>
    <circle cx="362" cy="190" r="24" fill="#FFFFFF"/>
</svg>
