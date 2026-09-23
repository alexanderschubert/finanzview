@extends('layouts.guest')

@php
    $oidc = app(\App\Services\OidcService::class);
    $registrationEnabled = \App\Models\ApplicationSetting::get('registration_enabled', true);
@endphp

@section('title', 'Anmelden')
@section('heading', 'Willkommen bei FinanzView')
@section('intro', 'Melde dich an, um deine Finanzen im Blick zu behalten.')

@section('content')

    <form method="POST" action="{{ url('/login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="fv-label">E-Mail-Adresse</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                placeholder="name@beispiel.de"
                class="fv-input"
            >
        </div>

        <div>
            <div class="flex items-baseline justify-between">
                <label for="password" class="fv-label">Passwort</label>
                <a href="{{ route('password.request') }}" class="fv-link text-[13px]">Passwort vergessen?</a>
            </div>

            <x-password-input required placeholder="Passwort" />
        </div>

        <label class="flex items-center gap-2.5 pt-1 cursor-pointer select-none">
            <input type="checkbox" name="remember" value="1" class="w-4 h-4 rounded">
            <span class="text-sm text-slate-600 dark:text-slate-400">Angemeldet bleiben</span>
        </label>

        <button type="submit" class="fv-btn fv-btn-primary w-full mt-2">
            Anmelden
        </button>
    </form>


    @if ($oidc->enabled())

        <div class="relative my-6" aria-hidden="true">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full h-px bg-slate-200 dark:bg-white/10"></div>
            </div>
            <div class="relative flex justify-center">
                <span class="px-3 bg-white dark:bg-slate-900 text-xs text-slate-400">oder</span>
            </div>
        </div>

        <a href="{{ route('oidc.redirect') }}" class="fv-btn fv-btn-secondary w-full">
            <x-icon name="key" class="w-[18px] h-[18px]" />
            {{ $oidc->buttonLabel() }}
        </a>

    @endif

@endsection


@if ($registrationEnabled)
    @section('footer')
        Noch kein Konto?
        <a href="{{ route('register') }}" class="fv-link ml-1">Jetzt registrieren</a>
    @endsection
@endif
