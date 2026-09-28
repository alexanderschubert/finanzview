@extends('layouts.app')

@section('title', 'Erscheinungsbild – FinanzView')
@section('eyebrow', 'Einstellungen')
@section('page_title', 'Erscheinungsbild')

@php
    $current = old('theme', $user->theme ?? 'system');

    $options = [
        'light' => 'Hell',
        'dark' => 'Dunkel',
        'system' => 'Automatisch',
    ];
@endphp

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-back-link :href="route('settings.index')" label="Einstellungen" />

    <x-page-header title="Erscheinungsbild" subtitle="„Automatisch“ folgt der Einstellung deines Geräts." />

    <x-flash />

    <form method="POST" action="{{ route('settings.appearance.update') }}" class="space-y-5">
        @csrf
        @method('PUT')

        <div class="fv-card p-5 sm:p-6">
            <div class="grid grid-cols-3 gap-3 sm:gap-5" role="radiogroup" aria-label="Darstellung">
                @foreach ($options as $value => $label)
                    <label class="group cursor-pointer text-center">
                        <input type="radio" name="theme" value="{{ $value }}" class="sr-only peer" @checked($current === $value) onchange="this.form.requestSubmit()">

                        {{-- Mini-Vorschau --}}
                        <span class="block overflow-hidden rounded-2xl ring-2 ring-transparent peer-checked:ring-emerald-500 peer-focus-visible:ring-emerald-500 transition aspect-[3/4] relative">
                            @if ($value === 'system')
                                <span class="absolute inset-0 bg-[#f2f2f7] [clip-path:polygon(0_0,100%_0,0_100%)]"></span>
                                <span class="absolute inset-0 bg-[#0b0b0c] [clip-path:polygon(100%_0,100%_100%,0_100%)]"></span>
                            @else
                                <span class="absolute inset-0 {{ $value === 'dark' ? 'bg-[#0b0b0c]' : 'bg-[#f2f2f7]' }}"></span>
                            @endif

                            <span class="absolute inset-x-[12%] top-[14%] h-[16%] rounded-lg {{ $value === 'dark' ? 'bg-[#1c1c1e]' : 'bg-white' }} {{ $value === 'system' ? 'bg-white/90' : '' }}"></span>
                            <span class="absolute inset-x-[12%] top-[36%] h-[10%] rounded-md bg-emerald-500/90"></span>
                            <span class="absolute left-[12%] right-[40%] top-[52%] h-[6%] rounded-full {{ $value === 'light' ? 'bg-slate-300' : 'bg-slate-600' }}"></span>
                            <span class="absolute left-[12%] right-[25%] top-[62%] h-[6%] rounded-full {{ $value === 'light' ? 'bg-slate-300' : 'bg-slate-600' }}"></span>
                            <span class="absolute inset-x-[12%] bottom-[10%] h-[14%] rounded-lg {{ $value === 'light' ? 'bg-white' : 'bg-[#1c1c1e]' }}"></span>
                        </span>

                        <span class="mt-2 block text-sm font-medium text-slate-900 dark:text-white">{{ $label }}</span>

                        <span class="mx-auto mt-1.5 flex h-5 w-5 items-center justify-center rounded-full border-2 border-slate-300 dark:border-slate-600 peer-checked:border-emerald-500 peer-checked:bg-emerald-500 transition">
                            <x-icon name="check-circle" class="w-5 h-5 text-white opacity-0 group-has-[:checked]:opacity-100" />
                        </span>
                    </label>
                @endforeach
            </div>

            @error('theme')
                <p class="mt-3 text-[13px] text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <noscript>
            <div class="flex justify-end">
                <button type="submit" class="fv-btn fv-btn-primary">Speichern</button>
            </div>
        </noscript>
    </form>

</div>

@endsection
