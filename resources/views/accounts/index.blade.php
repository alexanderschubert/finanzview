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

    // Kartenfarbe je Kontotyp, falls weder Konto noch Anbieter eine Farbe haben.
    $typeColors = [
        'checking' => '#0b7155',
        'savings' => '#1f5fa8',
        'credit_card' => '#3f3f45',
        'paypal' => '#123a86',
        'cash' => '#8a6516',
        'investment' => '#5b3fa8',
        'loan' => '#9f2d2d',
        'other' => '#3f3f45',
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

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

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

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    @foreach ($groupAccounts as $account)
                        @php
                            $iban = preg_replace('/\s+/', '', (string) $account->iban);
                            $number = $iban !== ''
                                ? '•••• ' . substr($iban, -4)
                                : ($account->account_number ? '•••• ' . substr((string) $account->account_number, -4) : null);
                        @endphp

                        <a href="{{ route('accounts.edit', $account) }}" class="group block {{ $account->is_active ? '' : 'opacity-60' }}">
                            <x-wallet-card
                                :color="$account->color"
                                :fallback-color="$account->provider?->color ?: ($typeColors[$account->type] ?? '#3f3f45')"
                                :title="$account->name"
                                :subtitle="$account->institution ?: ($typeLabels[$account->type] ?? 'Sonstiges')"
                                amount-label="Kontostand"
                                :amount="number_format($balances[$account->id], 2, ',', '.') . ' ' . ($account->currency === 'EUR' ? '€' : $account->currency)"
                                :number="$number"
                                :badge="$account->is_active ? null : 'Inaktiv'"
                                :provider="$account->provider"
                                class="transition group-hover:-translate-y-0.5 group-hover:shadow-xl"
                            />

                            <div class="mt-2 px-1 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                                <span>{{ $typeLabels[$account->type] ?? 'Sonstiges' }}</span>
                                @if ($account->include_in_total)
                                    <span class="inline-flex items-center gap-1"><x-icon name="check-circle" class="w-3.5 h-3.5 text-emerald-500" /> im Gesamtvermögen</span>
                                @else
                                    <span>nicht im Gesamtvermögen</span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endforeach

    @endif

</div>

@endsection
