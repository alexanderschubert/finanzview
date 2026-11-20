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
    {{ $attributes->merge(['class' => "relative aspect-[1.586] overflow-hidden rounded-2xl p-4 shadow-xl shadow-black/30 ring-1 ring-inset " . ($isLight ? 'ring-black/5' : 'ring-white/15') . " $text"]) }}
    style="background: linear-gradient(135deg, {{ $base }}, color-mix(in srgb, {{ $base }} {{ $isLight ? '80%, black' : '55%, black' }}));"
>
    <div aria-hidden="true" class="absolute inset-0" style="background: radial-gradient(120% 90% at 0% 0%, rgb(255 255 255 / {{ $isLight ? '0.55' : '0.2' }}), transparent 55%);"></div>
    <div aria-hidden="true" class="absolute -right-10 -bottom-14 w-40 h-40 rounded-full {{ $isLight ? 'bg-black/5' : 'bg-white/10' }}"></div>
    <div aria-hidden="true" class="absolute -right-2 -bottom-20 w-40 h-40 rounded-full {{ $isLight ? 'bg-black/[0.03]' : 'bg-white/5' }}"></div>

    <div class="relative flex h-full flex-col justify-between">

        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-sm font-semibold truncate">{{ $title }}</p>
                @if ($subtitle)
                    <p class="text-[11px] truncate {{ $muted }}">{{ $subtitle }}</p>
                @endif
            </div>

            <div class="flex items-center gap-2 shrink-0">
                @if ($badge)
                    <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $isLight ? 'bg-black/10' : 'bg-white/20' }}">{{ $badge }}</span>
                @endif

                @if ($provider?->logo)
                    <span class="w-7 h-7 rounded-lg bg-white/90 flex items-center justify-center overflow-hidden">
                        <img src="{{ asset($provider->logo) }}" alt="{{ $provider->name }}" class="w-2/3 h-2/3 object-contain">
                    </span>
                @elseif ($provider?->emoji)
                    <span class="text-xl" aria-hidden="true">{{ $provider->emoji }}</span>
                @endif
            </div>
        </div>

        <div>
            <p class="text-[11px] {{ $muted }}">{{ $amountLabel }}</p>
            <p class="fv-display text-lg font-extrabold tabular-nums">{{ $amount }}</p>
        </div>

        {{-- Zeile immer vorhanden, damit der Saldo bei allen Karten
             mittig steht – auch ohne IBAN bzw. Kartennummer. --}}
        <p class="font-mono text-[11px] tracking-[0.18em] min-h-4 {{ $muted }}" @if (blank($number)) aria-hidden="true" @endif>{{ $number }}</p>

    </div>
</div>
