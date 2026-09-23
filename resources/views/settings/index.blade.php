@extends('layouts.app')

@section('title', 'Einstellungen – FinanzView')
@section('eyebrow', 'System')
@section('page_title', 'Einstellungen')

@php
    $user = auth()->user();

    $initials = collect(preg_split('/\s+/', trim($user->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->join('');

    /*
     * Gruppen im Stil der iOS-Einstellungen:
     * [Route, Icon, Icon-Farbe, Titel, Beschreibung]
     */
    $groups = [
        'Konto' => [
            ['settings.profile', 'user', 'bg-sky-500', 'Profil', 'Name und E-Mail-Adresse'],
            ['settings.security', 'lock', 'bg-slate-500', 'Sicherheit', $user->hasEnabledTwoFactorAuthentication() ? 'Passwort · Zwei-Faktor aktiv' : 'Passwort und Zwei-Faktor-Authentifizierung'],
        ],
        'App' => [
            ['settings.appearance', 'sun', 'bg-violet-500', 'Erscheinungsbild', 'Hell, Dunkel oder automatisch'],
            ['settings.dashboard', 'layout', 'bg-orange-500', 'Dashboard', 'Widgets, Reihenfolge und Darstellung'],
            ['settings.financial', 'wallet', 'bg-emerald-600', 'Finanzen', 'Währung, Standardkonto und Grundeinstellungen'],
        ],
        'Daten' => [
            ['settings.data-export', 'download', 'bg-blue-500', 'Daten & Export', 'Exportieren, Sichern und Wiederherstellen'],
        ],
    ];

    if ($user->isAdmin()) {
        $groups['Verwaltung'] = [
            ['admin.index', 'shield', 'bg-red-500', 'Administration', 'Benutzer, Registrierung und Anbieter'],
        ];
    }
@endphp

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-page-header title="Einstellungen" />


    {{-- PROFILKARTE --}}

    <a href="{{ route('settings.profile') }}" class="fv-card flex items-center gap-4 p-4 hover:bg-slate-50 dark:hover:bg-white/5 transition">
        <div class="w-14 h-14 rounded-full bg-emerald-600 text-white flex items-center justify-center text-lg font-semibold">
            {{ $initials ?: '?' }}
        </div>

        <div class="flex-1 min-w-0">
            <p class="text-[17px] font-semibold text-slate-900 dark:text-white truncate">{{ $user->name }}</p>
            <p class="text-sm text-slate-500 dark:text-slate-400 truncate">{{ $user->email }}</p>
        </div>

        <x-icon name="chevron-right" class="w-5 h-5 text-slate-300 dark:text-slate-600" />
    </a>


    {{-- GRUPPEN --}}

    @foreach ($groups as $group => $items)
        <section>
            <h3 class="px-4 pb-1.5 text-[13px] font-medium text-slate-500 dark:text-slate-400">{{ $group }}</h3>

            <ul class="fv-card overflow-hidden">
                @foreach ($items as [$route, $icon, $color, $title, $description])
                    <li>
                        <a href="{{ route($route) }}" class="group flex items-center gap-3.5 pl-4 hover:bg-slate-50 dark:hover:bg-white/5 transition">
                            <span class="w-8 h-8 rounded-[9px] {{ $color }} text-white flex items-center justify-center shrink-0">
                                <x-icon :name="$icon" class="w-[18px] h-[18px]" />
                            </span>

                            <div class="flex-1 min-w-0 flex items-center gap-3 py-3 pr-4 {{ ! $loop->last ? 'border-b border-slate-100 dark:border-white/5' : '' }}">
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-slate-900 dark:text-white">{{ $title }}</p>
                                    <p class="text-[13px] text-slate-500 dark:text-slate-400 truncate">{{ $description }}</p>
                                </div>

                                <x-icon name="chevron-right" class="w-4 h-4 text-slate-300 dark:text-slate-600" />
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endforeach


    {{-- ABMELDEN --}}

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="fv-card w-full py-3.5 text-center font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition">
            Abmelden
        </button>
    </form>

</div>

@endsection
