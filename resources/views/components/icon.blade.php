@props(['name'])

@php
    /*
     * Einheitliche Linien-Icons (24×24, Strichstärke über
     * currentColor). Verwendung: <x-icon name="home" class="w-5 h-5" />
     */
    $paths = [
        'home' => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V9.5"/>',
        'landmark' => '<path d="M3 9.5 12 4l9 5.5"/><path d="M4 21h16"/><path d="M6 10v8M10 10v8M14 10v8M18 10v8"/>',
        'arrows' => '<path d="M7 20V4M7 4 3.5 7.5M7 4l3.5 3.5"/><path d="M17 4v16M17 20l-3.5-3.5M17 20l3.5-3.5"/>',
        'tag' => '<path d="M3 4a1 1 0 0 1 1-1h7.6a1 1 0 0 1 .7.3l8.4 8.4a1 1 0 0 1 0 1.4l-7.3 7.3a1 1 0 0 1-1.4 0L3.3 11.7a1 1 0 0 1-.3-.7Z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
        'target' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>',
        'banknote' => '<rect x="2.5" y="6" width="19" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 9.5h.01M18 14.5h.01"/>',
        'card' => '<rect x="2.5" y="5" width="19" height="14" rx="2.5"/><path d="M2.5 10h19M6.5 15h3"/>',
        'chart' => '<path d="M3 20h18"/><path d="M6 20v-6M12 20V6M18 20V10"/>',
        'repeat' => '<path d="M17 2.5 20.5 6 17 9.5"/><path d="M3.5 11V9a3 3 0 0 1 3-3h14"/><path d="M7 21.5 3.5 18 7 14.5"/><path d="M20.5 13v2a3 3 0 0 1-3 3h-14"/>',
        'settings' => '<path d="M4 6h9M17 6h3M4 12h3M11 12h9M4 18h11M19 18h1"/><circle cx="15" cy="6" r="2"/><circle cx="9" cy="12" r="2"/><circle cx="17" cy="18" r="2"/>',
        'shield' => '<path d="M12 3 4.5 6v5.5c0 4.5 3.2 8.2 7.5 9.5 4.3-1.3 7.5-5 7.5-9.5V6L12 3Z"/>',
        'logout' => '<path d="M9 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h3"/><path d="M14 16l4-4-4-4"/><path d="M18 12H8"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'more' => '<circle cx="5" cy="12" r="1.25" fill="currentColor"/><circle cx="12" cy="12" r="1.25" fill="currentColor"/><circle cx="19" cy="12" r="1.25" fill="currentColor"/>',
        'chevron-right' => '<path d="m9 6 6 6-6 6"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'x' => '<path d="M6 6l12 12M18 6 6 18"/>',
    ];
@endphp

<svg
    {{ $attributes->merge(['class' => 'w-5 h-5 shrink-0']) }}
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.75"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
>{!! $paths[$name] ?? '' !!}</svg>
