@extends('layouts.app')

@section('title', $user->name . ' – Administration – FinanzView')
@section('eyebrow', 'Administration')
@section('page_title', 'Benutzer bearbeiten')

@php
    $isSelf = $user->id === auth()->id();

    $initials = collect(preg_split('/\s+/', trim($user->name)))
        ->filter()->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->join('');

    $switch = 'relative inline-flex h-[31px] w-[51px] shrink-0 rounded-full bg-slate-200 dark:bg-slate-700 transition peer-checked:bg-emerald-500 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500 peer-disabled:opacity-50 after:absolute after:top-[2px] after:left-[2px] after:h-[27px] after:w-[27px] after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:translate-x-[20px]';
@endphp

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-back-link :href="route('admin.index')" label="Administration" />

    <div class="flex items-center gap-4">
        <div class="w-14 h-14 rounded-full flex items-center justify-center text-lg font-semibold {{ $user->is_admin ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-600 dark:bg-white/10 dark:text-slate-300' }}">
            {{ $initials ?: '?' }}
        </div>
        <div class="min-w-0">
            <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white truncate">{{ $user->name }}</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 truncate">
                Registriert am {{ $user->created_at?->format('d.m.Y') }}
                · {{ $user->last_login_at ? 'zuletzt ' . $user->last_login_at->format('d.m.Y H:i') : 'noch nie angemeldet' }}
            </p>
        </div>
    </div>

    <x-flash />

    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-5">
        @csrf
        @method('PATCH')

        <div class="fv-card p-5 sm:p-6 space-y-4">
            <x-field label="Name" for="name" error="name">
                <input id="name" name="name" type="text" required maxlength="255" value="{{ old('name', $user->name) }}" class="fv-input">
            </x-field>

            <x-field label="E-Mail-Adresse" for="email" error="email">
                <input id="email" name="email" type="email" required maxlength="255" value="{{ old('email', $user->email) }}" class="fv-input">
            </x-field>
        </div>

        <div class="fv-card divide-y divide-slate-100 dark:divide-white/5">
            <label class="flex items-center justify-between gap-4 px-5 py-3.5 {{ $isSelf ? '' : 'cursor-pointer' }}">
                <span>
                    <span class="block font-medium text-slate-900 dark:text-white">Aktiv</span>
                    <span class="block text-[13px] text-slate-500 dark:text-slate-400">
                        {{ $isSelf ? 'Du kannst dich nicht selbst deaktivieren.' : 'Deaktivierte Benutzer können sich nicht anmelden.' }}
                    </span>
                </span>
                <input type="hidden" name="is_active" value="{{ $isSelf ? '1' : '0' }}">
                <input type="checkbox" name="is_active" value="1" class="sr-only peer" @checked(old('is_active', $user->is_active)) @disabled($isSelf)>
                <span aria-hidden="true" class="{{ $switch }}"></span>
            </label>

            <label class="flex items-center justify-between gap-4 px-5 py-3.5 {{ $isSelf ? '' : 'cursor-pointer' }}">
                <span>
                    <span class="block font-medium text-slate-900 dark:text-white">Administrator</span>
                    <span class="block text-[13px] text-slate-500 dark:text-slate-400">
                        {{ $isSelf ? 'Du kannst dir die Adminrechte nicht selbst entziehen.' : 'Darf Benutzer, Registrierung und Anbieter verwalten.' }}
                    </span>
                </span>
                <input type="hidden" name="is_admin" value="{{ $isSelf ? '1' : '0' }}">
                <input type="checkbox" name="is_admin" value="1" class="sr-only peer" @checked(old('is_admin', $user->is_admin)) @disabled($isSelf)>
                <span aria-hidden="true" class="{{ $switch }}"></span>
            </label>
        </div>

        <section>
            <h3 class="px-1 pb-2 text-[13px] font-semibold text-slate-500 dark:text-slate-400">Neues Passwort setzen</h3>

            <div class="fv-card p-5 sm:p-6 space-y-4">
                <p class="text-[13px] text-slate-500 dark:text-slate-400">Leer lassen, um das Passwort nicht zu ändern.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-field label="Neues Passwort" for="password" error="password">
                        <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" class="fv-input">
                    </x-field>

                    <x-field label="Wiederholen" for="password_confirmation">
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="fv-input">
                    </x-field>
                </div>
            </div>
        </section>

        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
            <a href="{{ route('admin.index') }}" class="fv-btn fv-btn-secondary">Abbrechen</a>
            <button type="submit" class="fv-btn fv-btn-primary">Speichern</button>
        </div>
    </form>

</div>

@endsection
