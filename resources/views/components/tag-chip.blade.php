@props(['tag'])

@php $color = $tag->displayColor(); @endphp

{{-- Kleines Tag-Etikett mit Farbpunkt. --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-medium text-slate-700 dark:text-slate-200 whitespace-nowrap']) }}
    style="background-color: {{ $color }}1f">
    <span class="w-1.5 h-1.5 rounded-full" style="background-color: {{ $color }}" aria-hidden="true"></span>
    {{ $tag->name }}
</span>
