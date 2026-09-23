@extends('layouts.app')

@section('title', $recurringTransaction->description . ' – Wiederkehrend – FinanzView')
@section('eyebrow', 'Wiederkehrend')
@section('page_title', $recurringTransaction->description)

@php
    $item = $recurringTransaction;
    $isIncome = $item->type === 'income';

    $frequencyLabels = [
        'weekly' => 'Wöchentlich',
        'monthly' => 'Monatlich',
        'quarterly' => 'Vierteljährlich',
        'yearly' => 'Jährlich',
    ];

    $perYear = ['weekly' => 52, 'monthly' => 12, 'quarterly' => 4, 'yearly' => 1][$item->frequency] ?? 12;
    $yearlyAmount = (float) $item->amount * $perYear;

    $relative = function ($date) {
        if ($date->isToday()) {
            return 'Heute';
        }

        if ($date->isTomorrow()) {
            return 'Morgen';
        }

        if ($date->isPast()) {
            return 'Überfällig – wird beim nächsten Lauf gebucht';
        }

        $days = (int) abs(now()->startOfDay()->diffInDays($date->copy()->startOfDay()));

        return 'in ' . $days . ' Tagen';
    };
@endphp

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-flash />


    {{-- KOPF --}}

    <div class="fv-card p-6 flex flex-col items-center text-center {{ $item->is_active ? '' : 'opacity-80' }}">
        <x-emoji-tile :emoji="$item->category?->icon" fallback="repeat" size="lg" />

        <h2 class="mt-4 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $item->description }}</h2>

        <p class="mt-1 text-4xl font-semibold tracking-tight tabular-nums {{ $isIncome ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white' }}">
            {{ $isIncome ? '+' : '−' }}{{ number_format((float) $item->amount, 2, ',', '.') }} €
        </p>

        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            {{ $frequencyLabels[$item->frequency] ?? $item->frequency }}
            · {{ number_format($yearlyAmount, 2, ',', '.') }} € im Jahr
        </p>

        @unless ($item->is_active)
            <span class="mt-3 rounded-full bg-slate-100 dark:bg-white/10 px-3 py-1 text-xs font-medium text-slate-600 dark:text-slate-300">Pausiert</span>
        @endunless

        <div class="mt-5 flex gap-2">
            <a href="{{ route('recurring-transactions.edit', $item) }}" class="fv-btn fv-btn-secondary text-sm py-2">
                <x-icon name="pencil" class="w-4 h-4" />
                Bearbeiten
            </a>

            <form method="POST" action="{{ route('recurring-transactions.destroy', $item) }}"
                onsubmit="return confirm('„{{ addslashes($item->description) }}“ löschen? Bereits erzeugte Buchungen bleiben erhalten.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="fv-btn text-sm py-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10">
                    <x-icon name="trash" class="w-4 h-4" />
                    Löschen
                </button>
            </form>
        </div>
    </div>


    {{-- DETAILS --}}

    <dl class="fv-card overflow-hidden divide-y divide-slate-100 dark:divide-white/5 text-sm">
        <div class="flex items-center justify-between gap-4 px-4 py-3">
            <dt class="text-slate-500 dark:text-slate-400">Konto</dt>
            <dd class="font-medium text-slate-900 dark:text-white">{{ $item->account?->name ?? '–' }}</dd>
        </div>
        <div class="flex items-center justify-between gap-4 px-4 py-3">
            <dt class="text-slate-500 dark:text-slate-400">Kategorie</dt>
            <dd class="font-medium text-slate-900 dark:text-white">{{ $item->category ? $item->category->icon . ' ' . $item->category->name : '–' }}</dd>
        </div>
        <div class="flex items-center justify-between gap-4 px-4 py-3">
            <dt class="text-slate-500 dark:text-slate-400">Endet</dt>
            <dd class="font-medium text-slate-900 dark:text-white">{{ $item->end_date?->format('d.m.Y') ?? 'Nie' }}</dd>
        </div>
    </dl>


    {{-- NÄCHSTE TERMINE --}}

    <section>
        <h3 class="px-1 pb-2 text-[13px] font-semibold text-slate-500 dark:text-slate-400">Nächste Termine</h3>

        @if (empty($upcomingDates))
            <div class="fv-card px-4 py-4 text-sm text-slate-500 dark:text-slate-400">
                {{ $item->is_active ? 'Keine weiteren Termine – das Enddatum ist erreicht.' : 'Pausiert – es wird nichts gebucht.' }}
            </div>
        @else
            <ul class="fv-card overflow-hidden divide-y divide-slate-100 dark:divide-white/5">
                @foreach ($upcomingDates as $date)
                    <li class="flex items-center gap-3 px-4 py-3">
                        <div class="w-11 shrink-0 text-center">
                            <p class="text-[11px] font-semibold uppercase text-red-500">{{ $date->translatedFormat('M') }}</p>
                            <p class="text-lg font-semibold leading-tight tabular-nums text-slate-900 dark:text-white">{{ $date->format('j') }}</p>
                        </div>

                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-slate-900 dark:text-white">{{ $date->translatedFormat('l, j. F Y') }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $relative($date) }}</p>
                        </div>

                        <p class="font-semibold tabular-nums {{ $isIncome ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white' }}">
                            {{ $isIncome ? '+' : '−' }}{{ number_format((float) $item->amount, 2, ',', '.') }} €
                        </p>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>


    {{-- BISHERIGE BUCHUNGEN --}}

    <section>
        <h3 class="px-1 pb-2 text-[13px] font-semibold text-slate-500 dark:text-slate-400">Bisher gebucht</h3>

        @if ($recentTransactions->isEmpty())
            <div class="fv-card px-4 py-4 text-sm text-slate-500 dark:text-slate-400">
                Noch keine Buchungen erzeugt. Die erste folgt am Fälligkeitstag.
            </div>
        @else
            <ul class="fv-card overflow-hidden divide-y divide-slate-100 dark:divide-white/5">
                @foreach ($recentTransactions as $transaction)
                    <li>
                        <a href="{{ route('transactions.edit', $transaction) }}" class="flex items-center justify-between gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-white/5 transition">
                            <span class="text-sm text-slate-700 dark:text-slate-200 tabular-nums">{{ $transaction->transaction_date?->format('d.m.Y') }}</span>
                            <span class="font-medium tabular-nums {{ $transaction->type === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white' }}">
                                {{ $transaction->type === 'income' ? '+' : '−' }}{{ number_format((float) $transaction->amount, 2, ',', '.') }} €
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

</div>

@endsection
