@props([
    'title',
    'subtitle' => null,
    'href' => null,
    'linkLabel' => 'Alle anzeigen',
])

{{-- Karte mit Überschrift und optionalem Link rechts oben. --}}
<section {{ $attributes->merge(['class' => 'fv-card overflow-hidden h-full']) }}>
    <header class="flex items-start justify-between gap-4 px-5 pt-5 sm:px-6 sm:pt-6">
        <div class="min-w-0">
            <h3 class="text-[17px] font-semibold text-slate-900 dark:text-white">{{ $title }}</h3>

            @if ($subtitle)
                <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ $subtitle }}</p>
            @endif
        </div>

        @isset($actions)
            {{ $actions }}
        @elseif ($href)
            <a href="{{ $href }}" class="fv-link shrink-0 inline-flex items-center gap-0.5 text-sm">
                {{ $linkLabel }}
                <x-icon name="chevron-right" class="w-4 h-4" />
            </a>
        @endisset
    </header>

    <div class="px-5 pb-5 pt-4 sm:px-6 sm:pb-6">
        {{ $slot }}
    </div>
</section>
