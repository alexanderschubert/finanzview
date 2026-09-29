@extends('layouts.app')

@section('title', 'Umbuchungen erkennen – FinanzView')
@section('eyebrow', 'Buchungen')
@section('page_title', 'Umbuchungen')

@php
    $money = fn ($amount) => number_format((float) $amount, 2, ',', '.') . ' €';
@endphp

@section('content')

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-back-link :href="route('transactions.index')" label="Buchungen" />

    <x-page-header title="Mögliche Umbuchungen" subtitle="Abbuchung in einem Konto und gleich hohe Gutschrift in einem anderen – z. B. die Kreditkarten-Abrechnung vom Girokonto.">
        @if ($pairs->count() > 1)
            <form method="POST" action="{{ route('transactions.transfers.merge-all') }}"
                onsubmit="return confirm('Alle {{ $pairs->count() }} Paare als Umbuchung zusammenfassen?')">
                @csrf
                <button type="submit" class="fv-btn fv-btn-primary text-sm py-2.5">Alle zusammenfassen</button>
            </form>
        @endif
    </x-page-header>

    <x-flash />

    @if ($pairs->isEmpty())
        <div class="fv-card">
            <x-empty-state icon="check-circle" title="Keine möglichen Umbuchungen">
                Alles sauber – es gibt keine Buchungspaare, die doppelt zählen.
            </x-empty-state>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($pairs as $pair)
                @php [$expense, $income] = [$pair['expense'], $pair['income']]; @endphp

                <div class="fv-card p-4 sm:p-5 space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="flex-1 min-w-0 rounded-xl bg-slate-50 dark:bg-white/5 px-3 py-2.5">
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $expense->transaction_date->format('d.m.Y') }} · {{ $expense->account->name }}</p>
                            <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ $expense->merchant ?: $expense->description }}</p>
                            <p class="text-sm font-semibold tabular-nums text-slate-900 dark:text-white">−{{ $money($expense->amount) }}</p>
                        </div>

                        <x-icon name="chevron-right" class="w-5 h-5 shrink-0 text-slate-400" />

                        <div class="flex-1 min-w-0 rounded-xl bg-slate-50 dark:bg-white/5 px-3 py-2.5">
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $income->transaction_date->format('d.m.Y') }} · {{ $income->account->name }}</p>
                            <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ $income->merchant ?: $income->description }}</p>
                            <p class="text-sm font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">+{{ $money($income->amount) }}</p>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row sm:items-center gap-2">
                        <p class="text-[13px] text-slate-500 dark:text-slate-400 sm:mr-auto">
                            {{ $pair['days'] === 0 ? 'Am selben Tag' : ($pair['days'] === 1 ? '1 Tag Abstand' : $pair['days'] . ' Tage Abstand') }}
                        </p>

                        <form method="POST" action="{{ route('transactions.transfers.dismiss') }}">
                            @csrf
                            <input type="hidden" name="expense_id" value="{{ $expense->id }}">
                            <input type="hidden" name="income_id" value="{{ $income->id }}">
                            <button type="submit" class="fv-btn fv-btn-secondary text-sm py-2 w-full sm:w-auto">Keine Umbuchung</button>
                        </form>

                        <form method="POST" action="{{ route('transactions.transfers.merge') }}">
                            @csrf
                            <input type="hidden" name="expense_id" value="{{ $expense->id }}">
                            <input type="hidden" name="income_id" value="{{ $income->id }}">
                            <button type="submit" class="fv-btn fv-btn-primary text-sm py-2 w-full sm:w-auto">Als Umbuchung zusammenfassen</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <p class="px-1 text-[13px] text-slate-500 dark:text-slate-400">
            Beim Zusammenfassen wird die Abbuchung zur Umbuchung auf das Zielkonto und die Gutschrift entfernt.
            Kontostände bleiben gleich, Einnahmen und Ausgaben zählen nicht mehr doppelt. Ein erneuter Import legt die Gutschrift nicht wieder an.
        </p>
    @endif

</div>

@endsection
