@extends('layouts.app')

@section('title', 'Finanzanbieter – Administration – FinanzView')
@section('eyebrow', 'Administration')
@section('page_title', 'Finanzanbieter')

@php
    $types = [
        'bank' => 'Bank',
        'payment' => 'Zahlungsdienst',
        'card' => 'Kartenanbieter',
        'lender' => 'Kreditgeber',
        'other' => 'Sonstiges',
    ];
@endphp

@section('content')

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-back-link :href="route('admin.index')" label="Administration" />

    <x-page-header title="Finanzanbieter" subtitle="Banken und Dienste mit Logo und Farbe für Konten, Karten und Kredite.">
        <a href="{{ route('admin.providers.create') }}" class="fv-btn fv-btn-primary text-sm py-2.5">
            <x-icon name="plus" class="w-4 h-4" />
            Neuer Anbieter
        </a>
    </x-page-header>

    <x-flash />

    @if ($providers->isEmpty())
        <div class="fv-card">
            <x-empty-state icon="landmark" title="Noch keine Anbieter" :href="route('admin.providers.create')" action="Anbieter anlegen" />
        </div>
    @else
        <ul class="fv-card overflow-hidden divide-y divide-slate-100 dark:divide-white/5">
            @foreach ($providers as $provider)
                @php
                    $usage = $provider->accounts_count + $provider->credit_cards_count + $provider->loans_count;
                @endphp

                <li class="flex items-center gap-3 px-4 py-3 {{ $provider->is_active ? '' : 'opacity-60' }}">
                    <x-financial-provider :provider="$provider" size="sm" />

                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-slate-900 dark:text-white truncate">{{ $provider->name }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                            {{ $types[$provider->type] ?? 'Sonstiges' }}
                            · {{ $usage === 0 ? 'nicht verwendet' : $usage . ' × verwendet' }}
                            @unless ($provider->is_active) · inaktiv @endunless
                        </p>
                    </div>

                    <div class="flex items-center gap-1">
                        <form method="POST" action="{{ route('admin.providers.toggle-active', $provider) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="w-9 h-9 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-white/10 transition"
                                title="{{ $provider->is_active ? 'Deaktivieren' : 'Aktivieren' }}" aria-label="{{ $provider->name }} {{ $provider->is_active ? 'deaktivieren' : 'aktivieren' }}">
                                <x-icon :name="$provider->is_active ? 'pause' : 'check-circle'" class="w-4 h-4" />
                            </button>
                        </form>

                        <a href="{{ route('admin.providers.edit', $provider) }}" class="w-9 h-9 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-white/10 transition"
                            title="Bearbeiten" aria-label="{{ $provider->name }} bearbeiten">
                            <x-icon name="pencil" class="w-4 h-4" />
                        </a>

                        @if ($usage === 0)
                            <form method="POST" action="{{ route('admin.providers.destroy', $provider) }}"
                                onsubmit="return confirm('Anbieter „{{ addslashes($provider->name) }}“ löschen?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-9 h-9 rounded-lg flex items-center justify-center text-slate-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10 transition"
                                    title="Löschen" aria-label="{{ $provider->name }} löschen">
                                    <x-icon name="trash" class="w-4 h-4" />
                                </button>
                            </form>
                        @else
                            <span class="w-9 h-9" aria-hidden="true"></span>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>

        <p class="px-1 text-[13px] text-slate-500 dark:text-slate-400">Verwendete Anbieter können nicht gelöscht, aber deaktiviert werden.</p>
    @endif

</div>

@endsection
