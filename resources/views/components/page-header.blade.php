@props(['title', 'subtitle' => null])

{{-- Großer Seitentitel im Stil von iOS/macOS, rechts optionale Aktionen. --}}
<div {{ $attributes->merge(['class' => 'flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div class="flex min-w-0 items-center gap-4">
    @isset($leading)
        <div class="shrink-0">{{ $leading }}</div>
    @endisset

    <div class="min-w-0">
        <h2 class="text-[28px] sm:text-[34px] leading-tight font-bold tracking-tight text-slate-900 dark:text-white">
            {{ $title }}
        </h2>

        @if ($subtitle)
            <p class="mt-1 text-[15px] text-slate-500 dark:text-slate-400">
                {{ $subtitle }}
            </p>
        @endif
    </div>
    </div>

    @if (trim($slot) !== '')
        <div class="flex flex-wrap items-center gap-2">
            {{ $slot }}
        </div>
    @endif
</div>
