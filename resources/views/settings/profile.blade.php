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

    <x-page-header :title="$user->name" :subtitle="$user->email">
        <x-slot:leading>
            @if ($user->profile_photo_url)
                <img src="{{ $user->profile_photo_url }}" alt="" class="w-14 h-14 rounded-full object-cover" data-avatar>
            @else
                <div class="w-14 h-14 rounded-full bg-emerald-600 text-white flex items-center justify-center text-lg font-semibold" data-avatar>
                    {{ $initials ?: '?' }}
                </div>
            @endif
        </x-slot:leading>
    </x-page-header>

    <x-flash />

    <section class="fv-card p-5 sm:p-6">
        <h3 class="font-semibold text-slate-900 dark:text-white">Profilbild</h3>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Erscheint oben rechts. JPG, PNG oder WebP, bis 3 MB.</p>

        <div class="mt-4 flex flex-wrap items-center gap-3">
            <form method="POST" action="{{ route('settings.profile.photo.update') }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3">
                @csrf
                <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required aria-label="Profilbild auswählen"
                    class="block text-sm text-slate-600 dark:text-slate-300 file:mr-3 file:rounded-full file:border-0 file:bg-emerald-600 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-emerald-700">
                <button type="submit" class="fv-btn fv-btn-secondary text-sm py-2">Hochladen</button>
            </form>

            @if ($user->profile_photo_path)
                <form method="POST" action="{{ route('settings.profile.photo.destroy') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm text-red-600 dark:text-red-400 hover:underline">Entfernen</button>
                </form>
            @endif
        </div>

        @error('photo')
            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </section>

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
