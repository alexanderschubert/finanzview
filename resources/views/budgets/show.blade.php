@extends('layouts.app')

@section('title', $budget->name . ' – Budget – FinanzView')
@section('eyebrow', 'Budget')
@section('page_title', $budget->name)

@php
    $percentage = (float) ($calculation['percentage'] ?? 0);
    $spent = (float) ($calculation['spent'] ?? 0);
    $remaining = (float) ($calculation['remaining'] ?? 0);
    $applicable = (bool) ($calculation['applicable'] ?? false);
    $exceeded = (bool) ($calculation['exceeded'] ?? false);

    $tone = $exceeded ? 'negative' : ($percentage >= 80 ? 'warning' : 'positive');

    $periodLabel = ['monthly' => 'Monatliches Budget', 'yearly' => 'Jährliches Budget'][$budget->period] ?? 'Budget für einen Zeitraum';

    /*
     * Tagesbudget: nur für den laufenden Zeitraum sinnvoll.
     */
    $perDay = null;
    $periodEnd = $calculation['end_date'] ?? null;
    $periodStart = $calculation['start_date'] ?? null;

    if ($applicable && $remaining > 0 && $periodStart && $periodEnd && now()->between($periodStart, $periodEnd)) {
        $daysLeft = (int) now()->startOfDay()->diffInDays($periodEnd->copy()->startOfDay()) + 1;
        $perDay = $remaining / max(1, $daysLeft);
    }

    $transactionsByDay = $transactions->groupBy(fn ($transaction) => $transaction->transaction_date?->format('Y-m-d'));
@endphp

