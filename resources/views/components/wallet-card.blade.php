@props([
    'color' => null,
    'fallbackColor' => '#3f3f45',
    'title',
    'subtitle' => null,
    'amountLabel' => 'Saldo',
    'amount',
    'number' => null,
    'badge' => null,
    'provider' => null,
])

@php
    /*
     * Kartenfarbe: gültige Hex-Farbe, die nicht das helle
     * Standardgrau ist – sonst die Ersatzfarbe.
     */
    $isUsable = fn ($value) => is_string($value)
        && preg_match('/^#[0-9a-fA-F]{6}$/', $value)
        && strtolower($value) !== '#f1f5f9';

    $base = $isUsable($color) ? $color : ($isUsable($fallbackColor) ? $fallbackColor : '#3f3f45');

    /*
     * Helle Karten bekommen dunkle Schrift (relative Luminanz).
     */
    [$r, $g, $b] = sscanf($base, '#%02x%02x%02x');
    $luminance = (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255;
    $isLight = $luminance > 0.62;

    $text = $isLight ? 'text-slate-900' : 'text-white';
    $muted = $isLight ? 'text-slate-900/60' : 'text-white/70';
@endphp

{{-- Karte im Stil der Apple-Wallet (Seitenverhältnis einer echten Karte). --}}
<div
    {{ $attributes->merge(['class' => "relative aspect-[1.586] overflow-hidden rounded-2xl p-5 shadow-lg shadow-slate-900/20 $text"]) }}
    style="background: linear-gradient(135deg, {{ $base }}, color-mix(in srgb, {{ $base }} {{ $isLight ? '80%, black' : '55%, black' }}));"
>
    <div aria-hidden="true" class="absolute -right-12 -bottom-16 w-52 h-52 rounded-full {{ $isLight ? 'bg-black/5' : 'bg-white/10' }}"></div>
    <div aria-hidden="true" class="absolute -right-2 -bottom-24 w-52 h-52 rounded-full {{ $isLight ? 'bg-black/[0.03]' : 'bg-white/5' }}"></div>

    <div class="relative flex h-full flex-col justify-between">

        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="font-semibold truncate">{{ $title }}</p>
                @if ($subtitle)
                    <p class="text-xs truncate {{ $muted }}">{{ $subtitle }}</p>
                @endif
            </div>

            <div class="flex items-center gap-2 shrink-0">
                @if ($badge)
                    <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $isLight ? 'bg-black/10' : 'bg-white/20' }}">{{ $badge }}</span>
                @endif

                @if ($provider?->logo)
                    <span class="w-9 h-9 rounded-xl bg-white/90 flex items-center justify-center overflow-hidden">
                        <img src="{{ asset($provider->logo) }}" alt="{{ $provider->name }}" class="w-2/3 h-2/3 object-contain">
                    </span>
                @elseif ($provider?->emoji)
                    <span class="text-xl" aria-hidden="true">{{ $provider->emoji }}</span>
                @endif
            </div>
        </div>

        <div>
            <p class="text-xs {{ $muted }}">{{ $amountLabel }}</p>
            <p class="text-2xl font-semibold tracking-tight tabular-nums">{{ $amount }}</p>
        </div>

        @if ($number)
            <p class="font-mono text-sm tracking-[0.2em] {{ $muted }}">{{ $number }}</p>
        @endif

    </div>
</div>
