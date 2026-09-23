@extends('layouts.app')

@section('title', 'Administration – FinanzView')
@section('eyebrow', 'System')
@section('page_title', 'Administration')

@section('content')

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-page-header title="Administration" subtitle="Benutzer, Registrierung und Finanzanbieter.">
        <a href="{{ route('admin.providers.index') }}" class="fv-btn fv-btn-secondary text-sm py-2.5">
            <x-icon name="landmark" class="w-4 h-4" />
            Finanzanbieter
        </a>
    </x-page-header>

    <x-flash />


    {{-- KENNZAHLEN --}}

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <x-stat label="Benutzer">{{ $stats['total_users'] }}</x-stat>
        <x-stat label="Aktiv" tone="positive">{{ $stats['active_users'] }}</x-stat>
        <x-stat label="Deaktiviert" :tone="$stats['inactive_users'] > 0 ? 'negative' : 'neutral'">{{ $stats['inactive_users'] }}</x-stat>
        <x-stat label="Administratoren">{{ $stats['admin_users'] }}</x-stat>
    </div>


    {{-- REGISTRIERUNG --}}

    <section>
        <h3 class="px-1 pb-2 text-[13px] font-semibold text-slate-500 dark:text-slate-400">Registrierung</h3>

        <form method="POST" action="{{ route('admin.settings.registration') }}" class="fv-card flex items-center gap-4 p-4">
            @csrf
            @method('PATCH')

            <div class="flex-1 min-w-0">
                <p class="font-medium text-slate-900 dark:text-white">Neue Benutzer dürfen sich registrieren</p>
                <p class="text-[13px] text-slate-500 dark:text-slate-400">
                    {{ $registrationEnabled
                        ? 'Jeder, der FinanzView erreicht, kann ein Konto anlegen – auch per SSO.'
                        : 'Nur bestehende Benutzer können sich anmelden.' }}
                </p>
            </div>

            {{-- Schalter im iOS-Stil; ein Klick sendet das Formular --}}
            <button
                type="submit"
                role="switch"
                aria-checked="{{ $registrationEnabled ? 'true' : 'false' }}"
                aria-label="{{ $registrationEnabled ? 'Registrierung deaktivieren' : 'Registrierung aktivieren' }}"
                class="relative inline-flex h-[31px] w-[51px] shrink-0 items-center rounded-full transition {{ $registrationEnabled ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-slate-700' }}"
            >
                <span class="inline-block h-[27px] w-[27px] rounded-full bg-white shadow transition {{ $registrationEnabled ? 'translate-x-[22px]' : 'translate-x-[2px]' }}"></span>
            </button>
        </form>
    </section>


    {{-- BENUTZER --}}

    <section>
        <h3 class="px-1 pb-2 text-[13px] font-semibold text-slate-500 dark:text-slate-400">Benutzer</h3>

        <ul class="fv-card overflow-hidden divide-y divide-slate-100 dark:divide-white/5">
            @forelse ($users as $user)
                @php
                    $isSelf = $user->id === auth()->id();
                    $initials = collect(preg_split('/\s+/', trim($user->name)))
                        ->filter()->take(2)
                        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                        ->join('');
                @endphp

                <li class="flex flex-wrap items-center gap-3 px-4 py-3.5 {{ $user->is_active ? '' : 'opacity-60' }}">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-semibold {{ $user->is_admin ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 dark:bg-white/5 dark:text-slate-300' }}">
                        {{ $initials ?: '?' }}
                    </div>

                    <div class="flex-1 min-w-[12rem]">
                        <p class="font-medium text-slate-900 dark:text-white truncate">
                            {{ $user->name }}
                            @if ($isSelf)
                                <span class="ml-1 text-xs font-normal text-slate-400">(du)</span>
                            @endif
                        </p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                            {{ $user->email }}
                            · {{ $user->last_login_at ? 'zuletzt ' . $user->last_login_at->format('d.m.Y H:i') : 'noch nie angemeldet' }}
                        </p>

                        <div class="mt-1.5 flex flex-wrap gap-1.5">
                            @if ($user->is_admin)
                                <span class="rounded-full bg-emerald-50 dark:bg-emerald-500/10 px-2 py-0.5 text-[11px] font-medium text-emerald-700 dark:text-emerald-300">Admin</span>
                            @endif
                            @unless ($user->is_active)
                                <span class="rounded-full bg-red-50 dark:bg-red-500/10 px-2 py-0.5 text-[11px] font-medium text-red-700 dark:text-red-300">Deaktiviert</span>
                            @endunless
                            @if ($user->hasEnabledTwoFactorAuthentication())
                                <span class="rounded-full bg-slate-100 dark:bg-white/10 px-2 py-0.5 text-[11px] font-medium text-slate-600 dark:text-slate-300">2FA</span>
                            @endif
                            @if ($user->oidc_sub)
                                <span class="rounded-full bg-slate-100 dark:bg-white/10 px-2 py-0.5 text-[11px] font-medium text-slate-600 dark:text-slate-300">SSO</span>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-1">
                        <a href="{{ route('admin.users.edit', $user) }}" class="w-9 h-9 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-white/10 transition" title="Bearbeiten" aria-label="{{ $user->name }} bearbeiten">
                            <x-icon name="pencil" class="w-4 h-4" />
                        </a>

                        @unless ($isSelf)
                            <form method="POST" action="{{ route('admin.users.toggle-active', $user) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="w-9 h-9 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-white/10 transition"
                                    title="{{ $user->is_active ? 'Deaktivieren' : 'Aktivieren' }}" aria-label="{{ $user->is_active ? 'Benutzer deaktivieren' : 'Benutzer aktivieren' }}">
                                    <x-icon :name="$user->is_active ? 'pause' : 'check-circle'" class="w-4 h-4" />
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.users.toggle-admin', $user) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="w-9 h-9 rounded-lg flex items-center justify-center transition {{ $user->is_admin ? 'text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-500/10' : 'text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-white/10' }}"
                                    title="{{ $user->is_admin ? 'Adminrechte entfernen' : 'Zum Admin machen' }}" aria-label="{{ $user->is_admin ? 'Administratorrechte entfernen' : 'Administratorrechte vergeben' }}">
                                    <x-icon name="shield" class="w-4 h-4" />
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                onsubmit="return confirm('Möchtest du {{ addslashes($user->name) }} wirklich löschen? Alle Finanzdaten dieses Benutzers werden ebenfalls gelöscht.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-9 h-9 rounded-lg flex items-center justify-center text-slate-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10 transition" title="Löschen" aria-label="Benutzer löschen">
                                    <x-icon name="trash" class="w-4 h-4" />
                                </button>
                            </form>
                        @endunless
                    </div>
                </li>
            @empty
                <li>
                    <x-empty-state icon="user" title="Keine Benutzer" />
                </li>
            @endforelse
        </ul>
    </section>

</div>

@endsection
