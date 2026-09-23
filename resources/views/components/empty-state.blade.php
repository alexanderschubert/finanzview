@props([
    'icon' => 'plus',
    'title',
    'href' => null,
    'action' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center text-center py-8']) }}>
    <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-white/5 flex items-center justify-center text-slate-400">
        <x-icon :name="$icon" class="w-6 h-6" />
    </div>

    <p class="mt-3 font-medium text-slate-900 dark:text-white">{{ $title }}</p>

    @if (trim($slot) !== '')
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400 max-w-xs">{{ $slot }}</p>
    @endif

    @if ($href && $action)
        <a href="{{ $href }}" class="fv-btn fv-btn-secondary mt-4 text-sm py-2">
            <x-icon name="plus" class="w-4 h-4" />
            {{ $action }}
        </a>
    @endif
</div>