@section('content')

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <div class="flex items-center gap-4">
        <x-emoji-tile :emoji="$budget->icon" fallback="target" :color="$budget->color" size="lg" />

        <div class="flex-1 min-w-0">
            <h2 class="text-[28px] sm:text-[34px] leading-tight font-bold tracking-tight text-slate-900 dark:text-white truncate">{{ $budget->name }}</h2>
            <p class="text-[15px] text-slate-500 dark:text-slate-400">{{ $periodLabel }}</p>
        </div>

        <a href="{{ route('budgets.edit', $budget) }}" class="fv-btn fv-btn-secondary text-sm py-2.5">
            <x-icon name="pencil" class="w-4 h-4" />
            <span class="hidden sm:inline">Bearbeiten</span>
        </a>
    </div>

    <div class="flex justify-center sm:justify-start">
        <x-month-switcher route="budgets.show" :month="$selectedMonth" :params="['budget' => $budget->id]" />
    </div>

    <x-flash />


    @if (! $applicable)

        <div class="fv-card">
            <x-empty-state icon="calendar" :title="'Gilt nicht für ' . $referenceMonth->translatedFormat('F Y')">
                Das Budget läuft ab {{ $budget->start_date->format('d.m.Y') }}@if ($budget->end_date) bis {{ $budget->end_date->format('d.m.Y') }}@endif.
            </x-empty-state>
        </div>

    @else

        {{-- RING --}}

        <div class="fv-card p-6 sm:p-8 flex flex-col sm:flex-row items-center gap-6 sm:gap-10">

            <x-progress-ring :value="$percentage" :tone="$tone">
                <p class="text-xs text-slate-500 dark:text-slate-400">ausgegeben</p>
                <p class="text-2xl font-semibold tabular-nums {{ $exceeded ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white' }}">
                    {{ number_format($spent, 2, ',', '.') }} €
                </p>
                <p class="text-xs text-slate-500 dark:text-slate-400 tabular-nums">von {{ number_format((float) $budget->amount, 2, ',', '.') }} €</p>
            </x-progress-ring>

            <dl class="w-full flex-1 divide-y divide-slate-100 dark:divide-white/5 text-sm">
                <div class="flex items-center justify-between gap-4 pb-3">
                    <dt class="text-slate-500 dark:text-slate-400">{{ $remaining >= 0 ? 'Noch verfügbar' : 'Überschritten um' }}</dt>
                    <dd class="text-lg font-semibold tabular-nums {{ $remaining >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ number_format(abs($remaining), 2, ',', '.') }} €
                    </dd>
                </div>

                @if ($perDay !== null)
                    <div class="flex items-center justify-between gap-4 py-3">
                        <dt class="text-slate-500 dark:text-slate-400">Pro Tag noch</dt>
                        <dd class="font-semibold tabular-nums text-slate-900 dark:text-white">{{ number_format($perDay, 2, ',', '.') }} €</dd>
                    </div>
                @endif

                <div class="flex items-center justify-between gap-4 py-3">
                    <dt class="text-slate-500 dark:text-slate-400">Verbraucht</dt>
                    <dd class="font-semibold tabular-nums text-slate-900 dark:text-white">{{ number_format($percentage, 0, ',', '.') }} %</dd>
                </div>

                @if ($periodStart && $periodEnd)
                    <div class="flex items-center justify-between gap-4 pt-3">
                        <dt class="text-slate-500 dark:text-slate-400">Zeitraum</dt>
                        <dd class="font-medium tabular-nums text-slate-900 dark:text-white">{{ $periodStart->format('d.m.') }} – {{ $periodEnd->format('d.m.Y') }}</dd>
                    </div>
                @endif
            </dl>

        </div>


        {{-- KATEGORIEN --}}

        <section>
            <h3 class="px-1 pb-2 text-[13px] font-semibold text-slate-500 dark:text-slate-400">Kategorien</h3>

            @if ($budget->categories->isEmpty())
                <div class="flex gap-3 rounded-2xl bg-amber-50 dark:bg-amber-500/10 p-4 text-sm text-amber-800 dark:text-amber-300">
                    <x-icon name="alert" class="w-5 h-5" />
                    <p>
                        Diesem Budget sind keine Kategorien zugeordnet – es zählt deshalb keine Ausgaben.
                        <a href="{{ route('budgets.edit', $budget) }}" class="font-medium underline">Kategorien auswählen</a>
                    </p>
                </div>
            @else
                <div class="flex flex-wrap gap-2">
                    @foreach ($budget->categories as $category)
                        <a href="{{ route('transactions.index', ['category_id' => $category->id]) }}"
                            class="rounded-full bg-white dark:bg-slate-900 ring-1 ring-slate-900/5 dark:ring-white/10 px-3 py-1.5 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-white/5 transition">
                            {{ $category->icon }} {{ $category->name }}
                        </a>
                    @endforeach
                </div>
            @endif
        </section>


        {{-- BUCHUNGEN --}}

        <section class="space-y-5">
            <h3 class="px-1 -mb-3 text-[13px] font-semibold text-slate-500 dark:text-slate-400">
                Buchungen im Zeitraum
                <span class="font-normal text-slate-400 dark:text-slate-500">· {{ $transactions->count() }}</span>
            </h3>

            @if ($transactions->isEmpty())
                <div class="fv-card">
                    <x-empty-state icon="arrows" title="Noch keine Ausgaben">
                        In diesem Zeitraum gibt es keine Buchungen in den Kategorien dieses Budgets.
                    </x-empty-state>
                </div>
            @else
                @foreach ($transactionsByDay as $day => $dayTransactions)
                    <div>
                        <p class="px-1 pb-1.5 text-xs text-slate-400 dark:text-slate-500">
                            {{ \Carbon\Carbon::parse($day)->translatedFormat('l, j. F') }}
                        </p>

                        <ul class="fv-card overflow-hidden divide-y divide-slate-100 dark:divide-white/5">
                            @foreach ($dayTransactions as $transaction)
                                <li>
                                    <a href="{{ route('transactions.edit', $transaction) }}" class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-white/5 transition">
                                        <x-emoji-tile :emoji="$transaction->category?->icon" fallback="tag" />

                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-slate-900 dark:text-white truncate">{{ $transaction->description }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                                {{ collect([$transaction->category?->name, $transaction->merchant, $transaction->account?->name])->filter()->implode(' · ') }}
                                            </p>
                                        </div>

                                        <p class="font-semibold tabular-nums whitespace-nowrap {{ $transaction->type === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white' }}">
                                            {{ $transaction->type === 'income' ? '+' : '−' }}{{ number_format((float) $transaction->amount, 2, ',', '.') }} €
                                        </p>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            @endif
        </section>

    @endif

</div>

@endsection
