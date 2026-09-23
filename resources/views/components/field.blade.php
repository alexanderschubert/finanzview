@props([
    'label',
    'for' => null,
    'error' => null,
    'hint' => null,
])

{{-- Formularfeld mit Beschriftung, Hinweis und Fehlermeldung. --}}
<div {{ $attributes }}>
    <label @if ($for) for="{{ $for }}" @endif class="fv-label">{{ $label }}</label>

    {{ $slot }}

    @if ($error && $errors->has($error))
        <p class="mt-1.5 text-[13px] text-red-600 dark:text-red-400">{{ $errors->first($error) }}</p>
    @elseif ($hint)
        <p class="mt-1.5 text-[13px] text-slate-500 dark:text-slate-400">{{ $hint }}</p>
    @endif
</div>
