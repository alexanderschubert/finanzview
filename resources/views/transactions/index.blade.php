@extends('layouts.app')

@section('title', 'Buchungen – FinanzView')
@section('eyebrow', 'Finanzverwaltung')
@section('page_title', 'Buchungen')

@php
    $filtersActive = request()->filled('search')
        || request()->filled('month')
        || request()->filled('type')
        || request()->filled('account_id')
        || request()->filled('category_id');

    /*
     * Buchungen der aktuellen Seite nach Tag gruppieren.
     */
    $transactionsByDay = $transactions->getCollection()->groupBy(
        fn ($transaction) => $transaction->transaction_date?->format('Y-m-d') ?? 'ohne-datum'
    );

    $dayLabel = function (string $day) {
        if ($day === 'ohne-datum') {
            return 'Ohne Datum';
        }

        $date = \Carbon\Carbon::createFromFormat('Y-m-d', $day);

        if ($date->isToday()) {
            return 'Heute';
        }

        if ($date->isYesterday()) {
            return 'Gestern';
        }

        return $date->translatedFormat($date->isCurrentYear() ? 'l, j. F' : 'l, j. F Y');
    };
@endphp

@section('content')

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-5">

    <x-page-header title="Buchungen" subtitle="Deine Einnahmen, Ausgaben und Umbuchungen.">
        <a href="{{ route('transactions.create') }}" class="fv-btn fv-btn-primary text-sm py-2.5">
            <x-icon name="plus" class="w-4 h-4" />
            Neue Buchung
        </a>
    </x-page-header>


    {{-- ========================================================= --}}
    {{-- MELDUNGEN --}}
    {{-- ========================================================= --}}

    @if (session('success'))
        <div class="flex gap-3 rounded-2xl bg-emerald-50 dark:bg-emerald-500/10 p-4 text-sm text-emerald-800 dark:text-emerald-300" role="status">
            <x-icon name="check-circle" class="w-5 h-5" />
            <p>{{ session('success') }}</p>
        </div>
    @endif

    @if (session('error'))
        <div class="flex gap-3 rounded-2xl bg-red-50 dark:bg-red-500/10 p-4 text-sm text-red-700 dark:text-red-300" role="alert">
            <x-icon name="alert" class="w-5 h-5" />
            <p>{{ session('error') }}</p>
        </div>
    @endif


    {{-- ========================================================= --}}
    {{-- FILTER --}}
    {{-- ========================================================= --}}

    <form method="GET" action="{{ route('transactions.index') }}" class="fv-card p-4 sm:p-5 space-y-3">

        <div class="flex gap-2">
            <div class="relative flex-1">
                <x-icon name="search" class="w-[18px] h-[18px] absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                <input
                    type="search"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Beschreibung oder Händler suchen"
                    aria-label="Buchungen durchsuchen"
                    class="fv-input pl-10"
                >
            </div>

            <button type="submit" class="fv-btn fv-btn-primary text-sm">Suchen</button>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
            <input
                type="month"
                name="month"
                value="{{ request('month') }}"
                aria-label="Monat"
                onchange="this.form.submit()"
                class="fv-input text-sm py-2.5"
            >

            <select name="type" aria-label="Art" onchange="this.form.submit()" class="fv-input text-sm py-2.5">
                <option value="">Alle Arten</option>
                <option value="expense" @selected(request('type') === 'expense')>Ausgaben</option>
                <option value="income" @selected(request('type') === 'income')>Einnahmen</option>
                <option value="transfer" @selected(request('type') === 'transfer')>Umbuchungen</option>
            </select>

            <select name="account_id" aria-label="Konto" onchange="this.form.submit()" class="fv-input text-sm py-2.5">
                <option value="">Alle Konten</option>
                @foreach ($accounts as $account)
                    <option value="{{ $account->id }}" @selected((string) request('account_id') === (string) $account->id)>
                        {{ $account->name }}
                    </option>
                @endforeach
            </select>

            <select name="category_id" aria-label="Kategorie" onchange="this.form.submit()" class="fv-input text-sm py-2.5">
                <option value="">Alle Kategorien</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>
                        {{ $category->icon }} {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>

        @if ($filtersActive)
            <div class="flex items-center justify-between text-sm">
                <p class="text-slate-500 dark:text-slate-400">
                    {{ $transactions->total() }} {{ $transactions->total() === 1 ? 'Buchung' : 'Buchungen' }} gefunden
                </p>
                <a href="{{ route('transactions.index') }}" class="fv-link inline-flex items-center gap-1">
                    <x-icon name="x" class="w-4 h-4" />
                    Filter zurücksetzen
                </a>
            </div>
        @endif

    </form>


    {{-- ========================================================= --}}
    {{-- KENNZAHLEN --}}
    {{-- ========================================================= --}}

    <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
        <x-stat label="Einnahmen" icon="trending-up" tone="positive" :hint="$filtersActive ? 'Gefilterte Buchungen' : 'Alle Buchungen'">
            {{ $totalIncome > 0 ? '+' : '' }}{{ number_format($totalIncome, 2, ',', '.') }} €
        </x-stat>

        <x-stat label="Ausgaben" icon="trending-down" tone="negative" :hint="$filtersActive ? 'Gefilterte Buchungen' : 'Alle Buchungen'">
            {{ $totalExpense > 0 ? '−' : '' }}{{ number_format($totalExpense, 2, ',', '.') }} €
        </x-stat>

        <x-stat label="Saldo" icon="wallet" :tone="$totalIncome - $totalExpense < 0 ? 'negative' : 'neutral'" hint="Ohne Umbuchungen" class="col-span-2 lg:col-span-1">
            {{ $totalIncome - $totalExpense > 0 ? '+' : ($totalIncome - $totalExpense < 0 ? '−' : '') }}{{ number_format(abs($totalIncome - $totalExpense), 2, ',', '.') }} €
        </x-stat>
    </div>


    {{-- ========================================================= --}}
    {{-- BUCHUNGSLISTE --}}
    {{-- ========================================================= --}}

    @if ($transactions->isEmpty())

        <div class="fv-card">
            @if ($filtersActive)
                <x-empty-state icon="search" title="Keine Buchungen gefunden">
                    Für diese Filter gibt es keine Buchungen.
                </x-empty-state>
            @else
                <x-empty-state icon="arrows" title="Noch keine Buchungen" :href="route('transactions.create')" action="Erste Buchung erfassen">
                    Erfasse deine Einnahmen und Ausgaben, um den Überblick zu behalten.
                </x-empty-state>
            @endif
        </div>

    @else

        <div class="space-y-5">
            @foreach ($transactionsByDay as $day => $dayTransactions)
                <section>
                    <h3 class="px-1 pb-2 text-[13px] font-semibold text-slate-500 dark:text-slate-400">
                        {{ $dayLabel($day) }}
                    </h3>

                    <ul class="fv-card overflow-hidden divide-y divide-slate-100 dark:divide-white/5">
                        @foreach ($dayTransactions as $transaction)
                            @php
                                $isTransfer = $transaction->type === 'transfer';
                                $isIncome = $transaction->type === 'income';

                                $details = collect([
                                    $isTransfer ? null : $transaction->category?->name,
                                    $transaction->merchant,
                                    $isTransfer
                                        ? ($transaction->account?->name ?: 'Gelöschtes Konto') . ' → ' . ($transaction->transferAccount?->name ?: 'Gelöschtes Konto')
                                        : $transaction->account?->name,
                                ])->filter()->implode(' · ');
                            @endphp

                            <li>
                                <a
                                    href="{{ route('transactions.edit', $transaction) }}"
                                    class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-white/5 transition"
                                >
                                    @if ($isTransfer)
                                        <x-emoji-tile fallback="arrows" />
                                    @else
                                        <x-emoji-tile :emoji="$transaction->category?->icon" fallback="tag" />
                                    @endif

                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-1.5 min-w-0">
                                            <p class="font-medium text-slate-900 dark:text-white truncate">
                                                {{ $transaction->description }}
                                            </p>

                                            @if ($transaction->recurring_transaction_id)
                                                <x-icon name="repeat" class="w-3.5 h-3.5 text-slate-400" title="Wiederkehrend" />
                                                <span class="sr-only">Wiederkehrend</span>
                                            @endif
                                        </div>

                                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                            {{ $details ?: ($isTransfer ? 'Umbuchung' : 'Ohne Kategorie') }}
                                        </p>
                                    </div>

                                    <div class="text-right shrink-0">
                                        <p class="font-semibold tabular-nums whitespace-nowrap {{ $isTransfer ? 'text-slate-500 dark:text-slate-400' : ($isIncome ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white') }}">
                                            {{ $isTransfer ? '' : ($isIncome ? '+' : '−') }}{{ number_format($transaction->amount, 2, ',', '.') }} €
                                        </p>

                                        @if ($transaction->is_pending)
                                            <span class="text-[11px] font-medium text-amber-600 dark:text-amber-400">Ausstehend</span>
                                        @endif
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>

        @if ($transactions->hasPages())
            <div class="pt-2">
                {{ $transactions->links() }}
            </div>
        @endif

    @endif

</div>

@endsection
