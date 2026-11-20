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

@php
    // „•••• 1234“ wird zur vierteiligen Kartennummer erweitert.
    $display = $number;
    if (is_string($number) && preg_match('/^[•\s*]+([A-Za-z0-9]{4})$/u', $number, $m)) {
        $display = '•••• •••• •••• ' . $m[1];
    }

    $shine = $isLight ? 'rgb(255 255 255 / 0.65)' : 'rgb(255 255 255 / 0.22)';
@endphp

{{-- Karte im Look einer echten Bankkarte: Chip, Kontaktlos-Symbol, geprägte Nummer. --}}
<div
    {{ $attributes->merge(['class' => "@container relative aspect-[1.586] overflow-hidden rounded-[18px] shadow-xl shadow-black/30 ring-1 ring-inset " . ($isLight ? 'ring-black/10' : 'ring-white/20') . " $text"]) }}
    style="background: linear-gradient(135deg, {{ $base }}, color-mix(in srgb, {{ $base }} {{ $isLight ? '78%, black' : '50%, black' }}));"
>
    {{-- Glanz und feine Linienstruktur --}}
    <div aria-hidden="true" class="absolute inset-0" style="background: linear-gradient(115deg, {{ $shine }} 0%, transparent 38%, transparent 62%, rgb(255 255 255 / {{ $isLight ? '0.25' : '0.07' }}) 100%);"></div>
    <div aria-hidden="true" class="absolute inset-0 opacity-[0.07]" style="background: repeating-linear-gradient(135deg, currentColor 0 1px, transparent 1px 7px);"></div>
    <div aria-hidden="true" class="absolute -right-[18%] -bottom-[48%] aspect-square w-[75%] rounded-full {{ $isLight ? 'bg-black/[0.05]' : 'bg-white/[0.07]' }}"></div>
    <div aria-hidden="true" class="absolute -right-[4%] -bottom-[60%] aspect-square w-[75%] rounded-full {{ $isLight ? 'bg-black/[0.03]' : 'bg-white/[0.04]' }}"></div>

    <div class="absolute inset-0 flex flex-col justify-between p-[5.5cqw]">

        {{-- Kopf: Anbieter links, Kontaktlos rechts --}}
        <div class="flex items-start justify-between gap-3">
            <div class="flex min-w-0 items-center gap-2">
                @if ($provider?->logo)
                    <span class="flex h-[7cqw] w-[7cqw] min-h-6 min-w-6 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-white/90 shadow-sm">
                        <img src="{{ asset($provider->logo) }}" alt="{{ $provider->name }}" class="h-2/3 w-2/3 object-contain">
                    </span>
                @elseif ($provider?->emoji)
                    <span class="text-xl leading-none" aria-hidden="true">{{ $provider->emoji }}</span>
                @endif

                @if ($subtitle)
                    <p class="truncate text-[11px] font-semibold uppercase tracking-[0.14em] {{ $muted }}">{{ $subtitle }}</p>
                @endif
            </div>

            <div class="flex shrink-0 items-center gap-2">
                @if ($badge)
                    <span class="rounded-full px-2 py-0.5 text-[10px] font-medium {{ $isLight ? 'bg-black/10' : 'bg-white/20' }}">{{ $badge }}</span>
                @endif

                <svg viewBox="0 0 24 24" class="h-5 w-5 {{ $muted }}" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                    <path d="M8.5 8.5a5 5 0 0 1 0 7"/><path d="M12 6a8.5 8.5 0 0 1 0 12"/><path d="M15.5 3.5a12 12 0 0 1 0 17"/>
                </svg>
            </div>
        </div>

        {{-- Chip und Kartennummer --}}
        <div>
            <svg viewBox="0 0 44 34" class="h-[8.5cqw] min-h-6 w-auto" aria-hidden="true">
                <defs>
                    <linearGradient id="fv-chip-gold" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0" stop-color="#f6e3a1"/><stop offset=".5" stop-color="#d7b259"/><stop offset="1" stop-color="#b98d2f"/>
                    </linearGradient>
                </defs>
                <rect x="1" y="1" width="42" height="32" rx="6" fill="url(#fv-chip-gold)" stroke="#8d6a1c" stroke-opacity=".5"/>
                <path d="M1 12h12m0 0v10m0-10 6-5h6l6 5m0 0h12M31 12v10M13 22H1m12 0 6 5h6l6-5m0 0h12M22 7v20" fill="none" stroke="#8d6a1c" stroke-opacity=".55" stroke-width="1"/>
            </svg>

            <p class="mt-[3cqw] font-mono text-[clamp(11px,4.4cqw,15px)] tracking-[0.16em] min-h-4 whitespace-nowrap {{ $isLight ? '' : '[text-shadow:0_1px_1px_rgb(0_0_0/0.35)]' }}" @if (blank($display)) aria-hidden="true" @endif>{{ $display }}</p>
        </div>

        {{-- Fuß: Name links, Kontostand rechts --}}
        <div class="flex items-end justify-between gap-3">
            <p class="min-w-0 truncate text-[12px] font-semibold uppercase tracking-[0.1em]">{{ $title }}</p>

            <div class="shrink-0 text-right">
                <p class="text-[9px] uppercase tracking-wider {{ $muted }}">{{ $amountLabel }}</p>
                <p class="fv-display text-[clamp(14px,5.6cqw,19px)] font-extrabold tabular-nums leading-tight">{{ $amount }}</p>
            </div>
        </div>

    </div>
</div>
