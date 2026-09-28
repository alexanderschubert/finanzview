@extends('layouts.app')

@section('title', 'Profil – FinanzView')
@section('eyebrow', 'Einstellungen')
@section('page_title', 'Profil')

@php
    $initials = collect(preg_split('/\s+/', trim($user->name)))
        ->filter()->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->join('');
@endphp

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-back-link :href="route('settings.index')" label="Einstellungen" />

    <div class="flex flex-col items-center text-center">
        <div class="w-20 h-20 rounded-full bg-emerald-600 text-white flex items-center justify-center text-2xl font-semibold" data-avatar>
            {{ $initials ?: '?' }}
        </div>
        <h2 class="mt-3 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $user->name }}</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400">{{ $user->email }}</p>
    </div>

    <x-flash />

    <form method="POST" action="{{ route('settings.profile.update') }}" class="space-y-5">
        @csrf
        @method('PUT')

        <div class="fv-card p-5 sm:p-6 space-y-4">
            <x-field label="Name" for="name" error="name">
                <input id="name" name="name" type="text" required maxlength="255" autocomplete="name"
                    value="{{ old('name', $user->name) }}" class="fv-input">
            </x-field>

            <x-field label="E-Mail-Adresse" for="email" error="email" :hint="$user->oidc_sub ? 'Mit SSO verknüpft – die Anmeldung über SSO funktioniert unabhängig von dieser Adresse.' : 'Wird für die Anmeldung und „Passwort vergessen“ verwendet.'">
                <input id="email" name="email" type="email" required maxlength="255" autocomplete="email"
                    value="{{ old('email', $user->email) }}" class="fv-input">
            </x-field>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="fv-btn fv-btn-primary">Speichern</button>
        </div>
    </form>

    <section>
        <h3 class="px-1 pb-2 text-[13px] font-semibold text-slate-500 dark:text-slate-400">Konto</h3>

        <dl class="fv-card overflow-hidden divide-y divide-slate-100 dark:divide-white/5 text-sm">
            <div class="flex items-center justify-between gap-4 px-4 py-3">
                <dt class="text-slate-500 dark:text-slate-400">Mitglied seit</dt>
                <dd class="font-medium text-slate-900 dark:text-white">{{ $user->created_at?->format('d.m.Y') ?? '–' }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4 px-4 py-3">
                <dt class="text-slate-500 dark:text-slate-400">Letzte Anmeldung</dt>
                <dd class="font-medium text-slate-900 dark:text-white">{{ $user->last_login_at?->format('d.m.Y H:i') ?? '–' }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4 px-4 py-3">
                <dt class="text-slate-500 dark:text-slate-400">Rolle</dt>
                <dd class="font-medium text-slate-900 dark:text-white">{{ $user->isAdmin() ? 'Administrator' : 'Benutzer' }}</dd>
            </div>
            <a href="{{ route('settings.security') }}" class="flex items-center justify-between gap-4 px-4 py-3 hover:bg-slate-50 dark:hover:bg-white/5 transition">
                <span class="text-slate-500 dark:text-slate-400">Zwei-Faktor-Authentifizierung</span>
                <span class="flex items-center gap-1 font-medium {{ $user->hasEnabledTwoFactorAuthentication() ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white' }}">
                    {{ $user->hasEnabledTwoFactorAuthentication() ? 'Aktiv' : 'Aus' }}
                    <x-icon name="chevron-right" class="w-4 h-4 text-slate-300 dark:text-slate-600" />
                </span>
            </a>
        </dl>
    </section>

</div>

@endsection
