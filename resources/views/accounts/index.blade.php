@extends('layouts.app')

@section('title', 'Konten – FinanzView')
@section('eyebrow', 'Finanzverwaltung')
@section('page_title', 'Konten')

@php
    $typeLabels = [
        'checking' => 'Girokonto',
        'savings' => 'Sparkonto',
        'credit_card' => 'Kreditkarte',
        'paypal' => 'PayPal',
        'cash' => 'Bargeld',
        'investment' => 'Investment',
        'loan' => 'Kredit',
        'other' => 'Sonstiges',
    ];

    $groups = [
        'Aktive Konten' => $accounts->where('is_active', true),
        'Inaktive Konten' => $accounts->where('is_active', false),
    ];

    // Kontostand kostet je Konto eine Abfrage – daher nur einmal berechnen.
    $balances = $accounts->mapWithKeys(fn ($account) => [$account->id => $account->current_balance]);

    $totalBalance = $accounts
        ->where('include_in_total', true)
        ->sum(fn ($account) => $balances[$account->id]);
@endphp

@section('content')

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-page-header title="Konten" subtitle="Bankkonten, Bargeld und weitere Vermögenswerte.">
        <a href="{{ route('accounts.create') }}" class="fv-btn fv-btn-primary text-sm py-2.5">
            <x-icon name="plus" class="w-4 h-4" />
            Neues Konto
        </a>
    </x-page-header>

    <x-flash />

    @if ($accounts->isEmpty())

        <div class="fv-card">
            <x-empty-state icon="landmark" title="Noch keine Konten" :href="route('accounts.create')" action="Erstes Konto anlegen">
                Lege dein erstes Konto an, damit FinanzView dein Vermögen berechnen kann.
            </x-empty-state>
        </div>

    @else

        {{-- GESAMTVERMÖGEN --}}

        <div class="relative overflow-hidden rounded-3xl p-5 sm:p-6 text-white shadow-lg shadow-emerald-900/20 bg-linear-to-br from-emerald-500 to-emerald-800">
            <div aria-hidden="true" class="absolute -right-10 -top-12 w-44 h-44 rounded-full border-[20px] border-white/10"></div>

            <div class="relative">
                <p class="text-sm font-medium text-white/80">Gesamtvermögen</p>
                <p class="mt-1 text-[34px] leading-tight font-semibold tracking-tight tabular-nums">
                    {{ number_format($totalBalance, 2, ',', '.') }} €
                </p>
                <p class="mt-1 text-xs text-white/70">
                    {{ $accounts->where('include_in_total', true)->count() }} von {{ $accounts->count() }} Konten eingerechnet
                </p>
            </div>
        </div>


        {{-- LISTEN --}}

        @foreach ($groups as $group => $groupAccounts)
            @continue($groupAccounts->isEmpty())

            <section>
                <h3 class="px-1 pb-2 text-[13px] font-semibold text-slate-500 dark:text-slate-400">{{ $group }}</h3>

                <ul class="fv-card overflow-hidden divide-y divide-slate-100 dark:divide-white/5">
                    @foreach ($groupAccounts as $account)
                        <li>
                            <a href="{{ route('accounts.edit', $account) }}" class="flex items-center gap-3 px-4 py-3.5 hover:bg-slate-50 dark:hover:bg-white/5 transition {{ $account->is_active ? '' : 'opacity-60' }}">
                                <x-financial-provider :provider="$account->provider" :fallback-icon="$account->icon ?: '🏦'" size="sm" />

                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-slate-900 dark:text-white truncate">{{ $account->name }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                        {{ $account->institution ?: ($typeLabels[$account->type] ?? 'Sonstiges') }}
                                        @unless ($account->include_in_total)
                                            · nicht im Gesamtvermögen
                                        @endunless
                                    </p>
                                </div>

                                <p class="font-semibold tabular-nums whitespace-nowrap {{ $balances[$account->id] < 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white' }}">
                                    {{ number_format($balances[$account->id], 2, ',', '.') }} {{ $account->currency === 'EUR' ? '€' : $account->currency }}
                                </p>

                                <x-icon name="chevron-right" class="w-4 h-4 text-slate-300 dark:text-slate-600" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach

    @endif

</div>

@endsection
