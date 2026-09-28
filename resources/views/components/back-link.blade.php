@props([
    'href',
    'label' => 'Zurück',
])

{{-- Zurück-Link im iOS-Stil ("‹ Einstellungen"). --}}
<a href="{{ $href }}" {{ $attributes->merge(['class' => 'fv-link -ml-1 inline-flex items-center gap-0.5 text-[15px]']) }}>
    <x-icon name="chevron-left" class="w-5 h-5" />
    {{ $label }}
</a>
