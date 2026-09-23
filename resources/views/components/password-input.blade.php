@props([
    'id' => 'password',
    'name' => 'password',
    'autocomplete' => 'current-password',
])

{{-- Passwortfeld mit Knopf zum Ein-/Ausblenden (Skript im Guest-Layout). --}}
<div class="relative">
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="password"
        autocomplete="{{ $autocomplete }}"
        {{ $attributes->merge(['class' => 'fv-input pr-12']) }}
    >

    <button
        type="button"
        data-toggle-password="{{ $id }}"
        aria-label="Passwort anzeigen"
        class="absolute right-1.5 top-1/2 -translate-y-1/2 w-9 h-9 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition"
    >
        <x-icon name="eye" class="w-[18px] h-[18px]" data-icon-show />
        <x-icon name="eye-off" class="w-[18px] h-[18px] hidden" data-icon-hide />
    </button>
</div>
